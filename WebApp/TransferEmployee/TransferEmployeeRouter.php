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

/*****************   Temporary Transfer Employee Details  *******************/
$f3->route('POST /TemporaryTransfer', function($f3) {
    $decoded_items = json_decode($f3->get('BODY'), true);
    if($decoded_items != NULL) {    
        TemporaryTransfer($decoded_items);
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}); 
/*****************  End Temporary Transfer Employee Details *****************/
?>