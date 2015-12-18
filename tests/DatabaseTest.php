<?php

use Flintstone\Config;
use Flintstone\Database;

/**
 * @group database
 */
class DatabaseTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        @unlink(getcwd().'/test.dat');
    }

    /**
     * @expectedException Flintstone\Exception
     */
    public function testDatabaseInvalidName()
    {
        $config = new Config();
        $db = new Database('test!123', $config);
    }

    public function testGetDatabaseAndConfig()
    {
        $config = new Config(array(
            'directory' => __DIR__,
        ));

        $path = __DIR__ . DIRECTORY_SEPARATOR . 'test.dat';

        $db = new Database('test', $config);
        $this->assertEquals('test', $db->getName());
        $this->assertInstanceOf('Flintstone\Config', $db->getConfig());
        $this->assertEquals($path, $db->getPath());
    }

    public function testNameImmutability()
    {
        $db = new Database('test', new Config());
        $this->assertSame($db, $db->withName('test'));
        $this->assertNotEquals($db, $db->withName('tacos'));
    }


    public function testConfigImmutability()
    {
        $config = new Config();
        $sameConfig = $config->withExtension('.dat');
        $altConfig = $config->withExtension('db');

        $db = new Database('test', $config);
        $this->assertSame($db, $db->withConfig($sameConfig));
        $this->assertNotEquals($db, $db->withConfig($altConfig));
    }
}
