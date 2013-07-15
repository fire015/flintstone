<?php

/**
 * Flintstone - A key/value database store using flat files for PHP
 * Copyright (c) 2011 XEWeb
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @link http://www.xeweb.net/flintstone/
 * @copyright 2011 XEWeb
 * @author Jason <emailfire@gmail.com>
 * @version 1.2
 * @package flintstone
 */

class FlintstoneTbl {

	/**
	 * Stores the name.
	 * @access private
	 * @var string
	 */
	private $name;

	/**
	 * Stores the file.
	 * @access private
	 * @var string
	 */
	private $file;

	/**
	 * Stores the temporary file.
	 * @access private
	 * @var string
	 */
	private $tmpFile;

	/**
	 * Stores the record keys.
	 * @access private
	 * @var array
	 */
	private $keys;

	/**
	 * Stores the cached data.
	 * @access private
	 * @var array
	 */
	private $cache;

	/**
	 * Stores the options.
	 * @access private
	 * @var array
	 */
	private $options;

	/**
	 * Stores if the file is loaded.
	 */
	private $loaded;


	/**
	 * Constructor to initialize a new table instance.
	 *
	 * @param string $name the table's name
	 * @param string $file the storage file
	 * @param string $tmpFile the temporary file
	 * @param array $options the options
	 */
	public function __construct($name, $file, $tmpFile, $options) {
		$this->name = $name;
		$this->file = $file;
		$this->tmpFile = $tmpFile;

		$this->keys = array();
		$this->cache = array();
		$this->options = array(
			'gzip' => false,
			'cache' => true,
			'swap_memory_limit' => 1048576
		);

		if (!empty($options)) $this->setOptions($options);

		$this->loaded = false;
		$this->load();
	}

	/**
	 * Returns if the cache is enabled.
	 *
	 * @return bool $doCache
	 */
	private function cacheEnabled() {
		return $this->options['cache'];
	}

	/**
	 * Deletes the key from the table.
	 * If the key doesn't exist,
	 * null is returned.
	 *
	 * @param string $key the key
	 * @return boolean successful delete
	 */
	public function delete($key) {
		return $this->deleteKey($key);
	}

	/**
	 * Deletes the key from the table.
	 * If the key doesn't exist,
	 * null is returned.
	 *
	 * @param string $key the key
	 * @return boolean successful delete
	 */
	private function deleteKey($key) {
		if ($this->exists($key)) {
			if ($this->isLoaded() && $this->replaceKey($key, false)) {
				// Remove from cache
				if ($this->cacheEnabled() && array_key_exists($key, $this->cache)) {
					unset($this->cache[$key]);
				}

				// Remove from key list
				$index = array_search($key, $this->keys);
				unset($this->keys[$index]);
				$this->keys = array_values($this->keys);

				return true;
			}

			return false;
		}

		return null;
	}

	/**
	 * Checks if the key exists.
	 *
	 * @param string $key the key to check
	 * @return bool key exists?
	 */
	public function exists($key) {
		return in_array($key, $this->keys);
	}

	/**
	 * Flush the database
	 *
	 * @return boolean successful flush
	 */
	public function flush() {
		// Open file to truncate (w mode)
		if (($fp = $this->openFile($this->file, "wb")) !== false) {

			// Close file
			@fclose($fp);

			// Empty cache
			if ($this->options['cache'] === true) {
				$this->['cache'] = array();
			}

			// Empty keys
			$this->keys = array();
		}
		else {
			throw new Exception('Could not open table ' . $this->name);
		}

		return true;
	}

	/**
	 * Returns the record for key.
	 * If there is no record for given key,
	 * null is returned.
	 *
	 * @see getKey()
	 *
	 * @param string $key the record's key
	 * @return mixed the record
	 */
	public function get($key) {
		return $this->getKey($key);
	}

	/**
	 * Returns the keys.
	 *
	 * @return array $keys
	 */
	private function getAllKeys() {
		return $this->keys;
	}

	/**
	 * Returns the record for key.
	 * If there is no record for given key,
	 * null is returned.
	 *
	 * @param string $key the record's key
	 * @return mixed the record
	 */
	private function getKey($key) {
		if ($this->exists($key)) {
			$data = false;

			// Look in cache for key
			if ($this->options['cache'] && array_key_exists($key, $this->cache)) {
				return $this->cache[$key];
			}

			// Open file
			if (($fp = $this->openFile($this->file, "rb")) !== false) {

				// Lock file
				@flock($fp, LOCK_SH);

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
						if ($this->options['cache']) {
							$this->cache[$key] = $data;
						}

						break;
					}
				}

				// Unlock and close file
				@flock($fp, LOCK_UN);
				@fclose($fp);
			}
			else {
				throw new Exception('Could not open table ' . $this->name);
			}

