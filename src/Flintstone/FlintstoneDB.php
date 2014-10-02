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
     * Tell whether the cache is enabled or not
     *
     * @var boolean
     */
    private $cache_enabled = true;

    /**
     * Tell whether gzip is enabled or not
     *
     * @var boolean
     */
    private $gzip_enabled = false;

    /**
     * Database File Path
     *
     * @var string
     */
    private $file;

    /**
     * Swap Memory Limit
     *
     * @var  integer
     */
    private $swap_memory_limit;

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
    private $default_options = array(
        'dir' => '',
        'ext' => '.dat',
        'gzip' => false,
        'cache' => true,
        'swap_memory_limit' => 1048576,
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
        if (! preg_match("/^[A-Za-z0-9_\-]+$/", $database)) {
            throw new FlintstoneException('Invalid characters in database name');
        }

        $options = array_merge($this->default_options, $options);
        $dir = rtrim($options['dir'], '/\\') . DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) {
            throw new FlintstoneException($dir.' is not a valid directory');
        }

        $this->swap_memory_limit = filter_var(
            $options['swap_memory_limit'],
            FILTER_VALIDATE_INT,
            array('options' => array('min_range' => 0, 'default' => $this->default_options['swap_memory_limit']))
        );

        if ($options['cache'] !== $this->default_options['cache']) {
            $this->cache_enabled = !$this->cache_enabled;
        }

        if ($options['gzip'] !== $this->default_options['gzip']) {
            $this->gzip_enabled = !$this->gzip_enabled;
        }

        $extension = filter_var(
            $options['ext'],
            FILTER_SANITIZE_STRING,
            array('flags' => FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH)
        );

        $this->setFile($dir, $database, $extension);
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
        $key = $this->normalizeKey($key);

        $data = false;
        if ($this->cache_enabled && array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $filepointer = $this->openFile(self::FILE_READ);
        foreach ($filepointer as $line) {
            $data = $this->getDataFromLine($line, $key);
            if (false === $data) {
                continue;
            }
            $data = $this->preserveLines(unserialize($data), true);
            break;
        }

        $this->closeFile($filepointer);
        if ($this->cache_enabled && false !== $data) {
            $this->cache[$key] = $data;
        }

        return $data;
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
        $key = $this->normalizeKey($key);

        if ($this->get($key) !== false) {
            return $this->replace($key, $data);
        }

        if ($this->cache_enabled) {
            $this->cache[$key] = $data;
        }

        $data = $this->serializeData($data);
        $line = "$key=$data\n";
        $filepointer = $this->openFile(self::FILE_APPEND);
        $filepointer->fwrite($line);
        $this->closeFile($filepointer);

        return true;
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
        $key = $this->normalizeKey($key);

        $tmp = new SplTempFileObject($this->swap_memory_limit);
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
        if ($this->get($key) !== false && $this->replace($key, false)) {
            unset($this->cache[$key]);

            return true;
        }

        return false;
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
        $filepointer = $this->openFile(self::FILE_WRITE);
        $this->closeFile($filepointer);
        $this->cache = array();

        return true;
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
        $keys = array();
        $filepointer = $this->openFile(self::FILE_READ);
        foreach ($filepointer as $line) {
            $pieces = explode("=", $line);
            $keys[] = $pieces[0];
        }
        $this->closeFile($filepointer);

        return $keys;
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
     * Set the file
     *
     * @param string $directory file directory
     * @param string $basename  file basename
     * @param string $ext       file extension
     *
     */
    private function setFile($directory, $basename, $ext)
    {
        if (substr($ext, 0, 1) !== ".") {
            $ext = ".$ext";
        }
        if ($this->gzip_enabled && substr($ext, -3) !== ".gz") {
            $ext .= ".gz";
        }

        $this->file = $directory.$basename.$ext;
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

        if (!file_exists($path) && !@touch($path)) {
            throw new FlintstoneException('Could not create file ' . $path);
        } elseif (!is_readable($path)) {
            throw new FlintstoneException('Could not read file ' . $path);
        } elseif (!is_writable($path)) {
            throw new FlintstoneException('Could not write to file ' . $path);
        }

        if ($this->gzip_enabled) {
            $path = 'compress.zlib://' . $path;
        }
        $res  = $this->file_access_mode[$mode];

        $file = new SplFileObject($path, $res['mode']);
        $file->setFlags(SplFileObject::DROP_NEW_LINE|SplFileObject::SKIP_EMPTY|SplFileObject::READ_AHEAD);
        if (! $this->gzip_enabled && !$file->flock($res['operation'])) {
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
        if (! $this->gzip_enabled && ! $file->flock(LOCK_UN)) {
            $file = null;
            throw new FlintstoneException('Could not unlock file');
        }
        $file = null;
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
     * serialize a data before saving
     *
     * @param mixed $data
     *
     * @return string
     */
    private function serializeData($data)
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
        $serializeData = $this->serializeData($data);
        $pieces = explode("=", $line);
        if ($pieces[0] == $key) {
            if (false === $serializeData) {
                return null;
            }
            $line = "$key=$serializeData\n";
            if ($this->cache_enabled) {
                $this->cache[$key] = $data;
            }
        }

        return $line;
    }

    /**
     * Retrieve Data from a given line
     *
     * @param string $line file line
     * @param string $key  cache key
     *
     * @return string|boolean
     */
    private function getDataFromLine($line, $key)
    {
        $pieces = explode("=", $line);
        if ($pieces[0] != $key) {
            return false;
        }
        $data = $pieces[1];
        if (count($pieces) > 2) {
            array_shift($pieces);
            $data = implode("=", $pieces);
        }

        return $data;
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
