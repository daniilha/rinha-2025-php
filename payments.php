<?php
$asd = file_get_contents('php://input');
$da  = json_decode($asd, true);
echo($da['amount']);
echo($da['correlationId']);
