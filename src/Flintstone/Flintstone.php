<?php

/**
 * Flintstone - A key/value database store using flat files for PHP
 * Copyright (c) 2014 Jason M
 */

namespace Flintstone;

/**
 * The Flintstone database loader
 */

class Flintstone
{
    /**
     * Flintstone version
     * @var string
     */
    const VERSION = '1.8';

    /**
     * Static instance
     * @var array
     */
    private static $instance = array();

    /**
     * Load a database
     *
     * @param string $database the database name
     * @param array  $options  an array of options
     *
     * @return FlintstoneDB class
     *
     * @throws FlintstoneException when database cannot be loaded
     */
    public static function load($database, array $options = array())
    {
        if (!array_key_exists($database, self::$instance)) {
            self::$instance[$database] = new FlintstoneDB($database, $options);
        }

        return self::$instance[$database];
    }

    /**
     * Unload a database
     *
     * @param string $database the database name
     *
     */
    public static function unload($database)
    {
        unset(self::$instance[$database]);
    }
}
