<?php

/**
 * Flintstone Unit Tests
 */

require __DIR__ . '/../src/Flintstone/Flintstone.php';
require __DIR__ . '/../src/Flintstone/FlintstoneDB.php';
require __DIR__ . '/../src/Flintstone/FlintstoneException.php';

use Flintstone\Flintstone;

class TestFixture extends \PHPUnit_Framework_TestCase {

	/**
	 * Flintstone database
	 * @access protected
	 * @var object
	 */
	protected $db;

	/**
	 * Load the test database
	 */
	public function setUp() {
		$this->db = Flintstone::load('test', array('dir' => __DIR__));
	}

	/**
	 * Unload the test database and remove
	 */
	public function tearDown() {
		Flintstone::unload('test');
		$file = __DIR__ . '/test.dat';
		unlink($file);
	}
}