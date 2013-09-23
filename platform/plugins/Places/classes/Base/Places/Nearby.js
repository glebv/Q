/**
 * Autogenerated base class representing nearby rows
 * in the Places database.
 *
 * Don't change this file, since it can be overwritten.
 * Instead, change the Places/Nearby.js file.
 *
 * @module Places
 */

var Q = require('Q');
var Db = Q.require('Db');
var Places = Q.require('Places');
/**
 * Base class representing 'Nearby' rows in the 'Places' database
 * @namespace Base.Places
 * @class Nearby
 * @extends Db.Row
 * @constructor
 * @param {object} [fields={}] The fields values to initialize table row as 
 * an associative array of `{column: value}` pairs
 */
function Base (fields) {
	/**
	 * The name of the class
	 * @property className
	 * @type string
	 */
	this.className = "Places_Nearby";
}

Q.mixin(Base, Q.require('Db/Row'));

/**
 * @property fromZipcode
 * @type string
 */
/**
 * @property miles
 * @type number
 */
/**
 * @property toZipcode
 * @type string
 */
/**
 * @property updatedTime
 * @type string
 */

/**
 * This method uses Db to establish a connection with the information stored in the configuration.
 * If the this Db object has already been made, it returns this Db object.
 * @method db
 * @return {Db} The database connection
 */
Base.db = function () {
	return Places.db();
};

/**
 * Retrieve the table name to use in SQL statement
 * @method table
 * @param [withoutDbName=false] {boolean} Indicates wheather table name shall contain the database name
 * @return {string|Db.Expression} The table name as string optionally without database name if no table sharding was started
 * or Db.Expression object with prefix and database name templates is table was sharded
 */
Base.table = function (withoutDbName) {
	if (Q.Config.get(['Db', 'connections', 'Places', 'indexes', 'Nearby'], false)) {
		return new Db.Expression((withoutDbName ? '' : '{$dbname}.')+'{$prefix}nearby');
	} else {
		var conn = Db.getConnection('Places');
		var prefix = conn.prefix || '';
		var tableName = prefix + 'nearby';
		var dbname = Base.table.dbname;
		if (!dbname) {
			var dsn = Db.parseDsnString(conn['dsn']);
			dbname = Base.table.dbname = dsn.dbname;
		}
		return withoutDbName ? tableName : dbname + '.' + tableName;
	}
};

/**
 * The connection name for the class
 * @method connectionName
 * @return {string} The name of the connection
 */
Base.connectionName = function() {
	return 'Places';
};

/**
 * Create SELECT query to the class table
 * @method SELECT
 * @param fields {object|string} The field values to use in WHERE clauseas as an associative array of `{column: value}` pairs
 * @param [alias=null] {string} Table alias
 * @return {Db.Query.Mysql} The generated query
 */
Base.SELECT = function(fields, alias) {
	var q = Base.db().SELECT(fields, Base.table()+(alias ? ' '+alias : ''));
	q.className = 'Places_Nearby';
	return q;
};

/**
 * Create UPDATE query to the class table. Use Db.Query.Mysql.set() method to define SET clause
 * @method UPDATE
 * @param [alias=null] {string} Table alias
 * @return {Db.Query.Mysql} The generated query
 */
Base.UPDATE = function(alias) {
	var q = Base.db().UPDATE(Base.table()+(alias ? ' '+alias : ''));
	q.className = 'Places_Nearby';
	return q;
};

/**
 * Create DELETE query to the class table
 * @method DELETE
 * @param [table_using=null] {object} If set, adds a USING clause with this table
 * @param [alias=null] {string} Table alias
 * @return {Db.Query.Mysql} The generated query
 */
Base.DELETE = function(table_using, alias) {
	var q = Base.db().DELETE(Base.table()+(alias ? ' '+alias : ''), table_using);
	q.className = 'Places_Nearby';
	return q;
};

/**
 * Create INSERT query to the class table
 * @method INSERT
 * @param {object} [fields={}] The fields as an associative array of `{column: value}` pairs
 * @param [alias=null] {string} Table alias
 * @return {Db.Query.Mysql} The generated query
 */
Base.INSERT = function(fields, alias) {
	var q = Base.db().INSERT(Base.table()+(alias ? ' '+alias : ''), fields || {});
	q.className = 'Places_Nearby';
	return q;
};

// Instance methods
Base.prototype.setUp = function() {
	// does nothing for now
};

Base.prototype.db = function () {
	return Base.db();
};

Base.prototype.table = function () {
	return Base.table();
};

/**
 * Retrieves primary key fields names for class table
 * @method primaryKey
 * @return {string[]} An array of field names
 */
Base.prototype.primaryKey = function () {
	return [
		
	];
};

/**
 * Retrieves field names for class table
 * @method fieldNames
 * @return {array} An array of field names
 */
Base.prototype.fieldNames = function () {
	return [
		"fromZipcode",
		"miles",
		"toZipcode",
		"updatedTime"
	];
};

/**
 * Method is called before setting the field and verifies if value is string of length within acceptable limit.
 * Optionally accept numeric value which is converted to string
 * @method beforeSet_fromZipcode
 * @param {string} value
 * @return {string} The value
 * @throws {Error} An exception is thrown if 'value' is not string or is exceedingly long
 */
Base.prototype.beforeSet_fromZipcode = function (value) {
		if (value instanceof Db.Expression) return value;
		if (typeof value !== "string" && typeof value !== "number")
			throw new Error('Must pass a string to '+this.table()+".fromZipcode");
		if (typeof value === "string" && value.length > 10)
			throw new Error('Exceedingly long value being assigned to '+this.table()+".fromZipcode");
		return value;
};

/**
 * Method is called before setting the field to verify if value is a number
 * @method beforeSet_miles
 * @param {integer} value
 * @return {integer} The value
 * @throws {Error} If 'value' is not number
 */
Base.prototype.beforeSet_miles = function (value) {
		if (value instanceof Db.Expression) return value;
		value = Number(value);
		if (isNaN(value))
			throw new Error('Non-number value being assigned to '+this.table()+".miles");
		return value;
};

/**
 * Method is called before setting the field and verifies if value is string of length within acceptable limit.
 * Optionally accept numeric value which is converted to string
 * @method beforeSet_toZipcode
 * @param {string} value
 * @return {string} The value
 * @throws {Error} An exception is thrown if 'value' is not string or is exceedingly long
 */
Base.prototype.beforeSet_toZipcode = function (value) {
		if (value instanceof Db.Expression) return value;
		if (typeof value !== "string" && typeof value !== "number")
			throw new Error('Must pass a string to '+this.table()+".toZipcode");
		if (typeof value === "string" && value.length > 10)
			throw new Error('Exceedingly long value being assigned to '+this.table()+".toZipcode");
		return value;
};

/**
 * Check if mandatory fields are set and updates 'magic fields' with appropriate values
 * @method beforeSave
 * @param {array} value The array of fields
 * @return {array}
 * @throws {Error} If mandatory field is not set
 */
Base.prototype.beforeSave = function (value) {
	var fields = ['fromZipcode','miles','toZipcode'], i;
	if (!this._retrieved) {
		var table = this.table();
		for (i=0; i<fields.length; i++) {
			if (typeof this.fields[fields[i]] === "undefined") {
				throw new Error("the field "+table+"."+fields[i]+" needs a value, because it is NOT NULL, not auto_increment, and lacks a default value.");
			}
		}
	}
	// convention: we'll have updatedTime = insertedTime if just created.
	value['updatedTime'] = new Db.Expression('CURRENT_TIMESTAMP');
	return value;
};

module.exports = Base;