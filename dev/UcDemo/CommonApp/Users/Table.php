<?php

  namespace UcDemo\CommonApp\Users;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 1/24/14
   */
  class Table extends \Uc\Core\Db\Table {

    public function createDemoTable() {
      $db = $this->getAdapter();

      $sql = "
      DROP TABLE IF EXISTS `" . $this->getTableName() . "`;
      CREATE TABLE IF NOT EXISTS `" . $this->getTableName() . "` (
        `id` bigint(20) NOT NULL,
        `email` varchar(255) NOT NULL,
        `name` varchar(255) NOT NULL,
        `status` tinyint(1) NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
      ";
      $db->execute($sql);
    }

    public function __construct() {
      $this->createDemoTable();
      parent::__construct();
    }


    /**
     * @return string
     */
    public function getModelClass() {
      return '\UcDemo\CommonApp\Users\Model';
    }

    /**
     * @return string
     */
    public function getTableName() {
      return $this->getAdapter()->tablePrefix . 'test_users_table';
    }
  }