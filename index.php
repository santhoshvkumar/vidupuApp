<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
 
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

$f3 = require('lib/base.php');
$f3->config('config.ini');

// Load all required components and routers
require 'UserLogin/UserLoginRouter.php';
require 'UserLogin/UserLoginComponent.php';

require 'DailyQuote/DailyQuoteRouter.php';
require 'DailyQuote/DailyQuoteComponent.php';

require 'LeaveBalance/LeaveBalanceRouter.php';
require 'LeaveBalance/LeaveBalanceComponent.php';

require 'Profile/ProfileRouter.php';
require 'Profile/ProfileComponent.php';

require 'AttendanceOperation/AttendanceOperationRouter.php';
require 'AttendanceOperation/AttendanceOperationComponent.php';

require 'ApproveLeave/ApproveLeaveRouter.php';
require 'ApproveLeave/ApproveLeaveComponent.php';

require 'EmployeePaySlip/EmployeePaySlipComponent.php';
require 'EmployeePaySlip/EmployeePaySlipRouter.php';

require 'WebApp/Dashboard/DashboardRouter.php';
require 'WebApp/Dashboard/DashboardComponent.php';

require 'WebApp/AddEmployee/AddEmployeeComponent.php';
require 'WebApp/AddEmployee/AddEmployeeRouter.php';

require 'WebApp/Employee/EmployeeComponent.php';
require 'WebApp/Employee/EmployeeRouter.php';

require 'WebApp/SectionWiseFetchDetails/SectionWiseFetchDetailsComponent.php';
require 'WebApp/SectionWiseFetchDetails/SectionWiseFetchDetailsRouter.php';

require 'WebApp/Login/LoginComponent.php';
require 'WebApp/Login/LoginRouter.php';

require 'WebApp/BranchWiseFetchDetails/BranchWiseFetchDetailsComponent.php';
require 'WebApp/BranchWiseFetchDetails/BranchWiseFetchDetailsRouter.php';	

// require 'WebApp/ResetPassword/ResetPasswordComponent.php';
// require 'WebApp/ResetPassword/ResetPasswordRouter.php';

require 'WebApp/GetValueDashboard/GetValueDashboardComponent.php';
require 'WebApp/GetValueDashboard/GetValueDashboardRouter.php';

require 'WebApp/TransferEmployee/TransferEmployeeComponent.php';
require 'WebApp/TransferEmployee/TransferEmployeeRouter.php';

require 'WebApp/Reports/ReportsComponent.php';
require 'WebApp/Reports/ReportsRouter.php';

require 'Refreshment/refreshmentRouter.php';
require 'Refreshment/refreshmentComponent.php';

require 'WebApp/ApproveRefreshment/ApproveRefreshmentComponent.php';
require 'WebApp/ApproveRefreshment/ApproveRefreshmentRouter.php';

require 'AttendanceReport/DailyAttendanceReportRouter.php';
require 'AttendanceReport/DailyAttendanceReportComponent.php';

require 'WebApp/LeaveReason/LeaveReasonComponent.php';
require 'WebApp/LeaveReason/LeaveReasonRouter.php';

require 'WebApp/Organisation/OrganisationRouter.php';
require 'WebApp/Organisation/OrganisationComponent.php';

// Register all routers
\EmployeePaySlip\EmployeePaySlipRouter::register($f3);

$f3->route('GET /',
	function($f3) {
		echo "Hey There";
		
	}
);

$f3->run();