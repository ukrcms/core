<?php

  namespace Uc\Core;

  /**
   * Component init manually
   *
   * @author Ivan Scherbak <dev@funivan.com>
   */
  class Component {

    /**
     * Array of behaviors attached to this object
     *
     * @var array
     */
    protected $behaviors = array();

    /**
     * Component initialization
     * Call manually
     *
     * @return $this;
     */
    public function init() {
      return $this;
    }

    /**
     * @todo #phpstorm return application from \Uc\initApp
     * @return \Uc\Core\App
     */
    public function app() {
      return \Uc::app();
    }

    /**
     * Attach behavior.
     * Set owner and init behavior
     *
     * <code>
     * public function init(){
     *  $this->image = new \Ub\Helper\Image\Object()
     *  $this->image->owner_field = 'image_data';
     *  $this->attachBehavior('image', $this->image);
     * }
     * </code>
     * @param $name
     * @param Behavior $behavior
     * @return $this
     */
    public function attachBehavior($name, Behavior $behavior) {
      $behavior->setOwner($this);
      $behavior->init();
      $this->behaviors[$name] = $behavior;
      return $this;
    }

    /**
     * Run method in all behaviors
     * <code>
     *  $this->runAllBehaviors('beforeSave');
     * </code>
     *
     * @param $methodName
     * @return $this
     */
    public function runAllBehaviors($methodName) {
      /** @var $behavior Behavior */
      foreach ($this->behaviors as $behavior) {
        if ($behavior->hasMethod($methodName)) {
          $behavior->$methodName();
        }
      }
      return $this;
    }

    /**
     * Remove behavior from owner
     *
     * <code>
     *  $this->removeBehavior('image');
     * </code>
     *
     * @param $behaviorName
     * @return $this
     */
    public function removeBehavior($behaviorName) {
      unset($this->behaviors[$behaviorName]);
      return $this;
    }

  }