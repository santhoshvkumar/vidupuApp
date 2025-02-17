<?php 
$hostname = 'localhost';       
$dbname   = 'infoMicroFinProd';
$username = 'infomicrofin';            
$password = 'Password#1';

$connect_fetch_var = mysqli_connect($hostname, $username, $password, $dbname) or DIE('Connection to host is failed, perhaps the service is down!') or die(mysql_error());
mysqli_set_charset($connect_var,"utf8");
?>