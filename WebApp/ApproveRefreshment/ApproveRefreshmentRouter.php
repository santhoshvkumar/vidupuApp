<?php
require_once('ApproveRefreshmentComponent.php');

/*****************   Login User Temp  *******************/
$f3->route('POST /GetAllEmployeeRefreshmentDetails', function($f3) {
    $app = new ApproveRefreshmentComponent();
    $data = json_decode($f3->get('BODY'), true);
    $app->GetAllEmployeeRefreshmentDetails($data);
});

$f3->route('POST /ApproveEmployeeRefreshmentDetailsByID', function($f3) {
    $app = new ApproveRefreshmentComponent();
    $data = json_decode($f3->get('BODY'), true);
    $app->ApproveEmployeeRefreshmentDetailsByID($data);
}); 
/*****************  End Login User Temp *****************/
/*****************  Get Working Days Details *****************/
$f3->route('POST /GetWorkingDaysDetails', function($f3) {
    $app = new ApproveRefreshmentComponent();
    $data = json_decode($f3->get('BODY'), true);
    $app->GetWorkingDaysDetails($data);
});
/*****************  End Get Working Days Details *****************/
?>