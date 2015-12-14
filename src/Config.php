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

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\ClearableCache;
use Flintstone\Formatter\FormatterInterface;
use Flintstone\Formatter\SerializeFormatter;

class Config
{
    /**
     * Default config.
     *
     * @var array
     */
    protected $config = array(
        'dir' => '',
        'ext' => '.dat',
        'gzip' => false,
        'cache' => true,
        'formatter' => null,
        'swap_memory_limit' => 2097152,    // 2MB
    );

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        if (isset($config['dir'])) {
            $this->setDir($config['dir']);
        }
        if (isset($config['ext'])) {
            $this->setExt($config['ext']);
        }
        if (isset($config['gzip'])) {
            $this->setGzip($config['gzip']);
        }
        if (isset($config['cache'])) {
            $this->setCache($config['cache']);
        }
        if (isset($config['formatter'])) {
            $this->setFormatter($config['formatter']);
        }
        if (isset($config['swap_memory_limit'])) {
            $this->setSwapMemoryLimit($config['swap_memory_limit']);
        }
    }

    /**
     * Get the dir.
     *
     * @return string
     */
    public function getDir()
    {
        return $this->config['dir'];
    }

    /**
     * Set the dir.
     *
     * @param string $dir
     *
     * @throws Exception
     */
    public function setDir($dir)
    {
        if (!is_dir($dir)) {
            throw new Exception('Directory does not exist: '.$dir);
        }

        $this->config['dir'] = rtrim($dir, '/\\').DIRECTORY_SEPARATOR;
    }

    /**
     * Get the ext.
     *
     * @return string
     */
    public function getExt()
    {
        if ($this->useGzip()) {
            return $this->config['ext'].'.gz';
        }

        return $this->config['ext'];
    }

    /**
     * Set the ext.
     *
     * @param string $ext
     */
    public function setExt($ext)
    {
        if ('.' != substr($ext, 0, 1)) {
            $ext = '.'.$ext;
        }

        $this->config['ext'] = $ext;
    }

    /**
     * Use gzip?
     *
     * @return bool
     */
    public function useGzip()
    {
        return $this->config['gzip'];
    }

    /**
     * Set gzip.
     *
     * @param bool $gzip
     */
    public function setGzip($gzip)
    {
        $this->config['gzip'] = (bool) $gzip;
    }

    /**
     * Get the cache.
     *
     * @return Cache|false
     */
    public function getCache()
    {
        if ($this->config['cache'] === true) {
            $this->config['cache'] = new ArrayCache();
        }

        return $this->config['cache'];
    }

    /**
     * Set the cache.
     *
     * @param mixed $cache
     */
    public function setCache($cache)
    {
        if (!is_bool($cache) && !($cache instanceof Cache && $cache instanceof ClearableCache)) {
            throw new Exception('Cache must be a boolean or an instance of Doctrine\Common\Cache\Cache and Doctrine\Common\Cache\ClearableCache');
        }

        $this->config['cache'] = $cache;
    }

    /**
     * Get the formatter.
     *
     * @return FormatterInterface
     */
    public function getFormatter()
    {
        if ($this->config['formatter'] === null) {
            $this->config['formatter'] = new SerializeFormatter();
        }

        return $this->config['formatter'];
    }

    /**
     * Set the formatter.
     *
     * @param FormatterInterface $formatter
     */
    public function setFormatter(FormatterInterface $formatter)
    {
        $this->config['formatter'] = $formatter;
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
     * Set the swap memory limit.
     *
     * @param int $limit
     */
    public function setSwapMemoryLimit($limit)
    {
        $this->config['swap_memory_limit'] = (int) $limit;
    }
}
