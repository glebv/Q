<?php

/**
 * Autogenerated base class representing domain rows
 * in the Metrics database.
 *
 * Don't change this file, since it can be overwritten.
 * Instead, change the Metrics_Domain.php file.
 *
 * @module Metrics
 */
/**
 * Base class representing 'Domain' rows in the 'Metrics' database
 * @class Base_Metrics_Domain
 * @extends Db_Row
 */
abstract class Base_Metrics_Domain extends Db_Row
{
	/**
	 * @property $fields['hostname']
	 * @type string
	 */
	/**
	 * @property $fields['publisherId']
	 * @type integer
	 */
	/**
	 * @property $fields['status']
	 * @type mixed
	 */
	/**
	 * The setUp() method is called the first time
	 * an object of this class is constructed.
	 * @method setUp
	 */
	function setUp()
	{
		$this->setDb(self::db());
		$this->setTable(self::table());
		$this->setPrimaryKey(
			array (
			)
		);
	}

	/**
	 * Connects to database
	 * @method db
	 * @static
	 * @return {iDb} The database object
	 */
	static function db()
	{
		return Db::connect('Metrics');
	}

	/**
	 * Retrieve the table name to use in SQL statement
	 * @method table
	 * @static
	 * @param {boolean} [$with_db_name=true] Indicates wheather table name shall contain the database name
 	 * @return {string|Db_Expression} The table name as string optionally without database name if no table sharding
	 * was started or Db_Expression class with prefix and database name templates is table was sharded
	 */
	static function table($with_db_name = true)
	{
		if (Q_Config::get('Db', 'connections', 'Metrics', 'indexes', 'Domain', false)) {
			return new Db_Expression(($with_db_name ? '{$dbname}.' : '').'{$prefix}'.'domain');
		} else {
			$conn = Db::getConnection('Metrics');
  			$prefix = empty($conn['prefix']) ? '' : $conn['prefix'];
  			$table_name = $prefix . 'domain';
  			if (!$with_db_name)
  				return $table_name;
  			$db = Db::connect('Metrics');
  			return $db->dbName().'.'.$table_name;
		}
	}
	/**
	 * The connection name for the class
	 * @method connectionName
	 * @static
	 * @return {string} The name of the connection
	 */
	static function connectionName()
	{
		return 'Metrics';
	}

	/**
	 * Create SELECT query to the class table
	 * @method select
	 * @static
	 * @param $fields {array} The field values to use in WHERE clauseas as 
	 * an associative array of `column => value` pairs
	 * @param [$alias=null] {string} Table alias
	 * @return {Db_Query_Mysql} The generated query
	 */
	static function select($fields, $alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->select($fields, self::table().' '.$alias);
		$q->className = 'Metrics_Domain';
		return $q;
	}

	/**
	 * Create UPDATE query to the class table
	 * @method update
	 * @static
	 * @param [$alias=null] {string} Table alias
	 * @return {Db_Query_Mysql} The generated query
	 */
	static function update($alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->update(self::table().' '.$alias);
		$q->className = 'Metrics_Domain';
		return $q;
	}

	/**
	 * Create DELETE query to the class table
	 * @method delete
	 * @static
	 * @param [$table_using=null] {object} If set, adds a USING clause with this table
	 * @param [$alias=null] {string} Table alias
	 * @return {Db_Query_Mysql} The generated query
	 */
	static function delete($table_using = null, $alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->delete(self::table().' '.$alias, $table_using);
		$q->className = 'Metrics_Domain';
		return $q;
	}

	/**
	 * Create INSERT query to the class table
	 * @method insert
	 * @static
	 * @param [$fields=array()] {object} The fields as an associative array of `column => value` pairs
	 * @param [$alias=null] {string} Table alias
	 * @return {Db_Query_Mysql} The generated query
	 */
	static function insert($fields = array(), $alias = null)
	{
		if (!isset($alias)) $alias = '';
		$q = self::db()->insert(self::table().' '.$alias, $fields);
		$q->className = 'Metrics_Domain';
		return $q;
	}
	/**
	 * Inserts multiple records into a single table, preparing the statement only once,
	 * and executes all the queries.
	 * @method insertManyAndExecute
	 * @static
	 * @param {array} [$records=array()] The array of records to insert. 
	 * (The field names for the prepared statement are taken from the first record.)
	 * You cannot use Db_Expression objects here, because the function binds all parameters with PDO.
	 * @param {array} [$options=array()]
	 *   An associative array of options, including:
	 *
	 * * "chunkSize" {integer} The number of rows to insert at a time. Defaults to 1.<br/>
	 * * "onDuplicateKeyUpdate" {array} You can put an array of fieldname => value pairs here,
	 * 		which will add an ON DUPLICATE KEY UPDATE clause to the query.
	 *
	 */
	static function insertManyAndExecute($records = array(), $options = array())
	{
		self::db()->insertManyAndExecute(self::table(), $records, $options);
	}
	
