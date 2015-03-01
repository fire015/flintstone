<?php

/**
 * Flintstone Unit Tests
 */

namespace Flinstone\tests;

use Flintstone\Flintstone;
use Flintstone\Formatter\JsonFormatter;
use stdClass;

class TestFixture extends \PHPUnit_Framework_TestCase
{
    /**
     * Flintstone database
     *
     * @var object
     */
    protected $db;

    /**
     * Flintstone database name
     *
     * @var string
     */
    protected $dbName = 'test';

    /**
     * @expectedException \Flintstone\FlintstoneException
     * @expectedExceptionMessage Invalid characters in database name
     */
    public function testThrowsExceptionIfDatabaseNameIsInvalid()
    {
        Flintstone::load('test!123');
    }

    /**
     * @expectedException \Flintstone\FlintstoneException
     * @expectedExceptionMessage /x/y/z/ is not a valid directory
     */
    public function testThrowsExceptionIfDatabaseDirectoryIsInvalid()
    {
        Flintstone::load('blah', array(
            'dir' => '/x/y/z'
        ));
    }

    /**
     * @expectedException \Flintstone\FlintstoneException
     * @expectedExceptionMessage Formatter must implement \Flintstone\Formatter\FormatterInterface
     */
    public function testThrowsExceptionIfFormatterIsInvalid()
    {
        Flintstone::load('blah', array(
            'dir'   => __DIR__,
            'formatter' => new stdClass
        ));
    }

    public function testLoadMethodReturnsTheSameInstance()
    {
        $db1 = Flintstone::load($this->dbName, array('dir' => __DIR__));
        $db2 = Flintstone::load($this->dbName, array('dir' => __DIR__));
        $this->assertSame($db1, $db2);
    }

    /**
     * Run the feature test multiple times with different options
     *
     * @param \PHPUnit_Framework_TestResult $result
     *
     * @return \PHPUnit_Framework_TestResult
     */
    public function run(\PHPUnit_Framework_TestResult $result = null)
    {
        if ($result === null) {
            $result = $this->createResult();
        }

        // Default options
        $this->db = Flintstone::load($this->dbName, array('dir' => __DIR__));
        $result->run($this);

        // With no cache
        $this->db = Flintstone::load($this->dbName, array(
            'dir' => __DIR__,
            'cache' => false,
            'gzip' => false,
            'ext' => 'txt'
        ));
        $result->run($this);

        // With no cache and gzip compression
        $this->db = Flintstone::load($this->dbName, array(
            'dir' => __DIR__,
            'cache' => false,
            'gzip' => true,
            'ext' => 'txt'
        ));
        $result->run($this);

        // With gzip compression and cache
        $this->db = Flintstone::load($this->dbName, array(
            'dir' => __DIR__,
            'cache' => true,
            'gzip' => true,
            'swap_memory_limit' => 0
        ));
        $result->run($this);

        // With JSON formatter
        $this->db = Flintstone::load($this->dbName, array(
            'dir' => __DIR__,
            'cache' => false,
            'formatter' => new JsonFormatter()
        ));
        $result->run($this);

        return $result;
    }

    /**
     * Unload the test database and remove
     */
    public function tearDown()
    {
        Flintstone::unload($this->dbName);
        @unlink($this->db->getFile());
        clearstatcache();
    }
}
