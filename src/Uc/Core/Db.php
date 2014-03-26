<?php

  namespace Uc\Core;

  /**
   *
   * @author  Ivan Scherbak <dev@funivan.com>
   * @package Uc\Core
   */
  class Db extends Component {

    const QUOTE_IDENTIFIER = '`';

    /**
     *
     * @var string
     */
    public $dsn = null;

    /**
     *
     * @var string
     */
    public $username = null;

    /**
     *
     * @var string
     */
    public $password = null;

    /**
     *
     * @var array
     */
    public $options = array();


    /**
     * @var string
     */
    public $tablePrefix = '';

    /**
     *
     * @var string
     */
    protected $transactionKey = '';


    /**
     * @var \PDO
     */
    protected $pdo = null;

    /**
     *
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $options
     */
    public function __construct($dsn = '', $username = '', $password = '', $options = array()) {
      $this->dsn = $dsn;
      $this->username = $username;
      $this->password = $password;
      $this->options = empty($options) ? array(\PDO::ATTR_PERSISTENT => true) : $options;
      return $this;
    }

    /**
     * @return $this
     */
    public function init() {
      $this->connect();
      return parent::init();
    }

    /**
     * Connect to database
     *
     * @return $this
     */
    public function connect() {
      $this->pdo = new \PDO($this->dsn, $this->username, $this->password, $this->options);
      $this->execute('SET NAMES UTF8');
      $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      return $this;
    }

    /**
     * Prepare query and execute
     *
     * @param string $query
     * @param array $binds
     * @return \PDOStatement
     */
    public function execute($query, $binds = array()) {
      $stmt = $this->pdo->prepare($query);
      $stmt->execute($binds);
      return $stmt;
    }

    /**
     * Prepare query and return statement
     *
     * @param string $query
     * @return \PDOStatement
     */
    public function prepare($query) {
      return $this->pdo->prepare($query);
    }

    /**
     * @param string $sql
     * @param array $binds
     * @return array
     */
    public function fetchOne($sql, $binds = array()) {
      $result = (array)$this->execute($sql, $binds)->fetchAll(\PDO::FETCH_COLUMN, 0);
      return reset($result);
    }

    /**
     * @param string $sql
     * @param array $binds
     * @return array
     */
    public function fetchRow($sql, $binds = array()) {
      $result = (array)$this->execute($sql, $binds)->fetchAll(\PDO::FETCH_ASSOC);
      return reset($result);
    }

    /**
     * @param string $sql
     * @param array $binds
     * @return array
     */
    public function fetchCol($sql, $binds = array()) {
      return $this->execute($sql, $binds)->fetchAll(\PDO::FETCH_COLUMN, 0);
    }

    /**
     * @param string $sql
     * @param array $binds
     * @return array
     */
    public function fetchAll($sql, $binds = array()) {
      return $this->execute($sql, $binds)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Quote table and column names
     *
     * @param string $tableName
     * @return string
     */
    public function quote($tableName) {
      $q = self::QUOTE_IDENTIFIER;
      return ($q . str_replace("$q", "$q$q", $tableName) . $q);
    }

    /**
     * Smart method for transaction begin.
     * If transaction was starter early
     * it will not start at this time.
     * It is easy to understand in examples
     *
     * @param string $key
     * @return boolean
     */
    public function startTransaction($key) {
      if ($this->transactionKey == false) {
        $this->transactionKey = $key;
        return $this->pdo->beginTransaction();
      } else {
        return false;
      }
    }

    /**
     * Smart method to commit transaction
     *
     * @param string $key
     * @return boolean
     */
    public function endTransaction($key) {
      if ($this->transactionKey == $key) {
        $this->transactionKey = false;
        return $this->pdo->commit();
      } else {
        return false;
      }
    }

    /**
     * Smart method for rollback transaction.
     * Look into examples
     *
     * @param boolean|string $key
     * @return boolean
     */
    public function rollBackTransaction($key) {
      if ($this->transactionKey == $key) {
        $this->transactionKey = false;
        return $this->pdo->rollBack();
      } else {
        return false;
      }
    }

    /**
     * @return string
     */
    public function getLastInsertId() {
      return $this->pdo->lastInsertId();
    }

  }