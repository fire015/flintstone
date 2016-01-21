<?php

use Flintstone\Config;
use Flintstone\Database;

class DatabaseTest extends PHPUnit_Framework_TestCase
{
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
            'dir' => __DIR__,
        ));

        $path = __DIR__.DIRECTORY_SEPARATOR.'test.dat';

        $db = new Database('test', $config);
        $this->assertEquals('test', $db->getName());
        $this->assertInstanceOf('Flintstone\Config', $db->getConfig());
        $this->assertEquals($path, $db->getPath());
    }
}
