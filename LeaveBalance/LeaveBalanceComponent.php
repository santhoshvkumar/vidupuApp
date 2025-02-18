<?php

class ApplyLeaveMaster {
    public $empID;
    public $employeeName;
    public $leaveBalance;
    public $companyID;
    
    public function loadEmployeeDetails(array $data) {
        $this->empID = $data['empID'];
        return true;
    }

    public function loadApplyLeaveDetails(array $data) {
        $this->empID = $data['empID'];
        $this->fromDate = $data['fromDate'];
        $this->toDate = $data['toDate'];
        $this->leaveType = $data['leaveType'];
        $this->leaveReason = $data['leaveReason'];
        return true;
    }

    public function getLeaveBalanceInfo() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $queryLeaveBalance = "SELECT tblE.empID, tblL.leaveBalance 
                                FROM tblEmployee tblE 
                                LEFT JOIN tblLeaveBalance tblL ON tblE.empID = tblL.empID 
                                WHERE tblE.empID = '$this->empID' 
                                AND tblE.companyID = '$this->companyID'";
                                
            $rsd = mysqli_query($connect_var, $queryLeaveBalance);
            $resultArr = array();
            $count = 0;
            
            while($rs = mysqli_fetch_assoc($rsd)) {
                $resultArr = $rs;
                if(isset($rs['empID'])) {
                    $count++;
                }
            }
            
            mysqli_close($connect_var);

            if($count > 0) {
                echo json_encode(array(
                    "status" => "success",
                    "data" => $resultArr,
                    "record_count" => $count
                ));
            } else {
                echo json_encode(array(
                    "status" => "failure",
                    "record_count" => $count,
                    "message_text" => "No leave balance found for employee ID: $this->empID"
                ), JSON_FORCE_OBJECT);
            }
        } catch(PDOException $e) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ), JSON_FORCE_OBJECT);
        }
    }

    public function applyForLeave() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            // Check for existing leave applications in the given period
            $queryCheckOverlap = "SELECT COUNT(*) as overlap_count 
                                FROM tblApplyLeave 
                                WHERE employeeID = '$this->empID' 
                                AND ((fromDate BETWEEN '$this->fromDate' AND '$this->toDate') 
                                OR (toDate BETWEEN '$this->fromDate' AND '$this->toDate')
                                OR ('$this->fromDate' BETWEEN fromDate AND toDate))";
            
            $overlapResult = mysqli_query($connect_var, $queryCheckOverlap);
            $overlapData = mysqli_fetch_assoc($overlapResult);
            
            if ($overlapData['overlap_count'] > 0) {
                echo json_encode(array(
                    "status" => "warning",
                    "message_text" => "Leave application already exists for the selected date range"
                ), JSON_FORCE_OBJECT);
                mysqli_close($connect_var);
                return;
            }

            $queryApplyLeave = "INSERT INTO tblApplyLeave (employeeID, fromDate, toDate, typeOfLeave, reason, createdOn, status)VALUES ('$this->empID', '$this->fromDate', '$this->toDate', '$this->leaveType', '$this->leaveReason', CURRENT_DATE(), 'Yet To Be Approved')";
            $rsd = mysqli_query($connect_var, $queryApplyLeave);
            mysqli_close($connect_var);
            echo json_encode(array(
                "status" => "success",
                "message_text" => "Leave applied successfully"
            ), JSON_FORCE_OBJECT);
        } catch(PDOException $e) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ), JSON_FORCE_OBJECT);
        }
    }
}

function applyLeave(array $data) {
    $leaveObject = new ApplyLeaveMaster;
    if($leaveObject->loadApplyLeaveDetails($data)) {
        $leaveObject->applyForLeave();
    } else {
        echo json_encode(array(
            "status" => "error",
            "message_text" => "Invalid Input Parameters"
        ), JSON_FORCE_OBJECT);
    }
}

?> 