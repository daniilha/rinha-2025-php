<?php

require './connection.php';

$dbconn = Connection::connect();
$query= 'TRUNCATE TABLE payments;';
$result = $dbconn->query($query);
$query= 'TRUNCATE TABLE completed_payments;';
$result = $dbconn->query($query);
echo '
';
