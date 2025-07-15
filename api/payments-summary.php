<?php

require './connection.php';
// echo'olá';
// var_dump($_GET);
$to = $_GET['to'];
$from = $_GET['from'];

date_default_timezone_set('UTC');

$dtto = date($to);

$dtfrom = date($from);

$dbconn = Connection::connect();
$query= "select COUNT(correlationid) as total, SUM(amount) as amount, processor from payments WHERE processor NOT LIKE 'unset' AND requested_at BETWEEN '{$from}' AND '{$to}' GROUP BY processor";
// $result = pg_query($dbconn, $query);

$result = $dbconn->query($query);

$data = $result->fetchAll();

$final=[];
foreach ($data as $row) {
	$final[$row['processor']]=['totalAmount'=>(float) $row['amount'],'totalRequests'=>(int) $row['total']];
}
if (empty($final['default'])) {
	$final['default']=['totalAmount'=>0.0,'totalRequests'=>0];
}
if (empty($final['fallback'])) {
	$final['fallback']=['totalAmount'=>0.0,'totalRequests'=>0];
}
header('Content-Type: application/json; charset=utf-8');

echo json_encode($final);
