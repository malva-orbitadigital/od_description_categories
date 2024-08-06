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

