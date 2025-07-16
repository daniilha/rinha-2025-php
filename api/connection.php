<?php

class Connection {
	public $connection='';

	public static function connect() {
		// if (empty($connection)) {
		// $dbconn = pg_pconnect('host=api-db port=5432 dbname=rinha user=postgres password=postgres');

		$dbconn =new \PDO('pgsql:host=api-db port=5432 dbname=rinha user=postgres password=postgres');
		$dbconn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$dbconn->setAttribute(\PDO::ATTR_PERSISTENT, true);
		$connection=$dbconn;

		// } else {
		return $connection;
		// }
	}
}
