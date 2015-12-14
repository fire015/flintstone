<?php

use Flintstone\Config;
use Doctrine\Common\Cache\Cache;
use Flintstone\Formatter\JsonFormatter;
use Doctrine\Common\Cache\ClearableCache;

class ConfigTest extends PHPUnit_Framework_TestCase
{
    public function testDefaultConfig()
    {
        $config = new Config();
        $this->assertEquals('', $config->getDir());
        $this->assertEquals('.dat', $config->getExt());
        $this->assertFalse($config->useGzip());
        $this->assertInstanceOf('Doctrine\Common\Cache\ArrayCache', $config->getCache());
        $this->assertInstanceOf('Flintstone\Formatter\SerializeFormatter', $config->getFormatter());
        $this->assertEquals(2097152, $config->getSwapMemoryLimit());
    }

    public function testConfigConstructorOptions()
    {
        $config = new Config(array(
            'dir' => __DIR__,
            'ext' => 'test',
            'gzip' => true,
            'cache' => true,
            'formatter' => null,
            'swap_memory_limit' => 100,
        ));

        $this->assertEquals(__DIR__.DIRECTORY_SEPARATOR, $config->getDir());
        $this->assertEquals('.test.gz', $config->getExt());
        $this->assertTrue($config->useGzip());
        $this->assertInstanceOf('Doctrine\Common\Cache\ArrayCache', $config->getCache());
        $this->assertInstanceOf('Flintstone\Formatter\SerializeFormatter', $config->getFormatter());
        $this->assertEquals(100, $config->getSwapMemoryLimit());
    }

    public function testConfigSetFormatter()
    {
        $config = new Config();
        $config->setFormatter(new JsonFormatter());
        $this->assertInstanceOf('Flintstone\Formatter\JsonFormatter', $config->getFormatter());
    }

    /**
     * @expectedException Flintstone\Exception
     */
    public function testConfigInvalidDir()
    {
        $config = new Config();
        $config->setDir('/x/y/z/foo');
    }

    /**
     * @expectedException Flintstone\Exception
     */
    public function testConfigInvalidCache()
    {
        $config = new Config();
        $config->setCache(new ConfigTestCache());
    }

    public function testConfigCache()
    {
        $config = new Config();
        $config->setCache(new ConfigTestClearableCache());
    }
}

class ConfigTestCache implements Cache
{
    public function fetch($id)
    {
    }

    public function contains($id)
    {
    }

    public function save($id, $data, $lifeTime = 0)
    {
    }

    public function delete($id)
    {
    }

    public function getStats()
    {
    }

    public function deleteAll()
    {
    }
}

class ConfigTestClearableCache implements Cache, ClearableCache
{
    public function fetch($id)
    {
    }

    public function contains($id)
    {
    }

    public function save($id, $data, $lifeTime = 0)
    {
    }

    public function delete($id)
    {
    }

    public function getStats()
    {
    }

    public function deleteAll()
    {
    }
}
