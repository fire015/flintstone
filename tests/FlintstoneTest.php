<?php

use Flintstone\Flintstone;
use Flintstone\Cache\ArrayCache;
use Flintstone\Cache\NullCache;
use Flintstone\Formatter\JsonFormatter;

/**
 * @group flintstone
 * @group database
 */
class FlintstoneTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        @unlink(getcwd().'/test.dat');
    }

    /**
     * @expectedException Flintstone\Exception
     */
    public function testKeyInvalidName()
    {
        $db = Flintstone::load('test');
        $db->get('test!123');
    }

    /**
     * @expectedException Flintstone\Exception
     */
    public function testKeyInvalidData()
    {
        $db = Flintstone::load('test');
        $db->set('test', (object) array());
    }

    public function testDatabaseProvider()
    {
        return array(
            'no cache, no gzip' => array(
                'no-cache-no-gizp',
                array(
                    'dir' => __DIR__,
                    'cache' => new NullCache(),
                    'gzip' => false,
                ),
            ),
            'with cache, with gzip' => array(
                'with-cache-with-gzip',
                array(
                    'dir' => __DIR__,
                    'cache' => new ArrayCache(),
                    'gzip' => true,
                ),
            ),
            'no cache, no gzip, alt formatter' => array(
                'alt-formatter',
                array(
                    'dir' => __DIR__,
                    'cache' => new NullCache(),
                    'gzip' => false,
                    'formatter' => new JsonFormatter(),
                ),
            ),
        );
    }

    /**
     * @dataProvider testDatabaseProvider
     */
    public function testSetGet($dbname, $config)
    {
        $db = Flintstone::load($dbname, $config);

        $arr = array('foo' => "new\nline");
        $db->set('foo', 1);
        $db->set('name', 'john');
        $db->set('arr', $arr);

        $this->assertEquals(1, $db->get('foo'));
        $this->assertEquals('john', $db->get('name'));
        $this->assertEquals($arr, $db->get('arr'));
        unlink($db->getDatabase()->getPath());
    }

    /**
     * @dataProvider testDatabaseProvider
     */
    public function testGetCached($dbname, $config)
    {
        $db = Flintstone::load($dbname, $config);
        $db->set('foo', 1);
        $this->assertEquals(1, $db->get('foo'));
        $this->assertEquals(1, $db->get('foo'));
        unlink($db->getDatabase()->getPath());
    }

    /**
     * @dataProvider testDatabaseProvider
     */
    public function testGetUnkownValue($dbname, $config)
    {
        $db = Flintstone::load($dbname, $config);
        $this->assertFalse($db->get('foo'));
        unlink($db->getDatabase()->getPath());
    }

    /**
     * @dataProvider testDatabaseProvider
     */
    public function testUpdateAValue($dbname, $config)
    {
        $db = Flintstone::load($dbname, $config);
        $db->set('foo', 1);
        $this->assertEquals(1, $db->get('foo'));
        $db->set('foo', 2);
        $this->assertEquals(2, $db->get('foo'));
        unlink($db->getDatabase()->getPath());
    }

    /**
     * @dataProvider testDatabaseProvider
     */
    public function testDelete($dbname, $config)
    {
        $db = Flintstone::load($dbname, $config);
        $this->assertFalse($db->get('foo'));
        $db->set('foo', 1);
        $this->assertEquals(1, $db->get('foo'));
        $db->delete('foo');
        $this->assertFalse($db->get('foo'));
        unlink($db->getDatabase()->getPath());
    }

    /**
     * @dataProvider testDatabaseProvider
     */
    public function testFlush($dbname, $config)
    {
        $db = Flintstone::load($dbname, $config);
        $this->assertFalse($db->get('foo'));
        $db->set('foo', 1);
        $this->assertEquals(1, $db->get('foo'));
        $db->flush();
        $this->assertFalse($db->get('foo'));
        unlink($db->getDatabase()->getPath());
    }

    /**
     * @dataProvider testDatabaseProvider
     */
    public function testGetKeys($dbname, $config)
    {
        $db = Flintstone::load($dbname, $config);

        $arr = array('foo' => "new\nline");
        $db->set('foo', 1);
        $db->set('name', 'john');
        $db->set('arr', $arr);

        $this->assertEquals(['foo', 'name', 'arr'], $db->getKeys());
        unlink($db->getDatabase()->getPath());
    }


    /**
     * @dataProvider testDatabaseProvider
     */
    public function testGetAll($dbname, $config)
    {
        $db = Flintstone::load($dbname, $config);

        $arr = array('foo' => "new\nline");
        $db->set('foo', 1);
        $db->set('name', 'john');
        $db->set('arr', $arr);

        $result = $db->getAll();

        $this->assertCount(3, $result);
        $this->assertContains($arr, $result);
        unlink($db->getDatabase()->getPath());
    }
}
