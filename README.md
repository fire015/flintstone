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
		
		// Load the databases
		$users = $db->load('users');
		$settings = $db->load('settings');
		
		// Set keys
		$users->set('bob', array('email' => 'bob@site.com', 'password' => '123456'));
		$users->set('joe', array('email' => 'joe@site.com', 'password' => 'test'));
		$settings->set('site_offline', 1);
		$settings->set('site_back', '3 days');
		
		// Retrieve keys
		$user = $users->get('bob');
		echo 'Bob, your email is ' . $user['email'];
		
		$offline = $settings->get('site_offline');
		if ($offline == 1) {
			echo 'Sorry, the website is offline<br />';
			echo 'We will be back in ' . $settings->get('site_back');
		}
			
		// Retrieve all key names
		$keys = $users->getKeys(); // returns array('bob', 'joe', ...)
		
		foreach ($keys as $username) {
			$user = $users->get($username);
			echo $username.', your email is ' . $user['email'];
			echo $username.', your password is ' . $user['password'];
		}
		
		// Delete a key
		$users->delete('joe');
		
		// Flush the database
		$users->flush();
	}
	catch (Exception $e) {
		echo 'Exception: ' . $e->getMessage();
	}
	

## Changelog

### 23/01/2013 - 1.2
* Removed the multibyte unserialize method as it seems to work without

### 22/06/2012 - 1.1
* Added new method getKeys() to return an array of keys in the database (thanks to sinky)

### 17/06/2011 - 1.0
* Initial release