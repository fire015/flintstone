<?php

/*
 * This file is part of the Flintstone package.
 *
 * (c) Jason M <emailfire@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Flintstone;

class Flintstone
{
    /**
     * Flintstone version.
     *
     * @var string
     */
    const VERSION = '2.0';

    /**
     * Database class.
     *
     * @var Database
     */
    protected $database;

    /**
     * Constructor.
     *
     * @param Database $database
     */
    public function __construct(Database $database)
    {
        $this->setDatabase($database);
    }

    /**
     * Load the database
     *
     * @param string $database
     * @param array $config
     *
     * @return Flintstone
     */
    public static function load($database, array $config = array())
    {
        return new static(new Database($database, new Config($config)));
    }

    /**
     * Get the database.
     *
     * @return Database
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Set the database.
     *
     * @param Database $database
     */
    public function setDatabase(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Get a key from the database.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        return $this->getDatabase()->get($key);
    }

    /**
     * Set a key in the database.
     *
     * @param string $key
     * @param mixed $data
     */
    public function set($key, $data)
    {
        $this->getDatabase()->set($key, $data);
    }

    /**
     * Delete a key from the database.
     *
     * @param string $key
     */
    public function delete($key)
    {
        $this->getDatabase()->delete($key);
    }

    /**
     * Flush the database.
     */
    public function flush()
    {
        $this->getDatabase()->flush();
    }

    /**
     * Get all keys from the database.
     *
     * @return array
     */
    public function getKeys()
    {
        return $this->getDatabase()->getKeys();
    }

    /**
     * Get all data from the database.
     *
     * @return array
     */
    public function getAll()
    {
        return $this->getDatabase()->getAll();
    }
}