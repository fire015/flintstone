<?php

/**
 * Flintstone Unit Tests
 */

namespace Flinstone\tests;

use Flintstone\Flintstone;
use Flintstone\FlintstoneException;
use Flintstone\Formatter\JsonFormatter;
use PHPUnit_Framework_TestResult;

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
     * Test invalid database name
     * @expectedException Flintstone\FlintstoneException
     */
    public function testInvalidDatabaseName()
    {
        Flintstone::load('test!123');
    }

    /**
     * Test invalid database directory
     * @expectedException Flintstone\FlintstoneException
     */
    public function testInvalidDatabaseDir()
    {
        Flintstone::load('blah', array(
			'dir' => '/x/y/z'
		));
    }

    /**
     * Test invalid formatter
     * @expectedException Flintstone\FlintstoneException
     */
    public function testInvalidFormatter()
    {
        Flintstone::load('blah', array(
            'dir'   => __DIR__,
            'formatter' => new \stdClass()
        ));
    }

    /**
     * Run the feature test multiple times with different options
     */
    public function run(PHPUnit_Framework_TestResult $result = null)
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
