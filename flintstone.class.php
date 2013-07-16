<?php

/**
 * Flintstone - A key/value database store using flat files for PHP
 * Copyright (c) 2013 XEWeb
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
 * @copyright 2013 XEWeb
 * @author Jason <emailfire@gmail.com>
 * @version 1.3
 * @package flintstone
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
			self::$instance[$database] = new FlintstoneDB($database, $options);
		}
		
		return self::$instance[$database];
	}
}

class FlintstoneDB {
	
	/**
	 * Database name
	 * @access private
	 * @var string
	 */
	private $db = null;
	
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
	 * @access public
	 * @var array
	 */
	public $options = array('dir' => '', 'ext' => '.dat', 'gzip' => false, 'cache' => true, 'swap_memory_limit' => 1048576);
	
	/**
	 * Flintstone constructor
	 * @param string $database the database name
	 * @param array $options an array of options
	 * @return void
	 */
	public function __construct($database, $options) {
		
		// Check valid characters in database name
		if (!preg_match("/^([A-Za-z0-9_]+)$/", $database)) {
			throw new FlintstoneException('Invalid characters in database name');
		}
		
		// Set current database
		$this->db = $database;
		
		// Set options
		if (!empty($options)) {
			$this->setOptions($options);
		}
	}
	
	/**
	 * Set flintstone options
	 * @param array $options an array of options
	 * @return void
	 */
	public function setOptions($options) {
		foreach ($options as $key => $value) {
			$this->options[$key] = $value;
		}
	}
	
	/**
	 * Setup the database and perform pre-flight checks
	 * @return void
	 */
	public function setupDatabase() {
		if (empty($this->data)) {
		
			// Check database directory
			$dir = rtrim($this->options['dir'], '/\\') . DIRECTORY_SEPARATOR;
			
			if (!is_dir($dir)) {
				throw new FlintstoneException($dir . ' is not a valid directory');
			}
			
			// Set data
			$ext = $this->options['ext'];
			if (substr($ext, 0, 1) !== ".") $ext = "." . $ext;
			if ($this->options['gzip'] === true && substr($ext, -3) !== ".gz") $ext .= ".gz";
			$this->data['file'] = $dir . $this->db . $ext;
			$this->data['file_tmp'] = $dir . $this->db . "_tmp" . $ext;
			$this->data['cache'] = array();
			
			// Create database
			if (!file_exists($this->data['file'])) {
				if (($fp = $this->openFile($this->data['file'], "wb")) !== false) {
					@fclose($fp);
					@chmod($this->data['file'], 0777);
					clearstatcache();
				}
				else {
					throw new FlintstoneException('Could not create database ' . $this->db);
				}
			}
			
			// Check file is readable
			if (!is_readable($this->data['file'])) {
				throw new FlintstoneException('Could not read database ' . $this->db);
			}
			
			// Check file is writable
			if (!is_writable($this->data['file'])) {
				throw new FlintstoneException('Could not write to database ' . $this->db);
			}
		}
	}
	
	/**
	 * Open the database file
	 * @param string $file the file path
	 * @param string $mode the file mode
	 * @return object file pointer
	 */
	private function openFile($file, $mode) {
		if ($this->options['gzip'] === true) $file = 'compress.zlib://' . $file;
		return @fopen($file, $mode);
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
		if (($fp = $this->openFile($this->data['file'], "rb")) !== false) {
			
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
					if ($this->options['cache'] === true) {
						$this->data['cache'][$key] = $data;
					}
					
					break;
				}
			}
			
			// Unlock and close file
			@flock($fp, LOCK_UN);
			@fclose($fp);
		}
		else {
			throw new FlintstoneException('Could not open database ' . $this->db);
		}
		
		return $data;
	}
	
	/**
	 * Replace a key in the database
	 * @param string $key the key
	 * @param mixed $data the data to store, or false to delete
	 * @return boolean successful replace
	 */
	private function replaceKey($key, $data) {
		
		// Use memory or swap?
		$swap = true;
		if ($this->options['swap_memory_limit'] > 0) {
			clearstatcache();
			if (filesize($this->data['file']) <= $this->options['swap_memory_limit']) {
				$swap = false;
				$contents = "";
			}
		}
		
		if ($data !== false) {
		
			// Create a copy of data to push into cache
			if ($this->options['cache'] === true) {
				$orig_data = $data;
			}
			
			// Preserve new lines
			$data = $this->preserveLines($data, false);
			
			// Serialize data
			$data = serialize($data);
		}
		
		// Open tmp file
		if ($swap) {
			if (($tp = $this->openFile($this->data['file_tmp'], "ab")) !== false) {
				@flock($tp, LOCK_EX);
			}
			else {
				throw new FlintstoneException('Could not create temporary database for ' . $this->db);
			}
		}
		
		// Open file
		if (($fp = $this->openFile($this->data['file'], "rb")) !== false) {
			
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
					if ($this->options['cache'] === true) {
						$this->data['cache'][$key] = $orig_data;
					}
				}
				
				if ($swap) {
					
					// Write line
					$fwrite = @fwrite($tp, $line);
	
					if ($fwrite === false) {
						throw new FlintstoneException('Could not write to temporary database ' . $this->db);
					}
				}
				else {
					
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
				if (!@unlink($this->data['file'])) {
					throw new FlintstoneException('Could not remove old database ' . $this->db);
				}
				
				// Rename tmp file
				if (!@rename($this->data['file_tmp'], $this->data['file'])) {
					throw new FlintstoneException('Could not rename temporary database ' . $this->db);
				}
				
				// Set permissions
				@chmod($this->data['file'], 0777);
			}
			else {
				
				// Open file
				if (($fp = $this->openFile($this->data['file'], "wb")) !== false) {
					
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
						throw new FlintstoneException('Could not write to database ' . $this->db);
					}
				}
				else {
					throw new FlintstoneException('Could not open database ' . $this->db);
				}
			}
		}
		else {
			throw new FlintstoneException('Could not open database ' . $this->db);
		}
		
		return true;
	}
	
	/**
	 * Set a key to store in the database
	 * @param string $key the key
	 * @param mixed $data the data to store
	 * @return boolean successful set
	 */
	private function setKey($key, $data) {
		
		// Replace existing key?
		if ($this->getKey($key) !== false) {
			return $this->replaceKey($key, $data);
		}
		
		// Create a copy of data to push into cache
		if ($this->options['cache'] === true) {
			$orig_data = $data;
		}
		
		// Preserve new lines
		$data = $this->preserveLines($data, false);
		
		// Serialize data
		$data = serialize($data);
		
		// Open file
		if (($fp = $this->openFile($this->data['file'], "ab")) !== false) {
			
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
				throw new FlintstoneException('Could not write to database ' . $this->db);
			}
					
			// Save to cache
			if ($this->options['cache'] === true) {
				$this->data['cache'][$key] = $orig_data;
			}
		}
		else {
			throw new FlintstoneException('Could not open database ' . $this->db);
		}
		
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
		
		// Open file to truncate (w mode)
		if (($fp = $this->openFile($this->data['file'], "wb")) !== false) {
			
			// Close file
			@fclose($fp);
		
			// Empty cache
			if ($this->options['cache'] === true) {
				$this->data['cache'] = array();
			}
		}
		else {
			throw new FlintstoneException('Could not open database ' . $this->db);
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
		if (($fp = $this->openFile($this->data['file'], "rb")) !== false) {
			
			// Lock file
			@flock($fp, LOCK_SH);
			
			// Loop through each line of file
			while (($line = fgets($fp)) !== false) {
				
				// Split up seperator
				$pieces = explode("=", $line);
				$keys[] = $pieces[0];
			}

			// Unlock and close file
			@flock($fp, LOCK_UN);
			@fclose($fp);
		}
		else {
			throw new FlintstoneException('Could not open database ' . $this->db);
		}

		return $keys;
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
	 * @return boolean
	 */
	private function isValidKey($key) {

		// Check key length
		$len = strlen($key);
		
		if ($len < 1) {
			throw new FlintstoneException('No key has been set');
		}
		
		if ($len > 50) {
			throw new FlintstoneException('Maximum key length is 50 characters');
		}
		
		// Check valid characters in key
		if (!preg_match("/^([A-Za-z0-9_]+)$/", $key)) {
			throw new FlintstoneException('Invalid characters in key');
		}
		
		return true;
	}
	
	/**
	 * Check the data type is valid
	 * @param mixed $data the data
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
	 * @return mixed the data
	 */
	public function get($key) {
		$this->setupDatabase();
		
		if ($this->isValidKey($key)) {
			return $this->getKey($key);
		}
		
		return false;
	}
	
	/**
	 * Set a key to store in the database
	 * @param string $key the key
	 * @param mixed $data the data to store
	 * @return boolean successful set
	 */
	public function set($key, $data) {
		$this->setupDatabase();
		
		if ($this->isValidKey($key) && $this->isValidData($data)) {
			return $this->setKey($key, $data);
		}
		
		return false;
	}
	
	/**
	 * Replace a key in the database
	 * @param string $key the key
	 * @param mixed $data the data to store
	 * @return boolean successful replace
	 */
	public function replace($key, $data) {
		$this->setupDatabase();
		
		if ($this->isValidKey($key) && $this->isValidData($data)) {
			return $this->replaceKey($key, $data);
		}
		
		return false;
	}
	
	/**
	 * Delete a key from the database
	 * @param string $key the key
	 * @return boolean successful delete
	 */
	public function delete($key) {
		$this->setupDatabase();
		
		if ($this->isValidKey($key)) {
			return $this->deleteKey($key);
		}
		
		return false;
	}
	
	/**
	 * Flush the database
	 * @return boolean successful flush
	 */
	public function flush() {
		$this->setupDatabase();
		return $this->flushDatabase();
	}
	
	/**
	 * Get all keys from the database
	 * @return array list of keys
	 */
	public function getKeys() {
		$this->setupDatabase();
		return $this->getAllKeys();
	}
}

/**
 * Flintstone exception
 */
class FlintstoneException extends Exception { }
?>