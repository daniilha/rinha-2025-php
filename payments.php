<?php
$asd = file_get_contents('php://input');
$payment  = json_decode($asd, true);
// echo($da['amount']);
// echo($da['correlationId']);

$ch = curl_init('http://payment-processor-default:8080/payments');
// Setup request to send json via POST.
// curl_setopt($ch, CURLOPT_URL, );

$payload = (file_get_contents('php://input'));
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
// Return response instead of printing.
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
// curl_setopt($ch, CURLOPT_VERBOSE, 1);

// var_dump($ch);

// Send request.
// $result = curl_exec($ch);
// curl_close($ch);
// Print response.
// echo "<pre>$result</pre>";


$d = date('Y-m-d H:i:s.u', time());


$dbconn = pg_connect('host=api-db port=5432 dbname=rinha user=postgres password=postgres');
// var_dump($dbconn);
$result = pg_query($dbconn, 'select * from payments');
var_dump($result);

$query= "insert INTO payments 
(correlationId,amount,requested_at,processor) 
VALUES ('".$payment['correlationId']."',".$payment['amount'].",'".$d."','default')";
echo $query;
$result = pg_query($dbconn, $query);

var_dump($result);
