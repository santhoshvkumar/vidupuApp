<?php

class CheckInLockedComponent{
    public $organisationID;
    public $employeeID;
    public $month;
    public $excuses;
    public $createdBy;
    
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

    public function loadUnlockCheckInLockedEmployee(array $data) {
        if(!isset($data['employeeID']) && !isset($data['excuses']) && !isset($data['createdBy'])) {
            return false;
        }
        $this->employeeID = $data['employeeID'];
        $this->excuses = $data['excuses'];
        $this->createdBy = $data['createdBy'];
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

    public function getAllUnLockEmpHistory() {
        include('config.inc');
        $query = "select tblECIL.Reason, tblECIL.CreatedOn, tblU.userName as CreatedBy from tblExcuseCheckInLock tblECIL INNER JOIN tblUser tblU on tblU.userID = tblECIL.CreatedBy where employeeID = ? and DATE_FORMAT(CreatedOn, '%Y-%m') = ?";
        $stmt = mysqli_prepare($connect_var, $query);
        mysqli_stmt_bind_param($stmt, "si", $this->employeeID, $this->month);
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
                "status" => "success",
                "message_text" => "No data found"
            ], JSON_FORCE_OBJECT);
        }
    }

    public function getEmployeeLockedHistory() {
        include('config.inc');
        $query = "select tblA.attendanceID, tblA.attendanceDate, tblA.checkInTime, tblB.branchName, tblA.checkOutTime, tblA.TotalWorkingHour, tblA.isLateCheckIN from tblAttendance tblA INNER JOIN tblBranch tblB on tblB.branchID = tblA.checkInBranchID where DATE_FORMAT(attendanceDate, '%Y-%m') = ? and employeeID=? and isAutoCheckout=1";
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

    public function unlockCheckInLockedEmployee() {
        include('config.inc');
        $query = "UPDATE tblEmployee SET isCheckInLocked = 0 WHERE employeeID = ?";
        $stmt = mysqli_prepare($connect_var, $query);
        mysqli_stmt_bind_param($stmt, "s", $this->employeeID);
        mysqli_stmt_execute($stmt);
        $queryInsertExcuse = "INSERT INTO tblExcuseCheckInLock (employeeID, Reason, createdOn, createdBy) VALUES (?, ?, NOW(), ?)";
        $stmtInsertExcuse = mysqli_prepare($connect_var, $queryInsertExcuse);
        mysqli_stmt_bind_param($stmtInsertExcuse, "ssi", $this->employeeID, $this->excuses, $this->createdBy);
        mysqli_stmt_execute($stmtInsertExcuse);
        mysqli_stmt_close($stmtInsertExcuse);
      
        mysqli_stmt_close($stmt);
        mysqli_close($connect_var);
        echo json_encode([
            "status" => "success",
            "message_text" => "Employee unlocked successfully"
        ], JSON_FORCE_OBJECT);
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

function getAllUnLockEmpHistory($decoded_items) {
    $CheckInLockedObject = new CheckInLockedComponent();
    if ($CheckInLockedObject->loadEmployeeLockedHistory($decoded_items)) {
        $CheckInLockedObject->getAllUnLockEmpHistory();
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

function unlockCheckInLockedEmployee($decoded_items) {
    $CheckInLockedObject = new CheckInLockedComponent();
    if ($CheckInLockedObject->loadUnlockCheckInLockedEmployee($decoded_items)) {
        $CheckInLockedObject->unlockCheckInLockedEmployee();
    } else {
        echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
}
?>