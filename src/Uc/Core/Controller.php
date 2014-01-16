<?php

  namespace Uc\Core;

  /**
   *
   * @package Uc
   * @author  Ivan Scherbak <dev@funivan.com>
   */
  class Controller extends Module {

    /**
     * Render file from theme with layout
     * @todo #phpstorm параметр $file є прямим шляхом до файлу в темі
     *
     * @param string $file
     * @param array $data
     * @return string
     */
    public function render($file, $data = array()) {
      $content = $this->renderPartial($file, $data);
      return $this->renderLayout($content);
    }


    /**
     * Render file from view folder with layout
     * Helper function for rendering files from bundles view folder
     *
     * @param string $file
     * @param array $data
     * @return string
     */
    public function renderView($file, $data = array()) {
      $content = $this->renderViewPartial($file, $data);
      return $this->renderLayout($content);
    }


    /**
     * Render layout with content
     * Layout file path located in theme configuration
     *
     * @param string $content
     * @return string
     */
    public function renderLayout($content) {
      $layoutFile = \Uc::app()->theme->getLayoutFilePath();
      return $this->renderFile($layoutFile, array('content' => $content));
    }

  }