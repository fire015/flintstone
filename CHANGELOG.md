Change Log
==========
### 30/09/2014 - 1.6
* Updated limits on valid characters in key name and size
* Improved unit tests

### 29/05/2014 - 1.5
* Reduced some internal complexity
* Fixed gzip compression
* Unit tests now running against all options
* Removed `setOptions` method, must be passed into the `load` method

### 11/03/2014 - 1.4
* Now using Composer

### 16/07/2013 - 1.3
* Changed the load method to static so that multiple instances can be loaded without conflict (use Flintstone::load now instead of $db->load)
* Exception thrown is now FlintstoneException

### 23/01/2013 - 1.2
* Removed the multibyte unserialize method as it seems to work without

### 22/06/2012 - 1.1
* Added new method getKeys() to return an array of keys in the database (thanks to sinky)

### 17/06/2011 - 1.0
* Initial release