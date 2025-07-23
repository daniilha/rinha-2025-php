<?php

require './connection.php';

date_default_timezone_set('UTC');
$now = DateTime::createFromFormat('U.u', microtime(true));
$d = $now->format('Y-m-d\TH:i:s.u\Z');

$asd = file_get_contents('php://input');
$payment  = json_decode($asd, true);
$processor = 'unset';
$pay = [];
$pay['correlationId'] = $payment['correlationId'];
$pay['amount'] =  $payment['amount'];
apcu_fetch('lock', $success);
if ($success) {
	while ($success) {
		apcu_fetch('lock', $success);
		usleep(random_int(1, 3));
	}
}
$iterator = apcu_fetch('iterator');
if ($iterator === false) {
	$iterator = 0;
	apcu_add('iterator', $iterator);
}
$payload = json_encode($pay);
apcu_add($iterator . '', $payload);
apcu_inc('iterator');
// $ch = curl_init('http://payment-processor-default:8080/payments');
// $payload = (file_get_contents('php://input'));
// $payload = json_decode($payload, true);
// $payload['requestedAt']= $d;
// $payload=json_encode($payload);
// curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
// curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 180);
// curl_setopt($ch, CURLOPT_TIMEOUT_MS, 180);

// $result = curl_exec($ch);

// $curl_error = curl_error($ch);

// $msg=$result;
// // var_dump($msg);
// curl_close($ch);

// if (empty($result)) {
// 	$processor = 'fallback';
// 	$ch = curl_init('http://payment-processor-fallback:8080/payments');
// 	// $payload = (file_get_contents('php://input'));
// 	curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
// 	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
// 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// 	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 180);
// 	curl_setopt($ch, CURLOPT_TIMEOUT_MS, 180);

// 	$result = curl_exec($ch);
// 	curl_close($ch);
// }

// if (!empty($result)) {
// $dbconn = Connection::connect();
// // $result = pg_query($dbconn, 'select * from payments');

// $query= "insert INTO payments
// (\"correlationId\",amount,requested_at,processor,operation)
// VALUES ('" . $payment['correlationId'] . "'," . $payment['amount'] . ",'" . $d . "','" . $processor . "','incoming')";
// $result = $dbconn->query($query);
// // }
// $dbconn = null;
echo '
';

gc_collect_cycles();
gc_mem_caches();
