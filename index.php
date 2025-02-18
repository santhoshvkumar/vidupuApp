<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
 
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);


$f3=require('lib/base.php');

$f3->config('config.ini');


require 'UserLogin/UserLoginRouter.php';
require 'UserLogin/UserLoginComponent.php';
require 'DailyQuote/DailyQuoteRouter.php';
require 'DailyQuote/DailyQuoteComponent.php';


$f3->route('GET /',
	function($f3) {
		echo "Hey There";
		
	}
);


$f3->run();