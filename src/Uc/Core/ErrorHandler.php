<?php

  namespace Uc\Core;

  /**
   * @author Ivan Shcherbak <dev@funivan.com> 12/13/13
   */
  class ErrorHandler extends \Uc\Core\Component {

    /**
     * Register error handler
     *
     * @return $this
     */
    public function init() {
      register_shutdown_function(array($this, 'displayError'));
      set_error_handler(array($this, 'displayError'));
      return parent::init();
    }

    /**
     * ErrorHandler handler
     *
     * @param bool $type
     * @param string $message
     * @param string $file
     * @param string $line
     * @return null
     */
    public function displayError($type = false, $message = '', $file = '', $line = '') {
      $error = error_get_last();
      if ($type === false and empty($error)) {
        return null;
      }

      if (!ini_get('display_errors')) {
        echo 'Oops. #error-application ';
      } else {
        if (is_object($type) and ($type instanceof \Exception)) {
          echo $type->getMessage() . "\n";
          echo $type->getTraceAsString();
        } else {
          echo $message . "\n" . $file . ':' . $line . "\n";
          debug_print_backtrace();
        }
      }

      return null;
    }

  }