Flintstone
==========

[![Total Downloads](https://img.shields.io/packagist/dm/fire015/flintstone.svg)](https://packagist.org/packages/fire015/flintstone)
[![Build Status](https://travis-ci.org/fire015/flintstone.svg?branch=master)](https://travis-ci.org/fire015/flintstone)

A key/value database store using flat files for PHP.

Features include:

* Memory efficient
* File locking
* Caching
* Gzip compression
* Easy to use

For full documentation please visit http://www.xeweb.net/flintstone/

### Installation

The easiest way to install Flintstone is via [composer](http://getcomposer.org/). Create the following `composer.json` file and run the `php composer.phar install` command to install it.

```json
{
    "require": {
        "fire015/flintstone": "1.*"
    }
}
```

```php
<?php
require 'vendor/autoload.php';

use Flintstone\Flintstone;

$users = Flintstone::load('users', $options);
```

### Requirements

- Any flavour of PHP 5.3+ should do
- [optional] PHPUnit to execute the test suite

### Data types

Flintstone can store the following data types:

* Strings
* Integers
* Floats
* Arrays

### Usage examples

```php
try {

	// Set options
	$options = array('dir' => '/path/to/database/dir/');

	// Load the databases
	$users = Flintstone::load('users', $options);
	$settings = Flintstone::load('settings', $options);

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
catch (FlintstoneException $e) {
	echo 'An error occured: ' . $e->getMessage();
}
```