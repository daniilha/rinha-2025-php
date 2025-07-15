<?php

date_default_timezone_set('UTC');
$now = DateTime::createFromFormat('U.u', microtime(true));
$d = $now->format('Y-m-d\TH:i:s.u\Z');

$asd = file_get_contents('php://input');
$payment  = json_decode($asd, true);
$processor = 'unset';
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
$dbconn = pg_connect('host=api-db port=5432 dbname=rinha user=postgres password=postgres');
$result = pg_query($dbconn, 'select * from payments');

$query= "insert INTO payments 
(correlationId,amount,requested_at,processor,operation) 
VALUES ('" . $payment['correlationId'] . "'," . $payment['amount'] . ",'" . $d . "','" . $processor . "','incoming')";
$result = pg_query($dbconn, $query);
// }
echo '
';
