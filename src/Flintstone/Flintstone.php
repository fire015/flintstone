<?php

namespace Flintsone;

/**
 * Class Flintstone
 *
 * @package Flintsone
 */
class Flintstone {

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
            self::$instance[$database] = new Db($database, $options);
        }

        return self::$instance[$database];
    }
}