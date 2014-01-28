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
     * @var array
     */
    private $columns = array();

    /**
     * Primary key of the table
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
     * @return bool
     */
    public function existConnectMultilangTable() {
      return $this->isMultiLanguage;
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
     * @return string
     */
    public function getCurrentLanguage() {
      if ($this->currentLanguage === '') {
        return \Uc::app()->language->getCurrent();
      }
      return $this->currentLanguage;
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

      list($whereCondition, $params) = $this->getWhereAndParams($where);
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
     * @param $name
     * @return array|null
     */
    public function getRelation($name) {
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

      $this->fetchColumnInfo($rawColumnData);

      $this->init();
    }

    /**
     * @param $rawColumnData
     */
    private function fetchColumnInfo($rawColumnData) {
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
     * @param string $currentLang
     * @return $this
     */
    public static function instance($currentLang = '') {
      $tableClass = get_called_class();
      if (empty(self::$tableInstances[$tableClass])) {
        self::$tableInstances[$tableClass] = new $tableClass();
      }
      self::$tableInstances[$tableClass]->currentLanguage = $currentLang;
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
     *
     * @return string
     */
    public function pk() {
      return $this->pk;
    }

    /**
     * @param array $attributes
     * @return array 2 dimension array
     */
    protected function getWhereAndParams($attributes = null) {
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
     * @param $select
     * @internal param mixed $attributes
     * @return \Uc\Core\Db\Model|null
     */
    public function fetchOne($select) {
      if ($select instanceof \Uc\Core\Db\Select) {

        if ($this->isMultiLanguage) {
          //  bind multiLanguage table

          $select->cols('*');
          $select->join('LEFT JOIN '
            . $this->getMultiLangTable() .
            ' ON '
            . $this->getTableName() .
            '.id = ' .
            $this->getMultiLangTable() .
            '.table_lang_id');

          $select->where($this->getMultiLangTable() . '.lang=\'' . $this->getCurrentLanguage() . '\'');
        }

        $sql = $select->getQuery();
        $params = $select->getBinds();
      } else {
        list($where, $params) = $this->getWhereAndParams($select);

        if ($this->isMultiLanguage) {
          $joinLangTable = ' LEFT JOIN `'
            . $this->getMultiLangTable() .
            '` ON `'
            . $this->getTableName() .
            '`.`id` = `' .
            $this->getMultiLangTable() .
            '`.`table_lang_id`' .
            ' WHERE ' . $this->getMultiLangTable() . '.lang=\'' . $this->getCurrentLanguage() . '\'' .
            ' AND ';
        } else {
          $joinLangTable = ' where ';
        }
        $sql = 'Select * from `' . $this->getTableName() . '`' . $joinLangTable . $where . ' Limit 1';
      }
      # fetch data
      $smt = $this->getAdapter()->execute($sql, $params);
      $data = $smt->fetch(\PDO::FETCH_ASSOC);

      if (empty($data)) {
        return null;
      }

      # generate config
      $config = array(
        'stored' => true
      );

      return $this->createModel($data, $config);
    }

    /**
     *
     * @param mixed (string | array  | \Uc\Core\Db\Select ) $select
     * @return \Uc\Core\Db\Model[]
     */
    public function fetchAll($select = false) {
      if ($select instanceof \Uc\Core\Db\Select) {

        if ($this->isMultiLanguage) {
          //  bind multilanguage table
          $select->cols('*');
          $select->join('LEFT JOIN '
            . $this->getMultiLangTable() .
            ' ON '
            . $this->getTableName() .
            '.id = ' .
            $this->getMultiLangTable() .
            '.table_lang_id');

          $select->where($this->getMultiLangTable() . '.lang=\'' . $this->getCurrentLanguage() . '\'');
        }

        $sql = $select->getQuery();
        $params = $select->getBinds();
      } else {
        list($where, $params) = $this->getWhereAndParams($select);

        if ($this->isMultiLanguage) {
          $joinLangTable = ' LEFT JOIN `'
            . $this->getMultiLangTable() .
            '` ON `'
            . $this->getTableName() .
            '`.`id` = `' .
            $this->getMultiLangTable() .
            '`.`table_lang_id`' .
            ' WHERE ' . $this->getMultiLangTable() . '.lang=\'' . $this->getCurrentLanguage() . '\'' .
            ' AND ';
        } else {
          $joinLangTable = ' where ';
        }

        $sql = 'Select * from `' . $this->getTableName() . '`' . $joinLangTable . $where;
      }

      $smt = $this->getAdapter()->execute($sql, $params);

      $entitiesRawData = $smt->fetchAll(\PDO::FETCH_ASSOC);
      $items = $this->createModels($entitiesRawData);
      unset($entitiesRawData);

      return $items;
    }

    /**
     * @author  Ivan Scherbak <dev@funivan.com> 7/20/12 9:37 PM
     * @param array $fields
     * @return integer|boolean
     */
    public function insert($fields) {

      if ($this->isMultiLanguage) {

        $tablesFields = array();

        $fields['lang'] = $this->getCurrentLanguage();
        $tablesFields = $this->extractFields($tablesFields, $fields);

        $error = '';
        if ($this->lastInsertedRowCache === null) {
          $error .= $this->insertRowInTable($this->getTableName(), $tablesFields[$this->getTableName()]['params'], $tablesFields[$this->getTableName()]['set']);
          $this->lastInsertedRowCache = $this->getAdapter()->getLastInsertId();
        }

        //  insert row into multilanguage table
        $table = $tablesFields[$this->getMultiLangTable()];
        //  set bind to lang table row
        $table['params'][] = $this->lastInsertedRowCache;
        //@todo трохи не гуд що зашито, можливо винести у властивість таблиці
        $table['set'][] = '`table_lang_id` = ? ';

        //  set bind to lang
        $table['params'][array_search('`lang` = ? ', $table['set'])] = $this->getCurrentLanguage();

        $error .= $this->insertRowInTable($this->getMultiLangTable(), $table['params'], $table['set']);

        if ($error == '00000' and empty($this->lastInsertedRowCache)) {
          # pk is not auto increment and record was created
          return true;
        } else {
          #
          return $this->lastInsertedRowCache;
        }
      } else {

        $this->insertRow($this->getTableName(), $fields);
        $pk = $this->getAdapter()->getLastInsertId();

        return !empty($pk) ? $pk : true;
      }
    }

    /**
     *
     * @param array $fields
     * @param array|string $where
     * @return int
     */
    public function update($fields, $where) {

      if ($this->isMultiLanguage) {
        /*g*/
        $tablesFields = array();
        $tablesFields = $this->extractFields($tablesFields, $fields);

        if (array_key_exists($this->getMultiLangTable(), $tablesFields)) {
          list($whereString, $params) = $this->getWhereAndParams(array('table_lang_id' => $where[0],
            'lang' => $where[1]));

          $params = array_merge($tablesFields[$this->getMultiLangTable()]['params'], $params);
          $this->updateRowInTable($this->getMultiLangTable(), $whereString, $params, $tablesFields[$this->getMultiLangTable()]['set']);
        }

        if (array_key_exists($this->getTableName(), $tablesFields)) {
          list($whereString, $params) = $this->getWhereAndParams($where[0]);

          $params = array_merge($tablesFields[$this->getTableName()]['params'], $params);
          $smt = $this->updateRowInTable($this->getTableName(), $whereString, $params, $tablesFields[$this->getTableName()]['set']);
          return $smt->rowCount();
        }

        $sql = 'Select * FROM `' . $this->getTableName() . '`';
        $smt = $this->getAdapter()->execute($sql);
        return $smt->rowCount();

        /*g*/
      } else {

        $stm = $this->updateRow($this->getTableName(), $fields, $where);
        return $stm->rowCount();

      }
    }

    /**
     * @param $where
     * @return int
     */
    public function delete($where) {

      if ($this->isMultiLanguage) {
        list($whereString, $params) = $this->getWhereAndParams(array('table_lang_id' => $where));

        $this->deleteRowInTable($this->getMultiLangTable(), $whereString, $params);
      }

      list($whereString, $params) = $this->getWhereAndParams($where);

      return $this->deleteRowInTable($this->getTableName(), $whereString, $params);

    }

    /**
     * @author  Ivan Scherbak <dev@funivan.com> 7/20/12
     * @param array $entitiesRawData
     * @return array
     */
    protected function createModels($entitiesRawData = array()) {
      $items = array();
      if (!empty($entitiesRawData)) {
        # generate config and init items
        $config = array(
          'stored' => true
        );
        foreach ($entitiesRawData as $key => $data) {
          $items[$key] = $this->createModel($data, $config);
          unset($entitiesRawData[$key]);
        }
      }
      return $items;
    }

    /**
     *
     * @author  Ivan Scherbak <dev@funivan.com> 7/20/12
     * @param array $data
     * @param array $config
     * @return \Uc\Core\Db\Model
     */
    public function createModel(array $data = array(), $config = array()) {
      $className = $this->getModelClass();
      $object = new $className($data, $config, $this);
      return $object;
    }

    /**
     * Розстановка полів по таблицях
     * @param $tables
     * @param $fields
     * @internal param $set
     * @internal param $params
     * @return mixed
     */
    protected function extractFields($tables, $fields) {
      foreach ($fields as $key => $value) {
        $tableName = $this->getTableNameByField($key);

        $tables[$tableName]['params'][] = $value;
        $tables[$tableName]['set'][] = '`' . $key . '` = ? ';
      }
      return $tables;
    }

  }