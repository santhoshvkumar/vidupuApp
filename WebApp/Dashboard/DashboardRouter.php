<?php

/*****************   Login User Temp  *******************/
$f3->route('GET /DashboardDetails',

    function($f3){
            header('Content-Type: application/json');
            $decoded_items = json_decode($f3->get('BODY'),true);
            if(!$decoded_items == NULL)
                DashboardDetails($decoded_items);
            else
                echo json_encode(array("status"=>"error Login Value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);

    }

);
/*****************  End Login User Temp *****************/


?>