			return $data;
		}

		return null;
	}

	/**
	 * Returns the keys.
	 *
	 * @return array $keys the keys
	 */
	public function getKeys() {
		return $this->getAllKeys();
	}

	/**
	 * Returns the name.
	 *
	 * @return string $name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Checks if the table is empty.
	 *
	 * @return bool if has entries
	 */
	public function isEmpty() {
		return $this->size == 0;
	}

	/**
	 * Checks if the file is loaded.
	 *
	 * @return bool $loaded
	 */
	private function isLoaded() {
		return $this->loaded;
	}

	/**
	 * Checks if the data is valid.
	 * Only strings, ints, floats, and arrays can be stored in the table.
	 *
	 * @param mixed $data the data to check
	 * @return bool validation result
	 */
	private function isValidData($data) {
		return (is_string($data) || is_int($data) || is_float($data) || is_array($data));
	}

	/**
	 * Check the table has been loaded and valid key
	 * @param string $key the key
	 * @return boolean
	 */
	private function isValidKey($key) {
		// Check database loaded
		if ($this->file == null) {
			throw new Exception('Table has not been loaded');
		}

		// Check key length
		$len = strlen($key);

		if ($len < 1) {
			throw new Exception('No key has been set');
		}

		if ($len > 50) {
			throw new Exception('Maximum key length is 50 characters');
		}

		// Check valid characters in key
		if (!preg_match("/^([A-Za-z0-9_]+)$/", $key)) {
			throw new Exception('Invalid characters in key');
		}

		return true;
	}

	/**
	 * Loads the storage file.
	 *
	 * @return bool $loaded the success of loading
	 */
	private function load() {
		// Create table
		if (!file_exists($this->file())) {
			if (($fp = $this->openFile($this->file(), "wb")) !== false) {
				@fclose($fp);
				@chmod($this->file(), 0777);
				clearstatcache();
			} else {
				throw new Exception('Could not create table ' . $this->name);
			}
		}

		// Check file is readable
		if (!is_readable($this->getFile())) {
			throw new Exception('Could not read table ' . $this->name);
		}

		// Check file is writable
		if (!is_writable($this->getFile())) {
			throw new Exception('Could not write to table ' . $this->name);
		}

		return $this->loaded = true;
	}

	/**
	 * Opens the table file.

	 * @param string $file the file path
	 * @param string $mode the file mode
	 * @return object file pointer
	 */
	private function openFile($file, $mode) {
		if ($this->options['gzip'] === true) $file = 'compress.zlib://' . $file;
		return @fopen($file, $mode);
	}

	/**
	 * Preserve new lines, recursive function
	 * @param mixed $data the data
	 * @param boolean $reverse to reverse the replacement order
	 * @return mixed the data
	 */
	private function preserveLines($data, $reverse) {
		if ($reverse) {
			$from = array("\\n", "\\r");
			$to = array("\n", "\r");
		} else {
			$from = array("\n", "\r");
			$to = array("\\n", "\\r");
		}

		if (is_string($data)) {
			$data = str_replace($from, $to, $data);
		} elseif (is_array($data)) {
			foreach ($data as $key => $value) {
				$data[$key] = $this->preserveLines($value, $reverse);
			}
		}

		return $data;
	}

	/**
	 * Replaces the key in the table.
	 * If the key doesn't exist,
	 * null is returned.
	 *
	 * @param string $key the key
	 * @param mixed $data the data to store
	 * @return boolean successful replace
	 */
	public function replace($key, $data) {
		if ($this->isValidKey($key) && $this->isValidData($data)) {
			return $this->replaceKey($key, $data);
		}

		return false;
	}

	/**
	 * Replaces the key in the table.
	 * If the key doesn't exist,
	 * null is returned.
	 *
	 * @param string $key the key
	 * @param mixed $data the data to store
	 * @return boolean successful replace
	 */
	private function replaceKey($key, $data) {
		if ($this->exists($key)) {
			// Use memory or swap?
			$swap = true;

			if ($this->options['swap_memory_limit'] > 0) {
				clearstatcache();

				if (filesize($this->file) <= $this->options['swap_memory_limit']) {
					$swap = false;
					$contents = "";
				}
			}

			if ($data !== false) {
				// Create a copy of data to push into cache
				if ($this->options['cache']) {
					$orig_data = $data;
				}

				// Preserve new lines
				$data = $this->preserveLines($data, false);

				// Serialize data
				$data = serialize($data);
			}

			// Open tmp file
			if ($swap) {
				if (($tp = $this->openFile($this->tmpFile, "ab")) !== false) {
					@flock($tp, LOCK_EX);
				} else {
					throw new Exception('Could not create temporary table for ' . $this->name);
				}
			}

			// Open file
			if (($fp = $this->openFile($this->file, "rb")) !== false) {
				// Lock file
				@flock($fp, LOCK_SH);

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
						if ($this->options['cache']) {
							$this->cache[$key] = $orig_data;
						}
					}

					if ($swap) {
						// Write line
						$fwrite = @fwrite($tp, $line);

						if ($fwrite === false) {
							throw new Exception('Could not write to temporary database ' . $this->db);
						}
					} else {
						// Save line to memory
						$contents .= $line;
					}
				}

				// Unlock and close file
				@flock($fp, LOCK_UN);
				@fclose($fp);

				if ($swap) {
					// Unlock and close tmp file
					@flock($tp, LOCK_UN);
					@fclose($tp);

					// Remove file
					if (!@unlink($this->file)) {
						throw new Exception('Could not remove old table ' . $this->name);
					}

					// Rename tmp file
					if (!@rename($this->tmpFile, $this->file)) {
						throw new Exception('Could not rename temporary table ' . $this->name);
					}

					// Set permissions
					@chmod($this->file, 0777);
				} else {
					// Open file
					if (($fp = $this->openFile($this->file, "wb")) !== false) {

						// Lock file
						@flock($fp, LOCK_EX);

						// Write contents
						$fwrite = @fwrite($fp, $contents);

						// Unlock and close file
						@flock($fp, LOCK_UN);
						@fclose($fp);

						// Free up memory
						unset($contents);

						if ($fwrite === false) {
							throw new Exception('Could not write to table ' . $this->name);
						}
					}
					else {
						throw new Exception('Could not open table ' . $this->name);
					}
				}
			}
			else {
				throw new Exception('Could not open table ' . $this->name);
			}

			return true;
		}

		return null;
	}

	/**
	 * Sets the key to store in the table.
	 *
	 * @param string $key the key
	 * @param mixed $data the data to store
	 * @return boolean successful set
	 */
	public function set($key, $data) {
		if ($this->isValidKey($key) && $this->isValidData($data)) {
			return $this->setKey($key, $data);
		}

		return false;
	}

	/**
	 * Sets the key to store in the table.

	 * @param string $key the key
	 * @param mixed $data the data to store
	 * @return boolean successful set
	 */
	private function setKey($key, $data) {
		// Replace existing key?
		if ($this->exists($key)) {
			return $this->replaceKey($key, $data);
		}

		// Create a copy of data to push into cache
		if ($this->options['cache']) {
			$orig_data = $data;
		}

		// Preserve new lines
		$data = $this->preserveLines($data, false);

		// Serialize data
		$data = serialize($data);

		// Open file
		if (($fp = $this->openFile($this->file, "ab")) !== false) {

			// Lock file
			@flock($fp, LOCK_EX);

			// Set line, we don't use PHP_EOL to keep it cross-platform compatible
			$line = $key . "=" . $data . "\n";

			// Write line
			$fwrite = @fwrite($fp, $line);

			// Unlock and close file
			@flock($fp, LOCK_UN);
			@fclose($fp);

			if ($fwrite === false) {
				throw new Exception('Could not write to table ' . $this->name);
			}

			// Save to cache
			if ($this->options['cache']) {
				$this->cache[$key] = $orig_data;
			}
		}
		else {
			throw new Exception('Could not open table ' . $this->name);
		}

		return true;
	}

	/**
	 * Set flintstone options.

	 * @param array $options an array of options
	 * @return void
	 */
	private function setOptions($options) {
		foreach ($options as $key => $value) {
			$this->options[$key] = $value;
		}
	}

	/**
	 * Returns the table size.
	 *
	 * @return bool the number of records
	 */
	public function size() {
		return count($this->getAllKeys());
	}

}
