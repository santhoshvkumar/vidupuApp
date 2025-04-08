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

$f3->route('POST /AutoCheckout',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            autoCheckout($decoded_items);
        } else {
            echo json_encode(
                array(
                    "status" => "error AutoCheckout",
                    "message_text" => "Invalid input parameters"
                ),
                JSON_FORCE_OBJECT
            );
        }
    }
);

$f3->route('GET /attendance/employee/@empID/@month',
    function($f3) {
        header('Content-Type: application/json');
        $employeeID = $f3->get('PARAMS.empID');
        $month = $f3->get('PARAMS.month');
        
        if ($employeeID && $month) {
            $attendanceOperationObject = new AttendanceOperationMaster();
            $attendanceOperationObject->getEmployeeAttendanceHistory($employeeID, $month);
        } else {
            echo json_encode(
                array(
                    "status" => "error",
                    "message_text" => "Missing required parameters"
                ),
                JSON_FORCE_OBJECT
            );
        }   
    }
);

$f3->route('GET /employee/records/@employeeID',
    function($f3) {
        header('Content-Type: application/json');
        $employeeID = $f3->get('PARAMS.employeeID');
        
        if ($employeeID) {
            $attendanceOperationObject = new AttendanceOperationMaster();
            $attendanceOperationObject->getEmployeeRecords($employeeID);
        } else {
            echo json_encode(
                array(
                    "status" => "error",
                    "message_text" => "Missing employee ID parameter"
                ),
                JSON_FORCE_OBJECT
            );
        }
    }
);
?>