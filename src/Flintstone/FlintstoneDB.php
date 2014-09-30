<?php

/**
 * Flintstone - A key/value database store using flat files for PHP
 * Copyright (c) 2014 Jason M
 */

namespace Flintstone;

/**
 * The Flintstone database specific class
 */

class FlintstoneDB {

	/**
	 * File read flag
	 * @access public
	 * @var integer
	 */
	const FILE_READ = 1;

	/**
	 * File write flag
	 * @access public
	 * @var integer
	 */
	const FILE_WRITE = 2;

	/**
	 * File append flag
	 * @access public
	 * @var integer
	 */
	const FILE_APPEND = 3;

	/**
	 * Database data
	 * @access private
	 * @var array
	 */
	private $data = array();

	/**
	 * Flintstone options:
	 *
	 * - string		$dir				the directory to the database files
	 * - string		$ext				the database file extension
	 * - boolean	$gzip				use gzip to compress database
	 * - boolean	$cache				store get() results in memory
	 * - integer	$swap_memory_limit	write out each line to a temporary file and swap if database is larger than limit (0 to always do this)
	 *
	 * @access private
	 * @var array
	 */
	private $options = array(
		'dir' => '',
		'ext' => '.dat',
		'gzip' => false,
		'cache' => true,
		'swap_memory_limit' => 1048576
	);

	/**
	 * Flintstone constructor
	 * @param string $database the database name
	 * @param array $options an array of options
	 * @throws FlintstoneException when database cannot be loaded
	 * @return void
	 */
	public function __construct($database, $options) {

		// Check valid characters in database name
		if (!preg_match("/^[A-Za-z0-9_\-]+$/", $database)) {
			throw new FlintstoneException('Invalid characters in database name');
		}

		// Set options
		if (!empty($options)) {
			$this->options = array_merge($this->options, $options);
		}

		// Setup database
		$this->setupDatabase($database);
	}

	/**
	 * Setup the database and perform pre-flight checks
	 * @param string $database the database name
	 * @throws FlintstoneException when database cannot be loaded
	 * @return void
	 */
	private function setupDatabase($database) {

		// Check database directory
		$dir = rtrim($this->options['dir'], '/\\') . DIRECTORY_SEPARATOR;

		if (!is_dir($dir)) {
			throw new FlintstoneException($dir . ' is not a valid directory');
		}

		// Set data
		$ext = $this->options['ext'];
		if (substr($ext, 0, 1) !== ".") $ext = "." . $ext;
		if ($this->options['gzip'] === true && substr($ext, -3) !== ".gz") $ext .= ".gz";
		$this->data['file'] = $dir . $database . $ext;
		$this->data['file_tmp'] = $dir . $database . "_tmp" . $ext;
		$this->data['cache'] = array();

		// Create database file
		if (!file_exists($this->data['file'])) {
			$this->createFile($this->data['file']);
		}

		// Check file is readable
		if (!is_readable($this->data['file'])) {
			throw new FlintstoneException('Could not read file ' . $this->data['file']);
		}

		// Check file is writable
		if (!is_writable($this->data['file'])) {
			throw new FlintstoneException('Could not write to file ' . $this->data['file']);
		}
	}

	/**
	 * Create a database file
	 * @param string $file the file path
	 * @throws FlintstoneException when database cannot be created
	 * @return void
	 */
	private function createFile($file) {
		if (!@touch($file)) {
			throw new FlintstoneException('Could not create file ' . $file);
		}
	}

	/**
	 * Open the database file
	 * @param string $file the file path
	 * @param integer $mode the file mode
	 * @throws FlintstoneException when database cannot be opened or locked
	 * @return object file pointer
	 */
	private function openFile($file, $mode) {

		// Indicate the file is compressed
		if ($this->options['gzip'] === true) {
			$file = 'compress.zlib://' . $file;
		}

		// Open in read, write or append mode
		if ($mode == self::FILE_READ) {
			$mode = 'rb';
			$operation = LOCK_SH;
		}
		elseif ($mode == self::FILE_WRITE) {
			$mode = 'wb';
			$operation = LOCK_EX;
		}
		else {
			$mode = 'ab';
			$operation = LOCK_EX;
		}

		$fp = @fopen($file, $mode);

		if (!$fp) {
			throw new FlintstoneException('Could not open file ' . $file);
		}

		if (($this->options['gzip'] === false) && (!@flock($fp, $operation))) {
			throw new FlintstoneException('Could not lock file ' . $file);
		}

		return $fp;
	}

	/**
	 * Close the database file
	 * @param object $fp the file pointer
	 * @throws FlintstoneException when database cannot be unlocked
	 * @return void
	 */
	private function closeFile($fp) {
		if (($this->options['gzip'] === false) && (!@flock($fp, LOCK_UN))) {
			throw new FlintstoneException('Could not unlock file');
		}

		@fclose($fp);
		unset($fp);
	}

