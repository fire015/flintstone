<?php

require 'flintstone.tbl.class.php';

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

class Flintstone {

	/**
	 * Stores the tables
	 * @access private
	 * @var array
	 */
	private $tables;

	/**
	 * Flintstone options:
	 *
	 * - string		$dir				the directory to the database files (tables)
	 * - string		$ext				the database file extension
	 * - boolean	$gzip				use gzip to compress database
	 * - boolean	$cache				store get() results in memory
	 * - integer	$swap_memory_limit	write out each line to a temporary file and swap if database is larger than limit (0 to always do this)
	 *
	 * @access public
	 * @var array
	 */
	private $options;


	/**
	 * Flintstone constructor
	 * @param array $options an array of options
	 * @return void
	 */
	public function __construct($options = array()) {
		$this->options = array(
			'dir' => '',
			'ext' => '.dat',
			'gzip' => false,
			'cache' => true,
			'swap_memory_limit' => 1048576
		);

		if (!empty($options)) $this->setOptions($options);
		$this->tables = array();
	}

	/**
	 * Adds a new table to the database.
	 * If the table already is a member,
	 * null is returned.
	 *
	 * @param FlintStoneTbl $tbl the table to add
	 * @return bool added successfully
	 */
	private function addTable(FlintStoneTbl $tbl) {
		if (!array_key_exists($tbl->getName(), $this->tables)) {
			$this->tables[$tbl->getName()] = $tbl;
		}

		return null;
	}

	/**
	 * Flushs the database.
	 * If $tbls is set an there's an error flushing one of the tables,
	 * an array containing the tables's name is returned.
	 *
	 * @param bool $tbls also flush the tables
	 * @return mixed
	 */
	public function flush($tbls = false) {
		if ($tbls) {
			$fails = array();

			foreach ($this->tables as $tbl) {
				if (!$tbl->flush()) {
					array_push($fails, $tbl->getName());
				}
			}
		}

		$this->tables = array();

		return count($fails) > 0 ? $fails : true;
	}

	/**
	 * Returns the options.
	 *
	 * @return array $options the options
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * Checks if the database is empty.
	 * The database is empty if there are no tables.
	 *
	 * @return bool if it's empty
	 */
	public function isEmpty() {
		return count($this->tables) > 0;
	}

	/**
	 * Loads the table.
	 *
	 * @param string $table the table name
	 * @return FlintStoneTbl the table
	 */
	public function load($table) {

		// Check database directory
		if (empty($this->options['dir'])) {
			throw new Exception('Database directory has not been set');
		}

		if (!is_dir($this->options['dir'])) {
			throw new Exception($this->options['dir'] . ' is not a valid directory');
		}

		// Check valid characters in table name
		if (!preg_match("/^([A-Za-z0-9_]+)$/", $table)) {
			throw new Exception('Invalid characters in table name');
		}

		// Check table data
		if (!array_key_exists($table, $this->tables)) {

			// Set table data
			$dir = $this->options['dir'];
			$ext = $this->options['ext'];
			if (substr($ext, 0, 1) !== ".") $ext = "." . $ext;
			if (substr($dir, -1) !== DIRECTORY_SEPARATOR) $dir .= DIRECTORY_SEPARATOR;
			if ($this->options['gzip'] === true && substr($ext, -3) !== ".gz") $ext .= ".gz";

			// Create new table object
			$tbl = new FlintStoneTbl(
				$table,
				$dir . $table . $ext,
				$dir . $table . "_tmp" . $ext,
				array(
					$this->options['gzip'],
					$this->options['cache'],
					$this->options['swap_memory_limit']
				)
			);

			// Check if file was loaded
			$this->addTable($tbl);
		}

		return $this->tables[$table];
	}

	/**
	 * Set flintstone options
	 * @param array $options an array of options
	 * @return void
	 */
	private function setOptions($options) {
		foreach ($options as $key => $value) {
			$this->options[$key] = $value;
		}
	}

}
