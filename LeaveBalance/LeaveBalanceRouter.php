<?php

$f3->route('POST /ApplyLeave',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            applyLeave($decoded_items);
        } else {
            echo json_encode(
                array(
                    "status" => "error Leave Balance",
                    "message_text" => "Invalid input parameters"
                ),
                JSON_FORCE_OBJECT
            );
        }
    }
);

$f3->route('GET /GetLeaveBalance',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            getLeaveBalance($decoded_items);
        } else {
            echo json_encode(
                array(
                    "status" => "error Leave Balance",
                    "message_text" => "Invalid input parameters"
                ),
                JSON_FORCE_OBJECT
            );
        }
    }
);

$f3->route('POST /GetLeaveHistory',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            getLeaveHistory($decoded_items);
        } else {
            echo json_encode(
                array(
                    "status" => "error Leave Balance",
                    "message_text" => "Invalid input parameters"
                ),
                JSON_FORCE_OBJECT
            );
        }
    }
);

$f3->route('POST /UploadLeaveCertificate',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);           
        if (!$decoded_items == NULL) {
            uploadLeaveCertificate($decoded_items);
        } else {
            echo json_encode(
                array(
                    "status" => "error Upload Leave Certificate",  
                    "message_text" => "Invalid input parameters"
                ),
                JSON_FORCE_OBJECT
            );
        }
    }
);  

/*****************  End Upload Medical Certificate *****************/