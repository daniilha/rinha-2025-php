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

// $mc = new Memcached();
// $mc->addServer('mymemcached', 11211);

$servicequery = 'SELECT * FROM services';
$re = $dbconn->query($servicequery);
$svc = $re->fetchAll(\PDO::FETCH_ASSOC);
$svcs = array_combine(array_column($svc, 'ds'), $svc);

echo array_to_table($svcs, null);

$dbconn = Connection::connect();
$query= "select COUNT(completed_payments.\"correlationId\") as total, SUM(amount) as amount, processor from completed_payments WHERE processor NOT LIKE 'unset' GROUP BY processor";
// $result = pg_query($dbconn, $query);

$result = $dbconn->query($query);

$data = $result->fetchAll(\PDO::FETCH_ASSOC);

echo array_to_table($data, null);

echo "\n Host : ".gethostname();
// print_r($svcs);

// phpinfo();

function array_to_table($data, $args = []) {
	if (!is_array($args)) {
		$args = [];
	}
	foreach (['class','column_widths','custom_headers','format_functions','nowrap_head','nowrap_body','capitalize_headers'] as $key) {
		if (array_key_exists($key, $args)) {
			$$key = $args[$key];
		} else {
			$$key = false;
		}
	}
	if ($class) {
		$class = ' class="' . $class . '"';
	} else {
		$class = '';
	}
	if (!is_array($column_widths)) {
		$column_widths = [];
	}

	// get rid of headers row, if it exists (headers should exist as keys)
	if (array_key_exists('headers', $data)) {
		unset($data['headers']);
	}

	$html = '<table' . $class . '>';
	$i = 0;
	foreach ($data as $row) {
		++$i;

		// display headers
		if ($i == 1) {
			foreach ($row as $key => $value) {
				if (array_key_exists($key, $column_widths)) {
					$style = ' style="width:' . $column_widths[$key] . 'px;"';
				} else {
					$style = '';
				}
				$html .= '<col' . $style . ' />';
			}
			$html .= '<thead><tr>';
			foreach ($row as $key => $value) {
				if (is_array($custom_headers) && array_key_exists($key, $custom_headers) && ($custom_headers[$key])) {
					$header = $custom_headers[$key];
				} elseif ($capitalize_headers) {
					$header = ucwords($key);
				} else {
					$header = $key;
				}
				if ($nowrap_head) {
					$nowrap = ' nowrap';
				} else {
					$nowrap = '';
				}
				$html .= '<td' . $nowrap . '>' . $header . '</td>';
			}
			$html .= '</tr></thead>';
		}

		// display values
		if ($i == 1) {
			$html .= '<tbody>';
		}
		$html .= '<tr>';
		foreach ($row as $key => $value) {
			if (is_array($format_functions) && array_key_exists($key, $format_functions) && ($format_functions[$key])) {
				$function = $format_functions[$key];
				$value = $function($value);
			}
			if ($nowrap_body) {
				$nowrap = ' nowrap';
			} else {
				$nowrap = '';
			}
			$html .= '<td' . $nowrap . '>' . $value . '</td>';
		}
		$html .= '</tr>';
	}
	$html .= '</tbody>';
	$html .= '</table>';

	return $html;
}
