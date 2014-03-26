<?php

  namespace UcDemo\CommonApp;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 1/24/14
   */
  class App {

    protected static $app = null;

    /**
     * @return \Uc\Core\App
     */
    public static function init() {
      return self::get();
    }

    /**
     * @return \Uc\Core\App
     */
    public static function get() {
      # load application configuration
      if (empty(self::$app)) {

        $config = require_once __DIR__ . '/config.php';
        $app = \Uc::initApp(new \Uc\Core\App($config));

        /** @var $app \Uc\Core\App */
        self::$app = $app;
      }

      return self::$app;
    }

  }