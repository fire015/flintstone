<?php

use Flintstone\Cache\NullCache;
use Flintstone\Config;
use Flintstone\Formatter\JsonFormatter;

/**
 * @group config
 */
class ConfigTest extends PHPUnit_Framework_TestCase
{
    public function testDefaultConfig()
    {
        $config = new Config();
        $this->assertEquals(getcwd() . DIRECTORY_SEPARATOR, $config->getDirectory());
        $this->assertEquals('.dat', $config->getExtension());
        $this->assertFalse($config->useGzip());
        $this->assertInstanceOf('Flintstone\Cache\CacheInterface', $config->getCache());
        $this->assertInstanceOf('Flintstone\Formatter\FormatterInterface', $config->getFormatter());
        $this->assertEquals(2097152, $config->getSwapMemoryLimit());
    }

    public function testImmutabilityWithTheSameSettings()
    {
        $config = new Config();
        $alt = $config
            ->withExtension($config->getExtension())
            ->withDirectory($config->getDirectory())
            ->withGzip($config->useGzip())
            ->withCache($config->getCache())
            ->withFormatter($config->getFormatter())
            ->withSwapMemoryLimit($config->getSwapMemoryLimit());
        $this->assertSame($alt, $config);
    }

    public function testImmutabilityWithUpdatedSettings()
    {
        $config = new Config();
        $alt = $config
            ->withFormatter(new JsonFormatter())
            ->withExtension('db')
            ->withDirectory(__DIR__)
            ->withGzip(true)
            ->withCache(new NullCache())
            ->withSwapMemoryLimit(100);
        $this->assertNotSame($alt, $config);
    }

    /**
     * @expectedException Flintstone\Exception
     */
    public function testConfigInvalidDir()
    {
        $config = new Config();
        $config->withDirectory('/x/y/z/foo');
    }

    /**
     * @expectedException Flintstone\Exception
     */
    public function testConfigInvalidDirOnPermission()
    {
        $config = new Config();
        $config->withDirectory('/');
    }

    /**
     * @expectedException Flintstone\Exception
     */
    public function testConfigInvalidSwapMemory()
    {
        $config = new Config();
        $config->withSwapMemoryLimit(0);
    }
}