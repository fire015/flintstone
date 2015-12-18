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

class NullCache implements CacheInterface
{
    /**
     * {@inheritdoc}
     */
    public function contains($key)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $data)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
    }
}
