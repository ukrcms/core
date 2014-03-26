<?php

  namespace Uc\Core;

  /**
   *
   * @package Uc\Core
   */
  abstract class Widget extends Module {

    /**
     * @var array
     */
    protected $options = array();

    /**
     * Implement render logic
     *
     * @return string
     */
    public abstract function render();

    /**
     * Create instance
     *
     * <code>
     * \Widget\Menu::widget()->setItems($menuItems)->show();
     * </code>
     *
     * @param array $options
     * @return $this
     */
    public static function widget($options = array()) {
      /** @var $widget \Uc\Core\Widget */
      $widget = new static();
      $widget->setOptions($options);
      return $widget;
    }


    /**
     * @param $options
     * @return $this
     */
    public function setOptions($options) {
      $this->options = $options;
      return $this;
    }

    /**
     * @return array
     */
    public function getOptions() {
      return $this->options;
    }

  }