<?php

use Flintstone\Config;
use Flintstone\Formatter\JsonFormatter;

class ConfigTest extends PHPUnit_Framework_TestCase
{
    public function testDefaultConfig()
    {
        $config = new Config();
        $this->assertEquals(getcwd().DIRECTORY_SEPARATOR, $config->getDir());
        $this->assertEquals('.dat', $config->getExt());
        $this->assertFalse($config->useGzip());
        $this->assertInstanceOf(\Flintstone\Cache\ArrayCache::class, $config->getCache());
        $this->assertInstanceOf(\Flintstone\Formatter\SerializeFormatter::class, $config->getFormatter());
        $this->assertEquals(2097152, $config->getSwapMemoryLimit());
    }

    public function testConfigConstructorOptions()
    {
        $config = new Config([
            'dir' => __DIR__,
            'ext' => 'test',
            'gzip' => true,
            'cache' => false,
            'formatter' => null,
            'swap_memory_limit' => 100,
        ]);

        $this->assertEquals(__DIR__.DIRECTORY_SEPARATOR, $config->getDir());
        $this->assertEquals('.test.gz', $config->getExt());
        $this->assertTrue($config->useGzip());
        $this->assertFalse($config->getCache());
        $this->assertInstanceOf(\Flintstone\Formatter\SerializeFormatter::class, $config->getFormatter());
        $this->assertEquals(100, $config->getSwapMemoryLimit());
    }

    public function testConfigSetFormatter()
    {
        $config = new Config();
        $config->setFormatter(new JsonFormatter());
        $this->assertInstanceOf(JsonFormatter::class, $config->getFormatter());
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
    public function testConfigInvalidFormatter()
    {
        $config = new Config();
        $config->setFormatter(new self());
    }

    /**
     * @expectedException Flintstone\Exception
     */
    public function testConfigInvalidCache()
    {
        $config = new Config();
        $config->setCache(new self());
    }
}
