<?php
  namespace Uc\Core\User;

  /**
   *
   * @package Uc\Core\User
   */
  abstract class Identity extends \Uc\Core\Component {

    const N = __CLASS__;

    public $loginRoute = null;

    public $logoutRoute = null;

    public $successLoginRoute = null;

    public $successLogoutRoute = null;

    public $directAccess = null;

    /**
     *
     */
    public function __construct() {
      $this->init();
    }


    /**
     * @return string
     */
    protected function getSessionKey() {
      return md5('user_id' . \Uc::app()->url->getBaseUrl());
    }

    /**
     * @return null
     */
    public function getId() {
      return (isset($_SESSION[$this->getSessionKey()])) ? $_SESSION[$this->getSessionKey()] : null;
    }

    /**
     * @param $id
     */
    public function setId($id) {
      $_SESSION[$this->getSessionKey()] = $id;
    }

    public function deleteId() {
      unset($_SESSION[$this->getSessionKey()]);
    }

    /**
     * @return null
     */
    public function isLogin() {
      return $this->getId();
    }

    /**
     * @return null
     */
    public function getLoginRoute() {
      return $this->loginRoute;
    }

    /**
     * @return null
     */
    public function getSuccessLoginRoute() {
      return $this->successLoginRoute;
    }

    /**
     * @return null
     */
    public function getSuccessLogoutRoute() {
      return $this->successLogoutRoute;
    }

    /**
     * @return null
     */
    public function getLogoutRoute() {
      return $this->logoutRoute;
    }


    /**
     * Implement this method
     *
     * @return bool
     */
    public function getUser() {
      return false;
    }

    /**
     * Implement this method
     *
     * @param $login
     * @param $password
     * @return bool
     */
    public abstract function authenticate($login, $password);

    /**
     * @param $password
     * @return string
     */
    public function getPasswordHash($password) {
      $salt = substr('$2a$10$' . md5(uniqid() . microtime()), 0, 29);
      return crypt($password, $salt);
    }

    /**
     * @param $password
     * @param $hash
     * @return bool
     */
    public function checkPassword($password, $hash) {
      if (crypt($password, $hash) == $hash) {
        return true;
      } else {
        return false;
      }
    }
  }