	/**
	 * Get a key from the database
	 * @param string $key the key
	 * @return mixed the data
	 */
	private function getKey($key) {

		$data = false;

		// Look in cache for key
		if ($this->options['cache'] === true && array_key_exists($key, $this->data['cache'])) {
			return $this->data['cache'][$key];
		}

		// Open file
		$fp = $this->openFile($this->data['file'], self::FILE_READ);

		// Loop through each line of file
		while (($line = fgets($fp)) !== false) {

			// Remove new line character from end
			$line = rtrim($line);

			// Split up seperator
			$pieces = explode("=", $line);

			// Match found
			if ($pieces[0] == $key) {

				// Put remaining pieces back together
				if (count($pieces) > 2) {
					array_shift($pieces);
					$data = implode("=", $pieces);
				}
				else {
					$data = $pieces[1];
				}

				// Unserialize data
				$data = unserialize($data);

				// Preserve new lines
				$data = $this->preserveLines($data, true);

				// Save to cache
				if ($this->options['cache'] === true) {
					$this->data['cache'][$key] = $data;
				}

				break;
			}
		}

		// Close file
		$this->closeFile($fp);

		return $data;
	}

	/**
	 * Replace a key in the database
	 * @param string $key the key
	 * @param mixed $data the data to store, or false to delete
	 * @throws FlintstoneException when database cannot be written to
	 * @return boolean successful replace
	 */
	private function replaceKey($key, $data) {

		// Use memory or swap?
		$swap = true;

		if ($this->options['swap_memory_limit'] > 0) {
			clearstatcache(true, $this->data['file']);

			if (filesize($this->data['file']) <= $this->options['swap_memory_limit']) {
				$swap = false;
				$contents = "";
			}
		}

		if ($data !== false) {

			// Create a copy of data to push into cache
			if ($this->options['cache'] === true) {
				$origData = $data;
			}

			// Preserve new lines
			$data = $this->preserveLines($data, false);

			// Serialize data
			$data = serialize($data);
		}

		// Open tmp file
		if ($swap) {
			$tp = $this->openFile($this->data['file_tmp'], self::FILE_APPEND);
		}

		// Open file
		$fp = $this->openFile($this->data['file'], self::FILE_READ);

		// Loop through each line of file
		while (($line = fgets($fp)) !== false) {

			// Split up seperator
			$pieces = explode("=", $line);

			// Match found
			if ($pieces[0] == $key) {

				// Skip line to delete
				if ($data === false) continue;

				// New line
				$line = $key . "=" . $data . "\n";

				// Save to cache
				if ($this->options['cache'] === true) {
					$this->data['cache'][$key] = $origData;
				}
			}

			if ($swap) {

				// Write line to tmp file
				if (@fwrite($tp, $line) === false) {
					throw new FlintstoneException('Could not write to file ' . $this->data['file_tmp']);
				}
			}
			else {

				// Save line to memory
				$contents .= $line;
			}
		}

		// Close file
		$this->closeFile($fp);

		if ($swap) {

			// Close tmp file
			$this->closeFile($tp);

			// Rename tmp file
			if (!@rename($this->data['file_tmp'], $this->data['file'])) {
				throw new FlintstoneException('Could not rename file ' . $this->data['file_tmp']);
			}
		}
		else {

			// Open file
			$fp = $this->openFile($this->data['file'], self::FILE_WRITE);

			// Write contents
			if (@fwrite($fp, $contents) === false) {
				throw new FlintstoneException('Could not write to file ' . $this->data['file']);
			}

			// Close file
			$this->closeFile($fp);

			// Free up memory
			unset($contents);
		}

		return true;
	}

	/**
	 * Set a key to store in the database
	 * @param string $key the key
	 * @param mixed $data the data to store
	 * @throws FlintstoneException when database cannot be written to
	 * @return boolean
	 */
	private function setKey($key, $data) {

		// Replace existing key?
		if ($this->getKey($key) !== false) {
			return $this->replaceKey($key, $data);
		}

		// Save to cache
		if ($this->options['cache'] === true) {
			$this->data['cache'][$key] = $data;
		}

		// Preserve new lines
		$data = $this->preserveLines($data, false);

		// Serialize data
		$data = serialize($data);

		// Set line, we don't use PHP_EOL to keep it cross-platform compatible
		$line = $key . "=" . $data . "\n";

		// Open file
		$fp = $this->openFile($this->data['file'], self::FILE_APPEND);

		// Write line
		if (@fwrite($fp, $line) === false) {
			throw new FlintstoneException('Could not write to file ' . $this->data['file']);
		}

		// Close file
		$this->closeFile($fp);

		return true;
	}

