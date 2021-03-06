/**
 * @module Db
 */

var Q = require('Q');
var util = require('util');

/**
 * The class representing database row
 * @class Row
 * @namespace Db
 * @constructor
 * @param fields {object} Optional object of fields
 * @param [retrieved=false] {boolean} Optional if object was retrieved from database or created
 */
function Row(fields, retrieved /* false */) {

	/**
	 * The fields names
	 * @property _fieldNames
	 * @type array
	 * @private
	 */
	var _fieldNames = this.fieldNames();

	/**
	 * The names of the fields in primary key
	 * @property _primaryKey
	 * @type array
	 * @private
	 */
	var _primaryKey = this.primaryKey();

	/**
	 * A container for fields values.
	 * Used by [G/S]etters to store values of the fields
	 * @property fields
	 * @type object
	 */
	this.fields = {};
	var _fields = {};
	
	/**
	 * Whether this Db_Row was retrieved or not.
	 * The save() method uses this to decide whether to insert or update.
	 * @property _retrieved
	 * @type boolean
	 * @private
	 */
	var _retrieved;
	/**
	 * The value of the primary key of the row
	 * Is set automatically if the Db_Row was fetched from a Db_Result.
	 * @property _pkValue
	 * @type object
	 * @private
	 */
	var _pkValue;
	
	/**
	 * The fields of the row
	 * @property _fieldsModified
	 * @type object
	 * @private
	 */
	var _fieldsModified = {};
	
	/**
	 * The temporary config to make shards split
	 * @property _split
	 * @type object
	 * @private
	 */
	var _split = null;
	
	var k, i;

	for (i in _fieldNames) {
		k = _fieldNames[i];
		this.fields.__defineGetter__(k, (function (k, self) {
			return function () {
				if (self["beforeGet_" + k] && (typeof self["beforeGet_" + k] === "function")) {
					// NOTE: this is synchronous, we wouldn't be able to do any async,
					// and since Node is a single thread, we shouldn't do I/O at all in them!
					// This should be documented.
					self["beforeGet_" + k].call(self, _fields);
				}
				return _fields[k];
			};
		})(k, this));
		this.fields.__defineSetter__(k, (function (k, self) {
			return function (x) {
				// we shall skip beforeSet_xxx during shards split process to get exact copy of the data
				if (!_split && self["beforeSet_" + k] && (typeof self["beforeSet_" + k] === "function")) {
					// NOTE: this is synchronous, we wouldn't be able to do any async,
					// and since Node is a single thread, we shouldn't do I/O at all in them!
					// This should be documented.
					var result = self["beforeSet_" + k].call(self, x, _fields);
					if (result !== undefined) {
						x = result;
					}
				}
				_fieldsModified[k] = true;
				_fields[k] = x;
			};
		})(k, this));
		if (fields && (k in fields)) {
			this.fields[k] = fields[k];
		}
	}
	if ((_retrieved = !!retrieved)) {
		_fieldsModified = {};
	}

	/**
	 * Whether this Db_Row was retrieved or not.
	 * @property retrieved
	 * @type boolean
	 */
	this.__defineGetter__('retrieved', function () {
		return _retrieved;
	});
	
	/**
	 * Whether this Db_Row was retrieved or not.
	 * @property retrieved
	 * @type boolean
	 */
	this.__defineGetter__('pkValue', function () {
		return _pkValue;
	});

	_pkValue = calculatePKValue() || {};

	(function runSetUp(self) {
		if (self.__proto__) {
			runSetUp(self.__proto__);
		}
		if (self.setUp && (typeof self.setUp === "function")) {
			self.setUp.call(self);
		}
	})(this);
	
	/**
	 * Saves the row in the database.
	 *
	 * If the row was retrieved from the database, issues an UPDATE.
	 * If the row was created from scratch, then issue an INSERT.
	 * If object has methods beforeSave, beforeSaveExecute or afterSaveExecute they are triggered in
	 * appropriate time.
	 * @method save
	 * @param [onDuplicateKeyUpdate=false] {boolean} If MySQL is being used, you can set this to TRUE
	 *  to add an ON DUPLICATE KEY UPDATE clause to the INSERT statement
	 * @param [commit=false] {boolean} If this is TRUE, then the current transaction is committed right after the save.
	 *  Use this only if you started a transaction before.
	 * @param [callback=null] {function} This function is called when the queries have all completed.
	 *  It is passed the one optional argument:
	 *  errors: an Object. If there were any errors, it will be passed error object as returned from query.execute
	 *  If successful, it will be passed nothing.
	 */
	this.save = function (onDuplicateKeyUpdate /* = false */, commit /* = false */, callback) {

		var self = this, _continue = true;
		var rowClass = Q.require( this.className.split('_').join('/') );

		if (typeof onDuplicateKeyUpdate === 'function') {
			callback = onDuplicateKeyUpdate;
			onDuplicateKeyUpdate = commit = false;
		} else if (typeof commit === 'function') {
			callback = commit;
			commit = false;
		} else if (typeof callback !== 'function') {
			callback = function (err) {
				if (typeof err !== "undefined") {
					util.log("Db.Row: ERROR while saving " + self.className);
					util.log(err);
					util.log("Primary key: ", calculatePKValue());
				}
			};
		}

		if (this.className === "Row")
			throw new Error("If you're going to save, please extend Db.Row.");

		var modifiedFields = {}, key;
		for (key in _fields) {
			if (_fieldsModified[key]) {
				modifiedFields[key] = _fields[key];
			}
		}

		/**
		 * Optional. If defined the method is called before taking actions to save row.
		 * It can be used synchronously and can ignore callback but must return
		 * `modifiedFields` object. If used asyncronously shall pass this object
		 * to callback
		 *
		 * **NOTE:** *if this method is defined but do not return result and do not call callback,
		 * the `save()` method fails silently without making any changes in the database!!!*
		 * @method beforeSave
		 * @param modifiedFields {object}
		 * @param [callback=null] {function} This function is called when hook completes. Returns `error` -
		 *	error object if any and `modifiedFields` as parameters.
		 */
		if (!_split && typeof this.beforeSave === "function") { // skip beforeSave when on _split is defined
			try {
				modifiedFields = this.beforeSave(modifiedFields, function (error, modifiedFields) {
					if (error) callback && callback.call(self, error);
					else _do_save(modifiedFields);
				});
			} catch (error) {
				callback && callback.call(self, error);
				return;
			}
		}
		if (modifiedFields) _do_save(modifiedFields);

		function _do_save(modifiedFields) {
			if (!modifiedFields) {
				callback && callback.call(self, new Error(this.className+".beforeSave callback cancelled save")); // nothing saved
				return;
			}
			if (typeof modifiedFields !== "object")
				throw new Error(this.className + ".beforeSave() must return the array of (modified) fields to save!");

			var db, query, _inserting;
			if (!(db = self.db()))
				throw new Error("The database was not specified!");

			if (_retrieved) {
				// update the table
				query = rowClass.UPDATE().set(modifiedFields).where(_pkValue);
				_inserting = false;
			} else {
				// insert new row
				query = rowClass.INSERT(modifiedFields);
				if (onDuplicateKeyUpdate) 
					query.onDuplicateKeyUpdate(modifiedFields);
				_inserting = true;
			}

			function _do_callbacks(error, lastId) {
				if (error) callback && callback.call(self, error);
				else {
					// We assume that autoincrement field is the single primary key
					if (_inserting && _primaryKey.length === 1 && lastId)
						_fields[pk[0]] = lastId;
					_pkValue = calculatePKValue() || {};
					_fieldsModified = {};
					_retrieved = true;
					callback && callback.call(self);
				}
				query = null;
			}

			function _execute() {
				query = this;
				if (commit) query.commit();
				query.execute(function (error, lastId) {
					if (typeof self.afterSaveExecute === "function") {
						// trigger Db/Row/this.className/saveExecute after event.// NOTE: this is synchronous
						query.resume = _do_callbacks;
						if (!self.afterSaveExecute(query, error, lastId)) {
							_do_callbacks(error, lastId);
							// NOTE: this is synchronous
							// to use it the async way return *true* and use query.resume(error, result) to continue
						}
					} else {
						_do_callbacks(error, lastId);
					}
				}, {indexes: _split});
			}
			// trigger Db/Row/this.className/saveExecute before event. // NOTE: this is synchronous
			if (typeof this.beforeSaveExecute === "function") {
				query.resume = _execute;
				query = (_continue = !!this.beforeSaveExecute(query, modifiedFields)) || query; // NOTE: this is synchronous
												// to use it async way return *false* and use query.resume() to continue
												// or handle callbacks in some creative way
			}
			if (_continue) _execute.apply(query);
		}
	};
	
	/**
	 * Retrieves the row in the database. Object state does not change.
	 * If object has methods beforeRetrieve, beforeRetrieveExecute or afterRetrieveExecute they are triggered in
	 * appropriate time.
	 * @method retrieve
	 * @param [fields='*'] {string} The fields to retrieve and set in the Db_Row.
	 *  This gets used if we make a query to the database.
	 * @param [use_index=false] {boolean} If true, the primary key is used in searching.
	 *  An exception is thrown when some fields of the primary key are not specified
	 * @param [modifyQuery=false] {boolean} If true, returns a Db.Query object that can be modified, rather than
	 *  the result. You can call more methods, like limit, offset, where, orderBy,
	 *  and so forth, on that Db.Query. After you have modified it sufficiently,
	 *  get the ultimate result of this function, by calling the resume() method on
	 *  the Db.Query object (via the chainable interface).
	 * @param [callback=null] {function} This function is called when all queries have completed
	 *  It is passed the arguments:
	 *
	 * * errors: an Object. If there were any errors, it will be passed error object as returned from query.execute
	 *  If there were no errors, it will be passed null
	 * * result: an array of rows retrieved. If error occured it will be passed nothing
	 */
	this.retrieve = function (fields /* '*' */, use_index /* false */, modifyQuery /* false */, callback) {

		var self = this, _continue = true;
		var rowClass = Q.require( this.className.split('_').join('/') );

		if (typeof fields === 'function') {
			callback = fields;
			fields = '*';
			use_index = false;
			modifyQuery = false;
		} else if (typeof use_index === 'function') {
			callback = use_index;
			use_index = false;
			modifyQuery = false;
		} else if (typeof modifyQuery === 'function') {
			callback = modifyQuery;
			modifyQuery = false;
		} else if (typeof callback !== 'function' && !modifyQuery)
			throw new Error("Callback for retrieve method was not specified for " + this.className + ".");

		if (this.className === "Row")
			throw new Error("If you're going to save, please extend Db.Row.");
		
		var primaryKeyValue = calculatePKValue();
		var search_criteria = {};
		
		if (use_index === true) {
			if (!primaryKeyValue)
				throw new Error("Fields of the primary key were not specified for " + this.className + ".");
			// Use the primary key value as the search criteria
			search_criteria = primaryKeyValue;
		} else {
			// Use the modified fields as the search criteria.
			search_criteria = _fields;
			// If no fields were modified on this object,
			// then this function will just return an empty array -- see below.
		}
		
		/**
		 * Optional. If defined the method is called before taking actions to retrieve row.
		 * It can be used synchronously and can ignore callback but must return
		 * `search_criteria` object. If used asyncronously shall pass this object
		 * to callback
		 *
		 * **NOTE:** *if this method is defined but do not return result and do not call callback,
		 * the `retrieve()` method fails silently!!!*
		 * @method beforeRetrieve
		 * @param search_criteria {object}
		 * @param [callback=null] {function} This function is called when hook completes. Returns `error` -
		 *	error object if any and `search_criteria` as parameters.
		 */
		if (typeof this.beforeRetrieve === "function") {
			try {
				search_criteria = this.beforeRetrieve(search_criteria, function (error, search_criteria) {
					if (error) callback && callback.call(self, error);
					else return _do_retrieve(search_criteria);
				});
			} catch (error) {
				callback && callback.call(self, error);
				return;
			}
		}
		if (search_criteria) return _do_retrieve(search_criteria);

		function _do_retrieve(search_criteria) {
			if (!search_criteria) {
				callback && callback.call(self, new Error(this.className+".beforeRetrieve callback cancelled retrieve")); // nothing saved
				return;
			}
			if (typeof search_criteria !== "object")
				throw new Error(this.className + ".beforeRetrieve() must return the array of (modified) fields to save!");

			var db, query;
			if (!(db = self.db()))
				throw new Error("The database was not specified!");
			query = rowClass.SELECT(fields).where(search_criteria);

			function _do_callbacks(error, result) {
				var fetched = false;
				if (result[0]) {
					self.copyFromRow(result[0]);
					fetched = true;
				}
				if (error) {
					callback && callback.call(self, error);
				} else {
					callback && callback.call(self, null, result, fetched);
				}
				query = null;
			}

			function _execute() {
				// Now, execute the query!
				query = this;
				query.execute(function (error, result) {
					// trigger Db/Row/this.className/retrieveExecute after event.// NOTE: this is synchronous
					if (typeof self.afterRetrieveExecute === "function") {
						query.resume = _do_callbacks;
						if (!self.afterRetrieveExecute(query, error, result)) {
							_do_callbacks(error, result);
							// NOTE: This is synchronous.
							// To use it the async way return *true* and use query.resume(error, result) to continue
						}
					} else {
						_do_callbacks(error, result);
					}
				}, {indexes: _split});
			}

			function _resume(cback) {
				// callback can be defined either at .retrieve(callback) call or
				// as argument to .resume(callback)
				// so syntax obj.retrieve('*', false, true).begin().resume(callback)
				// or obj.retrieve('*', false, true, callback).begin().resume()
				// are both valid
				if (modifyQuery && typeof callback !== "function") {
					if (typeof cback !== "function") {
						throw new Error("At least one callback shall be defined for "+self.className+".retrieve()!");
					}
					callback = cback;
				}
				query = this;
				// trigger Db/Row/this.className/retrieveExecute before event. // NOTE: this is synchronous
				if (typeof self.beforeRetrieveExecute === "function") {
					query.resume = _execute;
					query = (_continue = !!self.beforeRetrieveExecute(query, search_criteria)) || query;
					// NOTE: this is synchronous.
					// To use it the async way, return *false* and use query.resume() to continue
				}
				if (_continue && query) {
					_execute.apply(query);
				} else {
					util.log(self.className + ': query is empty!');
				}
			}
			// Modify the query if necessary
			if (modifyQuery) {
				query.resume = _resume;
				return query;
			} else {
				_resume.apply(query);
			}
		}
	};

	/**
	 * Deletes the rows in the database.
	 * If object has methods beforeRemove, beforeRemoveExecute or afterRemoveExecute they are triggered in
	 * appropriate time.
	 * @method remove
	 * @param [search_criteria=null] {string|object} You can provide custom search criteria here, such as `&#123;"tag.name LIKE ": this.name&#125;`
	 *  If this is left null, and this Db_Row was retrieved, then the db rows corresponding
	 *  to the primary key are deleted.
	 *  But if it wasn't retrieved, then the modified fields are used as the search criteria.
	 * @param [use_index=false] {boolean} If true, the primary key is used in searching for rows to delete.
	 *  An exception is thrown when some fields of the primary key are not specified
	 * @param [callback=null] {function} This function is called when all queries have completed.
	 *  It is passed the arguments:
	 *
	 * * errors: an Object. If there were any errors, it will be passed error object as returned from query.execute
	 *    otherwise passed null.
	 * * count: an Integer the number of rows deleted. If there were any errors, it will be passed nothing
	 */
	this.remove = function (search_criteria /* null */, use_index /* false */, callback) {

		var self = this, _continue = true;
		var rowClass = Q.require( this.className.split('_').join('/') );

		if (typeof search_criteria === 'function') {
			callback = search_criteria;
			search_criteria = null;
			use_index = false;
		} else if (typeof use_index === 'function') {
			callback = use_index;
			use_index = false;
		} else if (typeof callback !== 'function') {
			callback = function (res, err) {
				if (typeof err !== "undefined") {
					util.log("ERROR while removing " + self.className + "!");
					util.log("Primary key: ", primaryKeyValue);
				}
			};
		}

		if (this.className === "Row")
			throw new Error("If you're going to save, please extend Db.Row.");

		var primaryKeyValue = calculatePKValue();
		// Check if we have specified all the primary key fields,
		if (use_index) {
			if (!primaryKeyValue)
				throw new Error("Fields of the primary key were not specified for " + this.className + ".");
			search_criteria = primaryKeyValue;
		}
		// If search criteria are not specified, try to compute them.
		if (!search_criteria) {
			if (_retrieved) {
				// use primary key
				search_criteria = primaryKeyValue;
			} else {
				// use modified fields
				search_criteria = _fields;
			}
		}

		/**
		 * Optional. If defined the method is called before taking actions to remove row.
		 * It can be used synchronously and can ignore callback but must return
		 * `search_criteria` object. If used asyncronously shall pass this object
		 * to callback
		 *
		 * **NOTE:** *if this method is defined but do not return result and do not call callback,
		 * the `remove()` method fails silently without changing database!!!*
		 * @method beforeRemove
		 * @param search_criteria {object}
		 * @param [callback=null] {function} This function is called when hook completes. Returns `error` -
		 *	error object if any and `search_criteria` as parameters.
		 */
		if (typeof this.beforeRemove === "function") {
			try {
				search_criteria = this.beforeRemove(search_criteria, function (error, search_criteria) {
					if (error) callback && callback.call(self, error);
					else _do_remove(search_criteria);
				});
			} catch (error) {
				callback && callback.call(self, error);
				return;
			}
		}
		if (search_criteria) _do_remove(search_criteria);

		function _do_remove(search_criteria) {
			if (!search_criteria) {
				callback && callback.call(self, new Error(this.className+".beforeRemove callback cancelled remove")); // nothing saved
				return;
			}
			var db, query;
			if (!(db = self.db()))
				throw new Error("The database was not specified!");
			query = rowClass.DELETE().where(search_criteria);

			function _do_callbacks(error, result) {
				if (error) callback && callback.call(self, error);
				else {
					_fields = {};
					_retrieved = false;
					_pkValue = {};
					_fieldsModified = {};
					callback && callback.call(self, null, result);
				}
				query = null;
			}

			function _execute() {
			// Now, execute the query!
				query = this;
				query.execute(function (error, result) {
					// trigger Db/Row/this.className/removeExecuteExecute after event.// NOTE: this is synchronous
					if (typeof self.afterRemoveExecute === "function") {
						query.resume = _do_callbacks;
						if (!self.afterRemoveExecute(query, error, result))
							_do_callbacks(error, result);	// NOTE: this is synchronous
												// to use it async way return *true* and use query.resume(error, result) to continue
					} else _do_callbacks(error, result);
				}, {indexes: _split});
			}

			// trigger Db/Row/this.className/removeExecute before event. // NOTE: this is synchronous
			if (typeof this.beforeRemoveExecute === "function") {
				query.resume = _execute;
				query = (_continue = !!this.beforeRemoveExecute(query, search_criteria)) || query; // NOTE: this is synchronous
												// to use it async way return *false* and use query.resume() to continue
												// or handle callbacks in some creative way
			}
			if (_continue) _execute.apply(query);
		}
	};

	/**
	 * Rolls back the transaction
	 * @method rollback
	 * @param [callback=null] {function} This function is called when rollback have completed.
	 *  It is passed the arguments:
	 *
	 * * errors: an Object. If there were any errors, it will be passed error object as returned from query.execute
	 *    otherwise passed null.
	 */
	this.rollback = function (callback) {
		var self = this;
		var rowClass = Q.require( this.className.split('_').join('/') );

		if (this.className === "Row")
			throw new Error("If you're going to save, please extend Db.Row.");

		var db, query, pk;
		if (!(db = self.db())) {
			throw new Error("The database was not specified!");
		}
		if (!(pk = calculatePKValue())) {
			pk = _fields;
		}
		query = db.rollback(pk).execute(callback);
	};

	function calculatePKValue() {
		var k, fname, res = {};
		for (k in _primaryKey) {
			fname = _primaryKey[k];
			if (typeof _fields[fname] === "undefined")
				return false;
			res[fname] = _fields[fname];
		}
		return Object.keys(res).length ? res : false;
	}
	
	/**
	 * Set up temporary config for shard split
	 * @method split
	 * @param index {object} Split shard index
	 *
	 * * 'indexes->connection' section of sharding config. Shall contain 'fields' and 'partition' fields
	 * * 'partition' field shall contain new points mapped to shards
	 * * 'shards' section of config shall be already filled with new shards config
	 */
	
	this.split = function (index) {
		_split = index;
		return this;
	};
	
	/**
	 * This function copies the members of another row,
	 * as well as the primary key, etc. and assigns it to this row.
	 * @method copyFromRow
	 * @param row {Db.Row} The source row. Be careful -- In this case, Db does not check 
	 *  whether the class of the Db_Row matches. It leaves things up to you.
	 * @return {Db_Row} returns this object, for chaining
	 */
	this.copyFromRow = function (row) {
		_retrieved = row.retrieved;
		for (var key in row.fields) {
			this.fields[key] = row.fields[key];
		}
		return this;
	};
}

/**
 * Get plain object representing the row
 * @method toArray
 */
Row.prototype.toArray = function () {
	var res = {};
	for (var field in this.fields) {
		if (this.fields[field] !== undefined) {
			res[field] = this.fields[field];
		}
	}
	return res;
};

Row.prototype.fillMagicFields = function () {
	var toFill = [];
	for (var i=0, l=toFill.length; i<l; ++i) {
		var f = toFill[i];
		if (!this.fields[f] || this.fields[f].expression === "CURRENT_TIMESTAMP") {
			toFill.push(f);
		}
	}
	if (!toFill.length) {
		return this;
	}
	this.db().getCurrentTimestamp(function (err, timestamp) {
		for (var i=0, l=toFill.length; i<l; ++i) {
			this.fields[toFill[i]] = timestamp;
		}
	});
	return this;
};

Row.prototype.className = "Db_Row";

module.exports = Row;
