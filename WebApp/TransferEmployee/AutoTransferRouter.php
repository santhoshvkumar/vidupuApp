<?php

/****************  Auto Transfer  *********************/
$f3->route('POST /AutoTransfer',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            AutoTransfer($decoded_items);
        } else {
            echo json_encode(
                array(
                    "status" => "error AutoTransfer",
                    "message_text" => "Invalid input parameters"
                ),
                JSON_FORCE_OBJECT
            );
        }
    }
);
/**************** End Auto Transfer  *********************/
