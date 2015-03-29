<?php

/**
 * Flintstone - A key/value database store using flat files for PHP
 * Copyright (c) 2014 Jason M
 */

namespace Flintstone;

use Flintstone\Formatter\FormatterInterface;
use Flintstone\Formatter\SerializeFormatter;
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
     * Formatter
     *
     * @var object
     */
    private $formatter;

    /**
     * Flintstone options:
     *
     * - string     $dir                the directory where the database files are stored
     * - string     $ext                the database file extension to use
     * - boolean    $gzip               use gzip to compress the database
     * - boolean    $cache              store get() results in memory
     * - object     $formatter          the formatter class used to encode/decode data
     * - integer    $swap_memory_limit  amount of memory to use before writing to a temporary file
     *
     * @var array
     */
    private $default_options = array(
        'dir' => '',
        'ext' => '.dat',
        'gzip' => false,
        'cache' => true,
        'formatter' => null,
        'swap_memory_limit' => 1048576,
    );

    /**
     * Flintstone constructor
     *
     * @param string $database the database name
     * @param array  $options  an array of options
     *
     * @throws \Flintstone\FlintstoneException when database cannot be loaded
     */
    public function __construct($database, array $options = array())
    {
        if (! preg_match('/^[\w-]+$/', $database)) {
            throw new FlintstoneException('Invalid characters in database name');
        }
        $options = array_merge($this->default_options, $options);
        $options['database'] = $database;

        $this->init($options);
    }

    /**
     * Options setter
     *
     * @param array $options an array of options
     *
     * @throws \Flintstone\FlintstoneException when using incorrect options values
     *
     * @return void
     */
    private function init(array $options)
    {
        $options['dir'] = rtrim($options['dir'], '/\\') . DIRECTORY_SEPARATOR;
        if (!is_dir($options['dir'])) {
            throw new FlintstoneException($options['dir'].' is not a valid directory');
        } elseif (! is_null($options['formatter']) && ! $options['formatter'] instanceof FormatterInterface) {
            throw new FlintstoneException('Formatter must implement \Flintstone\Formatter\FormatterInterface');
        }
        $this->formatter = $options['formatter'] ?: new SerializeFormatter;

        $this->swap_memory_limit = filter_var(
            $options['swap_memory_limit'],
            FILTER_VALIDATE_INT,
            array('options' => array('min_range' => 0, 'default' => $this->default_options['swap_memory_limit']))
        );

        if ($options['cache'] != $this->default_options['cache']) {
            $this->cache_enabled = !$this->cache_enabled;
        }

        if ($options['gzip'] != $this->default_options['gzip']) {
            $this->gzip_enabled = !$this->gzip_enabled;
        }

        $this->file = $options['dir'].$options['database'].$this->formatExtension($options['ext']);
    }

    /**
     * Format the file extension
     *
     * @param string $ext
     *
     * @return string
     */
    private function formatExtension($ext)
    {
        $ext = filter_var(
            $ext,
            FILTER_SANITIZE_STRING,
            array('flags' => FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH)
        );

        if ("." != substr($ext, 0, 1)) {
            $ext = ".".$ext;
        }
        if ($this->gzip_enabled && ".gz" != substr($ext, -3)) {
            $ext .= ".gz";
        }

        return $ext;
    }

    /**
     * Get a key from the database
     *
     * @param string $key the key
     *
     * @throws \Flintstone\FlintstoneException when key is invalid
     *
     * @return mixed the data
     */
    public function get($key)
    {
        $this->validateKey($key);

        $data = false;
        if ($this->cache_enabled && array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $filePointer = $this->openFile(self::FILE_READ);
        foreach ($filePointer as $line) {
            $data = $this->getDataFromLine($line, $key);
            if (false !== $data) {
                $data = $this->formatter->decode($data);
                break;
            }
        }

        $this->closeFile($filePointer);
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
     * @throws \Flintstone\FlintstoneException when key or data is invalid
     */
    public function set($key, $data)
    {
        $this->validateData($data);
        $this->validateKey($key);

        if ($this->get($key) !== false) {
            return $this->replace($key, $data);
        }

        if ($this->cache_enabled) {
            $this->cache[$key] = $data;
        }

        if ($data !== false) {
            $data = $this->formatter->encode($data);
        }
        $line = "$key=$data\n";
        $filePointer = $this->openFile(self::FILE_APPEND);
        $filePointer->fwrite($line);
        $this->closeFile($filePointer);

        return true;
    }

    /**
     * Replace a key in the database
     *
     * DEPRECATION WARNING! This method will be removed from the public API
     * in the next major point release
     *
     * @deprecated deprecated since version 1.8
     *
     * @param string $key  the key
     * @param mixed  $data the data to store
     *
     * @throws \Flintstone\FlintstoneException when key or data is invalid
     *
     * @return boolean successful replace
     */
    public function replace($key, $data)
    {
        $this->validateKey($key);

        $tmp = new SplTempFileObject($this->swap_memory_limit);
        $filePointer = $this->openFile(self::FILE_READ);
        foreach ($filePointer as $line) {
            $line = $this->replaceLine($line, $key, $data);
            if (!empty($line)) {
                $tmp->fwrite($line);
            }
        }
        $this->closeFile($filePointer);
        $tmp->rewind();

        $filePointer = $this->openFile(self::FILE_WRITE);
        foreach ($tmp as $line) {
            $filePointer->fwrite($line);
        }
        $tmp = null;
        $this->closeFile($filePointer);

        return true;
    }

    /**
     * Delete a key from the database
     *
     * @param string $key the key
     *
     * @throws \Flintstone\FlintstoneException when key is invalid
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
     * @throws \Flintstone\FlintstoneException when something goes wrong
     *
     * @return boolean successful flush
     */
    public function flush()
    {
        $filePointer = $this->openFile(self::FILE_WRITE);
        $this->closeFile($filePointer);
        $this->cache = array();

        return true;
    }

    /**
     * Get all keys from the database
     *
     * @throws \Flintstone\FlintstoneException when something goes wrong
     *
     * @return array list of keys
     */
    public function getKeys()
    {
        $keys = array();
        $filePointer = $this->openFile(self::FILE_READ);
        foreach ($filePointer as $line) {
            $pieces = explode("=", $line);
            $keys[] = $pieces[0];
        }
        $this->closeFile($filePointer);

        return $keys;
    }

    /**
     * Get all data from the database
     *
     * @throws \Flintstone\FlintstoneException when something goes wrong
     *
     * @return array list key => value data in DB
     */
    public function getAll()
    {
        $data = array();
        $filePointer = $this->openFile(self::FILE_READ);
        foreach ($filePointer as $line) {
            $pieces = explode("=", $line, 2);
            $data[$pieces[0]] = $this->formatter->decode($pieces[1]);
        }
        $this->closeFile($filePointer);

        return $data;
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
     * Open the database file
     *
     * @param integer $mode the file mode
     *
     * @throws \Flintstone\FlintstoneException when database cannot be opened or locked
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
        $res = $this->file_access_mode[$mode];

        $file = new SplFileObject($path, $res['mode']);
        if (self::FILE_READ == $mode) {
            $file->setFlags(SplFileObject::DROP_NEW_LINE|SplFileObject::SKIP_EMPTY|SplFileObject::READ_AHEAD);
        }
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
     * @throws \Flintstone\FlintstoneException when database cannot be unlocked
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
     * Validate the key
     *
     * @param string $key the key
     *
     * @throws \Flintstone\FlintstoneException when key is invalid
     *
     * @return void
     */
    private function validateKey($key)
    {
        if (! is_string($key)) {
            throw new FlintstoneException('Key must be a string');
        } elseif (strlen($key) > 1024) {
            throw new FlintstoneException('Maximum key length is 1024 characters');
        } elseif (strpos($key, '=') !== false) {
            throw new FlintstoneException('Key may not contain the equals character');
        }
    }

    /**
     * Check the data type is valid
     *
     * @param mixed $data the data
     *
     * @throws \Flintstone\FlintstoneException when data is invalid
     */
    private function validateData($data)
    {
        if (!is_string($data) && !is_int($data) && !is_float($data) && !is_array($data)) {
            throw new FlintstoneException('Invalid data type');
        }
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
        $encodeData = ($data !== false) ? $this->formatter->encode($data) : $data;
        $pieces = explode("=", $line);
        if ($pieces[0] == $key) {
            if (false === $encodeData) {
                return null;
            }
            $line = "$key=$encodeData";
            if ($this->cache_enabled) {
                $this->cache[$key] = $data;
            }
        }

        return $line."\n";
    }

    /**
     * Retrieve data from a given line
     *
     * @param string $line file line
     * @param string $key  cache key
     *
     * @return string|boolean
     */
    private function getDataFromLine($line, $key)
    {
        $pieces = explode("=", $line, 2);
        return $pieces[0] == $key ? $pieces[1] : false;
    }
}
