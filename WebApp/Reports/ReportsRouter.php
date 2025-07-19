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

$f3->route('POST /GetMonthlyAttendanceSummaryReport',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL) {
            GetMonthlyAttendanceSummaryReport($decoded_items);
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
        }
    }
);

$f3->route('POST /GetEmployees',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL) {
            GetEmployees($decoded_items);
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
        }
    }
);

$f3->route('POST /GetEmployeeLeaveReport',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL) {
            GetEmployeeLeaveReport($decoded_items);
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
        }
    }
);

$f3->route('POST /GetDailyCheckoutReport',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL) {
            GetDailyCheckoutReport($decoded_items);
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
    
    $ReportsObject = new ReportsComponent();
    if (!isset($decoded_items['organisationID'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing organisationID parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if (!isset($decoded_items['selectedMonth'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing selectedMonth parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if ($ReportsObject->loadOrganisationID($decoded_items) && 
        $ReportsObject->loadSelectedMonth($decoded_items)) {
        $ReportsObject->GetDesignationWiseLeaveReport();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
}

function GetMonthlyAttendanceSummaryReport($decoded_items) {
    
    $ReportsObject = new ReportsComponent();
    if (!isset($decoded_items['organisationID'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing organisationID parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if (!isset($decoded_items['selectedMonth'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing selectedMonth parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if ($ReportsObject->loadOrganisationID($decoded_items) && 
        $ReportsObject->loadSelectedMonth($decoded_items)) {
        $ReportsObject->GetMonthlyAttendanceSummaryReport();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
}

function GetEmployees($decoded_items) {
    $ReportsObject = new ReportsComponent();
    if (!isset($decoded_items['organisationID'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing organisationID parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if ($ReportsObject->loadOrganisationID($decoded_items)) {
        $ReportsObject->GetEmployees();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
}

function GetEmployeeLeaveReport($decoded_items) {
    $ReportsObject = new ReportsComponent();
    if (!isset($decoded_items['organisationID'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing organisationID parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if (!isset($decoded_items['employeeID'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing employeeID parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if (!isset($decoded_items['selectedYear'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing selectedYear parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if ($ReportsObject->loadOrganisationID($decoded_items) && 
        $ReportsObject->loadEmployeeID($decoded_items) &&
        $ReportsObject->loadSelectedYear($decoded_items)) {
        $ReportsObject->GetEmployeeLeaveReport();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
}

$f3->route('POST /GetMonthlyCheckoutReport',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL) {
            GetMonthlyCheckoutReport($decoded_items);
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
        }
    }
);

function GetMonthlyCheckoutReport($decoded_items) {
    $ReportsObject = new ReportsComponent();
    if (!isset($decoded_items['organisationID'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing organisationID parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if (!isset($decoded_items['selectedMonth'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing selectedMonth parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if ($ReportsObject->loadOrganisationID($decoded_items) && 
        $ReportsObject->loadSelectedMonth($decoded_items)) {
        $ReportsObject->GetMonthlyCheckoutReport();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
}
?>