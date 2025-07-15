<?php

while (true) {
	$dbconn = pg_connect('host=api-db port=5432 dbname=rinha user=postgres password=postgres');
	$result = pg_query($dbconn, 'select correlationid, amount,requested_at, processor, operation from payments where operation like \'incoming\' ORDER BY requested_at ASC LIMIT 5 ');
	if ($result) {
		$all = pg_fetch_all($result);

		$ids = [];
		foreach ($all as $row) {
			if (!empty($row['correlationid'])) {
				$ids[]=$row['correlationid'];
			}
		}
		$idin = implode(',', $ids);
		$query= "UPDATE payments SET operation = 'busy' WHERE correlationid IN '{$idin}'";
		$result = pg_query($dbconn, $query);

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

				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 1500);
				curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1500);

				$result = curl_exec($ch);

				$curl_error = curl_error($ch);

				$msg=$result;
				// var_dump($msg);
				curl_close($ch);

				if (empty($result)) {
					$processor = 'fallback';
					$ch = curl_init('http://payment-processor-fallback:8080/payments');
					// $payload = (file_get_contents('php://input'));
					curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
					curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 1500);
					curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1500);

					$result = curl_exec($ch);
					curl_close($ch);
				}

				if (!empty($result)) {
					// $dbconn = pg_connect('host=api-db port=5432 dbname=rinha user=postgres password=postgres');
					// $result = pg_query($dbconn, 'select * from payments');

					$query= "UPDATE payments SET processor = '{$processor}', operation = 'completed' WHERE correlationid = '{$row['correlationid']}'";
					$result = pg_query($dbconn, $query);
					echo "<br />\n";
					echo "correlationid: {$row['correlationid']}  amount: {$row['amount']}";
				}
			}
		}
	} else {
		usleep(1);
	}
}
