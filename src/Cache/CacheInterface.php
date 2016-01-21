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

interface CacheInterface
{
    /**
     * Check if a key exists in the cache.
     *
     * @param string $key
     *
     * @return bool
     */
    public function contains($key);

    /**
     * Get a key from the cache.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key);

    /**
     * Set a key in the cache.
     *
     * @param string $key
     * @param mixed $data
     */
    public function set($key, $data);

    /**
     * Delete a key from the cache.
     *
     * @param string $key
     */
    public function delete($key);

    /**
     * Flush the cache.
     */
    public function flush();
}
