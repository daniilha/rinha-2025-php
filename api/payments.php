<?php

ob_start();
// do initial processing here
echo '200'; // send the response
header('Connection: close');
header('Content-Length: ' . ob_get_length());
ob_end_flush();
@ob_flush();
flush();
fastcgi_finish_request();

$input = file_get_contents('php://input');
$payment  = json_decode($input, true);
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
