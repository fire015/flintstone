<?php

use Flintstone\Cache\ArrayCache;

class ArrayCacheTest extends PHPUnit_Framework_TestCase
{
    public function testGetAndSet()
    {
        $cache = new ArrayCache();
        $cache->set('foo', 'bar');
        $this->assertTrue($cache->contains('foo'));
        $this->assertEquals('bar', $cache->get('foo'));
    }

    public function testDelete()
    {
        $cache = new ArrayCache();
        $cache->set('foo', 'bar');
        $cache->delete('foo');
        $this->assertFalse($cache->contains('foo'));
    }

    public function testFlush()
    {
        $cache = new ArrayCache();
        $cache->set('foo', 'bar');
        $cache->flush();
        $this->assertFalse($cache->contains('foo'));
    }
}