	/**
	 * Method is called before setting the field and verifies if value is string of length within acceptable limit.
	 * Optionally accept numeric value which is converted to string
	 * @method beforeSet_hostname
	 * @param {string} $value
	 * @return {array} An array of field name and value
	 * @throws {Exception} An exception is thrown if $value is not string or is exceedingly long
	 */
	function beforeSet_hostname($value)
	{
		if ($value instanceof Db_Expression) return array('hostname', $value);
		if (!is_string($value) and !is_numeric($value))
			throw new Exception('Must pass a string to '.$this->getTable().".hostname");
		if (strlen($value) > 255)
			throw new Exception('Exceedingly long value being assigned to '.$this->getTable().".hostname");
		return array('hostname', $value);			
	}

	/**
	 * Method is called before setting the field and verifies if integer value falls within allowed limits
	 * @method beforeSet_publisherId
	 * @param {integer} $value
	 * @return {array} An array of field name and value
	 * @throws {Exception} An exception is thrown if $value is not integer or does not fit in allowed range
	 */
	function beforeSet_publisherId($value)
	{
		if ($value instanceof Db_Expression) return array('publisherId', $value);
		if (!is_numeric($value) or floor($value) != $value)
			throw new Exception('Non-integer value being assigned to '.$this->getTable().".publisherId");
		if ($value < 0 or $value > 1.844674407371E+19)
			throw new Exception("Out-of-range value '$value' being assigned to ".$this->getTable().".publisherId");
		return array('publisherId', $value);			
	}

	/**
	 * Method is called before setting the field and verifies if value belongs to enum values list
	 * @method beforeSet_status
	 * @param {string} $value
	 * @return {array} An array of field name and value
	 * @throws {Exception} An exception is thrown if $value does not belong to enum values list
	 */
	function beforeSet_status($value)
	{
		if ($value instanceof Db_Expression) return array('status', $value);
		if (!in_array($value, array('pending','verified')))
			throw new Exception("Out-of-range value '$value' being assigned to ".$this->getTable().".status");
		return array('status', $value);			
	}

	/**
	 * Method is called after field is set and used to keep $fieldsModified property up to date
	 * @method afterSet
	 * @param {string} $name The field name
	 * @param {mixed} $value The value of the field
	 * @return {mixed} Original value
	 */
	function afterSet($name, $value)
	{
		if (!in_array($name, $this->fieldNames()))
			$this->notModified($name);
		return $value;			
	}

	/**
	 * Check if mandatory fields are set and updates 'magic fields' with appropriate values
	 * @method beforeSave
	 * @param {array} $value The array of fields
	 * @return {array}
	 * @throws {Exception} If mandatory field is not set
	 */
	function beforeSave($value)
	{
		if (!$this->retrieved) {
			$table = $this->getTable();
			foreach (array('hostname','publisherId') as $name) {
				if (!isset($value[$name])) {
					throw new Exception("the field $table.$name needs a value, because it is NOT NULL, not auto_increment, and lacks a default value.");
				}
			}
		}
		return $value;			
	}

	/**
	 * Retrieves field names for class table
	 * @method fieldNames
	 * @static
	 * @param {string} [$table_alias=null] If set, the alieas is added to each field
	 * @param {string} [$field_alias_prefix=null] If set, the method returns associative array of `'prefixed field' => 'field'` pairs
	 * @return {array} An array of field names
	 */
	static function fieldNames($table_alias = null, $field_alias_prefix = null)
	{
		$field_names = array('hostname', 'publisherId', 'status');
		$result = $field_names;
		if (!empty($table_alias)) {
			$temp = array();
			foreach ($result as $field_name)
				$temp[] = $table_alias . '.' . $field_name;
			$result = $temp;
		} 
		if (!empty($field_alias_prefix)) {
			$temp = array();
			reset($field_names);
			foreach ($result as $field_name) {
				$temp[$field_alias_prefix . current($field_names)] = $field_name;
				next($field_names);
			}
			$result = $temp;
		}
		return $result;			
	}
};