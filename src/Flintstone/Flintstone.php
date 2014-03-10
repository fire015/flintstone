<?php

/**
 * Flintstone - A key/value database store using flat files for PHP
 * Copyright (c) 2014 Jason M
 */

namespace Flintstone;

class Flintstone {

	/**
	 * Flintstone version
	 * @access public
	 * @var string
	 */
	const VERSION = '2.0.0';

	/**
	 * Static instance
	 * @access private
	 * @var array
	 */
	private static $instance = array();

	/**
	 * Load a database
	 * @param string $database the database name
	 * @param array $options an array of options
	 * @return object the FlintstoneDB class
	 */
	public static function load($database, $options = array()) {
		if (!array_key_exists($database, self::$instance)) {
			self::$instance[$database] = new FlintstoneDB($database, $options);
		}

		return self::$instance[$database];
	}
}