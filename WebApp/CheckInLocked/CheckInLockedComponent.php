<?php

class CheckInLockedComponent{
    public $organisationID;
    public $employeeID;
    public $month;
    
    public function loadOrganisationID(array $data) {
        $this->organisationID = $data['organisationID'];
        return true;
    }

    public function loadEmployeeLockedHistory(array $data) {
        if(!isset($data['employeeID']) || !isset($data['month'])) {
            return false;
        }
        $this->employeeID = $data['employeeID'];
        $this->month = $data['month'];
        return true;
    }



    public function getAllCheckInLockedEmployees() {
        include('config.inc');
        $query = "SELECT employeeID, empID, employeeName, Designation FROM tblEmployee WHERE isCheckInLocked = 1";
        $result = mysqli_query($connect_var, $query);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        if(count($data) > 0) {
        echo json_encode([
                "status" => "success",
                "Count" => count($data),
                "data" => $data
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message_text" => "No data found"
            ], JSON_FORCE_OBJECT);
        }
    }

    public function getEmployeeLockedHistory() {
        include('config.inc');
        $query = "select * from tblAttendance where DATE_FORMAT(attendanceDate, '%Y-%m') = ? and employeeID=? and isAutoCheckout=1";
        $stmt = mysqli_prepare($connect_var, $query);
        mysqli_stmt_bind_param($stmt, "si", $this->month, $this->employeeID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        if(count($data) > 0) {
            echo json_encode([
                "status" => "success",
                "Count" => count($data),
                "data" => $data
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message_text" => "No data found"
            ], JSON_FORCE_OBJECT);
        }
    }
}

function getAllCheckInLockedEmployees($decoded_items) {
    $CheckInLockedObject = new CheckInLockedComponent();
    if ($CheckInLockedObject->loadOrganisationID($decoded_items)) {
        $CheckInLockedObject->getAllCheckInLockedEmployees();
    } else {
        echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
}

function getAllCheckInLockedEmployeeHistory($decoded_items) {
    $CheckInLockedObject = new CheckInLockedComponent();
    if ($CheckInLockedObject->loadEmployeeLockedHistory($decoded_items)) {
        $CheckInLockedObject->getEmployeeLockedHistory();
    } else {
        echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
}
?>