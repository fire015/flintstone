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

use Flintstone\Cache\CacheInterface;
use SplFileObject;
use SplTempFileObject;

class Database
{
    /**
     * File read flag.
     *
     * @var int
     */
    const FILE_READ = 1;

    /**
     * File write flag.
     *
     * @var int
     */
    const FILE_WRITE = 2;

    /**
     * File append flag.
     *
     * @var int
     */
    const FILE_APPEND = 3;

    /**
     * File access mode.
     *
     * @var array
     */
    protected $fileAccessMode = array(
        self::FILE_READ => array(
            'mode' => 'rb',
            'operation' => LOCK_SH,
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
     * Database name.
     *
     * @var string
     */
    protected $name;

    /**
     * Config class.
     *
     * @var Config
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param string $name
     * @param Config $config
     */
    public function __construct($name, Config $config)
    {
        $this->name = $this->filterName($name);
        $this->config = $config;
    }

    /**
     * Validate a submitted name for the database
     *
     * @param string $name
     *
     * @throws Exception If the name is invalid
     *
     * @return string
     */
    protected function filterName($name)
    {
        if (empty($name) || !preg_match('/^[\w-]+$/', $name)) {
            throw new Exception('Invalid characters in database name');
        }

        return $name;
    }

    /**
     * Get the database name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return an instance with the specified name.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified name.
     *
     * @param string $name
     *
     * @throws Exception
     *
     * @return self
     */
    public function withName($name)
    {
        $name = $this->filterName($name);
        if ($name === $this->name) {
            return $this;
        }
        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }

    /**
     * Return the Database configuration object
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Return an instance with the specified configuration.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the new configuration
     *
     * @param Config $config
     *
     * @return self
     */
    public function withConfig(Config $config)
    {
        if ($config === $this->config) {
            return $this;
        }
        $clone = clone $this;
        $clone->config = $config;

        return $clone;
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

        $cache = $this->getConfig()->getCache();
        if ($cache->contains($key)) {
            return $cache->get($key);
        }

        $data = $this->getValue($key);
        if ($data !== false) {
            $cache->set($key, $data);
        }

        return $data;
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
     * Retrieve the value from the Cache File
     *
     * @param  string $key
     *
     * @return mixed
     */
    protected function getValue($key)
    {
        $filePointer = $this->openFile(self::FILE_READ);
        $data = false;
        foreach ($filePointer as $line) {
            $data = $this->getDataFromLine($line, $key);
            if ($data !== false) {
                $data = $this->decodeData($data);
                break;
            }
        }

        $this->closeFile($filePointer);

        return $data;
    }

    /**
     * Open the database file.
     *
     * @param int $mode
     *
     * @throws Exception
     *
     * @return SplFileObject
     */
    protected function openFile($mode)
    {
        $path = $this->getPath();
        if ($this->getConfig()->useGzip()) {
            $path = 'compress.zlib://' . $path;
        }

        $res = $this->fileAccessMode[$mode];
        $file = new SplFileObject($path, $res['mode']);

        if (self::FILE_READ == $mode) {
            $file->setFlags(SplFileObject::DROP_NEW_LINE | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);
        }

        if (!$this->getConfig()->useGzip() && !$file->flock($res['operation'])) {
            throw new Exception('Could not lock file: ' . $path);
        }

        return $file;
    }

    /**
     * Get the path to the database file.
     *
     * @return string
     */
    public function getPath()
    {
        $ext = $this->config->getExtension();
        if ($this->config->useGzip()) {
            $ext .= '.gz';
        }

        $path = $this->config->getDirectory() . $this->getName() . $ext;
        if (!is_file($path)) {
            touch($path);
        }

        return $path;
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
     * Close the database file.
     *
     * @param SplFileObject $file
     *
     * @throws Exception
     */
    protected function closeFile(SplFileObject &$file)
    {
        if (!$this->getConfig()->useGzip() && !$file->flock(LOCK_UN)) {
            $file = null;
            throw new Exception('Could not unlock file');
        }

        $file = null;
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

        if ($this->get($key) !== false) {
            $this->replace($key, $data);

            return;
        }

        $filePointer = $this->openFile(self::FILE_APPEND);
        $filePointer->fwrite($this->getLineString($key, $data));
        $this->closeFile($filePointer);

        if ($cache = $this->getConfig()->getCache()) {
            $cache->delete($key);
        }
    }

    /**
     * Replace a key in the database.
     *
     * @param string $key
     * @param mixed $data
     */
    protected function replace($key, $data)
    {
        $tmp = new SplTempFileObject($this->getConfig()->getSwapMemoryLimit());
        $filePointer = $this->openFile(self::FILE_READ);
        foreach ($filePointer as $line) {
            $lineKey = $this->getKeyFromLine($line);
            if ($lineKey == $key && $data !== false) {
                $tmp->fwrite($this->getLineString($key, $data));
            }
        }

        $this->closeFile($filePointer);
        $tmp->rewind();


        $filePointer = $this->openFile(self::FILE_WRITE);
        foreach ($tmp as $line) {
            $filePointer->fwrite($line);
        }
        $this->closeFile($filePointer);
        $tmp = null;

        if ($cache = $this->getConfig()->getCache()) {
            $cache->delete($key);
        }
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
        $encodedData = $this->getConfig()->getFormatter()->encode($data);

        return $key . '=' . $encodedData . "\n";
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
        $filePointer = $this->openFile(self::FILE_WRITE);
        $this->closeFile($filePointer);

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
        $filePointer = $this->openFile(self::FILE_READ);

        foreach ($filePointer as $line) {
            $keys[] = $this->getKeyFromLine($line);
        }

        $this->closeFile($filePointer);

        return $keys;
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
     * Get all data from the database.
     *
     * @return array
     */
    public function getAll()
    {
        $data = array();
        $filePointer = $this->openFile(self::FILE_READ);

        foreach ($filePointer as $line) {
            $pieces = $this->getLinePieces($line);
            $data[$pieces[0]] = $this->decodeData($pieces[1]);
        }

        $this->closeFile($filePointer);

        return $data;
    }
}
