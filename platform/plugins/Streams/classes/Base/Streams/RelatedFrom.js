/**
 * Autogenerated base class representing related_from rows
 * in the Streams database.
 *
 * Don't change this file, since it can be overwritten.
 * Instead, change the Streams/RelatedFrom.js file.
 *
 * @module Streams
 */

var Q = require('Q');
var Db = Q.require('Db');
var Streams = Q.require('Streams');
/**
 * Base class representing 'RelatedFrom' rows in the 'Streams' database
 * @namespace Base.Streams
 * @class RelatedFrom
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
	this.className = "Streams_RelatedFrom";
}

Q.mixin(Base, Q.require('Db/Row'));

/**
 * @property fromPublisherId
 * @type string
 */
/**
 * @property fromStreamName
 * @type string
 */
/**
 * @property type
 * @type string
 */
/**
 * @property toPublisherId
 * @type string
 */
/**
 * @property toStreamName
 * @type string
 */
/**
 * @property insertedTime
 * @type string
 */

/**
 * This method uses Db to establish a connection with the information stored in the configuration.
 * If the this Db object has already been made, it returns this Db object.
 * @method db
 * @return {Db} The database connection
 */
Base.db = function () {
	return Streams.db();
};

/**
 * Retrieve the table name to use in SQL statement
 * @method table
 * @param [withoutDbName=false] {boolean} Indicates wheather table name shall contain the database name
 * @return {string|Db.Expression} The table name as string optionally without database name if no table sharding was started
 * or Db.Expression object with prefix and database name templates is table was sharded
 */
Base.table = function (withoutDbName) {
	if (Q.Config.get(['Db', 'connections', 'Streams', 'indexes', 'RelatedFrom'], false)) {
		return new Db.Expression((withoutDbName ? '' : '{$dbname}.')+'{$prefix}related_from');
	} else {
		var conn = Db.getConnection('Streams');
		var prefix = conn.prefix || '';
		var tableName = prefix + 'related_from';
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
	return 'Streams';
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
	q.className = 'Streams_RelatedFrom';
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
	q.className = 'Streams_RelatedFrom';
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
	q.className = 'Streams_RelatedFrom';
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
	q.className = 'Streams_RelatedFrom';
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
		"fromPublisherId",
		"fromStreamName",
		"type",
		"toPublisherId",
		"toStreamName"
	];
};

/**
 * Retrieves field names for class table
 * @method fieldNames
 * @return {array} An array of field names
 */
Base.prototype.fieldNames = function () {
	return [
		"fromPublisherId",
		"fromStreamName",
		"type",
		"toPublisherId",
		"toStreamName",
		"insertedTime"
	];
};

/**
 * Method is called before setting the field and verifies if value is string of length within acceptable limit.
 * Optionally accept numeric value which is converted to string
 * @method beforeSet_fromPublisherId
 * @param {string} value
 * @return {string} The value
 * @throws {Error} An exception is thrown if 'value' is not string or is exceedingly long
 */
Base.prototype.beforeSet_fromPublisherId = function (value) {
		if (value instanceof Db.Expression) return value;
		if (typeof value !== "string" && typeof value !== "number")
			throw new Error('Must pass a string to '+this.table()+".fromPublisherId");
		if (typeof value === "string" && value.length > 31)
			throw new Error('Exceedingly long value being assigned to '+this.table()+".fromPublisherId");
		return value;
};

/**
 * Method is called before setting the field and verifies if value is string of length within acceptable limit.
 * Optionally accept numeric value which is converted to string
 * @method beforeSet_fromStreamName
 * @param {string} value
 * @return {string} The value
 * @throws {Error} An exception is thrown if 'value' is not string or is exceedingly long
 */
Base.prototype.beforeSet_fromStreamName = function (value) {
		if (value instanceof Db.Expression) return value;
		if (typeof value !== "string" && typeof value !== "number")
			throw new Error('Must pass a string to '+this.table()+".fromStreamName");
		if (typeof value === "string" && value.length > 255)
			throw new Error('Exceedingly long value being assigned to '+this.table()+".fromStreamName");
		return value;
};

/**
 * Method is called before setting the field and verifies if value is string of length within acceptable limit.
 * Optionally accept numeric value which is converted to string
 * @method beforeSet_type
 * @param {string} value
 * @return {string} The value
 * @throws {Error} An exception is thrown if 'value' is not string or is exceedingly long
 */
Base.prototype.beforeSet_type = function (value) {
		if (value instanceof Db.Expression) return value;
		if (typeof value !== "string" && typeof value !== "number")
			throw new Error('Must pass a string to '+this.table()+".type");
		if (typeof value === "string" && value.length > 255)
			throw new Error('Exceedingly long value being assigned to '+this.table()+".type");
		return value;
};

/**
 * Method is called before setting the field and verifies if value is string of length within acceptable limit.
 * Optionally accept numeric value which is converted to string
 * @method beforeSet_toPublisherId
 * @param {string} value
 * @return {string} The value
 * @throws {Error} An exception is thrown if 'value' is not string or is exceedingly long
 */
Base.prototype.beforeSet_toPublisherId = function (value) {
		if (value instanceof Db.Expression) return value;
		if (typeof value !== "string" && typeof value !== "number")
			throw new Error('Must pass a string to '+this.table()+".toPublisherId");
		if (typeof value === "string" && value.length > 31)
			throw new Error('Exceedingly long value being assigned to '+this.table()+".toPublisherId");
		return value;
};

/**
 * Method is called before setting the field and verifies if value is string of length within acceptable limit.
 * Optionally accept numeric value which is converted to string
 * @method beforeSet_toStreamName
 * @param {string} value
 * @return {string} The value
 * @throws {Error} An exception is thrown if 'value' is not string or is exceedingly long
 */
Base.prototype.beforeSet_toStreamName = function (value) {
		if (value instanceof Db.Expression) return value;
		if (typeof value !== "string" && typeof value !== "number")
			throw new Error('Must pass a string to '+this.table()+".toStreamName");
		if (typeof value === "string" && value.length > 255)
			throw new Error('Exceedingly long value being assigned to '+this.table()+".toStreamName");
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
	var fields = ['fromPublisherId','fromStreamName','toPublisherId','toStreamName'], i;
	if (!this._retrieved) {
		var table = this.table();
		for (i=0; i<fields.length; i++) {
			if (typeof this.fields[fields[i]] === "undefined") {
				throw new Error("the field "+table+"."+fields[i]+" needs a value, because it is NOT NULL, not auto_increment, and lacks a default value.");
			}
		}
	}
	if (!this._retrieved && !value['insertedTime'])
		value['insertedTime'] = new Db.Expression('CURRENT_TIMESTAMP');
	return value;
};

module.exports = Base;