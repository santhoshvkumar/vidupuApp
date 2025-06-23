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

$f3->route('GET /attendance/employee/@empID/@year/@month',
    function($f3) {
        header('Content-Type: application/json');
        $employeeID = $f3->get('PARAMS.empID');
        $year = $f3->get('PARAMS.year');
        $month = $f3->get('PARAMS.month');
        
        if ($employeeID && $year && $month) {
            $attendanceOperationObject = new AttendanceOperationMaster();
            $attendanceOperationObject->getEmployeeAttendanceHistory($employeeID, $year, $month);
        } else {
            echo json_encode(
                array(
                    "status" => "error",
                    "message_text" => "Missing required parameters (employeeID, year, month)"
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

$f3->route('GET /GetTodayCheckIn/@employeeID/@organisationID',
    function($f3) {
        header('Content-Type: application/json');
        $employeeID = $f3->get('PARAMS.employeeID');
        $organisationID = $f3->get('PARAMS.organisationID');
        
        if ($employeeID && $organisationID) {
            $attendanceOperationObject = new AttendanceOperationMaster();
            $attendanceOperationObject->getTodayCheckIn($employeeID, $organisationID);
        } else {
            echo json_encode(
                array(
                    "status" => "error",
                    "message_text" => "Missing required parameters (employeeID, organisationID)"
                ),
                JSON_FORCE_OBJECT
            );
        }
    }
);

$f3->route('POST /GetTodayCheckIn',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        
        if (!$decoded_items == NULL) {
            getTodayCheckIn($decoded_items);
        } else {
            echo json_encode(
                array(
                    "status" => "error",
                    "message_text" => "Invalid input parameters"
                ),
                JSON_FORCE_OBJECT
            );
        }
    }
);
?>
