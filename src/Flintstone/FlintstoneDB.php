<?php

/**
 * Flintstone - A key/value database store using flat files for PHP
 * Copyright (c) 2014 Jason M
 */

namespace Flintstone;

use SplFileObject;
use SplTempFileObject;

/**
 * The Flintstone database specific class
 */

class FlintstoneDB
{
    /**
     * File read flag
     *
     * @var integer
     */
    const FILE_READ = 1;

    /**
     * File write flag
     *
     * @var integer
     */
    const FILE_WRITE = 2;

    /**
     * File append flag
     *
     * @var integer
     */
    const FILE_APPEND = 3;

    /**
     * File Access Mode
     *
     * @var array
     */
    private $file_access_mode = array(
        self::FILE_READ => array(
            'mode' => 'rb',
            'operation' => LOCK_SH
        ),
        self::FILE_WRITE => array(
            'mode' => 'wb',
            'operation' => LOCK_EX,
        ),
        self::FILE_APPEND => array(
            'mode' => 'ab',
            'operation' => LOCK_EX,
        ),
    );

    /**
     * Database Memory cache
     *
     * @var array
     */
    private $cache = array();

    /**
     * Database File Path
     *
     * @var string
     */
    private $file;

    /**
     * Flintstone options:
     *
     * - string		$dir				the directory to the database files
     * - string		$ext				the database file extension
     * - boolean	$gzip				use gzip to compress database
     * - boolean	$cache				store get() results in memory
     * - integer	$swap_memory_limit	write out each line to a temporary file and
     *                                  swap if database is larger than limit (0 to always do this)
     *
     * @var array
     */
    private $options = array(
        'dir' => '',
        'ext' => '.dat',
        'gzip' => false,
        'cache' => true,
    );

    /**
     * Flintstone constructor
     *
     * @param string $database the database name
     * @param array  $options  an array of options
     *
     * @throws FlintstoneException when database cannot be loaded
     *
     * @return void
     */
    public function __construct($database, array $options = array())
    {
        if (!preg_match("/^[A-Za-z0-9_\-]+$/", $database)) {
            throw new FlintstoneException('Invalid characters in database name');
        }
        $this->options = array_merge($this->options, $options);
        $this->setupDatabase($database);
    }

    /**
     * Setup the database and perform pre-flight checks
     *
     * @param string $database the database name
     *
     * @throws FlintstoneException when database cannot be loaded
     *
     * @return void
     */
    private function setupDatabase($database)
    {
        $dir = rtrim($this->options['dir'], '/\\') . DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) {
            throw new FlintstoneException($dir . ' is not a valid directory');
        }

        $ext = $this->options['ext'];
        if (substr($ext, 0, 1) !== ".") {
            $ext = "." . $ext;
        }
        if ($this->options['gzip'] === true && substr($ext, -3) !== ".gz") {
            $ext .= ".gz";
        }

        $file = $dir.$database.$ext;

        if (!file_exists($file) && !@touch($file)) {
            throw new RuntimeException('Could not create file ' . $file);
        } elseif (!is_readable($file)) {
            throw new RuntimeException('Could not read file ' . $file);
        } elseif (!is_writable($file)) {
            throw new RuntimeException('Could not write to file ' . $file);
        }

