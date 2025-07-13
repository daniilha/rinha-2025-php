<?php
$asd = file_get_contents('php://input');
$da  = json_decode($asd, true);
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
$result = curl_exec($ch);
curl_close($ch);
// Print response.
echo "<pre>$result</pre>";
