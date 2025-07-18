<?php
$f3->route('POST /GetAllActiveEmployees',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            GetAllActiveEmployees($decoded_items);
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
);
$f3->route('POST /GetAllAbesentEmployees',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            GetAllAbesentEmployees($decoded_items);     
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
);
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
$f3->route('POST /ResetMiskenlyEarlyCheckout',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            ResetMiskenlyEarlyCheckout($decoded_items);
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }   
);
$f3->route('POST /GetAllActiveEmployeesforAll',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            GetAllActiveEmployeesforAll($decoded_items);
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }       
);
$f3->route('POST /GetAllCheckInMembersforAll',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            GetAllCheckInMembersforAll($decoded_items); 
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
);
$f3->route('POST /GetAllEarlyCheckOutMembersforAll',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            GetAllEarlyCheckOutMembersforAll($decoded_items);
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
);
$f3->route('POST /GetAllOnLeaveMembersforAll',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            GetAllOnLeaveMembersforAll($decoded_items); 
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
);
$f3->route('POST /GetAllLateCheckInMembersforAll',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            GetAllLateCheckInMembersforAll($decoded_items); 
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
);
$f3->route('POST /GetAllAbesentEmployeesforAll',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            GetAllAbesentEmployeesforAll($decoded_items);       
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
);

$f3->route('POST /GetAllTemporaryEmployees',
    function ($f3) {
        $json = $f3->get('BODY');
        $decoded_items = json_decode($json, true);
        GetAllTemporaryEmployees($decoded_items);
    }
);

$f3->route('POST /GetAllTemporaryEmployeesforAll',
    function ($f3) {
        $json = $f3->get('BODY');
        $decoded_items = json_decode($json, true);
        GetAllTemporaryEmployeesforAll($decoded_items);
    }
);
?>