<?php

// $mc = new Memcached();
// $mc->addServer('mymemcached', 11211);
apcu_add('key1', 'value1');
apcu_add('key2', 'value2');
apcu_add('key3', 'value3');

echo 'key1 : ' . apcu_fetch('iterator') . "\n";
echo 'key2 : ' . apcu_fetch('lock') . "\n";
echo 'key3 : ' . apcu_fetch(0) . "\n";

echo 'key3 : ' . apcu_fetch('key3') . "\n";

echo'olá';
var_dump($_GET);
var_dump($_POST);

phpinfo();
