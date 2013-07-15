<?php

/**
 * Flintstone - A key/value database store using flat files for PHP
 * Copyright (c) 2011 XEWeb
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @link http://www.xeweb.net/flintstone/
 * @copyright 2011 XEWeb
 * @author Jason <emailfire@gmail.com>
 * @version 1.2
 * @package flintstone
 */

class FlintstoneTbl {

	/**
	 * Stores the name.
	 * @access private
	 * @var string
	 */
	private $name;

	/**
	 * Stores the file.
	 * @access private
	 * @var string
	 */
	private $file;

	/**
	 * Stores the temporary file.
	 * @access private
	 * @var string
	 */
	private $tmpFile;

	/**
	 * Stores the record keys.
	 * @access private
	 * @var array
	 */
	private $keys;

	/**
	 * Stores the data.
	 * @access private
	 * @var array
	 */
	private $data;


	/**
	 * Constructor to initialize a new table instance.
	 *
	 * @param string $name the table's name
	 */
	public function __construct($name) {
		$this->name = $name;
	}

	/**
	 *
	 */
	public function delete($key) {}

	/**
	 *
	 */
	private function deleteKey($key) {}

	/**
	 *
	 */
	public function get($key) {}

	/**
	 *
	 */
	private function getAllKeys() {}

	/**
	 *
	 */
	public function getFile() {}

	/**
	 *
	 */
	private function getKey($key) {}

	/**
	 *
	 */
	public function getKeys() {}

	/**
	 *
	 */
	public function getTmpFile() {}

	/**
	 *
	 */
	private function isValidData($data) {}

	/**
	 *
	 */
	private function isValidKey($key) {}

	/**
	 *
	 */
	private function preserveLines($data, $reverse) {}

	/**
	 *
	 */
	public function replace($key, $data) {}

	/**
	 *
	 */
	private function replaceKey($key, $data) {}

	/**
	 *
	 */
	public function set($key, $data) {}

	/**
	 *
	 */
	public function setFile() {}

	/**
	 *
	 */
	private function setKey($key, $data) {}

	/**
	 *
	 */
	public function setTmpFile() {}
	
}
