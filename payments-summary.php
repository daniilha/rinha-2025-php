<?php

// echo'olá';
// var_dump($_GET);
$to = $_GET['to'];
$from = $_GET['from'];

date_default_timezone_set('UTC');

$dtto = date($to);

$dtfrom = date($from);

$dbconn = pg_connect('host=api-db port=5432 dbname=rinha user=postgres password=postgres');
$query= "select COUNT(correlationid) as total, SUM(amount) as amount, processor from payments WHERE requested_at BETWEEN '{$from}' AND '{$to}' GROUP BY processor";
$result = pg_query($dbconn, $query);

$data = pg_fetch_all($result);

$final=[];
foreach ($data as $row) {
	$final[$row['processor']]=['totalAmount'=>$row['amount'],'totalRequests'=>$row['total']];
}
if (empty($final['default'])) {
	$final['default']=['totalAmount'=>0.0,'totalRequests'=>0];
}
if (empty($final['fallback'])) {
	$final['fallback']=['totalAmount'=>0.0,'totalRequests'=>0];
}
echo json_encode($final);
