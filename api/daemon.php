<?php

require './connection.php';
try {
	$dbconn = Connection::connect();
} catch (Exception $e) {
	echo PHP_EOL;
	echo 'DB indisponivel, tentando novamente...';
	usleep(100);
	die;
}
$host = gethostname();
$daemon =  random_int(1, 100) . '-' . uniqid();
$i = 0;
$limit = 10;

$query= "UPDATE payments SET  operation = 'incoming' WHERE payments.processor = '{$host}'";
$result = $dbconn->query($query);

$selectquery = $dbconn->prepare("select payments.\"correlationId\" as \"correlationId\", amount,requested_at, processor, operation 
from payments where operation like 'busy' AND daemon = '{$daemon}' 
LIMIT {$limit}");

$updatequery = $dbconn->prepare("UPDATE payments SET processor = '{$host}', daemon = '{$daemon}', operation = 'busy'  FROM (select payments.\"correlationId\" as \"correlationId\"	from payments where operation like 'incoming'  
ORDER BY requested_at ASC 
LIMIT {$limit} FOR UPDATE) AS pay WHERE payments.\"correlationId\" = pay.\"correlationId\"");

// $deletequery= "DELETE FROM payments WHERE payments.\"correlationId\" IN ('?')";

// $insertquery= 'INSERT INTO completed_payments ("correlationId",amount,requested_at,processor) VALUES ? ON CONFLICT DO NOTHING';