        $this->file = $file;
    }

    /**
     * Get a key from the database
     *
     * @param string $key the key
     *
     * @throws FlintstoneException when key is invalid
     *
     * @return mixed the data
     */
    public function get($key)
    {
        return $this->getKey($this->normalizeKey($key));
    }

    /**
     * Set a key to store in the database
     *
     * @param string $key  the key
     * @param mixed  $data the data to store
     *
     * @return boolean successful set
     *
     * @throws FlintstoneException when key or data is invalid
     */
    public function set($key, $data)
    {
        $this->validateData($data);

        return $this->setKey($this->normalizeKey($key), $data);
    }

    /**
     * Replace a key in the database
     *
     * @param string $key  the key
     * @param mixed  $data the data to store
     *
     * @throws FlintstoneException when key or data is invalid
     *
     * @return boolean successful replace
     */
    public function replace($key, $data)
    {
        $this->validateData($data);

        return $this->replaceKey($this->normalizeKey($key), $data);
    }

    /**
     * Delete a key from the database
     *
     * @param string $key the key
     *
     * @throws FlintstoneException when key is invalid
     *
     * @return boolean successful delete
     */
    public function delete($key)
    {
        return $this->deleteKey($this->normalizeKey($key));
    }

    /**
     * Flush the database
     *
     * @throws FlintstoneException when something goes wrong
     *
     * @return boolean successful flush
     */
    public function flush()
    {
        return $this->flushDatabase();
    }

    /**
     * Get all keys from the database
     *
     * @throws FlintstoneException when something goes wrong
     *
     * @return array list of keys
     */
    public function getKeys()
    {
        return $this->getAllKeys();
    }

    /**
     * Get the database file
     *
     * @return string file path
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Check the database has been loaded and valid key
     *
     * @param string $key the key
     *
     * @throws FlintstoneException when key is invalid
     */
    private function normalizeKey($key)
    {
        if (! is_string($key)) {
            throw new FlintstoneException('Key must be an string');
        } elseif (strlen($key) > 1024) {
            throw new FlintstoneException('Maximum key length is 1024 characters');
        } elseif (strpos($key, '=') !== false) {
            throw new FlintstoneException('Key may not contain the equals character');
        }

        return $key;
    }

    /**
     * Check the data type is valid
     *
     * @param mixed $data the data
     *
     * @throws FlintstoneException when data is invalid
     */
    private function validateData($data)
    {
        if (!is_string($data) && !is_int($data) && !is_float($data) && !is_array($data)) {
            throw new FlintstoneException('Invalid data type');
        }
    }

    /**
     * Open the database file
     *
     * @param integer $mode the file mode
     *
     * @throws FlintstoneException when database cannot be opened or locked
     *
     * @return \SplFileObject
     */
    private function openFile($mode)
    {
        $path = $this->file;

        if ($this->options['gzip'] === true) {
            $path = 'compress.zlib://' . $path;
        }
        $res  = $this->file_access_mode[$mode];

        $file = new SplFileObject($path, $res['mode']);
        $file->setFlags(SplFileObject::DROP_NEW_LINE|SplFileObject::SKIP_EMPTY|SplFileObject::READ_AHEAD);
        if (!$this->options['gzip'] && !$file->flock($res['operation'])) {
            throw new FlintstoneException('Could not lock file ' . $path);
        }

        return $file;
    }

    /**
     * Close the database file
     *
     * @param object $file the file pointer
     *
     * @throws FlintstoneException when database cannot be unlocked
     *
     * @return void
     */
    private function closeFile($file)
    {
        if (!$this->options['gzip'] && !$file->flock(LOCK_UN)) {
            $file = null;
            throw new FlintstoneException('Could not unlock file');
        }
        $file = null;
    }

    /**
     * Get a key from the database
     *
     * @param string $key the key
     *
     * @return mixed the data
     */
    private function getKey($key)
    {
        $data = false;
        if ($this->options['cache'] === true && array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $filepointer = $this->openFile(self::FILE_READ);
        foreach ($filepointer as $line) {
            $pieces = explode("=", $line);
            if ($pieces[0] != $key) {
                continue;
            }
            $data = $pieces[1];
            if (count($pieces) > 2) {
                array_shift($pieces);
                $data = implode("=", $pieces);
            }
            $data = unserialize($data);
            $data = $this->preserveLines($data, true);
            if ($this->options['cache'] === true) {
                $this->cache[$key] = $data;
            }
            break;
        }

        $this->closeFile($filepointer);

        return $data;
    }

    /**
     * Replace a key in the database
     *
     * @param string $key  the key
     * @param mixed  $data the data to store, or false to delete
     *
     * @return boolean successful replace
     *
     * @throws FlintstoneException when database cannot be written to
     */
    private function replaceKey($key, $data)
    {
        $tmp = new SplTempFileObject;
        $filepointer = $this->openFile(self::FILE_READ);
        foreach ($filepointer as $line) {
            $line = $this->replaceLine($line, $key, $data);
            if (empty($line)) {
                continue;
            }
            $tmp->fwrite($line);
        }
        $this->closeFile($filepointer);
        $tmp->rewind();

        $filepointer = $this->openFile(self::FILE_WRITE);
        foreach ($tmp as $line) {
            $filepointer->fwrite($line."\n");
        }
        $tmp = null;
        $this->closeFile($filepointer);

        return true;
    }

    /**
     * serialize a data before saving
     * @param  mixed  $data
     * @return string
     */
    private function formatData($data)
    {
        if ($data !== false) {
            return serialize($this->preserveLines($data, false));
        }

        return $data;
    }

    /**
     * update line content depending on the key and data
     *
     * @param string $line file line
     * @param string $key  cache key
     * @param mixed  $data raw data
     *
     * @return boolean
     */
    private function replaceLine($line, $key, $data)
    {
        $serializeData = $this->formatData($data);
        $pieces = explode("=", $line);
        if ($pieces[0] == $key) {
            if (false === $serializeData) {
                return null;
            }
            $line = "$key=$serializeData\n";
            if (true === $this->options['cache']) {
                $this->cache[$key] = $data;
            }
        }

        return $line;
    }

    /**
     * Set a key to store in the database
     *
     * @param string $key  the key
     * @param mixed  $data the data to store
     *
     * @throws FlintstoneException when database cannot be written to
     *
     * @return boolean
     */
    private function setKey($key, $data)
    {
        if ($this->getKey($key) !== false) {
            return $this->replaceKey($key, $data);
        }

        if ($this->options['cache'] === true) {
            $this->cache[$key] = $data;
        }

        $data = $this->formatData($data);
        $line = "$key=$data\n";
        $filepointer = $this->openFile(self::FILE_APPEND);
        $filepointer->fwrite($line);
        $this->closeFile($filepointer);

        return true;
    }

    /**
     * Delete a key from the database
     *
     * @param string $key the key
     *
     * @return boolean successful delete
     */
    private function deleteKey($key)
    {
        if ($this->getKey($key) !== false && $this->replaceKey($key, false)) {
            unset($this->cache[$key]);

            return true;
        }

        return false;
    }

    /**
     * Flush the database
     *
     * @return boolean successful flush
     */
    private function flushDatabase()
    {
        $filepointer = $this->openFile(self::FILE_WRITE);
        $this->closeFile($filepointer);
        $this->cache = array();

        return true;
    }

    /**
     * Get all keys from the database
     *
     * @return array of keys
     */
    private function getAllKeys()
    {
        $keys = array();
        $filepointer  = $this->openFile(self::FILE_READ);
        foreach ($filepointer as $line) {
            $pieces = explode("=", $line);
            $keys[] = $pieces[0];
        }
        $this->closeFile($filepointer);

        return $keys;
    }

    /**
     * Preserve new lines, recursive function
     *
     * @param mixed $data the data
     *
     * @param boolean $reverse to reverse the replacement order
     *
     * @return mixed the data
     */
    private function preserveLines($data, $reverse = false)
    {
        $search  = array("\n", "\r");
        $replace = array("\\n", "\\r");
        if ($reverse) {
            $search  = array("\\n", "\\r");
            $replace = array("\n", "\r");
        }

        if (is_string($data)) {
            $data = str_replace($search, $replace, $data);
        } elseif (is_array($data)) {
            foreach ($data as &$value) {
                $value = $this->preserveLines($value, $reverse);
            }
            unset($value);
        }

        return $data;
    }
}
