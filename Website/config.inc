<?php
$hostname = 'viduppucluster-instance-1.cbakmcsgaskb.ap-south-1.rds.amazonaws.com';       
$dbname   = 'viduppu';
$username = 'viduppuAdmin';            
$password = 'wbAaY5A3p173Mvp5YpP4';
$port = '3306';

$connect_var = mysqli_connect($hostname, $username, $password, $dbname, $port) or DIE('Connection to host is failed, perhaps the service is down!') or die(mysql_error());
mysqli_set_charset($connect_var,"utf8");
?>
