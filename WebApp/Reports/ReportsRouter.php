<?php
$f3->route('POST /GetAttendanceReport',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if($decoded_items != NULL) {
            GetAttendanceReport($decoded_items);
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    }
);  
$f3->route('POST /GetSectionWiseAttendanceReport',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if($decoded_items != NULL) {
            GetSectionWiseAttendanceReport($decoded_items);
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    }
);
$f3->route('POST /GetLeaveReport',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if($decoded_items != NULL) {    
            GetLeaveReport($decoded_items);
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    }
);
$f3->route('POST /GetDesignationWiseAttendanceReport',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if($decoded_items != NULL) {
            GetDesignationWiseAttendanceReport($decoded_items);  
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    }
);
$f3->route('POST /GetManagementLeaveReport',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL) {
            GetManagementLeaveReport($decoded_items);
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
        }
    }
);
$f3->route('POST /GetDesignationWiseLeaveReport',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL) {
            GetDesignationWiseLeaveReport($decoded_items);
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
        }
    }
);

function GetManagementLeaveReport($decoded_items) {
    $ReportsObject = new ReportsComponent();
    if ($ReportsObject->loadOrganisationID($decoded_items) &&
        $ReportsObject->loadSelectedMonth($decoded_items)) {
        $ReportsObject->GetManagementLeaveReport();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters. Required: organisationID and selectedMonth"), JSON_FORCE_OBJECT);
    }
}

function GetDesignationWiseLeaveReport($decoded_items) {
    error_log("GetDesignationWiseLeaveReport called with params: " . json_encode($decoded_items));
    
    $ReportsObject = new ReportsComponent();
    if (!isset($decoded_items['organisationID'])) {
        error_log("Missing organisationID parameter");
        echo json_encode(array("status" => "error", "message_text" => "Missing organisationID parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if (!isset($decoded_items['selectedMonth'])) {
        error_log("Missing selectedMonth parameter");
        echo json_encode(array("status" => "error", "message_text" => "Missing selectedMonth parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if ($ReportsObject->loadOrganisationID($decoded_items) && 
        $ReportsObject->loadSelectedMonth($decoded_items)) {
        error_log("Parameters loaded successfully, calling GetDesignationWiseLeaveReport");
        $ReportsObject->GetDesignationWiseLeaveReport();
    } else {
        error_log("Failed to load parameters");
        echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
}
?>