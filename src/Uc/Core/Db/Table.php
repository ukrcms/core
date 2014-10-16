<?php
  namespace Uc\Core\Db;

  /**
   * @author Ivan Scherbak <dev@funivan.com>
   */
  abstract class Table extends \Uc\Core\Component {

    /**
     * Class name of table
     * Used for easy support. better refactoring etc
     */
    const N = __CLASS__;

    /**
     * Relation constant One to One
     */
    const RELATION_ONE_TO_ONE = 1;

    /**
     * Relation constant One to Many
     */
    const RELATION_ONE_TO_MANY = 2;

    /**
     * Relation constant Many to Many
     */
    const RELATION_MANY_TO_MANY = 3;

    /**
     * Array of columns
     *
     * @var array
     */
    protected $columns = array();

    /**
     * Primary key of the table
     *
     * @var string
     */
    protected $pk = false;

    /**
     * @var string
     */
    protected $selectClass = '\Uc\Core\Db\Select';

    protected $modelClass = '\Uc\Core\Db\Model';
    
    protected static $tableInstances = array();

    /**
     * @return string
     */
    public abstract function getTableName();


    /**
     * @return array
     */
    public function relations() {

    }

    /**
     * Return relation info as array or null if relation with this name doe not exist
     *
     * @param string $name
     * @return array|null
     */
    public function getRelationInfo($name) {
      $relations = $this->relations();
      $name = lcfirst($name);
      return isset($relations[$name]) ? $relations[$name] : null;
    }

    /**
     *
     */
    public function __construct() {

      $rawColumnData = $this->getAdapter()->fetchAll('SHOW COLUMNS FROM ' . $this->getAdapter()->quote($this->getTableName()));
      $this->initMetaData($rawColumnData);

      $this->init();
    }

    /**
     * @param $rawColumnData
     */
    protected function initMetaData($rawColumnData) {
      foreach ($rawColumnData as $columnInfo) {

        if (isset($columnInfo['Field'])) {
          $this->columns[$columnInfo['Field']] = true;
        }

        if (isset($columnInfo['Key']) and $columnInfo['Key'] == 'PRI') {
          $this->pk = $columnInfo['Field'];
        }
      }
    }


    /**
     * @return \Uc\Core\Db
     */
    public function getAdapter() {
      return \Uc::app()->db;
    }

    /**
     * @return $this
     */
    public static function instance() {
      $tableClass = get_called_class();
      if (empty(self::$tableInstances[$tableClass])) {
        self::$tableInstances[$tableClass] = new $tableClass();
      }               
      return self::$tableInstances[$tableClass];
    }


    /**
     *
     * @return \Uc\Core\Db\Select
     */
    public function select() {
      $selectClassName = $this->selectClass;
      return new $selectClassName($this);
    }

    /**
     *
     * @param string $columnName
     * @return boolean
     */
    public function hasColumn($columnName) {
      return isset($this->columns[$columnName]);
    }

    /**
     * Primary key column name
     *
     * @return string
     */
    public function pk() {
      return $this->pk;
    }

    /**
     * @param array|null|string $attributes
     * @return array 2 dimension array
     */
    protected function getWhereAndBinds($attributes = null) {
      if ($attributes === null) {
        return array(
          '1', array()
        );
      }

      $params = array();
      if (!is_array($attributes)) {
        $params[$this->pk . ' = '] = $attributes;
      } else {
        $params = $attributes;
      }
      $keys = array_keys($params);

      foreach ($keys as $k => $val) {
        if (strpos($val, '=') === false and strpos($val, '<') === false and strpos($val, '>') === false) {
          $val .= ' = ';
        }
        $keys[$k] = $val . ' ? ';
      }

      $where = implode('AND ', $keys);
      $bind = array_values($params);

      return array(
        $where, $bind
      );
    }

    /**
     *
     * @param array|string|\Uc\Core\Db\Select $select
     * @return \Uc\Core\Db\Model|null
     */
    public function fetchOne($select) {
      $db = $this->getAdapter();

      if ($select instanceof \Uc\Core\Db\Select) {
        $sql = $select->getQuery();
        $params = $select->getBinds();
      } else {
        list($where, $params) = $this->getWhereAndBinds($select);
        $sql = 'SELECT * FROM ' . $db->quote($this->getTableName()) . ' WHERE ' . $where . ' LIMIT 1';
      }

      # fetch data
      $data = $db->fetchRow($sql, $params);

      if (empty($data)) {
        return null;
      }

      return $this->createModel($data, array(
        'stored' => true
      ));
    }

    /**
     *
     * @param array|null|string|\Uc\Core\Db\Select $select
     * @return \Uc\Core\Db\Model[]
     */
    public function fetchAll($select = null) {
      $db = $this->getAdapter();

      if ($select instanceof \Uc\Core\Db\Select) {
        $sql = $select->getQuery();
        $binds = $select->getBinds();
      } else {
        list($where, $binds) = $this->getWhereAndBinds($select);
        $sql = 'SELECT * FROM ' . $db->quote($this->getTableName()) . ' WHERE ' . $where;
      }

      $entitiesRawData = $db->fetchAll($sql, $binds);
      if (empty($entitiesRawData)) {
        return array();
      }

      $items = $this->createModels($entitiesRawData, array(
        'stored' => true
      ));

      unset($entitiesRawData);

      return $items;
    }

    /**
     *
     * @param array $fields
     * @return integer|boolean
     */
    public function insert($fields) {

      $columns = array();

      $db = $this->getAdapter();

      foreach ($fields as $column => $value) {
        $columns[] = $db->quote($column) . ' = ?';
      }
      $sql = "INSERT INTO " . $db->quote($this->getTableName()) . ' SET ' . implode(', ', $columns) . '';
      $binds = array_values($fields);

      $db->execute($sql, $binds);

      $pk = $this->getAdapter()->getLastInsertId();

      return !empty($pk) ? $pk : true;
    }

    /**
     *
     * @param array $fields
     * @param array|string $where
     * @return int
     */
    public function update($fields, $where) {

      $columns = array();
      $db = $this->getAdapter();

      foreach ($fields as $column => $value) {
        $columns[] = $db->quote($column) . ' = ?';
      }

      list($whereCondition, $whereBinds) = $this->getWhereAndBinds($where);
      $binds = array_values($fields);
      $binds = array_merge($binds, $whereBinds);

      $sql = "UPDATE " . $db->quote($this->getTableName()) . ' SET ' . implode(', ', $columns) . ' WHERE ' . $whereCondition;

      return $db->execute($sql, $binds)->rowCount();
    }

    /**
     * Delete items from database
     *
     * ```php
     * $table->delete(); // delete all from table
     *
     * $table->delete( [ 'title' => 'custom' ] );
     *
     *
     * $table->delete( 12 ); // primary key = 12
     *
     * ```
     *
     * @param $where
     * @return \PDOStatement
     */
    public function delete($where) {
      list($whereCondition, $binds) = $this->getWhereAndBinds($where);
      $db = $this->getAdapter();
      $sql = 'DELETE FROM ' . $db->quote($this->getTableName()) . '  WHERE ' . $whereCondition;
      return $db->execute($sql, $binds);
    }

    /**
     * @param array $entitiesRawData
     * @param array $config
     * @return Model[]
     */
    protected function createModels($entitiesRawData = array(), $config = array()) {
      $items = array();
      if (!empty($entitiesRawData)) {
        # generate config and init items
        foreach ($entitiesRawData as $key => $data) {
          $items[$key] = $this->createModel($data, $config);
          unset($entitiesRawData[$key]);
        }
      }
      return $items;
    }

    /**
     * @param array $data
     * @param array $config
     * @return Model
     */
    public function createModel(array $data = array(), $config = array()) {
      return (new $this->modelClass($data, $config, $this));
    }

  }