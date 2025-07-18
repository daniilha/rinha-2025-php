<?php

require './connection.php';
$dbconn = Connection::connect();
$host = gethostname();
while (true) {
	$dbconn->beginTransaction();
	$query= "UPDATE payments SET processor = '{$host}', operation = 'busy' WHERE payments.\"correlationId\" IN (select payments.\"correlationId\" as \"correlationId\"	from payments where operation like 'incoming'  
	ORDER BY requested_at ASC 
	LIMIT 10 FOR UPDATE)";
	$result = $dbconn->query($query);
	$dbconn->commit();
	$result = $dbconn->query('select payments."correlationId" as "correlationId", amount,requested_at, processor, operation 
	from payments where operation like \'busy\' AND processor = \'' . $host . '\' 
	ORDER BY requested_at ASC 
	LIMIT 20');
	$all = $result->fetchAll();
	// var_dump($all);
	if (!empty($all[0])) {
		$query= "UPDATE payments SET operation = 'incoming' WHERE operation = 'failed'";
		$dbconn->query($query);
		$ids = [];
		foreach ($all as $row) {
			if (!empty($row['correlationId'])) {
				$ids[$row['correlationId']]=[];
			}
		}
		// $idin = implode("','", $ids);
		// if (!empty($idin)) {
		// }
		$timeout = 500;
		// echo "\n";
		// echo "a : {$all[0]['correlationId']} host : {$host}";
		$mh = curl_multi_init();

		foreach ($all as $row) {
			if (!empty($row['correlationId'])) {
				// $row = pg_fetch_row($result);

				$ids[$row['correlationId']]['processor'] = 'default';
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
				$ids[$row['correlationId']]['handle']=$ch;

				curl_multi_add_handle($mh, $ch);
			}
		}
		do {
			curl_multi_exec($mh, $unfinishedHandles);
			usleep(1);
		} while ($unfinishedHandles);
		curl_multi_close($mh);
		$mh2 = curl_multi_init();
		$fallback = [];

		foreach ($all as $row) {
			$handle = $ids[$row['correlationId']]['handle'];
			$cresult = curl_multi_getcontent($handle);
			$msg=json_decode($cresult, true);
			if (!empty($cresult)) {
				// $dbconn = pg_connect('host=api-db port=5432 dbname=rinha user=postgres password=postgres');
				// $result = pg_query($dbconn, 'select * from payments');

				// if (curl_getinfo($cresult, CURLOPT_URL) == 'http://payment-processor-default:8080/payments') {
				$processor = $ids[$row['correlationId']]['processor'];
				// } else {
				// 	$processor = curl_getinfo($handle, CURLINFO_URL);
				// }

				$query= "UPDATE payments SET processor = '{$processor}', operation = 'completed' WHERE payments.\"correlationId\" = '{$row['correlationId']}'";

				$result = $dbconn->query($query);

				if ($msg['message']!='payment processed successfully') {
					echo PHP_EOL, PHP_EOL;
					print_r("\n" . $row['correlationId']);
				}
				// sleep(1);
				// echo "<br />\n";
				// echo "correlationId: {$row['correlationId']}  amount: {$row['amount']}";
			} else {
				$ids[$row['correlationId']]['processor'] = 'fallback';
				$ch = curl_init('http://payment-processor-fallback:8080/payments');
				// $payload = (file_get_contents('php://input'));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
				curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $timeout);
				curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);

				$ids[$row['correlationId']]['handle']=$ch;
				$fallback[]=$row;
				// $cresult = curl_exec($ch);
				curl_multi_add_handle($mh2, $ch);
			}
		}

		do {
			curl_multi_exec($mh2, $unfinishedHandles);
			usleep(1);
		} while ($unfinishedHandles);

		curl_multi_close($mh2);

		foreach ($fallback as $row) {
			$handle = $ids[$row['correlationId']]['handle'];
			$cresult = curl_multi_getcontent($handle);
			$msg=json_decode($cresult, true);
			// if (!empty($cresult)) {
			if (!empty($cresult)&&$msg['message']==='payment processed successfully') {
				// $dbconn = pg_connect('host=api-db port=5432 dbname=rinha user=postgres password=postgres');
				// $result = pg_query($dbconn, 'select * from payments');

				// if (curl_getinfo($cresult, CURLOPT_URL) == 'http://payment-processor-default:8080/payments') {
				$processor = $ids[$row['correlationId']]['processor'];

				// } else {
				// 	$processor = curl_getinfo($handle, CURLINFO_URL);
				// }

				$query= "UPDATE payments SET processor = '{$processor}', operation = 'completed' WHERE payments.\"correlationId\" = '{$row['correlationId']}'";

				$result = $dbconn->query($query);

				$msg=json_decode($cresult, true);
				if ($msg['message']!='payment processed successfully') {
					// echo PHP_EOL, PHP_EOL;

					var_dump($all);
					die;
				}
				// sleep(1);
				// echo "<br />\n";
				// echo "correlationId: {$row['correlationId']}  amount: {$row['amount']}";
				// }
			} else {
				$query= "UPDATE payments SET operation = 'failed' WHERE payments.\"correlationId\" = '{$row['correlationId']}'";
				$result = $dbconn->query($query);
			}
		}
	} else {
		usleep(1);
	}
}
