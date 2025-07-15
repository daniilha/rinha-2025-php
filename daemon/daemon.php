<?php

require './connection.php';

while (true) {
	$dbconn = Connection::connect();
	$result = $dbconn->query('select correlationid, amount,requested_at, processor, operation 
	from payments where operation like (CASE WHEN (SELECT COUNT(*) FROM payments WHERE operation LIKE \'busy\') > 0 THEN \'busy\' ELSE \'incoming\' END) 
	ORDER BY requested_at ASC 
	LIMIT 50 ');

	if ($result) {
		$all = $result->fetchAll();
		$query= "UPDATE payments SET operation = 'incoming' WHERE operation = 'failed'";
		$dbconn->query($query);
		$ids = [];
		foreach ($all as $row) {
			if (!empty($row['correlationid'])) {
				$ids[]=$row['correlationid'];
			}
		}
		$idin = implode("','", $ids);
		if (!empty($idin)) {
			$query= "UPDATE payments SET operation = 'busy' WHERE correlationid IN ('{$idin}')";
			$result = $dbconn->query($query);
		}
		// var_dump($all);

		foreach ($all as $row) {
			if (!empty($row['correlationid'])) {
				// $row = pg_fetch_row($result);
				$processor = 'default';
				$ch = curl_init('http://payment-processor-default:8080/payments');

				$rq = (new DateTime($row['requested_at']));
				$rqd = $rq->format('Y-m-d\TH:i:s.u\Z');
				$payload = ['correlationId'=>$row['correlationid'],'amount'=>$row['amount'],'requestedAt'=>$rqd];

				$payload=json_encode($payload);
				// echo $payload;
				curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
				curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 500);
				curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);

				$result = curl_exec($ch);

				$curl_error = curl_error($ch);

				if (curl_errno($ch)) {
					$err= curl_errno($ch);
					echo "\n";
					echo "default error : {$err}";
				}

				// $msg=$result;
				// var_dump($msg);
				curl_close($ch);

				if (empty($result)) {
					$processor = 'fallback';
					$ch = curl_init('http://payment-processor-fallback:8080/payments');
					// $payload = (file_get_contents('php://input'));
					curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
					curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 500);
					curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);

					$result = curl_exec($ch);

					if (curl_errno($ch)) {
						$err= curl_errno($ch);
						echo "\n";
						echo "fallback error : {$err}";
					}

					curl_close($ch);
				}

				if (!empty($result)) {
					// $dbconn = pg_connect('host=api-db port=5432 dbname=rinha user=postgres password=postgres');
					// $result = pg_query($dbconn, 'select * from payments');

					$query= "UPDATE payments SET processor = '{$processor}', operation = 'completed' WHERE correlationid = '{$row['correlationid']}'";
					$result = $dbconn->query($query);
					// echo "<br />\n";
					// echo "correlationid: {$row['correlationid']}  amount: {$row['amount']}";
				} else {
					$query= "UPDATE payments SET operation = 'failed' WHERE correlationid = '{$row['correlationid']}'";
					$result = $dbconn->query($query);
				}
			}
		}
	} else {
		usleep(1);
	}
}