while (true &&$i<1000) {
	++$i;
	// $dbconn->beginTransaction();

	$result = $selectquery->execute();
	if ($result) {
		$all = $selectquery->fetchAll();

		$n = count($all);

		if ($n < $limit) {
			// 		$query= "UPDATE payments SET daemon = '{$host}', operation = 'busy'  FROM (select payments.\"correlationId\" as \"correlationId\"	from payments where operation like 'incoming'
			// ORDER BY requested_at ASC
			// LIMIT {$limit} FOR UPDATE) AS pay WHERE payments.\"correlationId\" = pay.\"correlationId\"";
			// 		$result = $dbconn->query($query);
			$result = $updatequery->execute();
		}
		// $dbconn->commit();

		// var_dump($all);
		if (!empty($all[0])) {
			// $query= "UPDATE payments SET operation = 'incoming' WHERE operation = 'failed'";
			// $dbconn->query($query);
			$ids = [];
			foreach ($all as $row) {
				if (!empty($row['correlationId'])) {
					$ids[$row['correlationId']]=[];
				}
			}
			// $idin = implode("','", $ids);
			// if (!empty($idin)) {
			// }
			$timeout = 600;
			// echo "\n";
			// echo "a : {$all[0]['correlationId']} host : {$host}";
			$mh = curl_multi_init();
			$handles = [];
			foreach ($all as $row) {
				if (!empty($row['correlationId'])) {
					// $row = pg_fetch_row($result);

					$ids[$row['correlationId']]['processor'] = 'default';
					$ch = curl_init('http://payment-processor-default:8080/payments');

					// $rq = (new DateTime($row['requested_at']));

					$rq = DateTime::createFromFormat('U.u', microtime(true));
					// $d = $now->format('Y-m-d\TH:i:s.u\Z');

					$rqd = $rq->format('Y-m-d\TH:i:s.u\Z');
					$payload = ['correlationId'=>$row['correlationId'],'amount'=>$row['amount'],'requestedAt'=>$rqd];

					$ids[$row['correlationId']]['payload']=$payload;
					$ids[$row['correlationId']]['correlationId']=$row['correlationId'];
					$payload=json_encode($payload);

					// echo $payload;
					curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
					curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $timeout);
					curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);
					$ids[$row['correlationId']]['handle']=$ch;

					// $ids[$row['correlationId']]['handle']=$row['correlationId'];

					$handles[]= $ch;
					curl_multi_add_handle($mh, $ch);
				}
			}
			$updids = [];

			do {
				$status = curl_multi_exec($mh, $activeCount);
				if ($status == CURLM_OK && $activeCount) {
					curl_multi_select($mh);

					while ($done = curl_multi_info_read($mh)) {
						$info = curl_getinfo($done['handle']);

						$output = curl_multi_getcontent($done['handle']);

						// echo PHP_EOL;
						// print_r($info);

						// echo PHP_EOL;
						$msg=json_decode($output, true);
						// print_r($output);
						// $cresult = curl_multi_getcontent($handle);
						if (!empty($msg['message'])) {
							// foreach ($ids as $idcor => $handles) {
							// $handles = array_keys($done['handle'], $ids, 'handle');
							$idcor = (array_search($done['handle'], array_column($ids, 'handle', 'correlationId')));
							// die;
							// $idcor = array_search($done['handle'], )['correlationId'];
							// var_dump($idcor);
							// if ($handles['handle']==$done['handle']) {
							$updids[]=$idcor;
							// $handle = $ids[$idcor]['handle'];
							// $cresult = curl_multi_getcontent($handle);
							// $msg=json_decode($cresult, true);
							// if (!empty($msg['message'])&&$msg['message']==='payment processed successfully') {
							// $payload=$ids[$idcor]['payload'];
							// $query = 'INSERT INTO completed_payments ("correlationId",amount,requested_at,processor) VALUES ' .
							// "('" . $payload['correlationId'] . "'," . $payload['amount'] . ",'" . $payload['requestedAt'] . "','default')
							//  ON CONFLICT DO NOTHING; ";

							// $result = $dbconn->query($query);
							// // 	// }
							// // 	$updids[] = $row['correlationId'];
							// $query = "DELETE FROM payments WHERE payments.\"correlationId\" = ('{$row['correlationId']}'); ";
							// $result = $dbconn->query($query);
						}
						// request successful.  process output using the callback function.
						// $callback($output, $info);

						// if (isset($urls[$i + 1])) {
						// 	// start a new request (it's important to do this before removing the old one)
						// 	$ch = curl_init();
						// 	$options[CURLOPT_URL] = $urls[$i++];  // increment i
						// 	curl_setopt_array($ch, $options);
						// 	curl_multi_add_handle($master, $ch);
						// }

						// // remove the curl handle that just completed
						// curl_multi_remove_handle($master, $done['handle']);
					}

					// Wait some time before checking again:
				} else {
					break;
				}
			} while ($activeCount>0);
			if (!empty($resqueryas)) {
				$result = $dbconn->query($resqueryas);
			}

			if (!empty($updids)) {
				// $upd = implode("','", $updids);
				// $query= "DELETE FROM payments WHERE payments.\"correlationId\" IN ('{$upd}')";
				// $result = $dbconn->query($query);
				// $query= 'INSERT INTO completed_payments ("correlationId",amount,requested_at,processor) VALUES ';
				// $inserts = [];
				// foreach ($updids as $ins) {
				// 	$payload = $ids[$ins]['payload'];
				// 	$querystring = "('" . $payload['correlationId'] . "'," . $payload['amount'] . ",'" . $payload['requestedAt'] . "','default')";
				// 	$inserts[]=$querystring;
				// }
				// $in = implode(', ', $inserts);
				// $query.= $in;
				// $query.=' ON CONFLICT DO NOTHING';
				// $result = $dbconn->query($query);
			}

			curl_multi_close($mh);
			$mh2 = curl_multi_init();
			$fallback = [];
			// $updids = [];
			foreach ($all as $row) {
				if (!in_array($row['correlationId'], $updids)) {
					$handle = $ids[$row['correlationId']]['handle'];
					$cresult = curl_multi_getcontent($handle);
					$msg=json_decode($cresult, true);
					// if ($msg) {
					// 	if ($msg['message']!=='payment processed successfully') {
					// 		echo $msg['message'];
					// 	}
					// }
					// if (!empty($msg['message'])&&$msg['message']!=='payment processed successfully') {
					// 	$updidsd[] = $row['correlationId'];
					// echo $msg['message'];
					// }

					if (!empty($msg['message'])) {
						// $dbconn = pg_connect('host=api-db port=5432 dbname=rinha user=postgres password=postgres');
						// $result = pg_query($dbconn, 'select * from payments');

						// if (curl_getinfo($cresult, CURLOPT_URL) == 'http://payment-processor-default:8080/payments') {
						$processor = $ids[$row['correlationId']]['processor'];
						// } else {
						// 	$processor = curl_getinfo($handle, CURLINFO_URL);
						// }
						$updids[] = $row['correlationId'];

						if ($msg['message']!='payment processed successfully') {
							echo PHP_EOL, PHP_EOL;
							print_r("\n" . $row['correlationId']);
						}
						// sleep(1);
						// echo "<br />\n";
						// echo "correlationId: {$row['correlationId']}  amount: {$row['amount']}";
					}

					if (empty($msg['message'])) {
						$ids[$row['correlationId']]['processor'] = 'fallback';
						$ch = curl_init('http://payment-processor-fallback:8080/payments');
						// $payload = (file_get_contents('php://input'));

						// $rq = (new DateTime($row['requested_at']));
						$rq = DateTime::createFromFormat('U.u', microtime(true));
						$rqd = $rq->format('Y-m-d\TH:i:s.u\Z');
						$payload = ['correlationId'=>$row['correlationId'],'amount'=>$row['amount'],'requestedAt'=>$rqd];
						$ids[$row['correlationId']]['payload']=$payload;
						$payload=json_encode($payload);
						curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
						curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

						curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $timeout);
						curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);

						$ids[$row['correlationId']]['correlationId']=$row['correlationId'];

						$ids[$row['correlationId']]['handle']=$ch;
						$fallback[]=$row;
						// $cresult = curl_exec($ch);
						curl_multi_add_handle($mh2, $ch);
					}
				}
			}

			// $dbconn->beginTransaction();

			if (!empty($updids)) {
				$upd = implode("','", $updids);
				$query= "DELETE FROM payments WHERE payments.\"correlationId\" IN ('{$upd}')";
				$result = $dbconn->query($query);
				$query= 'INSERT INTO completed_payments ("correlationId",amount,requested_at,processor) VALUES ';
				$inserts = [];
				foreach ($updids as $ins) {
					$payload = $ids[$ins]['payload'];
					$querystring = "('" . $payload['correlationId'] . "'," . $payload['amount'] . ",'" . $payload['requestedAt'] . "','default')";
					$inserts[]=$querystring;
				}
				$in = implode(', ', $inserts);
				$query.= $in;
				$query.=' ON CONFLICT DO NOTHING';

				$result = $dbconn->query($query);
			}

			// $dbconn->commit();

			$resqueryas = '';
			$updids = [];
			do {
				$status = curl_multi_exec($mh2, $activeCount);
				if ($status == CURLM_OK && $activeCount) {
					curl_multi_select($mh2);

					while ($done = curl_multi_info_read($mh2)) {
						$info = curl_getinfo($done['handle']);

						$output = curl_multi_getcontent($done['handle']);

						// echo PHP_EOL;
						// print_r($info);

						// echo PHP_EOL;

						// print_r($output);
						$msg=json_decode($output, true);
						if (!empty($msg['message'])) {
							// foreach ($ids as $idcor => $handles) {
							// $handles = array_keys($done['handle'], $ids, 'handle');
							$idcor = (array_search($done['handle'], array_column($ids, 'handle', 'correlationId')));

							// if ($handles['handle']==$done['handle']) {
							$updids[]=$idcor;
							// 	// $handle = $ids[$idcor]['handle'];
							// 	// $cresult = curl_multi_getcontent($handle);
							// 	// $msg=json_decode($cresult, true);
							// 	// if (!empty($msg['message'])&&$msg['message']==='payment processed successfully') {
							// $payload=$ids[$idcor]['payload'];
							// $query = 'INSERT INTO completed_payments ("correlationId",amount,requested_at,processor) VALUES ' .
							// "('" . $payload['correlationId'] . "'," . $payload['amount'] . ",'" . $payload['requestedAt'] . "','fallback')
							// ON CONFLICT DO NOTHING; ";

							// $result = $dbconn->query($query);
							// // }
							// $updids[] = $row['correlationId'];
							// $query = "DELETE FROM payments WHERE payments.\"correlationId\" = ('{$row['correlationId']}'); ";
							// $result = $dbconn->query($query);
							// }
							// }
						}
						// request successful.  process output using the callback function.
						// $callback($output, $info);

						// if (isset($urls[$i + 1])) {
						// 	// start a new request (it's important to do this before removing the old one)
						// 	$ch = curl_init();
						// 	$options[CURLOPT_URL] = $urls[$i++];  // increment i
						// 	curl_setopt_array($ch, $options);
						// 	curl_multi_add_handle($master, $ch);
						// }

						// // remove the curl handle that just completed
						// curl_multi_remove_handle($master, $done['handle']);
					}

					// Wait some time before checking again:
				} else {
					break;
				}
			} while ($activeCount>0);
			// if (!empty($resqueryas)) {
			// 	$result = $dbconn->query($resqueryas);
			// }

			if (!empty($updids)) {
				// $upd = implode("','", $updids);
				// $query= "DELETE FROM payments WHERE payments.\"correlationId\" IN ('{$upd}')";
				// $result = $dbconn->query($query);
				// $query= 'INSERT INTO completed_payments ("correlationId",amount,requested_at,processor) VALUES ';
				// $inserts = [];
				// foreach ($updids as $ins) {
				// 	$payload = $ids[$ins]['payload'];
				// 	$querystring = "('" . $payload['correlationId'] . "'," . $payload['amount'] . ",'" . $payload['requestedAt'] . "', 'fallback')";
				// 	$inserts[]=$querystring;
				// }
				// $in = implode(', ', $inserts);
				// $query.= $in;
				// $query.=' ON CONFLICT DO NOTHING';

				// $result = $dbconn->query($query);
			}

			curl_multi_close($mh2);

			// $updids = [];
			$updidsf = [];
			$updidsd = [];
			foreach ($fallback as $row) {
				if (!in_array($row['correlationId'], $updids)) {
					$handle = $ids[$row['correlationId']]['handle'];
					$cresult = curl_multi_getcontent($handle);
					$msg=json_decode($cresult, true);
					// if ($msg) {
					// 	if ($msg['message']!=='payment processed successfully') {
					// 		echo $msg['message'];
					// 	}
					// }

					// if (!empty($cresult)) {
					if (!empty($msg['message'])&&$msg['message']==='payment processed successfully') {
						// $dbconn = pg_connect('host=api-db port=5432 dbname=rinha user=postgres password=postgres');
						// $result = pg_query($dbconn, 'select * from payments');

						// if (curl_getinfo($cresult, CURLOPT_URL) == 'http://payment-processor-default:8080/payments') {
						$processor = $ids[$row['correlationId']]['processor'];

						// } else {
						// 	$processor = curl_getinfo($handle, CURLINFO_URL);
						// }

						$updids[] = $row['correlationId'];

						// $payload=$ids[$row['correlationId']]['payload'];
						// $query= 'INSERT INTO completed_payments ("correlationId",amount,requested_at,processor) VALUES ' .
						// "('" . $payload['correlationId'] . "'," . $payload['amount'] . ",'" . $payload['requestedAt'] . "','fallback')";

						// $result = $dbconn->query($query);

						// $result = $dbconn->query($query);

						// $msg=json_decode($cresult, true);
						// if ($msg['message']!='payment processed successfully') {
						// 	// echo PHP_EOL, PHP_EOL;

						// 	var_dump($all);
						// 	die;
						// }
						// sleep(1);
						// echo "<br />\n";
						// echo "correlationId: {$row['correlationId']}  amount: {$row['amount']}";
						// }
					}
					if (!empty($msg['message'])&&$msg['message']!=='payment processed successfully') {
						$updidsd[] = $row['correlationId'];
						// echo $msg['message'];
					}
					if (empty($msg['message'])) {
						$updidsf[] = $row['correlationId'];

						// $query= "UPDATE payments SET operation = 'failed' WHERE payments.\"correlationId\" = '{$row['correlationId']}'";

						// $result = $dbconn->query($query);
					}
				}
			}

			// $dbconn->beginTransaction();

			if (!empty($updids)) {
				$upd = implode("','", $updids);
				$query= "DELETE FROM payments WHERE payments.\"correlationId\" IN ('{$upd}')";
				$result = $dbconn->query($query);
				$query= 'INSERT INTO completed_payments ("correlationId",amount,requested_at,processor) VALUES ';
				$inserts = [];
				foreach ($updids as $ins) {
					$payload = $ids[$ins]['payload'];
					$querystring = "('" . $payload['correlationId'] . "'," . $payload['amount'] . ",'" . $payload['requestedAt'] . "', 'fallback')";
					$inserts[]=$querystring;
				}
				$in = implode(', ', $inserts);
				$query.= $in;
				$query.=' ON CONFLICT DO NOTHING';

				$result = $dbconn->query($query);
			}

			if (!empty($updidsd)) {
				$upd = implode("','", $updidsd);

				$query= "DELETE FROM payments WHERE payments.\"correlationId\" IN ('{$upd}')";
				$result = $dbconn->query($query);
				$query= 'INSERT INTO completed_payments ("correlationId",amount,requested_at,processor) VALUES ';
				$inserts = [];
				foreach ($updidsd as $ins) {
					$payload = $ids[$ins]['payload'];
					$querystring = "('" . $payload['correlationId'] . "'," . $payload['amount'] . ",'" . $payload['requestedAt'] . "', 'fallback')";
					$inserts[]=$querystring;
				}
				$in = implode(', ', $inserts);
				$query.= $in;
				$query.=' ON CONFLICT DO NOTHING';
				$result = $dbconn->query($query);
			}

			if (!empty($updidsf)) {
				$upd = implode("','", $updidsf);
				$query= "UPDATE payments SET  operation = 'incoming' WHERE payments.\"correlationId\" IN ('{$upd}')";
				$result = $dbconn->query($query);
			}
			// $dbconn->commit();
		} else {
			usleep(1);
		}
	}
	gc_collect_cycles();
	gc_mem_caches();
}

$dbconn = null;
