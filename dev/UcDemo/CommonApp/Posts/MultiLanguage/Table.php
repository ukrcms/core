<?php
  /**
   * UkrCmsTeam
   * User: muhasjo <muhasjo@gmail.com>
   * Date: 1/25/14
   * Time: 9:30 PM
   */

  namespace UcDemo\CommonApp\Posts\MultiLanguage;

  class Table extends \Uc\Core\Db\Table {

    public function __construct() {
      $this->createDemoTable();
      parent::__construct();

    }

    public function createDemoTable() {

      $db = $this->getAdapter();

      $sql = "
      DROP TABLE IF EXISTS `" . $this->getMultiLangTable() . "`;
      CREATE TABLE IF NOT EXISTS `" . $this->getMultiLangTable() . "` (
        `table_lang_id` bigint(20) NOT NULL,
        `lang` varchar(2) NOT NULL,
        `content` text NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
      ";
      $db->execute($sql);

      $sql = "
      DROP TABLE IF EXISTS `" . $this->getTableName() . "`;
      CREATE TABLE IF NOT EXISTS `" . $this->getTableName() . "` (
        `id` bigint(20) NOT NULL AUTO_INCREMENT,
        `status` tinyint(1) NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
      ";
      $db->execute($sql);

    }

    protected $isMultiLanguage = true;


    /**
     * @return string
     */
    public function getModelClass() {
      return '\UcDemo\CommonApp\Posts\Model';
    }

    /**
     * @return string
     */
    public function getTableName() {
      return $this->getAdapter()->tablePrefix . 'test_posts_multilang_table';
    }

  }