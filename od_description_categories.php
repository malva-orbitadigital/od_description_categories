<?php
if (!defined('_PS_VERSION_')) {
   exit;
}

require __DIR__ . '/vendor/autoload.php';

use OrbitaDigital\DescriptionCategories\Description;

class Od_description_categories extends Module
{

   public function __construct()
   {
      $this->name = 'od_description_categories';
      $this->tab = 'front_office_features';
      $this->version = '1.0';
      $this->author = 'Malva Pérez López';
      $this->need_instance = 0;
      $this->ps_versions_compliancy = [
         'min' => '1.6.0',
         'max' => '1.7.9'
      ];
      $this->bootstrap = true;

      parent::__construct();

      $this->displayName = $this->l('Description categories');
      $this->description = $this->l('Adds another description to the categories.');
   }

   public function install()
   {
      return Description::createTable()
         && parent::install()
         && $this->registerHook('actionFrontControllerSetMedia');
   }

   public function uninstall()
   {
      return Description::deleteTable()
         && parent::uninstall();
   }

   public function hookActionFrontControllerSetMedia()
   {
      if ($this->context->controller->php_self !== 'category') return;

      $data = Description::select('description2', [
         'id_category = ' . Tools::getValue('id_category', 0),
         'id_lang = ' . $this->context->language->id
      ])[0]['description2'];

      $this->context->controller->registerJavascript(
         'od_description_categories',
         'modules/' . $this->name . '/views/js/placeDescription.js',
         ['position' => 'bottom', 'priority' => 150]
      );
      Media::addJsDef([
         $this->name . '_msg' => $data
      ]);
   }

   protected function getFormValues()
   {
      $values = [];
      $languages = Language::getLanguages(false);
      foreach ($languages as $lang) {
         $values['description2'][$lang['id_lang']] = Tools::getValue('description2_' . $lang['id_lang'], '');
      }

      $values['id_category'] = Tools::getValue('id_category', '');
      return $values;
   }

   /**
    * Handles the module's configuration page
    * @return string The page's HTML content 
    */
   public function getContent()
   {
      $output = '';

      if (Tools::isSubmit('submit' . $this->name)) {
         $values = $this->getFormValues();
         if (empty(array_filter($values['description2'])) || $values['id_category'] === '') {
            $output = $this->displayError($this->l('Description is required'));
         } else if (Description::insert($values)) {
            $output = $this->displayConfirmation($this->l('Description saved'));
         } else {
            $output = $this->displayError($this->l("Couldn't save the description"));
         }
      } else if (Tools::isSubmit('delete' . $this->name)) {
         if (!Description::delete((int) Tools::getValue('id_category', 0))) {
            $output = $this->displayError($this->l("Couldn't delete the description"));
         } else {
            $output = $this->displayConfirmation($this->l('Description deleted'));
         }
      }
      return $output . $this->displayForm() . $this->displayList();
   }

   /**
    * Returns the categories that can have description and are not already saved in the DB
    * @return array
    */
   private function getCategories()
   {
      $assigned = array_column(Description::select('id_category', [], 'id_category'), 'id_category');
      $categories = Category::getCategories();
      $options = [];
      foreach ($categories as $category) {
         foreach ($category as $info) {
            if ($info['infos']['description'] === '' || in_array($info['infos']['id_category'], $assigned)) continue;

            $options[] = [
               'id' => $info['infos']['id_category'],
               'name' => $info['infos']['name']
            ];
         }
      }
      return $options;
   }

   public function displayList()
   {
      $fields = [
         'id_category' => [
            'title' => $this->l('ID_Category'),
            'type' => 'number',
         ],
         'name_category' => [
            'title' => $this->l('Category'),
            'type' => 'text',
         ],
         'description2' => [
            'title' => $this->l('Description'),
            'type' => 'text',
         ]
      ];

      $helper = new HelperList();

      $helper->table = $this->name;
      $helper->module = $this;
      $helper->token = Tools::getAdminTokenLite('AdminModules');
      $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

      $helper->shopLinkType = '';
      $helper->title = $this->l('Description categories');
      $helper->no_link = true;
      $helper->show_toolbar = false;
      $helper->simple_header = true;
      $helper->identifier = 'id_category';
      $helper->actions = ['edit', 'delete'];
      $data = Description::select('*', ['id_lang = ' . $this->context->language->id], 'id_category');
      foreach ($data as &$c) {
         $c['name_category'] = Category::getCategoryInformation([$c['id_category']], $this->context->language->id)[$c['id_category']]['name'];
      }
      return $helper->generateList($data, $fields);
   }

   /**
    * Builds the configuration form
    * @return string HTML code
    */
   public function displayForm()
   {
      $form = [
         'form' => [
            'tinymce' => true,
            'legend' => [
               'title' => $this->l('New description'),
            ],
            'input' => [
               [
                  'type' => 'select',
                  'label' => $this->l('Category'),
                  'name' => 'id_category',
                  'required' => true,
                  'options' => [
                     'query' => $this->getCategories(),
                     'id' => 'id',
                     'name' => 'name',
                  ]
               ], [
                  'type' => 'textarea',
                  'name' => 'description2',
                  'label' => $this->l('Description 2'),
                  'required' => true,
                  'lang' => true,
                  'rows' => 3,
                  'class' => 'rte',
                  'autoload_rte' => true,
               ]
            ],
            'submit' => [
               'title' => $this->l('Save'),
               'class' => 'btn btn-default pull-right'
            ]
         ],
      ];

      $helper = new HelperForm();

      $helper->module = $this;
      $helper->identifier = $this->identifier;
      $helper->token = Tools::getAdminTokenLite('AdminModules');
      $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
      $helper->default_form_language = $this->context->language->id;
      $helper->tpl_vars = [
         'fields_value' => $this->getFormValues(),
         'languages'    => $this->context->controller->getLanguages(),
         'id_language'  => $this->context->language->id,
      ];

      $helper->submit_action = 'submit' . $this->name;

      return $helper->generateForm([$form]);
   }
}
