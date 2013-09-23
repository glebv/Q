/**
 * Class representing app_user rows.
 *
 * This description should be revised and expanded.
 *
 * @module Users
 */
var Q = require('Q');
var Db = Q.require('Db');

/**
 * Class representing 'AppUser' rows in the 'Users' database
 * @namespace Users
 * @class AppUser
 * @extends Base.Users.AppUser
 * @constructor
 * @param fields {object} The fields values to initialize table row as
 * an associative array of `{column: value}` pairs
 */
function Users_AppUser (fields) {

	/**
	 * The setUp() method is called the first time
	 * an object of this class is constructed.
	 * @method setUp
	 */
	this.setUp = function () {
		// put any code here
	};

	// Run constructors of mixed in objects
	this.constructors.call(this, arguments);

}

Q.mixin(Users_AppUser, Q.require('Base/Users/AppUser'));

module.exports = Users_AppUser;