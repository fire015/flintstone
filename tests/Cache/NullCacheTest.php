<?php

use Flintstone\Cache\NullCache;

/**
 * @group cache
 */
class NullCacheTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider nullCacheProvider
     */
    public function testGetter($param, $value)
    {
        $cache = new NullCache();
        $cache->set($param, $value);
        $this->assertFalse($cache->contains($param));
        $this->assertNull($cache->get($param));
    }

    public function nullCacheProvider()
    {
        return array(
            array('test', 'toto'),
            array('foo', array('bar', 'baz')),
        );
    }
}
