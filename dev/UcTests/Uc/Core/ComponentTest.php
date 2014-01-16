<?php

  namespace UcTests\Uc\Core;

  /**
   * @package UcTests\ukrcmscore
   */
  class ComponentTest extends \UcTests\Main {

    public function testInit() {
      $component = new \Uc\Core\Component();
      $class = $component->init();
      $this->assertEquals($class, $component);
    }
  }