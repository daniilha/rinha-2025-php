<?php

require './connection.php';
$dbconn = Connection::connect();
$host = gethostname();
while (true) {
	$dbconn->beginTransaction();
	$query= "UPDATE payments SET processor = '{$host}', operation = 'busy' WHERE payments.\"correlationId\" IN (select payments.\"correlationId\" as \"correlationId\"	from payments where operation like 'incoming'  
	ORDER BY requested_at ASC 
	LIMIT 25 FOR UPDATE)";
	$result = $dbconn->query($query);
	$dbconn->commit();
	$result = $dbconn->query('select payments."correlationId" as "correlationId", amount,requested_at, processor, operation 
	from payments where operation like \'busy\' AND processor = \'' . $host . '\' 
	ORDER BY requested_at ASC 
	LIMIT 25 FOR UPDATE');
	$all = $result->fetchAll();
	// var_dump($all);
	if (!empty($all[0])) {
		$query= "UPDATE payments SET operation = 'incoming' WHERE operation = 'failed'";
		$dbconn->query($query);
		$ids = [];
		foreach ($all as $row) {
			if (!empty($row['correlationId'])) {
				$ids[]=$row['correlationId'];
			}
		}
		$idin = implode("','", $ids);
		if (!empty($idin)) {
		}
		$timeout = 500;
		// echo "\n";
		// echo "a : {$all[0]['correlationId']} host : {$host}";
		foreach ($all as $row) {
			if (!empty($row['correlationId'])) {
				// $row = pg_fetch_row($result);
				$processor = 'default';
				$ch = curl_init('http://payment-processor-default:8080/payments');

				$rq = (new DateTime($row['requested_at']));
				$rqd = $rq->format('Y-m-d\TH:i:s.u\Z');
				$payload = ['correlationId'=>$row['correlationId'],'amount'=>$row['amount'],'requestedAt'=>$rqd];

				$payload=json_encode($payload);
				// echo $payload;
				curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
				curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $timeout);
				curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);

				$cresult = curl_exec($ch);

				$curl_error = curl_error($ch);

				if (curl_errno($ch)) {
					$err= curl_errno($ch);
					echo "\n";
					echo "default error : {$err}";
				}

				// $msg=$result;
				// var_dump($msg);
				curl_close($ch);

				if (empty($cresult)) {
					$processor = 'fallback';
					$ch = curl_init('http://payment-processor-fallback:8080/payments');
					// $payload = (file_get_contents('php://input'));
					curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
					curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $timeout);
					curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);

					$cresult = curl_exec($ch);

					if (curl_errno($ch)) {
						$err= curl_errno($ch);
						echo "\n";
						echo "fallback error : {$err}";
					}

					curl_close($ch);
				}

				if (!empty($cresult)) {
					// $dbconn = pg_connect('host=api-db port=5432 dbname=rinha user=postgres password=postgres');
					// $result = pg_query($dbconn, 'select * from payments');

					$query= "UPDATE payments SET processor = '{$processor}', operation = 'completed' WHERE payments.\"correlationId\" = '{$row['correlationId']}'";
					$result = $dbconn->query($query);

					$msg=json_decode($cresult, true);
					if ($msg['message']!='payment processed successfully') {
						print_r("\n" . $row['correlationId']);
					}
					// sleep(1);
					// echo "<br />\n";
					// echo "correlationId: {$row['correlationId']}  amount: {$row['amount']}";
				} else {
					$query= "UPDATE payments SET operation = 'failed' WHERE payments.\"correlationId\" = '{$row['correlationId']}'";
					$result = $dbconn->query($query);
				}
			}
		}
	} else {
		usleep(1);
	}
}
