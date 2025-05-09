<?php

$f3->route('POST /SectionWiseFetchDetails',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            SectionWiseFetchDetails($decoded_items);
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
); 

$f3->route('POST /SectionEmployees',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL)
            SectionEmployees($decoded_items);
        else
            echo json_encode(array("status"=>"error", "message_text"=>"Invalid input parameters"), JSON_FORCE_OBJECT);
    }
); 
$f3->route('POST /SectionWiseAttendanceDetails',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            SectionWiseAttendanceDetails($decoded_items);
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
); 
$f3->route('POST /SectionWiseAttendanceForToday',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            SectionWiseAttendanceForToday($decoded_items);  
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
); 

?>