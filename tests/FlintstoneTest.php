<?php

use Flintstone\Flintstone;
use Flintstone\Formatter\JsonFormatter;

class FlintstoneTest extends PHPUnit_Framework_TestCase
{
    public function testGetDatabaseAndConfig()
    {
        $db = new Flintstone('test', array(
            'dir' => __DIR__,
            'cache' => false,
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
        $db->set('test', new self());
    }

    public function testOperations()
    {
        $this->runOperationsTests(array(
            'dir' => __DIR__,
            'cache' => false,
            'gzip' => false,
        ));

        $this->runOperationsTests(array(
            'dir' => __DIR__,
            'cache' => true,
            'gzip' => true,
        ));

        $this->runOperationsTests(array(
            'dir' => __DIR__,
            'cache' => false,
            'gzip' => false,
            'formatter' => new JsonFormatter(),
        ));
    }

    protected function runOperationsTests($config)
    {
        $db = new Flintstone('test', $config);
        $arr = array('foo' => "new\nline");

        $this->assertFalse($db->get('foo'));

        $db->set('foo', 1);
        $db->set('name', 'john');
        $db->set('arr', $arr);
        $this->assertEquals(1, $db->get('foo'));
        $this->assertEquals('john', $db->get('name'));
        $this->assertEquals($arr, $db->get('arr'));

        $db->set('foo', 2);
        $this->assertEquals(2, $db->get('foo'));
        $this->assertEquals('john', $db->get('name'));
        $this->assertEquals($arr, $db->get('arr'));

        $db->delete('name');
        $this->assertFalse($db->get('name'));
        $this->assertEquals($arr, $db->get('arr'));

        $keys = $db->getKeys();
        $this->assertEquals(2, count($keys));
        $this->assertEquals('foo', $keys[0]);
        $this->assertEquals('arr', $keys[1]);

        $data = $db->getAll();
        $this->assertEquals(2, count($data));
        $this->assertEquals(2, $data['foo']);
        $this->assertEquals($arr, $data['arr']);

        $db->flush();
        $this->assertFalse($db->get('foo'));
        $this->assertFalse($db->get('arr'));
        $this->assertEquals(0, count($db->getKeys()));
        $this->assertEquals(0, count($db->getAll()));

        unlink($db->getDatabase()->getPath());
    }
}
