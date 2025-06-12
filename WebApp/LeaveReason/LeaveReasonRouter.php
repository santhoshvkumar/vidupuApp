<?php
$f3->route('GET /GetAllLeaveReasonDetails',

    function($f3){
            GetAllLeaveReasonDetails();

    }
);
$f3->route('POST /UpdateLeaveReasonDetailsBasedonReasonID',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL)
            UpdateLeaveReasonDetailsBasedonReasonID($decoded_items);
        else
            echo json_encode(array("status"=>"error", "message_text"=>"Invalid input parameters"), JSON_FORCE_OBJECT);
    }
);
$f3->route('POST /AddLeaveReasonDetails',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            AddLeaveReasonDetails($decoded_items);
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
); 
?>