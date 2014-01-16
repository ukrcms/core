<?php

  /**
   *
   * @package \Uc\Core
   * @author  Ivan Shcherbak <dev@funivan.com>
   */
  namespace Uc\Core;

  /**
   *
   * @package Uc\Core
   */
  class Module extends Component {

    /**
     * Automatically invoke module initialization
     *
     */
    public function __construct() {
      $this->init();
    }

    /**
     * Render file by absolute path
     *
     * @param string $file
     * @param array $data
     * @return string
     */
    public function renderFile($file, $data = array()) {
      $file = str_replace('\\', DIRECTORY_SEPARATOR, $file);
      if (is_array($data)) {
        extract($data, EXTR_OVERWRITE);
      }

      ob_start();
      ob_implicit_flush(false);
      include $file;
      return ob_get_clean();
    }

    /**
     * Render file from theme without layout
     *
     * <code>
     * $this->renderPartial('/test/custom')  ; //file /Test/custom.php
     * </code>
     * <code>
     * $this->renderPartial('custom')
     * </code>
     *
     * @param string $file
     * @param array $data
     * @return string
     */
    public function renderPartial($file, $data = array()) {
      $moduleViewFile = $this->getThemeViewFilePath($file);
      return $this->renderFile($moduleViewFile, $data);
    }


    /**
     * Render file from view without layout
     *
     * @param string $file
     * @param array $data
     * @return string
     */
    public function renderViewPartial($file, $data = array()) {
      $file = $this->getClassViewFilePath($file);
      return $this->renderFile($file, $data);
    }

    /**
     * @param string $file
     * @return string
     */
    protected function getThemeViewFilePath($file) {
      if ($file[0] != DIRECTORY_SEPARATOR) {
        $shortClassName = preg_replace('!^(.*)\\\([^\\\]+)$!', '$1', get_class($this));
        $partialFilePath =
          DIRECTORY_SEPARATOR
          . str_replace('\\', DIRECTORY_SEPARATOR, $shortClassName)
          . DIRECTORY_SEPARATOR . $file;
      } else {
        $partialFilePath = $file;
      }


      $moduleViewFile = \Uc::app()->theme->getViewFilePath($partialFilePath);
      $moduleViewFile = \Uc::app()->theme->getAbsoluteFilePath($moduleViewFile);
      return $moduleViewFile;
    }

    /**
     * @param string $file
     * @return string string
     */
    protected function getClassViewFilePath($file) {
      $object = new \ReflectionObject($this);
      $file = dirname($object->getFilename())
        . DIRECTORY_SEPARATOR . \Uc::app()->theme->getViewsDir()
        . DIRECTORY_SEPARATOR . trim($file, DIRECTORY_SEPARATOR)
        . \Uc::app()->theme->getTemplateExtension();

      return $file;
    }


    /**
     * Alias
     *
     * @return Url
     */
    public function url() {
      return $this->app()->url;
    }
  }