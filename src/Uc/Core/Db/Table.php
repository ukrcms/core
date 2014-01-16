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
     * @var string/null
     */
    protected $currentLanguage = null;

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
     * clear cache
     */
    public function clearLastInsertedRowCache() {
      $this->lastInsertedRowCache = null;
    }

    /**
     * @return string
     */
    public function getCurrentLanguage() {
      if ($this->currentLanguage === null) {
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
        throw new \Uc\Core\Exception('Set \Uc::app()->db->tableLangsSuffix, because this table"' . $this->getTableName() . '" has connected multilang table"');
      }

      $stmt = $this->getAdapter()->prepare('SHOW COLUMNS FROM ' . $this->getAdapter()->quoteTable($this->getTableName()));

      $stmt->execute();
      $rawColumnData = $stmt->fetchAll();

      $this->setFieldsInTable($this->getTableName(), $rawColumnData);

      if ($this->isMultiLanguage) {
        $stmt = $this->getAdapter()->prepare('SHOW COLUMNS FROM ' . $this->getAdapter()->quoteTable($this->getMultiLangTable()));
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
     * @param       $tableName
     * @param array $data
     */
    private function setFieldsInTable($tableName, $data = array()) {

      foreach ($data as $item) {
        $this->fieldsInTable[$tableName][] = $item['Field'];
      }

    }

    /*
     * find table name by field
     */
    /**
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
     * @static
     * @param string $currentLang
     * @return $this
     */
    public static function instance($currentLang = '') {
      $tableClass = get_called_class();
      if (empty(self::$tableInstances[$tableClass])) {
        self::$tableInstances[$tableClass] = new $tableClass();
      }
      self::$tableInstances[$tableClass]->currentLang = $currentLang;
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
     * @return array
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
     * @todo переписати цей метод
     * @author  Ivan Scherbak <dev@funivan.com> 7/20/12 9:37 PM
     * @param array $fields
     * @return mixed (boolean | integer)
     */
    public function insert($fields) {

      if ($this->isMultiLanguage) {

        $set = $params = $tables = array();

        $fields['lang'] = $this->getCurrentLanguage();
        //@todo $tables по назві змінної спочатку думаєш що це масив таблиць, а виявляється масив полів Дуже важко орієнтуватись
        $tables = $this->extractFields($tables, $fields, $set, $params);

        $error = '';
        if ($this->lastInsertedRowCache === null) {
          $sql = 'Insert into ' . $this->getTableName() . ' Set ' . implode(', ', $tables[$this->getTableName()]['set']);
          $smt = $this->getAdapter()->execute($sql, $tables[$this->getTableName()]['params']);
          $this->lastInsertedRowCache = $this->getAdapter()->getLastInsertId();
          $error = $smt->errorCode();
        }

        //  insert row into multilanguage table
        $table = $tables[$this->getMultiLangTable()];
        //  set bind to lang table row
        $table['params'][] = $this->lastInsertedRowCache;
        $table['set'][] = '`table_lang_id` = ? '; //@todo трохи не гуд що зашито, можливо винести у властивість таблиці

        //  set bind to lang
        $table['params'][array_search('`lang` = ? ', $table['set'])] = $this->getCurrentLanguage();

        $sql = 'Insert into ' . $this->getMultiLangTable() . ' Set ' . implode(', ', $table['set']);
        $smt = $this->getAdapter()->execute($sql, $table['params']);
        $error .= "\n" . $smt->errorCode();

        if ($error == '00000' and empty($this->lastInsertedRowCache)) {
          # pk is not auto increment and record was created
          return true;
        } else {
          #
          return $this->lastInsertedRowCache;
        }
      } else {

        //@todo переписати метод Винести код який повторяється вгору
        $set = $params = array();
        foreach ($fields as $key => $value) {
          $params[] = $value;
          $set[] = '`' . $key . '` = ? ';                  //@todo юзати qutoe
        }

        $sql = 'Insert into ' . $this->getTableName() . ' Set ' . implode(', ', $set);
        $smt = $this->getAdapter()->execute($sql, $params);
        $pk = $this->getAdapter()->lastInsertId();
        $error = $smt->errorCode();

        if ($error == '00000' and empty($pk)) {
          # pk is not auto increment and record was created
          return true;
        } else {
          #
          return $pk;
        }
      }
    }

    /**
     *
     * @param array $fields
     * @param array $where
     * @return int
     */
    public function update($fields, $where) {

      if ($this->isMultiLanguage) {
        $tables = array();

        foreach ($fields as $key => $value) {
          $tableName = $this->getTableNameByField($key);
          $tables[$tableName]['binds'][] = $value;
          $tables[$tableName]['set'][] = '`' . $key . '` = ? ';
        }

        if (array_key_exists($this->getMultiLangTable(), $tables)) {
          list($whereString, $params) = $this->getWhereAndParams(array('table_lang_id' => $where[0],
            'lang' => $where[1]));
          $params = array_merge($tables[$this->getMultiLangTable()]['binds'], $params);
          $sql = 'Update `' . $this->getMultiLangTable() . '` Set ' . implode(', ', $tables[$this->getMultiLangTable()]['set']) . ' Where ' . $whereString . '';
          $smt = $this->getAdapter()->execute($sql, $params);
        }

        if (array_key_exists($this->getTableName(), $tables)) {
          list($whereString, $params) = $this->getWhereAndParams($where[0]);

          $params = array_merge($tables[$this->getTableName()]['binds'], $params);
          $sql = 'Update `' . $this->getTableName() . '` Set ' . implode(', ', $tables[$this->getTableName()]['set']) . ' Where ' . $whereString . '';
          $smt = $this->getAdapter()->execute($sql, $params);
          return $smt->rowCount();
        }

        $sql = 'Select * FROM `' . $this->getTableName() . '`';
        $smt = $this->getAdapter()->execute($sql);
        return $smt->rowCount();
      } else {
        list($whereString, $params) = $this->getWhereAndParams($where);

        $set = array();
        foreach ($fields as $key => $value) {
          $binds[] = $value;
          $set[] = '`' . $key . '` = ? ';
        }

        $params = array_merge($binds, $params);
        $sql = 'Update `' . $this->getTableName() . '` Set ' . implode(', ', $set) . ' Where ' . $whereString . '';
        $smt = $this->getAdapter()->execute($sql, $params);
        return $smt->rowCount();
      }
    }

    /**
     * @param $where
     * @return int
     */
    public function delete($where) {
      if ($this->isMultiLanguage) {
        list($whereString, $params) = $this->getWhereAndParams(array('table_lang_id' => $where));

        $sql = 'Delete  from `' . $this->getMultiLangTable() . '`  Where ' . $whereString . '';
        $smt = $this->getAdapter()->execute($sql, $params);
      }

      list($whereString, $params) = $this->getWhereAndParams($where);
      $sql = 'Delete from `' . $this->getTableName() . '`  Where ' . $whereString . '';
      $smt = $this->getAdapter()->execute($sql, $params);
      return $smt->rowCount();
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
     * @todo #refactoring Delete this method
     * @return mixed
     */
    public function getRowCount() {
      $sql = 'Select * FROM `' . $this->getTableName() . '`';
      $smt = $this->getAdapter()->execute($sql);
      return $smt->rowCount();
    }

    /**
     * @param $tables
     * @param $fields
     * @param $set
     * @param $params
     * @return mixed
     */
    protected function extractFields($tables, $fields, $set, $params) {
      foreach ($fields as $key => $value) {
        $tableName = $this->getTableNameByField($key);

        $tables[$tableName]['params'][] = $params[] = $value;
        $tables[$tableName]['set'][] = $set[] = '`' . $key . '` = ? ';
      }
      return $tables;
    }
  }