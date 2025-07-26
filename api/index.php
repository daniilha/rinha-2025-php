<?php

require './connection.php';
try {
	$dbconn = Connection::connect();
} catch (Exception $e) {
	echo PHP_EOL;
	echo 'DB indisponivel, tentando novamente...';
	sleep(1);
	die;
}

// $mc = new Memcached();
// $mc->addServer('mymemcached', 11211);

$servicequery = 'SELECT * FROM services';
$re = $dbconn->query($servicequery);
$svc = $re->fetchAll();
$svcs = array_combine(array_column($svc, 'ds'), $svc);

print_r($svcs);

phpinfo();
