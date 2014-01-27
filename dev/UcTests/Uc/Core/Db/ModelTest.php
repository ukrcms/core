<?php

  namespace UcTests\Uc\Core\Db;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 1/24/14
   */
  class ModelTest extends \UcTests\Main {

    public function testSimpleInsertAndDelete() {

      \UcDemo\CommonApp\App::init();

      $table = \UcDemo\CommonApp\Users\Table::instance();
      $table->createDemoTable();

      $all = $table->fetchOne('1');
      $this->assertEmpty($all);

      $model = $table->createModel();
      $model->id = 1;
      $model->email = 'test@ukrcms.com';
      $model->save();

      $user = $table->fetchOne('1');
      $this->assertInstanceOf('\Uc\Core\Db\Model', $user);
      $this->assertEquals('test@ukrcms.com', $user->email);

      $testEmail = 'test2@ukrcms.com';
      $user->email = $testEmail;
      $user->save();

      $this->assertEquals($testEmail, $user->email);

      $user = $table->fetchOne('1');
      $this->assertInstanceOf('\Uc\Core\Db\Model', $user);
      $this->assertEquals($testEmail, $user->email);
    }

    public function testAutoincrement() {
      \UcDemo\CommonApp\App::init();

      $table = \UcDemo\CommonApp\Posts\Table::instance();
      $table->createDemoTable();

      $all = $table->fetchOne('1');
      $this->assertEmpty($all);

      $model = $table->createModel();
      $model->title = 'Hi there';
      $model->save();

      $this->assertEquals(1, $model->id);

      $model = $table->createModel();
      $model->title = 'Second post';
      $model->save();

      $this->assertEquals(2, $model->id);

      $postModel = $table->fetchOne('2');
      $this->assertInstanceOf('\Uc\Core\Db\Model', $postModel);

      $this->assertEquals($model->title, $postModel->title);

    }

    public function testMultiLangAddPost() {
      \UcDemo\CommonApp\App::init();

      $table = \UcDemo\CommonApp\Posts\MultiLanguage\Table::instance();
      $table->createDemoTable();

      $all = $table->fetchOne('0');
      $this->assertEmpty($all);

      $model = $table->createModel();
      $model->status = '1';
      $model->content = 'Ukrainian';
      $model->save();

      $post = $table->fetchOne(array(
        'id' => '0'
      ));
      $this->assertInstanceOf('\UcDemo\CommonApp\Posts\Model', $post);
      $this->assertEquals($post->lang, \Uc::app()->language->getDefault());
      $this->assertEquals($post->content, 'Ukrainian');

      $model = $table->instance('en')->createModel();
      $model->status = '1';

      $model->content = 'English';
      $model->save();

      $post = $table->fetchOne(array(
        'lang' =>'en'
      ));

      $this->assertInstanceOf('\UcDemo\CommonApp\Posts\Model', $post);

      $this->assertEquals($post->id, 0);
      $this->assertEquals($post->lang, 'en');
      $this->assertEquals($post->content, 'English');

    }

  }