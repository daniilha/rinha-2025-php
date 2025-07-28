<?php

require './connection.php';

if ($_GET!==null) {
	$to = $_GET['to'];
	$from = $_GET['from'];
} else {
	$to = null;
	$from=null;
}

date_default_timezone_set('UTC');

$dtto = date($to);

$dtfrom = date($from);

$dbconn = Connection::connect();
$query= "select COUNT(completed_payments.\"correlationId\") as total, SUM(amount) as amount, processor from completed_payments WHERE processor NOT LIKE 'unset'";
// $result = pg_query($dbconn, $query);
if (isset($to) && isset($from)) {
	$query.=" AND requested_at BETWEEN '{$from}' AND '{$to}' ";
}
$query.='GROUP BY processor';

$result = $dbconn->query($query);

$data = $result->fetchAll(\PDO::FETCH_ASSOC);

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
