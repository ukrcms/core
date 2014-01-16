<?php

  namespace Uc\Core;

  /**
   * @author Ivan Scherbak <dev@funivan.com>
   */
  class Theme extends Component {

    /**
     *
     * @var string
     */
    public $layout = null;

    /**
     *
     * @var string
     */
    public $basePath = null;

    /**
     *
     * @var string
     */
    public $baseUrl = null;

    /**
     * @var string
     */
    public $themeName = null;

    /**
     * @var string
     */
    public $viewsDir = 'views';

    /**
     * @var string
     */
    public $templateExtension = '.php';

    /**
     * @var array
     */
    protected $values = array();

    /**
     * @return string
     */
    public function __toString() {
      return $this->themeName;
    }

    /**
     * Get path to view file from theme
     *
     *
     * @param string $file
     * @return string
     */
    public function getViewFilePath($file) {
      $file = DIRECTORY_SEPARATOR . $this->viewsDir . DIRECTORY_SEPARATOR . 'bundles'
        . DIRECTORY_SEPARATOR . trim($file, DIRECTORY_SEPARATOR) . $this->templateExtension;
      return $file;
    }

    /**
     * Get path to layout file
     *
     * @return string
     */
    public function getLayoutFilePath() {
      $file = '/' . $this->viewsDir . '/layouts/' . $this->layout . $this->templateExtension;
      $file = \Uc::app()->theme->getAbsoluteFilePath($file);
      return $file;
    }

    /**
     * @param $file
     * @return string
     */
    public function getAbsoluteFilePath($file) {
      return $this->basePath . DIRECTORY_SEPARATOR . $this->themeName . DIRECTORY_SEPARATOR . trim($file, DIRECTORY_SEPARATOR);
    }

    /**
     * Full url of theme
     *
     * @return string
     */
    public function getUrl() {
      return \Uc::app()->url->getBaseUrl() . $this->baseUrl . '/' . $this->themeName;
    }

    /**
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setValue($name, $value) {
      $this->values[$name] = $value;
      return $this;
    }

    /**
     * @todo #phpstorm підказувати все що попадає з методу setValue
     *
     * @param string $name
     * @return string
     */
    public function getValue($name) {
      return isset($this->values[$name]) ? $this->values[$name] : null;
    }

    /**
     * @return string
     */
    public function getViewsDir() {
      return $this->viewsDir;
    }

    /**
     * @return string
     */
    public function getTemplateExtension() {
      return $this->templateExtension;
    }

  }