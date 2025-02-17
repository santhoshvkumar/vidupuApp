<?php
$hostname = 'localhost';       
$dbname   = 'tnscVidupuApp';
$username = 'root';            
$password = 'root';
$port = '8889';

$connect_var = mysqli_connect($hostname, $username, $password, $dbname, $port) or DIE('Connection to host is failed, perhaps the service is down!') or die(mysql_error());
mysqli_set_charset($connect_var,"utf8");
?>
