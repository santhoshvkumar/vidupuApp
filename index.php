<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
 
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);


$f3=require('lib/base.php');

$f3->config('config.ini');


require 'UserLogin/UserLoginRouter.php';
require 'UserLogin/UserLoginComponent.php';
require 'DailyQuote/DailyQuoteRouter.php';
require 'DailyQuote/DailyQuoteComponent.php';
require 'LeaveBalance/LeaveBalanceRouter.php';
require 'LeaveBalance/LeaveBalanceComponent.php';
require 'Profile/ProfileRouter.php';
require 'Profile/ProfileComponent.php';
require 'LeaveHistory/LeaveHistoryRouter.php';
require 'LeaveHistory/LeaveHistoryComponent.php';

$f3->route('GET /',
	function($f3) {
		echo "Hey There";
		
	}
);

// LeaveHistory routes
$f3->route('GET /leave-history', 'LeaveHistory\LeaveHistoryRouter->getAllLeaveHistory');
$f3->route('GET /leave-history/@employeeID', 'LeaveHistory\LeaveHistoryRouter->getLeaveHistoryByEmployee');
$f3->route('PUT /cancel-leave', 'LeaveHistory\LeaveHistoryRouter->cancelLeave');

$f3->run();