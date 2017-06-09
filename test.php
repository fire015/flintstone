<?php

require 'vendor/autoload.php';

use Flintstone\Flintstone;

$db = new Flintstone('test', [
    'dir' => __DIR__,
    'cache' => false
]);

$start = microtime(true);

/*
for ($i = 1; $i <= 10000; $i++) {
    $db->set('user_' . $i, 'this is user ' . $i);
}
*/

//$db->delete('user_400');

var_dump($db->get('user_4000'));

echo 'Time: ' . (microtime(true) - $start) . PHP_EOL;
echo 'Memory: ' . memory_get_peak_usage() . PHP_EOL;