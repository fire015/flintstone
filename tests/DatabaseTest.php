<?php

use Flintstone\Config;
use Flintstone\Database;

class DatabaseTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Database
     */
    private $db;

    protected function setUp()
    {
        $config = new Config([
            'dir' => __DIR__,
        ]);

        $this->db = new Database('test', $config);
    }

    protected function tearDown()
    {
        if (is_file($this->db->getPath())) {
            unlink($this->db->getPath());
        }
    }

    /**
     * @expectedException Flintstone\Exception
     * @expectedExceptionMessage Invalid characters in database name
     */
    public function testDatabaseInvalidName()
    {
        $config = new Config();
        $db = new Database('test!123', $config);
    }

    public function testGetDatabaseAndConfig()
    {
        $this->assertEquals('test', $this->db->getName());
        $this->assertInstanceOf(Config::class, $this->db->getConfig());
        $this->assertEquals(__DIR__ . DIRECTORY_SEPARATOR . 'test.dat', $this->db->getPath());
    }

    public function testAppendToFile()
    {
        $this->db->appendToFile('foo=bar');
        $this->assertEquals('foo=bar', file_get_contents($this->db->getPath()));
    }

    public function testFlushFile()
    {
        $this->db->appendToFile('foo=bar');
        $this->db->flushFile();
        $this->assertEmpty(file_get_contents($this->db->getPath()));
    }

    public function testReadFromFile()
    {
        $this->db->appendToFile('foo=bar');
        $file = $this->db->readFromFile();

        foreach ($file as $line) {
            $this->assertInstanceOf(\Flintstone\Line::class, $line);
            $this->assertEquals('foo', $line->getKey());
            $this->assertEquals('bar', $line->getData());
        }
    }

    public function testWriteTempToFile()
    {
        $tmpFile = new SplTempFileObject();
        $tmpFile->fwrite('foo=bar');
        $tmpFile->rewind();

        $this->db->writeTempToFile($tmpFile);
        $this->assertEquals('foo=bar', file_get_contents($this->db->getPath()));
    }
}
