<?php

/*****************   Transfer Employee Details  *******************/
$f3->route('POST /TransferEmployeeDetails', function($f3) {
    $decoded_items = json_decode($f3->get('BODY'), true);
    if($decoded_items != NULL) {
        TransferEmployeeDetails($decoded_items);
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}); 
/*****************  End Transfer Employee Details *****************/

/*****************   Delete Transfer Employee  *******************/
$f3->route('POST /DeleteTransferEmployee', function($f3) {
    $decoded_items = json_decode($f3->get('BODY'), true);
    if($decoded_items != NULL) {
        DeleteTransferEmployee($decoded_items);
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}); 
/*****************  End Delete Transfer Employee *****************/

/*****************   Get All Employee Transfers  *******************/
$f3->route('POST /GetAllEmployeeTransfers', function($f3) {
    $decoded_items = json_decode($f3->get('BODY'), true);
    if($decoded_items != NULL) {
        GetAllEmployeeTransfers($decoded_items);
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}); 
/*****************  End Get All Employee Transfers *****************/
?>