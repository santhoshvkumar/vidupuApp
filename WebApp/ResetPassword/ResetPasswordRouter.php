<?php
$f3->route('POST /ResetPassword',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL)
            ResetPassword($decoded_items);
        else
            echo json_encode(array("status"=>"error", "message_text"=>"Invalid input parameters"), JSON_FORCE_OBJECT);
    }
); 
?>