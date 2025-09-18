<?php


$f3->route('POST /getAllCheckInLockedEmployees',
function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            getAllCheckInLockedEmployees($decoded_items);  
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
);


$f3->route('POST /getAllCheckInLockedEmpHistory',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            getAllCheckInLockedEmployeeHistory($decoded_items);
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
);

?>