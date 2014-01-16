<?php

  /**
   * Container Class of Application
   *
   * @todo #phpstorm plugin. initApp and app methods return instance of class $appClass
   * @author  Ivan Scherbak <dev@funivan.com>
   */
  class Uc {

    /**
     * @var \Uc\Core\App
     */
    private static $app = null;

    /**
     *
     * @param string $appClass
     * @param array $components
     * @return \Uc\Core\App
     */
    public static function initApp($appClass, $components = array()) {
      self::$app = new $appClass($components);
      return self::$app;
    }

    /**
     * @return \Uc\Core\App
     */
    public static function app() {
      return self::$app;
    }

  }