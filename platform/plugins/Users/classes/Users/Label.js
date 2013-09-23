/**
 * Class representing label rows.
 *
 * This description should be revised and expanded.
 *
 * @module Users
 */
var Q = require('Q');
var Db = Q.require('Db');

/**
 * Class representing 'Label' rows in the 'Users' database
 * <br/>enables display and renaming of labels and their icons
 * @namespace Users
 * @class Label
 * @extends Base.Users.Label
 * @constructor
 * @param fields {object} The fields values to initialize table row as
 * an associative array of `{column: value}` pairs
 */
function Users_Label (fields) {

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

Q.mixin(Users_Label, Q.require('Base/Users/Label'));

module.exports = Users_Label;