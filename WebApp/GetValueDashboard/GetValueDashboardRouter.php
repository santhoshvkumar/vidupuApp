<?php
$f3->route('POST /GetValueDashboardforCheckin',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            GetValueDashboardforCheckin($decoded_items);  
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
);

$f3->route('POST /GetValueDashboardforLateCheckin',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            GetValueDashboardforLateCheckin($decoded_items);  
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
); 
$f3->route('POST /GetValueDashboardforEarlyCheckout',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            GetValueDashboardforEarlyCheckout($decoded_items);  
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
);
$f3->route('POST /GetValueDashboardforOnLeave',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            GetValueDashboardforOnLeave($decoded_items);      
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
);
?>