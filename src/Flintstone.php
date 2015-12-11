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
	 * Database class
	 *
	 * @var Database
	 */
	protected $database;

	/**
	 * Config class
	 *
	 * @var Config
	 */
	protected $config;

	/**
	 * Constructor
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
	 * Get the database
	 *
	 * @return Database
	 */
	public function getDatabase()
	{
		return $this->database;
	}

	/**
	 * Set the database
	 *
	 * @param Database $database
	 */
	public function setDatabase(Database $database)
	{
		$this->database = $database;
	}

	/**
	 * Get the config
	 *
	 * @return Config
	 */
	public function getConfig()
	{
		return $this->config;
	}

	/**
	 * Set the config
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
				return $cache->fetch($key);
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
			$cache->save($key, $data);
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
	}

    /**
     * Replace a key in the database.
     *
     * @param string $key
     * @param mixed $data
     */
    protected function replace($key, $data)
	{
		// Open a temporary file for writing and the database for reading
		$tmp = $this->getDatabase()->openTempFile();
		$filePointer = $this->getDatabase()->openFile(Database::FILE_READ);

		foreach ($filePointer as $line) {
			$lineKey = $this->getKeyFromLine($line);

			if ($lineKey == $key) {
				$tmp->fwrite($this->getLineString($key, $data));
			}
			else {
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
     * @throws \Flintstone\FlintstoneException when data is invalid
     */
    private function validateData($data)
    {
        if (!is_string($data) && !is_int($data) && !is_float($data) && !is_array($data)) {
            throw new Exception('Invalid data type');
        }
    }

    /**
     * Retrieve data from a given line.
     *
     * @param string $line
     * @param string $key
     *
     * @return string|boolean
     */
    protected function getDataFromLine($line, $key)
    {
        $pieces = explode('=', $line, 2);

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
        $pieces = explode('=', $line, 2);

        return $pieces[0];
    }

    /**
     * Get the line string to write
     *
     * @param string $key
     * @param mixed $data
     *
     * @return string
     */
    protected function getLineString($key, $data)
    {
        return $key . "=" . $this->encodeData($data) . "\n";
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