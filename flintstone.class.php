<?php

class Flintstone {
	
	// Database name
	private $db = null;
	
	// Database data
	public $data = array();
	
	// Options
	public $options = array('dir' => 'db/', 'ext' => '.txt', 'gzip' => false, 'cache' => true, 'swap_memory_limit' => 1048576);
	
	/*
	 * Flintstone constructor
	 */
	public function __construct($options = array()) {
		if (!empty($options)) $this->setOptions($options);
	}
	
	/*
	 * Set flintstone options
	 * @param $options an array of options
	 */
	public function setOptions($options) {
		foreach ($options as $key => $value) {
			$this->options[$key] = $value;
		}
	}
	
	/*
	 * Load a database
	 * @param $database the database name
	 */
	public function load($database) {
		
		// Check database directory
		if (!isset($this->options['dir']) || !is_dir($this->options['dir'])) {
			throw new Exception($this->options['dir'] . ' is not a valid directory');
		}
		
		// Check valid characters in database name
		if (!preg_match("/^([A-Za-z0-9_]+)$/", $database)) {
			throw new Exception('Invalid characters in database name');
		}
		
		// Set current database
		$this->db = $database;
		
		// Check database data
		if (!array_key_exists($this->db, $this->data)) {
			
			// Set database data
			$ext = $this->options['ext'];
			if ($this->options['gzip'] === true && substr($ext, -3) !== ".gz") $ext .= ".gz";
			$this->data[$this->db]['file'] = $this->options['dir'] . $this->db . $ext;
			$this->data[$this->db]['file_tmp'] = $this->options['dir'] . $this->db . "_tmp" . $ext;
			$this->data[$this->db]['cache'] = array();
			
			// Create database
			if (!file_exists($this->data[$this->db]['file'])) {
				if (($fp = $this->openFile($this->data[$this->db]['file'], "wb")) !== false) {
					@fclose($fp);
					@chmod($this->data[$this->db]['file'], 0777);
					clearstatcache();
				}
				else {
					throw new Exception('Could not create database ' . $this->db);
				}
			}
			
			// Check file is readable
			if (!is_readable($this->data[$this->db]['file'])) {
				throw new Exception('Could not read database ' . $this->db);
			}
			
			// Check file is writable
			if (!is_writable($this->data[$this->db]['file'])) {
				throw new Exception('Could not write to database ' . $this->db);
			}
		}
		
		return $this;
	}
	
	/*
	 * Open the database file
	 * @param $file the file path
	 * @param $mode the file mode
	 */
	private function openFile($file, $mode) {
		if ($this->options['gzip'] === true) $file = 'compress.zlib://' . $file;
		return @fopen($file, $mode);
	}

	/*
	 * Get a key from the database
	 * @param $key the key
	 */
	private function getKey($key) {
		
		$data = false;
		
		// Look in cache for key
		if ($this->options['cache'] === true && array_key_exists($key, $this->data[$this->db]['cache'])) {
			return $this->data[$this->db]['cache'][$key];
		}
		
		// Open file
		if (($fp = $this->openFile($this->data[$this->db]['file'], "rb")) !== false) {
			
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
					$data = $this->unserialize($data);
					
					// Preserve new lines
					$data = $this->preserveLines($data, true);
					
					// Save to cache
					if ($this->options['cache'] === true) {
						$this->data[$this->db]['cache'][$key] = $data;
					}
					
					break;
				}
			}
			
