<?php

use Flintstone\Config;
use Flintstone\Formatter\JsonFormatter;

class ConfigTest extends PHPUnit_Framework_TestCase
{
	public function testDefaultConfig()
	{
		$config = new Config;
		$this->assertEquals('', $config->getDir());
		$this->assertEquals('.dat', $config->getExt());
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

		$this->assertEquals(__DIR__ . DIRECTORY_SEPARATOR, $config->getDir());
		$this->assertEquals('.test.gz', $config->getExt());
		$this->assertEquals(true, $config->useGzip());
		$this->assertEquals(true, $config->useCache());
		$this->assertInstanceOf('Doctrine\Common\Cache\ArrayCache', $config->getCache());
		$this->assertInstanceOf('Flintstone\Formatter\SerializeFormatter', $config->getFormatter());
		$this->assertEquals(100, $config->getSwapMemoryLimit());
	}

	public function testConfigSetFormatter()
	{
		$config = new Config;
		$config->setFormatter(new JsonFormatter);
		$this->assertInstanceOf('Flintstone\Formatter\JsonFormatter', $config->getFormatter());
	}

	/**
	 * @expectedException Flintstone\Exception
	 */
	public function testConfigInvalidDir()
	{
		$config = new Config;
		$config->setDir('/x/y/z/foo');
	}

	/**
	 * @expectedException Flintstone\Exception
	 */
	public function testConfigInvalidCache()
	{
		$config = new Config;
		$config->setCache(new ConfigTest);
	}
}