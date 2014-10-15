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

$users = Flintstone::load('users', array('dir' => '/path/to/database/dir/'));
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

### Options

|Name				|Type		|Default Value	|Description														|
|---				|---		|---					|---														|
|dir				|string		|null					|the directory where the database files are stored			|
|ext				|string		|.dat					|the database file extension to use							|
|gzip				|boolean	|false					|use gzip to compress the database							|
|cache				|boolean	|true					|store get() results in memory								|
|formatter			|object		|SerializeFormatter		|the formatter class used to encode/decode data				|
|swap_memory_limit	|integer	|1048576				|amount of memory to use before writing to a temporary file	|


### Usage examples

```php
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
```

### Changing the formatter
By default Flintstone will encode/decode data using PHP's serialize functions, however you can override this with your own class if you prefer.

Just make sure it implements `Flintstone\Formatter\FormatterInterface` and then you can provide it as the `formatter` option.

If you wish to use JSON as the formatter, Flintstone already ships with this as per the example below:

```php
<?php
require 'vendor/autoload.php';

use Flintstone\Flintstone;
use Flintstone\Formatter\JsonFormatter;

$users = Flintstone::load('users', array(
	'dir' => __DIR__,
	'formatter' => new JsonFormatter()
));
```

### Who is using Flintstone?

- [Key-Value Store](https://github.com/adammbalogh/key-value-store)
