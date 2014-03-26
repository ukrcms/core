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
    private $columns = array();

    /**
     * Primary key of the table
     *
     * @var string
     */
    private $pk = false;

    /**
     * @var bool
     */
    protected $isMultiLanguage = false;

    /**
     * @var string
     */
    protected $currentLanguage = '';

    /**
     * @var null
     */
    protected $lastInsertedRowCache = null;

    /*
     * array fields in table
     */
    protected $fieldsInTable = array();

    /**
     * @todo refactor. Move to method
     * @var string
     */
    protected $selectClass = \Uc\Core\Db\Select::N;

    protected static $tableInstances = array();


    /**
     * @return string
     */
    public abstract function getModelClass();

    /**
     * @return string
     */
    public abstract function getTableName();

    /**
     * @return string
     */
    public function getMultiLangTable() {
      return $this->getTableName() . \Uc::app()->db->tableLangsSuffix;
    }


    /**
     * Очищення кешу, останнього доданого рядка в основній таблиці,
     * потрібно для роботи багатомовності,
     * основна таблиця посилається на таблицю з мовами
     */
    public function clearLastInsertedRowCache() {
      $this->lastInsertedRowCache = null;
    }


    /**
     *
     * @return array
     */
    public function relations() {

    }

    /**
     * @param string $tableName
     * @param array $binds key=>value   Key - name of column
     * @return \PDOStatement
     */
    private function insertRow($tableName, array $binds) {
      $columns = array();

      $db = $this->getAdapter();

      foreach ($binds as $col => $value) {
        $columns[] = $db->quote($col) . ' = ?';
      }

      $sql = "INSERT INTO " . $db->quote($tableName) . ' SET ' . implode(', ', $columns) . '';
      $binds = array_values($binds);

      return $db->execute($sql, $binds);
    }


    /**
     * @param string $tableName
     * @param array $binds
     * @param array|string $where
     * @return \PDOStatement
     */
    protected function updateRow($tableName, array $binds, $where) {

      $columns = array();

      $db = $this->getAdapter();
      foreach ($binds as $col => $value) {
        $columns[] = $db->quote($col) . ' = ?';
      }

      list($whereCondition, $params) = $this->getWhereAndBinds($where);
      $binds = array_values($binds);
      $binds = array_merge($binds, $params);

      $sql = "UPDATE " . $db->quote($tableName) . ' SET ' . implode(', ', $columns) . ' WHERE ' . $whereCondition;

      return $db->execute($sql, $binds);
    }

    /**
     * Розставовка ключів і значень для запиту в БД
     * @param $fields
     * @param $params
     * @param $set
     */
    private static function extractFieldsValues($fields, $params, $set) {
      foreach ($fields as $key => $value) {
        $params[] = $value;
        $set[] = '`' . $key . '` = ? ';
      }
    }

    private function insertRowInTable($tableName, $tableParams, $tableSet) {
      $sql = 'Insert into ' . $tableName . ' Set ' . implode(', ', $tableSet);
      $smt = $this->getAdapter()->execute($sql, $tableParams);
      return $smt->errorCode();
    }

    private function updateRowInTable($tableName, $whereString, $params, $set) {
      $sql = 'Update `' . $tableName . '` Set ' . implode(', ', $set) . ' Where ' . $whereString . '';
      return $this->getAdapter()->execute($sql, $params);
    }

    private function deleteRowInTable($tableName, $whereString, $params) {
      $sql = 'Delete from `' . $tableName . '`  Where ' . $whereString . '';
      return $this->getAdapter()->execute($sql, $params);
    }


    /**
     * Return relation info as array or null if relation with this name doe not exist
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

      if ($this->isMultiLanguage && empty(\Uc::app()->db->tableLangsSuffix)) {
        throw new \Uc\Core\Exception('Invalid tableLangsSuffix for multilanguage table');
      }

      $stmt = $this->getAdapter()->prepare('SHOW COLUMNS FROM ' . $this->getAdapter()->quote($this->getTableName()));

      $stmt->execute();
      $rawColumnData = $stmt->fetchAll();

      $this->setFieldsInTable($this->getTableName(), $rawColumnData);

      if ($this->isMultiLanguage) {
        $stmt = $this->getAdapter()->prepare('SHOW COLUMNS FROM ' . $this->getAdapter()->quote($this->getMultiLangTable()));
        $stmt->execute();
        $rawColumnDataMultiLang = $stmt->fetchAll();

        $this->setFieldsInTable($this->getMultiLangTable(), $rawColumnDataMultiLang);

        $rawColumnData = array_merge($rawColumnData, $rawColumnDataMultiLang);
      }

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
     * встановлення привязки поля до конкретної таблиці
     * @param       $tableName
     * @param array $data
     */
    private function setFieldsInTable($tableName, $data = array()) {
      foreach ($data as $item) {
        $this->fieldsInTable[$tableName][] = $item['Field'];
      }
    }


    /**
     * Отримання назви таблиці по назві поля
     * @param $fieldName
     * @return bool|int|string
     */
    private function getTableNameByField($fieldName) {

      foreach ($this->fieldsInTable as $key => $value) {

        $item = array_search($fieldName, $value);
        if ($item != false) {
          return $key;
        }
      }
      return false;
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
     * @author  Ivan Scherbak <dev@funivan.com> 7/20/12 9:37 PM
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
     * @param $where
     * @return \PDOStatement
     */
    public function delete($where) {
      list($whereCondition, $binds) = $this->getWhereAndBinds($where);
      $db = $this->getAdapter();
      $sql = 'Delete from ' . $db->quote($this->getTableName()) . '  WHERE ' . $whereCondition;
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
      $className = $this->getModelClass();
      $object = new $className($data, $config, $this);
      return $object;
    }

  }