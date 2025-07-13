<?php
echo"olá";
var_dump($_GET);
$to = $_GET['to'];
$from = $_GET['from'];
$dtto = date_create($to);
$dtfrom = date_create($from);
