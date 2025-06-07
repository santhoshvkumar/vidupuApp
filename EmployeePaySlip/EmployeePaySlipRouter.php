<?php
/*****************  Employee Pay Slip  *******************/
$f3->route('POST /EmployeePaySlip',

    function($f3){
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            EmployeePaySlip($decoded_items);
        else
            echo json_encode(array("status"=>"error Here for Employee Pay Slip Value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
);