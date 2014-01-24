<?php

  namespace UcTests\Uc\Core\Db;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 1/24/14
   */
  class SelectTest extends \UcTests\Main {

    protected function createDemoPosts($num) {
      \UcDemo\CommonApp\App::init();

      $table = \UcDemo\CommonApp\Posts\Table::instance();
      $table->getAdapter()->execute('Delete from ' . $table->getAdapter()->quote($table->getTableName()));

      for ($index = 1; $index <= $num; $index++) {
        $model = $table->createModel();
        $model->title = 'post-' . $index;
        $model->save();
      }

    }

    public function testSimpleFetch() {


      $this->createDemoPosts(5);

      $table = \UcDemo\CommonApp\Posts\Table::instance();
      $select = $table->select();

      $items = $select->fetchAll();
      $this->assertCount(5, $items);

    }
  }
