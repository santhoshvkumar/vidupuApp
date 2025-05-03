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
// Commenting out LeaveBalance temporarily
require 'LeaveBalance/LeaveBalanceRouter.php';
require 'LeaveBalance/LeaveBalanceComponent.php';

require 'Profile/ProfileRouter.php';
require 'Profile/ProfileComponent.php';

require 'AttendanceOperation/AttendanceOperationRouter.php';
require 'AttendanceOperation/AttendanceOperationComponent.php';

require 'ApproveLeave/ApproveLeaveRouter.php';
require 'ApproveLeave/ApproveLeaveComponent.php';

require 'WebApp/Dashboard/DashboardRouter.php';
require 'WebApp/Dashboard/DashboardComponent.php';

require 'WebApp/AddEmployee/AddEmployeeComponent.php';
require 'WebApp/AddEmployee/AddEmployeeRouter.php';

require 'WebApp/Employee/EmployeeComponent.php';
require 'WebApp/Employee/EmployeeRouter.php';

// require 'AddEmployee/AddEmployeeComponent.php';
// require 'AddEmployee/AddEmployeeRouter.php';

// require 'LeaveHistory/LeaveHistoryRouter.php';
// require 'LeaveHistory/LeaveHistoryComponent.php';
// require 'Attendance/AttendanceRouter.php';
// require 'Attendance/AttendanceComponent.php';
 

$f3->route('GET /',
	function($f3) {
		echo "Hey There";
		
	}
);

$f3->run();