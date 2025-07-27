<?php

require './connection.php';
try {
	$dbconn = Connection::connect();
} catch (Exception $e) {
	echo PHP_EOL;
	echo 'DB indisponivel, tentando novamente...';
	sleep(1);
	die;
}
$host = gethostname();
$daemon =  random_int(1, 100) . '-' . uniqid();
$i = 0;
$limit = 25;

$query= "UPDATE payments SET  operation = 'incoming' WHERE payments.processor = '{$host}'";
$result = $dbconn->query($query);

$selectquery = $dbconn->prepare("select payments.\"correlationId\" as \"correlationId\", amount,requested_at, processor, operation 
from payments where operation like 'busy' AND daemon = '{$daemon}' 
LIMIT {$limit}");

$updatequery = $dbconn->prepare("UPDATE payments SET processor = '{$host}', daemon = '{$daemon}', operation = 'busy'  FROM (select payments.\"correlationId\" as \"correlationId\"	from payments where operation like 'incoming'  
ORDER BY requested_at ASC 
LIMIT {$limit} FOR UPDATE) AS pay WHERE payments.\"correlationId\" = pay.\"correlationId\"");

while (true &&$i<1000) {
	++$i;

	$iterator = apcu_fetch('iterator');

	$servicequery = 'SELECT * FROM services';
	$re = $dbconn->query($servicequery);
	$svc = $re->fetchAll(\PDO::FETCH_ASSOC);
	$svcs = array_combine(array_column($svc, 'ds'), $svc);
	if (isset($default)) {
		if ($default['last_update'] < $svcs['default']['last_update']) {
			$default = $svcs['default'];
		}
	} else {
		$default = $svcs['default'];
	}

	if (isset($fallback)) {
		if ($fallback['last_update'] < $svcs['fallback']['last_update']) {
			$fallback = $svcs['fallback'];
		}
	} else {
		$fallback = $svcs['fallback'];
	}

	if ($iterator!==false) {
		apcu_add('lock', true);

		$query= 'INSERT INTO payments ("correlationId",amount) VALUES ';

		$inserts = [];
		for ($i = 0; $i<$iterator; ++$i) {
			$pay = apcu_fetch($i . '');
			$payload = json_decode($pay, true);
			if (!empty($payload)) {
				$querystring = "('" . $payload['correlationId'] . "'," . $payload['amount'] . ')';
				$inserts[]=$querystring;
			}
		}
		$in = implode(', ', $inserts);
		$query.= $in;
		$query.=' ON CONFLICT DO NOTHING';
		apcu_clear_cache();
		if ($in!=='') {
			$result = $dbconn->query($query);
		}
	}

	$result = $selectquery->execute();
	if ($result) {
		$all = $selectquery->fetchAll(\PDO::FETCH_ASSOC);

		$n = count($all);

		if ($n < $limit) {
			$result = $updatequery->execute();
		}

		if (!empty($all[0])) {
			$ids = [];
			foreach ($all as $row) {
				if (!empty($row['correlationId'])) {
					$ids[$row['correlationId']]=[];
				}
			}

			$timeout = 1000;

			$mh = curl_multi_init();
			$handles = [];
			if ($default['failing']==0) {
				foreach ($all as $row) {
					if (!empty($row['correlationId'])) {
						$ids[$row['correlationId']]['processor'] = 'default';
						$ch = curl_init('http://payment-processor-default:8080/payments');

						$rq = DateTime::createFromFormat('U.u', microtime(true));

						if ($rq ===false) {
							$rq = DateTime::createFromFormat('U.u', microtime(true));
						}

						$rqd = $rq->format('Y-m-d\TH:i:s.u\Z');
						$payload = ['correlationId'=>$row['correlationId'],'amount'=>$row['amount'],'requestedAt'=>$rqd];

						$ids[$row['correlationId']]['payload']=$payload;
						$ids[$row['correlationId']]['correlationId']=$row['correlationId'];
						$payload=json_encode($payload);
						$ctimeout = $default['rs_delay'] + $timeout;
						curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
						curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $ctimeout);
						curl_setopt($ch, CURLOPT_TIMEOUT_MS, $ctimeout);
						$ids[$row['correlationId']]['handle']=$ch;

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

							$msg=json_decode($output, true);

							if (!empty($msg['message'])) {
								$idcor = (array_search($done['handle'], array_column($ids, 'handle', 'correlationId')));

								$updids[]=$idcor;
							}
						}
					} else {
						break;
					}
				} while ($activeCount>0);
				if (!empty($resqueryas)) {
					$result = $dbconn->query($resqueryas);
				}

				if (!empty($updids)) {
				}
			}
			curl_multi_close($mh);
			$mh2 = curl_multi_init();
			$fallback = [];

			foreach ($all as $row) {
				if (!in_array($row['correlationId'], $updids)) {
					$handle = $ids[$row['correlationId']]['handle'];
					$cresult = curl_multi_getcontent($handle);
					$msg=json_decode($cresult, true);

					if (!empty($msg['message'])) {
						$processor = $ids[$row['correlationId']]['processor'];

						$updids[] = $row['correlationId'];

						if ($msg['message']!='payment processed successfully') {
							echo PHP_EOL, PHP_EOL;
							print_r("\n" . $row['correlationId']);
						}
					}

					if (empty($msg['message'])) {
						$ids[$row['correlationId']]['processor'] = 'fallback';
						$ch = curl_init('http://payment-processor-fallback:8080/payments');

						$rq = DateTime::createFromFormat('U.u', microtime(true));
						$rqd = $rq->format('Y-m-d\TH:i:s.u\Z');
						$payload = ['correlationId'=>$row['correlationId'],'amount'=>$row['amount'],'requestedAt'=>$rqd];
						$ids[$row['correlationId']]['payload']=$payload;
						$payload=json_encode($payload);
						curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
						curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						$ctimeout = $fallback['rs_delay'] + $timeout;

						curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $ctimeout);
						curl_setopt($ch, CURLOPT_TIMEOUT_MS, $ctimeout);

						$ids[$row['correlationId']]['correlationId']=$row['correlationId'];

						$ids[$row['correlationId']]['handle']=$ch;
						$fallback[]=$row;

						curl_multi_add_handle($mh2, $ch);
					}
				}
			}

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

			if ($fallback['failing']==0) {
				$resqueryas = '';
				$updids = [];
				do {
					$status = curl_multi_exec($mh2, $activeCount);
					if ($status == CURLM_OK && $activeCount) {
						curl_multi_select($mh2);

						while ($done = curl_multi_info_read($mh2)) {
							$info = curl_getinfo($done['handle']);

							$output = curl_multi_getcontent($done['handle']);

							$msg=json_decode($output, true);
							if (!empty($msg['message'])) {
								$idcor = (array_search($done['handle'], array_column($ids, 'handle', 'correlationId')));

								$updids[]=$idcor;
							}
						}
					} else {
						break;
					}
				} while ($activeCount>0);

				if (!empty($updids)) {
				}

				curl_multi_close($mh2);

				$updidsf = [];
				$updidsd = [];
				foreach ($fallback as $row) {
					if (!in_array($row['correlationId'], $updids)) {
						$handle = $ids[$row['correlationId']]['handle'];
						$cresult = curl_multi_getcontent($handle);
						$msg=json_decode($cresult, true);

						if (!empty($msg['message'])&&$msg['message']==='payment processed successfully') {
							$processor = $ids[$row['correlationId']]['processor'];

							$updids[] = $row['correlationId'];
						}
						if (!empty($msg['message'])&&$msg['message']!=='payment processed successfully') {
							$updidsd[] = $row['correlationId'];
						}
						if (empty($msg['message'])) {
							$updidsf[] = $row['correlationId'];
						}
					}
				}

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
			}
		} else {
			usleep(1);
		}
	}
	gc_collect_cycles();
	gc_mem_caches();
}

$dbconn = null;