			// Unlock and close file
			@flock($fp, LOCK_UN);
			@fclose($fp);
		}
		else {
			throw new Exception('Could not read database ' . $this->db);
		}
		
		return $data;
	}
	
	/*
	 * Replace a key in the database
	 * @param $key the key
	 * @param $data the data to store or false to delete
	 */
	private function replaceKey($key, $data) {
		
		// Use memory or swap?
		$swap = true;
		if ($this->options['swap_memory_limit'] > 0) {
			clearstatcache();
			if (filesize($this->data[$this->db]['file']) <= $this->options['swap_memory_limit']) {
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
			if (($tp = $this->openFile($this->data[$this->db]['file_tmp'], "ab")) !== false) {
				@flock($tp, LOCK_EX);
			}
			else {
				throw new Exception('Could not create temporary database for ' . $this->db);
			}
		}
		
		// Open file
		if (($fp = $this->openFile($this->data[$this->db]['file'], "rb")) !== false) {
			
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
						$this->data[$this->db]['cache'][$key] = $orig_data;
					}
				}
				
				if ($swap) {
					
					// Write line
					$fwrite = @fwrite($tp, $line);
	
					if ($fwrite === false) {
						throw new Exception('Could not write to temporary database ' . $this->db);
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
				if (!@unlink($this->data[$this->db]['file'])) {
					throw new Exception('Could not remove old database ' . $this->db);
				}
				
				// Rename tmp file
				if (!@rename($this->data[$this->db]['file_tmp'], $this->data[$this->db]['file'])) {
					throw new Exception('Could not rename temporary database ' . $this->db);
				}
				
				// Set permissions
				@chmod($this->data[$this->db]['file'], 0777);
			}
			else {
				
				// Open file
				if (($fp = $this->openFile($this->data[$this->db]['file'], "wb")) !== false) {
					
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
						throw new Exception('Could not write to database ' . $this->db);
					}
				}
				else {
					throw new Exception('Could not read database ' . $this->db);
				}
			}
		}
		else {
			throw new Exception('Could not read database ' . $this->db);
		}
		
		return true;
	}
	
	/*
	 * Set a key to store in database
	 * @param $key the key
	 * @param $data the data to store
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
		if (($fp = $this->openFile($this->data[$this->db]['file'], "ab")) !== false) {
			
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
				throw new Exception('Could not write to database ' . $this->db);
			}
					
			// Save to cache
			if ($this->options['cache'] === true) {
				$this->data[$this->db]['cache'][$key] = $orig_data;
			}
		}
		else {
			throw new Exception('Could not read database ' . $this->db);
		}
		
		return true;
	}
	
	/*
	 * Delete a key from the database
	 * @param $key the key
	 */
	private function deleteKey($key) {
		
		// Find key
		if ($this->getKey($key) !== false) {
			
			// Replace existing key
			if ($this->replaceKey($key, false)) {
				
				// Remove from cache
				if ($this->options['cache'] === true && array_key_exists($key, $this->data[$this->db]['cache'])) {
					unset($this->data[$this->db]['cache'][$key]);
				}
				
				return true;
			}			
		}
		
		return false;
	}
	
	/*
	 * Flush the database
	 */
	private function flushDatabase() {
		
		// Open file to truncate (w mode)
		if (($fp = $this->openFile($this->data[$this->db]['file'], "wb")) !== false) {
			
			// Close file
			@fclose($fp);
		
			// Empty cache
			if ($this->options['cache'] === true) {
				$this->data[$this->db]['cache'] = array();
			}
		}
		else {
			throw new Exception('Could not read database ' . $this->db);
		}
		
		return true;
	}
	
	/*
	 * Preserve new lines, recursive function
	 * @param $data the data
	 * @param $reverse to reverse the replacement order
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
	
	/*
	 * Mulit-byte unserialize function
	 * @param $string the string
	 */
	private function unserialize($string) {
		$string = preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $string);
		return unserialize($string);
	}
	
	/*
	 * Check the database has been loaded and valid key length
	 * @param $key the key
	 */
	private function isValidKey($key) {
		
		// Check database loaded
		if ($this->db == null) {
			throw new Exception('Database has not been loaded');
		}
		
		// Check key length
		$len = strlen($key);
		
		if ($len < 1) {
			throw new Exception('No key has been set');
		}
		
		if ($len > 50) {
			throw new Exception('Maximum key length is 50 characters');
		}
		
		return true;
	}
	
	/*
	 * Check the data type is valid
	 * @param $data the data
	 */
	private function isValidData($data) {
		if (!is_string($data) && !is_int($data) && !is_float($data) && !is_array($data)) {
			throw new Exception('Invalid data type');
		}
		return true;
	}
	
	/*
	 * Get a key from the database
	 * @param $key the key
	 */
	public function get($key) {
		if ($this->isValidKey($key)) {
			return $this->getKey($key);
		}
		return false;
	}
	
	/*
	 * Set a key to store in database
	 * @param $key the key
	 * @param $data the data to store
	 */
	public function set($key, $data) {
		if ($this->isValidKey($key) && $this->isValidData($data)) {
			return $this->setKey($key, $data);
		}
		return false;
	}
	
	/*
	 * Replace a key in the database
	 * @param $key the key
	 * @param $data the data to store
	 */
	public function replace($key, $data) {
		if ($this->isValidKey($key) && $this->isValidData($data)) {
			return $this->replaceKey($key, $data);
		}
		return false;
	}
	
	/*
	 * Delete a key from the database
	 * @param $key the key
	 */
	public function delete($key) {
		if ($this->isValidKey($key)) {
			return $this->deleteKey($key);
		}
		return false;
	}
	
	/*
	 * Flush the database
	 */
	public function flush() {
		return $this->flushDatabase();
	}
}

echo "<pre>";
$time_start = microtime(true);

function echo_memory_usage() {
	$mem_usage = memory_get_usage();
   
	if ($mem_usage < 1024)
		echo $mem_usage." bytes\n";
	elseif ($mem_usage < 1048576)
		echo round($mem_usage/1024,2)." kilobytes\n";
	else
		echo round($mem_usage/1048576,2)." megabytes\n";
}

try {
	$db = new Flintstone(array('gzip' => true));
	
	/*
	for ($i = 1; $i <= 100; $i++) {
		$key = 'user' . $i;
		$value = array('id' => $i, 'name' => 'user' . $i, 'age' => ($i * 10), 'test' => array("my\ndog", "my\npony"), 'description' => "Lorem\n\rIpsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.");
		$db->load('users')->set($key, $value);
	}
	*/
	
	print_r($db->load('users')->get('user68')); echo "\n";
	$db->load('users')->replace('user68', 'xxx');
	print_r($db->load('users')->get('user68')); echo "\n";
	print_r($db->data);
	$db->load('users')->flush();
	print_r($db->data);
	
	$time_end = microtime(true);
	echo "\n\n---------------\n" . ($time_end - $time_start) . "\n";
	echo_memory_usage();
}
catch (Exception $e) {
	echo 'Exception: ' . $e->getMessage();
}

?>