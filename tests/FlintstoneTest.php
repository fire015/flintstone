<?php

use Flintstone\Config;
use Flintstone\Flintstone;

class FlintstoneTest extends PHPUnit_Framework_TestCase
{
	public function testGetDatabaseAndConfig()
	{
		$db = new Flintstone('test', array(
			'dir' => __DIR__,
			'cache' => false
		));

		$this->assertInstanceOf('Flintstone\Database', $db->getDatabase());
		$this->assertInstanceOf('Flintstone\Config', $db->getConfig());
	}

	/**
	 * @expectedException Flintstone\Exception
	 */
	public function testKeyInvalidName()
	{
		$db = new Flintstone('test', array());
		$db->get('test!123');
	}

	/**
	 * @expectedException Flintstone\Exception
	 */
	public function testKeyInvalidData()
	{
		$db = new Flintstone('test', array());
		$db->set('test', new FlintstoneTest);
	}

	public function testGetSetDelete()
	{
		$db = new Flintstone('test', array(
			'dir' => __DIR__,
			'cache' => false
		));

		$this->assertFalse($db->get('foo'));

		$db->set('foo', 1);
		$db->set('name', 'john');
		$this->assertEquals(1, $db->get('foo'));
		$this->assertEquals('john', $db->get('name'));

		$db->set('foo', 2);
		$this->assertEquals(2, $db->get('foo'));
		$this->assertEquals('john', $db->get('name'));

		unlink($db->getDatabase()->getPath());
	}
}