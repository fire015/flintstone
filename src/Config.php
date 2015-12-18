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

use Flintstone\Cache\ArrayCache;
use Flintstone\Cache\CacheInterface;
use Flintstone\Formatter\FormatterInterface;
use Flintstone\Formatter\SerializeFormatter;

/**
 * A immutable value object class to configure a Database instance.
 */
class Config
{
    /**
     * Config.
     *
     * @var array
     */
    protected $config = array();

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        $config = $this->normalizeConfig($config);

        $this->config['directory'] = $this->filterDirectory($config['directory']);
        $this->config['extension'] = $this->filterExtension($config['extension']);
        $this->config['gzip'] = (bool) $config['gzip'];
        $this->config['cache'] = $config['cache'];
        $this->config['formatter'] = $config['formatter'];
        $this->config['swap_memory_limit'] = $this->filterInteger($config['swap_memory_limit']);
    }

    /**
     * Normalize the user supplied config.
     *
     * @param array $config
     *
     * @return array
     */
    protected function normalizeConfig(array $config)
    {
        $defaultConfig = array(
            'directory' => getcwd(),
            'extension' => '.dat',
            'gzip' => false,
            'cache' => new ArrayCache(),
            'formatter' => new SerializeFormatter(),
            'swap_memory_limit' => 2097152,    // 2MB
        );

        return array_replace($defaultConfig, $config);
    }

    /**
     * Filter Directory
     *
     * @param string $directory
     *
     * @throws Exception If the Directory does not exist or is not accessible
     *
     * @return string
     */
    protected function filterDirectory($directory)
    {
        if (!is_dir($directory)) {
            throw new Exception(sprintf('The submitted directory `%s` does not exist.',$directory));
        }

        if (!is_readable($directory) || !is_writable($directory)) {
            throw new Exception(sprintf('You don\'t have permission to read or write on `%s`', $directory));
        }

        return rtrim($directory, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * Filter the extension
     *
     * @param string $extension
     *
     * @return string
     */
    protected function filterExtension($extension)
    {
        if ('.' != substr($extension, 0, 1)) {
            $extension = '.' . $extension;
        }

        return $extension;
    }

    /**
     * Filter a Integer
     *
     * @param int $int
     *
     * @throws Exception if the submitted integer is not in the valid range
     *
     * @return int
     */
    protected function filterInteger($int)
    {
        $int = filter_var($int, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
        if (false === $int) {
            throw new Exception('The submitted value is not a valid integer');
        }

        return $int;
    }

    /**
     * Return the directory which will contain the cache file.
     *
     * @return string
     */
    public function getDirectory()
    {
        return $this->config['directory'];
    }

    /**
     * Return the File extension
     *
     * @return string
     */
    public function getExtension()
    {
        return $this->config['extension'];
    }

    /**
     * Tell whether we are using Gzip compression
     *
     * @return bool
     */
    public function useGzip()
    {
        return $this->config['gzip'];
    }

    /**
     * Return the cache driver.
     *
     * @return CacheInterface|null
     */
    public function getCache()
    {
        return $this->config['cache'];
    }

    /**
     * Return the formatter engine.
     *
     * @return FormatterInterface
     */
    public function getFormatter()
    {
        return $this->config['formatter'];
    }

    /**
     * Get the swap memory limit.
     *
     * @return int
     */
    public function getSwapMemoryLimit()
    {
        return $this->config['swap_memory_limit'];
    }

    /**
     * Return an instance with the specified directory.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified directory.
     *
     * @param string $dir
     *
     * @return self
     */
    public function withDirectory($dir)
    {
        $dir = $this->filterDirectory($dir);
        if ($dir === $this->config['directory']) {
            return $this;
        }
        $clone = clone $this;
        $clone->config['directory'] = $dir;

        return $clone;
    }

    /**
     * Return an instance with the specified extension.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified extension.
     *
     * @param string $ext
     *
     * @return self
     */
    public function withExtension($ext)
    {
        $ext = $this->filterExtension($ext);
        if ($ext === $this->config['extension']) {
            return $this;
        }
        $clone = clone $this;
        $clone->config['extension'] = $ext;

        return $clone;
    }

    /**
     * Return an instance with the specified gzip status.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified gzip status.
     *
     * @param bool $gzip
     *
     * @return self
     */
    public function withGzip($gzip)
    {
        $gzip = (bool) $gzip;
        if ($gzip === $this->config['gzip']) {
            return $this;
        }
        $clone = clone $this;
        $clone->config['gzip'] = $gzip;

        return $clone;
    }

    /**
     * Return an instance with the specified cache driver
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified cache driver.
     *
     * @param CacheInterface $cache
     *
     * @return self
     */
    public function withCache(CacheInterface $cache)
    {
        if ($cache === $this->config['cache']) {
            return $this;
        }
        $clone = clone $this;
        $clone->config['cache'] = $cache;

        return $clone;
    }

    /**
     * Return an instance with the specified formatter engine
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified formatter engine.
     *
     * @param FormatterInterface $formatter
     *
     * @return self
     */
    public function withFormatter(FormatterInterface $formatter)
    {
        if ($formatter === $this->config['formatter']) {
            return $this;
        }
        $clone = clone $this;
        $clone->config['formatter'] = $formatter;

        return $clone;
    }

    /**
     * Return an instance with the specified swap memory limit
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified  swap memory limit.
     *
     * @param int $limit
     *
     * @return self
     */
    public function withSwapMemoryLimit($limit)
    {
        $limit = $this->filterInteger($limit);
        if ($limit === $this->config['swap_memory_limit']) {
            return $this;
        }

        $clone = clone $this;
        $clone->config['swap_memory_limit'] = $limit;

        return $clone;
    }
}
