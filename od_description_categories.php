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
         // dump($values);die;
         if (empty(array_filter($values['description2'])) || $values['id_category'] == '') {
            $output = $this->displayError($this->l('Description is required'));
         } else if (Description::insert($values)) {
            $output = $this->displayConfirmation($this->l('Description saved'));
         } else {
            $output = $this->displayError($this->l("Couldn't save the description"));
         }
      }
      return $output . $this->displayForm();
   }

   private function getCategories()
   {
      $categories = Category::getCategories();
      $options = [];
      foreach ($categories as $category) {
         foreach ($category as $info) {
            if ($info['infos']['level_depth'] < 2) continue;

            $options[] = [
               'id' => $info['infos']['id_category'],
               'name' => $info['infos']['name']
            ];
         }
      }
      return $options;
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
               'title' => $this->l('Descriptions'),
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
