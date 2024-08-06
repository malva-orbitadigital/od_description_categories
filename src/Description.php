<?php

declare(strict_types=1);

namespace OrbitaDigital\DescriptionCategories;

use Db;

class Description
{

   private const TABLE_NAME = 'od_description_categories';

   /**
    * Creates the table
    * @return bool if table was created, false otherwise
    */
   static public function createTable(): bool
   {
      return Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . self::TABLE_NAME .
         '(id_category INT NOT NULL,
          id_lang INT NOT NULL,
          description2 TEXT NOT NULL,
          PRIMARY KEY (id_category, id_lang)
          ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;');
   }

   /**
    * Deletes the table
    * @return bool true if table was deleted, false otherwise
    */
   static public function deleteTable(): bool
   {
      return Db::getInstance()->execute('DROP TABLE `' . _DB_PREFIX_ . self::TABLE_NAME . '`');
   }

