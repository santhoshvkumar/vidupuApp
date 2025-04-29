<?php

/*****************   Add Employee Details  *******************/
$f3->route('POST /AddEmployeeDetails', function($f3) {
    $decoded_items = json_decode($f3->get('BODY'), true);
    if($decoded_items != NULL) {
        AddEmployeeDetails($decoded_items);
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}); 
/*****************  End Add Employee Details *****************/
?>