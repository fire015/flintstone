<?php

use Flintstone\Cache\ArrayCache;

class ArrayCacheTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ArrayCache
     */
    private $cache;

    protected function setUp()
    {
        $this->cache = new ArrayCache();
    }

    public function testGetAndSet()
    {
        $this->cache->set('foo', 'bar');
        $this->assertTrue($this->cache->contains('foo'));
        $this->assertEquals('bar', $this->cache->get('foo'));
    }

    public function testDelete()
    {
        $this->cache->set('foo', 'bar');
        $this->cache->delete('foo');
        $this->assertFalse($this->cache->contains('foo'));
    }

    public function testFlush()
    {
        $this->cache->set('foo', 'bar');
        $this->cache->flush();
        $this->assertFalse($this->cache->contains('foo'));
    }
}
