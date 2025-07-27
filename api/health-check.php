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
$timeout = 10000;

$i = 0;

while ($i<100) {
	++$i;
	$servicequery = 'SELECT * FROM services';
	$re = $dbconn->query($servicequery);
	$svc = $re->fetchAll(\PDO::FETCH_ASSOC);
	if (count($svc)==0) {
		$servicequery= "INSERT INTO services (ds) VALUES ('default'),('fallback') ON CONFLICT DO NOTHING";
		$dbconn->query($servicequery);
	} else {
		$svcs = array_combine(array_column($svc, 'ds'), $svc);
		if ($svcs['default']['lock']!==true && (time() - $svcs['default']['last_update'])>5) {
			$servicequery= "UPDATE services SET lock = TRUE WHERE ds LIKE ('default')";
			$dbconn->query($servicequery);

			$ch = curl_init('http://payment-processor-default:8080/payments/service-health');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $timeout);
			curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);
			$result = curl_exec($ch);
			$res=json_decode($result, true);
			// print_r($res);
			// die;
			$res['failing'] = $res['failing'] ? 1 : 0;
			$last =time();
			$servicequery= "UPDATE services SET lock = FALSE , failing ={$res['failing']}, rs_delay = {$res['minResponseTime']}, last_update = {$last} WHERE ds LIKE ('default')";
			$dbconn->query($servicequery);
		}

		if ($svcs['fallback']['lock']!==true && (time() - $svcs['fallback']['last_update'])>5) {
			$servicequery= "UPDATE services SET lock = TRUE WHERE ds LIKE ('fallback')";
			$dbconn->query($servicequery);

			$ch = curl_init('http://payment-processor-fallback:8080/payments/service-health');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $timeout);
			curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);
			$result = curl_exec($ch);
			$res=json_decode($result, true);
			// print_r($res);
			// die;
			$res['failing'] = $res['failing'] ? 1 : 0;
			$last =time();
			$servicequery= "UPDATE services SET lock = FALSE , failing ={$res['failing']}, rs_delay = {$res['minResponseTime']}, last_update = {$last} WHERE ds LIKE ('fallback')";
			$dbconn->query($servicequery);
		}
		// $dbconn = null;
	}
	// sleep(5);
}
