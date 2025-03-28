<?php

$f3->route('POST /CancelLeave',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            cancelLeave($decoded_items);
        } else {
            echo json_encode(
                array(
                    "status" => "error Cancel Leave",
                    "message_text" => "Invalid input parameters"
                ),
                JSON_FORCE_OBJECT
            );
        }
    }
);

$f3->route('POST /Checkin',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            checkIn($decoded_items);
        } else {
            echo json_encode(
                array(
                    "status" => "error Checkin",
                    "message_text" => "Invalid input parameters"
                ),
                JSON_FORCE_OBJECT
            );
        }
    }
);

$f3->route('POST /Checkout',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            checkOut($decoded_items);
        } else {
            echo json_encode(
                array(
                    "status" => "error Checkout",
                    "message_text" => "Invalid input parameters"
                ),
                JSON_FORCE_OBJECT
            );
        }
    }
);

$f3->route('GET /AutoCheckout',
    function($f3) {
        header('Content-Type: application/json');
        autoCheckout();
    }
);

$f3->route('GET /attendance/employee/@empID',
    function($f3) {
        getEmployeeAttendance($f3);
    }
);

$f3->route('GET /attendance/stats/@empID',
    function($f3) {
        getEmployeeAttendanceStats($f3);
    }
);
?>