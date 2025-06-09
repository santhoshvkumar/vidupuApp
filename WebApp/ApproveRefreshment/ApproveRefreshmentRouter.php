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


$f3->route('POST /GetEmployeeDetails',

    function($f3){
            header('Content-Type: application/json');
            $decoded_items = json_decode($f3->get('BODY'),true);
            if(!$decoded_items == NULL)
                GetEmployeeDetails($decoded_items);
            else
                echo json_encode(array("status"=>"error Login Value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);

    }

);


$f3->route('POST /GetEmployeeBasedOnBranch',

    function($f3){
            header('Content-Type: application/json');
            $decoded_items = json_decode($f3->get('BODY'),true);
            if(!$decoded_items == NULL)
                GetEmployeeBasedOnBranch($decoded_items);
            else
                echo json_encode(array("status"=>"error Login Value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);

    }

);

$f3->route('POST /GetEmployeeDetailsBasedOnID', function($f3) {
    $app = new ApproveRefreshmentComponent();
    $data = json_decode($f3->get('BODY'), true);
    $app->GetEmployeeDetailsBasedOnID($data);
});

$f3->route('POST /UpdateEmployeeDetailsBasedOnID',
    function($f3){
            header('Content-Type: application/json');
            $decoded_items = json_decode($f3->get('BODY'),true);
            if(!$decoded_items == NULL)
                UpdateEmployeeDetailsBasedOnID($decoded_items);
            else
                echo json_encode(array("status"=>"error Login Value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
);
$f3->route('POST /ResetPassword',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL)
            ResetPassword($decoded_items);
        else
            echo json_encode(array("status"=>"error", "message_text"=>"Invalid input parameters"), JSON_FORCE_OBJECT);
    }
); 
$f3->route('POST /ResetDeviceFingerprint',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL)
            ResetDeviceFingerprint($decoded_items);
        else
            echo json_encode(array("status"=>"error", "message_text"=>"Invalid input parameters"), JSON_FORCE_OBJECT);
    }
);
$f3->route('POST /ResetEmployeeActiveStatus',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL)
            ResetEmployeeActiveStatus($decoded_items);
        else
            echo json_encode(array("status"=>"error", "message_text"=>"Invalid input parameters"), JSON_FORCE_OBJECT);
    }
);

$f3->route('POST /CalculateRefreshmentAllowance', function($f3) {
    $app = new ApproveRefreshmentComponent();
    $data = json_decode($f3->get('BODY'), true);
    $app->CalculateRefreshmentAllowance($data);
});
/*****************  End Login User Temp *****************/
?>