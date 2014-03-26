<?php

  /**
   * Simple registry for Application
   *
   * If you need several applications you can simply inherit this class
   *
   * <code>
   *  class Cf extends Uc{}
   *  Cf::initApp(new \Cf\App($config));
   *  Cf::app();
   * </code>
   *
   * @todo #phpstorm plugin. initApp and app methods return instance of class $app
   * @author  Ivan Scherbak <dev@funivan.com>
   */
  class Uc {

    /**
     * @var \Uc\Core\App
     */
    private static $app = null;

    /**
     * @param \Uc\Core\App $app
     * @return \Uc\Core\App
     */
    public static function initApp(\Uc\Core\App $app) {
      static::$app = $app;
      return static::$app;
    }

    /**
     * @return \Uc\Core\App|null
     */
    public static function app() {
      return static::$app;
    }

  }