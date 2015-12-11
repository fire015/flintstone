<?php

require 'vendor/autoload.php';

use Flintstone\Config;
use Flintstone\Flintstone;

$config = array(
	'dir' => __DIR__,
	'cache' => false
);

$db = new Flintstone('users', $config);

for ($i = 1; $i < 50; $i++) {
	//$db->set('user_' . $i, array('name' => 'Jason', 'age' => $i));
}

print_r($db->get('user_1'));

$db->set('user_20', array('name' => 'Bob', 'age' => date('r')));

print_r($db->get('user_20'));