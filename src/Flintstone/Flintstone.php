<?php

/**
 * Flintstone - A key/value database store using flat files for PHP
 * Copyright (c) 2014 Jason M
 */

namespace Flintstone;

/**
 * The Flintstone database loader
 */

class Flintstone {

	/**
	 * Flintstone version
	 * @access public
	 * @var string
	 */
	const VERSION = '1.5';

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
	 * @return FlintstoneDB class
	 * @throws FlintstoneException when database cannot be loaded
	 */
	public static function load($database, $options = array()) {
		if (!array_key_exists($database, self::$instance)) {
			self::$instance[$database] = new FlintstoneDB($database, $options);
		}

		return self::$instance[$database];
	}

	/**
	 * Unload a database
	 * @param string $database the database name
	 * @return void
	 */
	public static function unload($database) {
		unset(self::$instance[$database]);
	}
}