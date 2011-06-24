Flintstone
==========

A key/value database store using flat files for PHP.

Features include:

* Memory efficient
* File locking
* Caching
* Gzip compression
* Easy to use

For full documentation please visit http://www.xeweb.net/flintstone/

Requirements
-------------

* PHP 5
* Read/write file permissions

Data types
----------

Flintstone can store the following data types:

* Strings
* Integers
* Floats
* Arrays

Usage examples
---------------

	try {
	
		// Load flintstone
		$db = new Flintstone(array('dir' => '/path/to/database/dir/'));
		
		// Set keys
		$db->load('users')->set('bob', array('email' => 'bob@site.com', 'password' => '123456'));
		$db->load('users')->set('joe', array('email' => 'joe@site.com', 'password' => 'test'));
		$db->load('settings')->set('site_offline', 1);
		$db->load('settings')->set('site_back', '3 days');
		
		// Retrieve keys
		$user = $db->load('users')->get('bob');
		echo 'Bob, your email is ' . $user['email'];
		
		$offline = $db->load('settings')->get('site_offline');
		if ($offline == 1) {
			echo 'Sorry, the website is offline<br />';
			echo 'We will be back in ' . $db->load('settings')->get('site_back');
		}
		
		// Delete a key
		$db->load('users')->delete('joe');
		
		// Flush database
		$db->load('users')->flush();
	}
	catch (Exception $e) {
		echo 'Exception: ' . $e->getMessage();
	}
	

## Changelog

### 17/06/2011 - 1.0
* Initial release