	/**
	 * Delete a key from the database
	 * @param string $key the key
	 * @return boolean successful delete
	 */
	private function deleteKey($key) {

		// Find key
		if ($this->getKey($key) !== false) {

			// Replace existing key
			if ($this->replaceKey($key, false)) {

				// Remove from cache
				if ($this->options['cache'] === true && array_key_exists($key, $this->data['cache'])) {
					unset($this->data['cache'][$key]);
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Flush the database
	 * @return boolean successful flush
	 */
	private function flushDatabase() {

		// Open file
		$fp = $this->openFile($this->data['file'], self::FILE_WRITE);

		// Close file
		$this->closeFile($fp);

		// Empty cache
		if ($this->options['cache'] === true) {
			$this->data['cache'] = array();
		}

		return true;
	}

	/**
	 * Get all keys from the database
	 * @return array of keys
	 */
	private function getAllKeys() {

		$keys = array();

		// Open file
		$fp = $this->openFile($this->data['file'], self::FILE_READ);

		// Loop through each line of file
		while (($line = fgets($fp)) !== false) {

			// Split up seperator
			$pieces = explode("=", $line);

			// Add key to array
			$keys[] = $pieces[0];
		}

		// Close file
		$this->closeFile($fp);

		return $keys;
	}

	/**
	 * Preserve new lines, recursive function
	 * @param mixed $data the data
	 * @param boolean $reverse to reverse the replacement order
	 * @return mixed the data
	 */
	private function preserveLines($data, $reverse) {

		// Which way round are we preserving?
		if ($reverse) {
			$from = array("\\n", "\\r");
			$to = array("\n", "\r");
		}
		else {
			$from = array("\n", "\r");
			$to = array("\\n", "\\r");
		}

		if (is_string($data)) {
			$data = str_replace($from, $to, $data);
		}
		elseif (is_array($data)) {
			foreach ($data as $key => $value) {
				$data[$key] = $this->preserveLines($value, $reverse);
			}
		}

		return $data;
	}

	/**
	 * Check the database has been loaded and valid key
	 * @param string $key the key
	 * @throws FlintstoneException when key is invalid
	 * @return boolean
	 */
	private function isValidKey($key) {
		if (!is_string($key)) {
			throw new FlintstoneException('Key must be a string');
		}

		if (strlen($key) > 1024) {
			throw new FlintstoneException('Maximum key length is 1024 characters');
		}

		if (strpos($key, '=') !== false) {
			throw new FlintstoneException('Key may not contain the equals character');
		}

		return true;
	}

	/**
	 * Check the data type is valid
	 * @param mixed $data the data
	 * @throws FlintstoneException when data is invalid
	 * @return boolean
	 */
	private function isValidData($data) {
		if (!is_string($data) && !is_int($data) && !is_float($data) && !is_array($data)) {
			throw new FlintstoneException('Invalid data type');
		}

		return true;
	}

	/**
	 * Get a key from the database
	 * @param string $key the key
	 * @throws FlintstoneException when key is invalid
	 * @return mixed the data
	 */
	public function get($key) {
		if ($this->isValidKey($key)) {
			return $this->getKey($key);
		}
	}

	/**
	 * Set a key to store in the database
	 * @param string $key the key
	 * @param mixed $data the data to store
	 * @throws FlintstoneException when key or data is invalid
	 * @return boolean successful set
	 */
	public function set($key, $data) {
		if ($this->isValidKey($key) && $this->isValidData($data)) {
			return $this->setKey($key, $data);
		}
	}

	/**
	 * Replace a key in the database
	 * @param string $key the key
	 * @param mixed $data the data to store
	 * @throws FlintstoneException when key or data is invalid
	 * @return boolean successful replace
	 */
	public function replace($key, $data) {
		if ($this->isValidKey($key) && $this->isValidData($data)) {
			return $this->replaceKey($key, $data);
		}
	}

	/**
	 * Delete a key from the database
	 * @param string $key the key
	 * @throws FlintstoneException when key is invalid
	 * @return boolean successful delete
	 */
	public function delete($key) {
		if ($this->isValidKey($key)) {
			return $this->deleteKey($key);
		}
	}

	/**
	 * Flush the database
	 * @throws FlintstoneException when something goes wrong
	 * @return boolean successful flush
	 */
	public function flush() {
		return $this->flushDatabase();
	}

	/**
	 * Get all keys from the database
	 * @throws FlintstoneException when something goes wrong
	 * @return array list of keys
	 */
	public function getKeys() {
		return $this->getAllKeys();
	}

	/**
	 * Get the database file
	 * @return string file path
	 */
	public function getFile() {
		return $this->data['file'];
	}
}
