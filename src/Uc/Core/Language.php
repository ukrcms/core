<?php

  namespace Uc\Core;

  /**
   * @package Uc\Core
   * @author  Mykhaylo Tarchan <muhasjo@gmail.com>
   */
  class Language extends Component {

    const N = __CLASS__;

    public $languages = array();

    public $default = '';

    /**
     * @var string
     */
    private $current = '';

    /**
     * @return array
     */
    public function getLanguages() {
      return $this->languages;
    }

    /**
     * @return string
     */
    public function getCurrent() {
      if ($this->current === '')
        return $this->default;
      return $this->current;
    }

    /**
     * @return string
     */
    public function getDefault() {
      return $this->default;
    }

    public function getCurrentLangValue() {
      return $this->languages[$this->getCurrent()];
    }

    /**
     * @param $lang
     * @return $this
     */
    public function setCurrent($lang) {
      $this->current = $lang;
      return $this;
    }

    /**
     * @param $lang
     * @return string
     */
    public function getLangValue($lang) {
      if ($this->exist($lang)) {
        return $this->languages[$lang];
      }
      return null;
    }

    /**
     * @param $lang
     * @return bool
     */
    public function exist($lang) {
      return isset($this->languages[$lang]);
    }
  }

