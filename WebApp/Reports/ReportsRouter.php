<?php
require_once 'ReportsComponent.php';

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

// Function definitions
function GetAttendanceReport($decoded_items) {
    $ReportsObject = new ReportsComponent();
    if (!isset($decoded_items['startDate'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing startDate parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if (!isset($decoded_items['endDate'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing endDate parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if (!isset($decoded_items['organisationID'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing organisationID parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if ($ReportsObject->loadOrganisationID($decoded_items) && $ReportsObject->loadReportsforGivenDate($decoded_items)) {
        $ReportsObject->GetAttendanceReport();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
}

function GetSectionWiseAttendanceReport($decoded_items) {
    $ReportsObject = new ReportsComponent();
    if (!isset($decoded_items['startDate'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing startDate parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if (!isset($decoded_items['endDate'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing endDate parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if (!isset($decoded_items['organisationID'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing organisationID parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if ($ReportsObject->loadOrganisationID($decoded_items) && $ReportsObject->loadReportsforGivenDate($decoded_items)) {
        $ReportsObject->GetSectionWiseAttendanceReport();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
}

function GetLeaveReport($decoded_items) {
    $ReportsObject = new ReportsComponent();
    if (!isset($decoded_items['startDate'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing startDate parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if (!isset($decoded_items['endDate'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing endDate parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if (!isset($decoded_items['organisationID'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing organisationID parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if ($ReportsObject->loadOrganisationID($decoded_items) && $ReportsObject->loadReportsforGivenDate($decoded_items)) {
        $ReportsObject->GetLeaveReport();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
}

function GetDesignationWiseAttendanceReport($decoded_items) {
    $ReportsObject = new ReportsComponent();
    if (!isset($decoded_items['startDate'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing startDate parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if (!isset($decoded_items['endDate'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing endDate parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if (!isset($decoded_items['organisationID'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing organisationID parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if ($ReportsObject->loadOrganisationID($decoded_items) && $ReportsObject->loadReportsforGivenDate($decoded_items)) {
        $ReportsObject->GetDesignationWiseAttendanceReport();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
}

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
        // Load employee type filter (optional parameter)
        $ReportsObject->loadEmployeeType($decoded_items);
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

function GetDailyCheckoutReport($decoded_items) {
    $ReportsObject = new ReportsComponent();
    if (!isset($decoded_items['organisationID'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing organisationID parameter"), JSON_FORCE_OBJECT);
        return;
    }

    if (!isset($decoded_items['selectedDate'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing selectedDate parameter"), JSON_FORCE_OBJECT);
        return;
    }

    if ($ReportsObject->loadOrganisationID($decoded_items) && 
        $ReportsObject->loadSelectedDate($decoded_items)) {
        $ReportsObject->GetDailyCheckoutReport();
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

$f3->route('POST /DebugAutoCheckoutRecords',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL) {
            DebugAutoCheckoutRecords($decoded_items);
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

    // Load required parameters
    if ($ReportsObject->loadOrganisationID($decoded_items) && 
        $ReportsObject->loadSelectedMonth($decoded_items)) {
        
        // Load employee type (optional parameter)
        $ReportsObject->loadEmployeeType($decoded_items);
        
        $ReportsObject->GetMonthlyCheckoutReport();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
}

function DebugAutoCheckoutRecords($decoded_items) {
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
        $ReportsObject->DebugAutoCheckoutRecords();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
}

$f3->route('POST /GetWorkingDays',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if($decoded_items != NULL) {
            GetWorkingDays($decoded_items);
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    }
);

function GetWorkingDays($decoded_items) {
    $ReportsObject = new ReportsComponent();
    if ($ReportsObject->loadSelectedMonth($decoded_items)) {
        $ReportsObject->GetWorkingDays();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
}

$f3->route('POST /DeleteCertificate',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if($decoded_items != NULL) {
            DeleteCertificate($decoded_items);
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    }
);

function DeleteCertificate($decoded_items) {
    $ReportsObject = new ReportsComponent();
    if (!isset($decoded_items['certificatePath'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing certificatePath parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if (!isset($decoded_items['leaveID'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing leaveID parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    $ReportsObject->deleteCertificate($decoded_items);
}

$f3->route('POST /UploadCertificate',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if($decoded_items != NULL) {
            UploadCertificate($decoded_items);
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    }
);

function UploadCertificate($decoded_items) {
    $ReportsObject = new ReportsComponent();
    if (!isset($decoded_items['leaveID'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing leaveID parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    if (!isset($decoded_items['certificateType'])) {
        echo json_encode(array("status" => "error", "message_text" => "Missing certificateType parameter"), JSON_FORCE_OBJECT);
        return;
    }
    
    $ReportsObject->uploadCertificate($decoded_items);
}
?>