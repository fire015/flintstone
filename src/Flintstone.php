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
     * Config class.
     *
     * @var Config
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param Database|string $database
     * @param Config|array $config
     */
    public function __construct($database, $config)
    {
        if (is_string($database)) {
            $database = new Database($database);
        }

        if (is_array($config)) {
            $config = new Config($config);
        }

        $this->setDatabase($database);
        $this->setConfig($config);
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
     * Get the config.
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set the config.
     *
     * @param Config $config
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
        $this->getDatabase()->setConfig($config);
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
        $this->validateKey($key);

        // Fetch the key from cache
        if ($cache = $this->getConfig()->getCache()) {
            if ($cache->contains($key)) {
                return $cache->get($key);
            }
        }

        // Fetch the key from database
        $filePointer = $this->getDatabase()->openFile(Database::FILE_READ);
        $data = false;

        foreach ($filePointer as $line) {
            $data = $this->getDataFromLine($line, $key);

            if ($data !== false) {
                $data = $this->decodeData($data);
                break;
            }
        }

        $this->getDatabase()->closeFile($filePointer);

        // Save the data to cache
        if ($cache && $data !== false) {
            $cache->set($key, $data);
        }

        return $data;
    }

    /**
     * Set a key in the database.
     *
     * @param string $key
     * @param mixed $data
     */
    public function set($key, $data)
    {
        $this->validateKey($key);
        $this->validateData($data);

        // If the key already exists we need to replace it
        if ($this->get($key) !== false) {
            $this->replace($key, $data);

            return;
        }

        // Write the key to the database
        $filePointer = $this->getDatabase()->openFile(Database::FILE_APPEND);
        $filePointer->fwrite($this->getLineString($key, $data));
        $this->getDatabase()->closeFile($filePointer);

        // Delete the key from cache
        if ($cache = $this->getConfig()->getCache()) {
            $cache->delete($key);
        }
    }

    /**
     * Delete a key from the database.
     *
     * @param string $key
     */
    public function delete($key)
    {
        $this->validateKey($key);

        if ($this->get($key) !== false) {
            $this->replace($key, false);
        }
    }

    /**
     * Flush the database.
     */
    public function flush()
    {
        $filePointer = $this->getDatabase()->openFile(Database::FILE_WRITE);
        $this->getDatabase()->closeFile($filePointer);

        // Flush the cache
        if ($cache = $this->getConfig()->getCache()) {
            $cache->flush();
        }
    }

    /**
     * Get all keys from the database.
     *
     * @return array
     */
    public function getKeys()
    {
        $keys = array();
        $filePointer = $this->getDatabase()->openFile(Database::FILE_READ);

        foreach ($filePointer as $line) {
            $keys[] = $this->getKeyFromLine($line);
        }

        $this->getDatabase()->closeFile($filePointer);

        return $keys;
    }

    /**
     * Get all data from the database.
     *
     * @return array
     */
    public function getAll()
    {
        $data = array();
        $filePointer = $this->getDatabase()->openFile(Database::FILE_READ);

        foreach ($filePointer as $line) {
            $pieces = $this->getLinePieces($line);
            $data[$pieces[0]] = $this->decodeData($pieces[1]);
        }

        $this->getDatabase()->closeFile($filePointer);

        return $data;
    }

    /**
     * Replace a key in the database.
     *
     * @param string $key
     * @param mixed $data
     */
    protected function replace($key, $data)
    {
        // Write a new database to a temporary file
        $tmp = $this->getDatabase()->openTempFile();
        $filePointer = $this->getDatabase()->openFile(Database::FILE_READ);

        foreach ($filePointer as $line) {
            $lineKey = $this->getKeyFromLine($line);

            if ($lineKey == $key) {
                if ($data !== false) {
                    $tmp->fwrite($this->getLineString($key, $data));
                }
            } else {
                $tmp->fwrite($line . "\n");
            }
        }

        $this->getDatabase()->closeFile($filePointer);
        $tmp->rewind();

        // Overwrite the database with the temporary file
        $filePointer = $this->getDatabase()->openFile(Database::FILE_WRITE);

        foreach ($tmp as $line) {
            $filePointer->fwrite($line);
        }

        $this->getDatabase()->closeFile($filePointer);
        $tmp = null;

        // Delete the key from cache
        if ($cache = $this->getConfig()->getCache()) {
            $cache->delete($key);
        }
    }

    /**
     * Validate the key.
     *
     * @param string $key
     *
     * @throws Exception
     */
    protected function validateKey($key)
    {
        if (empty($key) || !preg_match('/^[\w-]+$/', $key)) {
            throw new Exception('Invalid characters in key');
        }
    }

    /**
     * Check the data type is valid.
     *
     * @param mixed $data the data
     *
     * @throws Exception
     */
    protected function validateData($data)
    {
        if (!is_string($data) && !is_int($data) && !is_float($data) && !is_array($data)) {
            throw new Exception('Invalid data type');
        }
    }

    /**
     * Retrieve the pieces from a given line.
     *
     * @param string $line
     *
     * @return array
     */
    protected function getLinePieces($line)
    {
        return explode('=', $line, 2);
    }

    /**
     * Retrieve data from a given line for a specific key.
     *
     * @param string $line
     * @param string $key
     *
     * @return string|bool
     */
    protected function getDataFromLine($line, $key)
    {
        $pieces = $this->getLinePieces($line);

        return ($pieces[0] == $key) ? $pieces[1] : false;
    }

    /**
     * Retrieve key from a given line.
     *
     * @param string $line
     *
     * @return string
     */
    protected function getKeyFromLine($line)
    {
        $pieces = $this->getLinePieces($line);

        return $pieces[0];
    }

    /**
     * Get the line string to write.
     *
     * @param string $key
     * @param mixed $data
     *
     * @return string
     */
    protected function getLineString($key, $data)
    {
        return $key . '=' . $this->encodeData($data) . "\n";
    }

    /**
     * Decode a string into data.
     *
     * @param string $data
     *
     * @return mixed
     */
    protected function decodeData($data)
    {
        return $this->getConfig()->getFormatter()->decode($data);
    }

    /**
     * Encode data into a string.
     *
     * @param mixed $data
     *
     * @return string
     */
    protected function encodeData($data)
    {
        return $this->getConfig()->getFormatter()->encode($data);
    }
}
