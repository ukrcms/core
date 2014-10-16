<?php

  namespace Uc\Core\User;

  /**
   * @author Ivan Shcherbak <dev@funivan.com>
   */
  abstract class Identity extends \Uc\Core\Component {

    public $loginRoute = null;

    public $logoutRoute = null;

    public $successLoginRoute = null;

    public $successLogoutRoute = null;

    public $directAccess = null;

    public function __construct() {
      $this->init();
    }

    public abstract function getUser();

    /**
     * Implement this method
     *
     * @param string $login
     * @param string $password
     * @return bool
     */
    public abstract function authenticate($login, $password);

    /**
     * @return string
     */
    protected function getSessionKey() {
      return md5('user_id' . \Uc::app()->url->getBaseUrl());
    }

    /**
     * @return null|int
     */
    public function getId() {
      return (isset($_SESSION[$this->getSessionKey()])) ? $_SESSION[$this->getSessionKey()] : null;
    }

    /**
     * @param int $id
     */
    public function setId($id) {
      $_SESSION[$this->getSessionKey()] = $id;
    }

    /**
     * @return $this
     */
    public function deleteId() {
      unset($_SESSION[$this->getSessionKey()]);
      return $this;
    }

    /**
     * @return boolean
     */
    public function isLogin() {
      return $this->getId() !== null;
    }

    /**
     * @return string
     */
    public function getLoginRoute() {
      return $this->loginRoute;
    }

    /**
     * @return string
     */
    public function getSuccessLoginRoute() {
      return $this->successLoginRoute;
    }

    /**
     * @return string
     */
    public function getSuccessLogoutRoute() {
      return $this->successLogoutRoute;
    }

    /**
     * @return string
     */
    public function getLogoutRoute() {
      return $this->logoutRoute;
    }

    /**
     * @param string $password
     * @return string
     */
    public function getPasswordHash($password) {
      $salt = substr('$2a$10$' . md5(uniqid() . microtime()), 0, 29);
      return crypt($password, $salt);
    }

    /**
     * @param string $password
     * @param string $hash
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