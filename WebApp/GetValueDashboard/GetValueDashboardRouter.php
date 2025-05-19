<?php
$f3->route('POST /GetAllCheckInMembers',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            GetAllCheckInMembers($decoded_items);  
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
);
$f3->route('POST /GetAllLateCheckInMembers',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            GetAllLateCheckInMembers($decoded_items);  
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT); 
    }
);
$f3->route('POST /GetAllEarlyCheckOutMembers',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            GetAllEarlyCheckOutMembers($decoded_items);  
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
);
$f3->route('POST /GetAllOnLeaveMembers',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            GetAllOnLeaveMembers($decoded_items);     
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
);
?>