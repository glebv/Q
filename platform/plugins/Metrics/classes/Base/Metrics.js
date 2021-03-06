/**
 * Autogenerated base class for the Metrics model.
 * 
 * Don't change this file, since it can be overwritten.
 * Instead, change the Metrics.js file.
 *
 * @module Metrics
 */
var Q = require('Q');
var Db = Q.require('Db');

/**
 * Base class for the Metrics model
 * @namespace Base
 * @class Metrics
 * @static
 */
function Base () {
	return this;
}
 
module.exports = Base;
	
/**
 * The list of model classes
 * @property tableClasses
 * @type array
 */
Base.tableClasses = [
	"Metrics_Domain",
	"Metrics_HostnameSession",
	"Metrics_Publisher",
	"Metrics_Session",
	"Metrics_Share",
	"Metrics_Visit"
];

/**
 * This method uses Db.connect() to establish a connection to database using information stored in the configuration.
 * If the connection to Db object has already been made, it returns this Db object.
 * @method db
 * @return {Db} The database connection
 */
Base.db = function () {
	return Db.connect('Metrics');
};

/**
 * The connection name for the class
 * @method connectionName
 * @return {string} The name of the connection
 */
Base.connectionName = function() {
	return 'Metrics';
};

/**
 * Link to Metrics.Domain model
 * @property Domain
 * @type Metrics.Domain
 */
Base.Domain = Q.require('Metrics/Domain');
/**
 * Link to Metrics.HostnameSession model
 * @property HostnameSession
 * @type Metrics.HostnameSession
 */
Base.HostnameSession = Q.require('Metrics/HostnameSession');
/**
 * Link to Metrics.Publisher model
 * @property Publisher
 * @type Metrics.Publisher
 */
Base.Publisher = Q.require('Metrics/Publisher');
/**
 * Link to Metrics.Session model
 * @property Session
 * @type Metrics.Session
 */
Base.Session = Q.require('Metrics/Session');
/**
 * Link to Metrics.Share model
 * @property Share
 * @type Metrics.Share
 */
Base.Share = Q.require('Metrics/Share');
/**
 * Link to Metrics.Visit model
 * @property Visit
 * @type Metrics.Visit
 */
Base.Visit = Q.require('Metrics/Visit');