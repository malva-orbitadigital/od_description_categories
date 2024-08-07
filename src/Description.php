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
          id_parent INT NOT NULL,
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

   /**
    * @param string $select fields to select
    * @param mixed $where conditions
    * 
    * @return array|false
    */
   static public function select(string $select = '*', $where = [], $groupBy = null)
   {
      if ($select === '') $select = '*';
      $sql = 'SELECT ' . $select . ' FROM ' . _DB_PREFIX_ . self::TABLE_NAME;

      if (!is_array($where)) {
         $where = [$where];
      }
      if (!empty($where)) {
         $sql .= ' WHERE ' . implode(' AND ', array_filter($where));
      }
      if ($groupBy) {
         $sql .= ' GROUP BY ' . $groupBy;
      }

      return Db::getInstance()->executeS($sql);
   }

   /**
    * Inserts a new description of a category
    * @param array $data of the note
    * @return bool true if inserted, false otherwise
    */
   static public function insert(array $data): bool
   {
      if (empty($data)) return false;
      $defaultText = self::getFirstNotEmpty($data['description2']);
      foreach ($data['description2'] as $key => $value) {
         $aux = [
            'id_category' => $data['id_category'],
            'id_parent' => $data['id_parent'],
            'id_lang' => $key,
            'description2' => $value === '' ? $defaultText : $value
         ];
         if (!Db::getInstance()->insert(self::TABLE_NAME, [$aux])) return false;
      }
      return true;
   }

   /**
    * Updates the description of a category
    * @param array $data of the note
    * @return bool true if updated, false otherwise
    */
   static public function update(array $data): bool
   {
      if (empty($data)) return false;
      $defaultText = self::getFirstNotEmpty($data['description2']);
      foreach ($data['description2'] as $key => $value) {
         $aux = [
            'description2' => $value === '' ? $defaultText : $value,
         ];
         if (!Db::getInstance()->update(self::TABLE_NAME, $aux, 'id_category = ' . $data['id_category'] . ' AND id_lang = ' . $key)) return false;
      }
      return true;
   }

   /**
    * Iterates through an array of texts and returns the first not empty
    * @param array $texts
    * @return string the first not empty, or '' if all are empty
    */
   static private function getFirstNotEmpty(array $texts): string
   {
      if (empty(array_filter($texts))) return '';
      $text = array_shift($texts);
      return $text === '' ? self::getFirstNotEmpty($texts) : $text;
   }

   /**
    * Deletes a description and it's children
    * @param int $id of the note
    * @return bool true if deleted, false otherwise
    */
   static public function delete(int $id): bool
   {
      if ($id <= 0) return false;

      $valid = Db::getInstance()->delete(self::TABLE_NAME, 'id_category = ' . $id);
      $children = self::select('id_category', ['id_parent = ' . $id], 'id_category');
      if (empty($children)) return $valid;

      foreach ($children as $child) {
         $valid = self::delete((int) $child['id_category']) && $valid;
      }
      return $valid;
   }
}
