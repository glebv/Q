<?php
/**
 * @module Metrics
 */
/**
 * Class representing 'Session' rows in the 'Metrics' database
 * You can create an object of this class either to
 * access its non-static methods, or to actually
 * represent a session row in the Metrics database.
 *
 * @class Metrics_Session
 * @extends Base_Metrics_Session
 */
class Metrics_Session extends Base_Metrics_Session
{
	/**
	 * The setUp() method is called the first time
	 * an object of this class is constructed.
	 * @method setUp
	 */
	function setUp()
	{
		parent::setUp();
	}

	/**
	 * Implements the __set_state method, so it can work with
	 * with var_export and be re-imported successfully.
	 * @method __set_state
	 * @param {array} $array
	 * @return {Metrics_Session} Class instance
	 */
	static function __set_state(array $array) {
		$result = new Metrics_Session();
		foreach($array as $k => $v)
			$result->$k = $v;
		return $result;
	}
};