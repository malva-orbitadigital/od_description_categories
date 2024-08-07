<?php
if (!defined('_PS_VERSION_')) {
   exit;
}

require __DIR__ . '/vendor/autoload.php';

use OrbitaDigital\DescriptionCategories\Description;
use PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\CleanHtml;
use PrestaShopBundle\Form\Admin\Type\FormattedTextareaType;
use PrestaShopBundle\Form\Admin\Type\TranslateType;

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
         && $this->registerHook('actionCategoryFormBuilderModifier')
         && $this->registerHook('actionAfterCreateCategoryFormHandler')
         && $this->registerHook('actionAfterUpdateCategoryFormHandler');
   }

   public function uninstall()
   {
      return Description::deleteTable()
         && parent::uninstall();
   }

   /**
    * Adds a new field to the admin category form
    * @param array $params
    */
   public function hookActionCategoryFormBuilderModifier($params)
   {
      $formBuilder = $params['form_builder'];
      $formBuilder->add(
         'description2',
         TranslateType::class,
         [
            'type' => FormattedTextareaType::class,
            'label' => $this->l('Description 2'),
            'locales' => Language::getLanguages(),
            'hideTabs' => false,
            'required' => false,
         ]
      );
   }

   /**
    * Actions after a new category is created
    * @param array $params
    */
   public function hookActionAfterCreateCategoryFormHandler($params)
   {
      Description::insert([
         'id_category' => $params['id'],
         'description2' => $params['form_data']['description2']
      ]);
   }

   /**
    * Actions after category update
    * @param array $params
    */
   public function hookActionAfterUpdateCategoryFormHandler($params)
   {
      $data = [
         'id_category' => $params['id'],
         'description2' => $params['form_data']['description2']
      ];
      if (Description::select('id_category', ['id_category = ' . $params['id']])) {
         Description::update($data);
         return;
      }
      Description::insert($data);
   }
}
