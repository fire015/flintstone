<?php

/**
 * Flintstone Unit Tests
 */

require __DIR__ . '/../vendor/autoload.php';

use Flintstone\Flintstone;

class TestFixture extends \PHPUnit_Framework_TestCase {

	/**
	 * Flintstone database
	 * @access protected
	 * @var object
	 */
	protected $db;

	/**
	 * Flintstone database name
	 * @access protected
	 * @var string
	 */
	protected $dbName = 'test';

	/**
	 * Run the feature test multiple times with different options
	 */
	public function run(PHPUnit_Framework_TestResult $result = null) {
		if ($result === null) {
			$result = $this->createResult();
		}

		// Default options
		$this->db = Flintstone::load($this->dbName, array('dir' => __DIR__));
		$result->run($this);

		// With no cache
		$this->db = Flintstone::load($this->dbName, array('dir' => __DIR__, 'cache' => false));
		$result->run($this);

		// With no cache and file swap
		$this->db = Flintstone::load($this->dbName, array('dir' => __DIR__, 'cache' => false, 'swap_memory_limit' => 0));
		$result->run($this);

		// With gzip compression
		$this->db = Flintstone::load($this->dbName, array('dir' => __DIR__, 'gzip' => true));
		$result->run($this);

		// With gzip compression and no cache
		$this->db = Flintstone::load($this->dbName, array('dir' => __DIR__, 'gzip' => true, 'cache' => false));
		$result->run($this);

		// With gzip compression, no cache and file swap
		$this->db = Flintstone::load($this->dbName, array('dir' => __DIR__, 'gzip' => true, 'cache' => false, 'swap_memory_limit' => 0));
		$result->run($this);

		return $result;
	}

	/**
	 * Unload the test database and remove
	 */
	public function tearDown() {
		Flintstone::unload($this->dbName);
		unlink($this->db->getFile());
	}
}