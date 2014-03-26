<?php


  namespace Uc\Core;

  /**
   * @author Ivan Shcherbak <dev@funivan.com>
   */
  class Behavior {

    /**
     * Main object
     *
     * @var object
     */
    protected $owner = null;

    /**
     * Automatically invoke on attachBehavior from component
     *
     * @return $this
     */
    public function init() {
      return $this;
    }

    /**
     * Set owner for this behavior
     *
     * @param object $owner
     * @return $this
     */
    public function setOwner($owner) {
      $this->owner = $owner;
      return $this;
    }


    /**
     * Return owner of this behavior
     *
     * @return object
     */
    protected function getOwner() {
      return $this->owner;
    }

    /**
     * Check if you can call method outside from behavior
     * You can rewrite part this method if you use `__call`
     *
     * @param $methodName
     * @return bool
     */
    public function hasMethod($methodName) {
      return (method_exists($this, $methodName) and is_callable(array($this, $methodName)));
    }

    /**
     * Check if property exists
     * You can rewrite this method if you use __get or __isset
     *
     * @param $propertyName
     * @return bool
     */
    public function hasProperty($propertyName) {
      return property_exists($this, $propertyName);
    }

  }