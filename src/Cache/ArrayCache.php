<?php

/*
 * This file is part of the Flintstone package.
 *
 * (c) Jason M <emailfire@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Flintstone\Cache;

class ArrayCache implements CacheInterface
{
    /**
     * Cache data.
     *
     * @var array
     */
    protected $cache = [];

    /**
     * {@inheritdoc}
     */
    public function contains($key)
    {
        return array_key_exists($key, $this->cache);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return $this->cache[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $data)
    {
        $this->cache[$key] = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        unset($this->cache[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $this->cache = [];
    }
}
