<?php

  namespace Uc\Core;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 12/13/13
   */
  class Config extends \Uc\Core\Component {

    /**
     * @var array
     */
    public $bundles = array();

    /**
     * @var string
     */
    public $basePath = '';

    /**
     * @var string
     */
    public $filesPath = '';

    /**
     * Route to error action
     * For example: app/site/error
     * @var string
     */
    public $errorActionPath = '';

    /**
     * @var array
     */
    public $developersIp = array();

    /**
     * @return bool
     */
    public function isDevIp() {
      if (empty($_SERVER['REMOTE_ADDR'])) {
        return false;
      }

      if (!empty($_SERVER['SERVER_ADDR']) and $_SERVER['SERVER_ADDR'] === $_SERVER['REMOTE_ADDR']) {
        return true;
      }

      return in_array($_SERVER['REMOTE_ADDR'], $this->developersIp);
    }

  }