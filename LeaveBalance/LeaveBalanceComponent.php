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
        $this->leaveDuration = $data['leaveDuration'];
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
                    "result" => $resultArr,
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
    public function getLeaveHistoryInfo() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $queryLeaveHistory = "SELECT applyLeaveID, fromDate, toDate, leaveDuration, typeOfLeave, reason, status FROM tblApplyLeave WHERE employeeID = '$this->empID' and status != 'Cancelled' ORDER by applyLeaveID DESC";
            $rsd = mysqli_query($connect_var, $queryLeaveHistory);
            $resultArr = array();
            $count = 0;
            while($rs = mysqli_fetch_assoc($rsd)) {
                $resultArr[] = $rs;
                $count++;
            }
            mysqli_close($connect_var);

            if($count > 0) {
                echo json_encode(array(
                    "status" => "success",
                    "result" => $resultArr,
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
            // For Casual Leave validation
            if ($this->leaveType === 'Casual Leave') {
                // Get current year's start and mid dates
                $currentYear = date('Y');
                $yearStart = "$currentYear-01-01";
                $yearMid = "$currentYear-07-01";
                $yearEnd = "$currentYear-12-31";
                
                // Check total casual leaves taken in the year
                $queryCasualLeaves = "SELECT 
                    SUM(CASE 
                        WHEN fromDate >= '$yearStart' AND toDate <= '$yearMid' THEN leaveDuration
                        ELSE 0 
                    END) as first_half_leaves,
                    SUM(CASE 
                        WHEN fromDate >= '$yearMid' AND toDate <= '$yearEnd' THEN leaveDuration
                        ELSE 0 
                    END) as second_half_leaves
                    FROM tblApplyLeave 
                    WHERE employeeID = '$this->empID' 
                    AND typeOfLeave = 'Casual Leave'
                    AND status != 'Cancelled'
                    AND fromDate >= '$yearStart'";
                $casualResult = mysqli_query($connect_var, $queryCasualLeaves);
                $casualData = mysqli_fetch_assoc($casualResult);
                
                $firstHalfLeaves = floatval($casualData['first_half_leaves']);
                $secondHalfLeaves = floatval($casualData['second_half_leaves']);
                
                // Check if the leave spans across half years
                $isFirstHalf = strtotime($this->fromDate) < strtotime($yearMid);
                $isSecondHalf = strtotime($this->toDate) >= strtotime($yearMid);
                
                if ($isFirstHalf && $isSecondHalf) {
                    echo json_encode(array(
                        "status" => "warning",
                        "message_text" => "Casual Leave cannot span across half years"
                    ), JSON_FORCE_OBJECT);
                    mysqli_close($connect_var);
                    return;
                }
                 // Validate against half-year limits
            if ($isFirstHalf) {
                if (($firstHalfLeaves + $this->leaveDuration) > 10) {
                    echo json_encode(array(
                        "status" => "warning",
                        "message_text" => "Cannot exceed 10 days of Casual Leave in first half of the year"
                    ), JSON_FORCE_OBJECT);
                    mysqli_close($connect_var);
                    return;
                }
            } else {
                $availableSecondHalf = 10 + (10 - $firstHalfLeaves); // Unused first half leaves added to second half
                if (($secondHalfLeaves + $this->leaveDuration) > $availableSecondHalf) {
                    echo json_encode(array(
                        "status" => "warning",
                        "message_text" => "Exceeds available Casual Leave balance for second half of the year"
                    ), JSON_FORCE_OBJECT);
                        mysqli_close($connect_var);
                        return;
                    }
                }
            }

            // Check for existing leave applications in the given period, including adjacent days
            $queryCheckOverlap = "SELECT COUNT(*) as overlap_count, GROUP_CONCAT(DISTINCT typeOfLeave) as leave_types 
                                FROM tblApplyLeave 
                                WHERE employeeID = '$this->empID' 
                                AND status != 'Cancelled' AND status != 'Rejected'
                                AND (
                                    (fromDate BETWEEN DATE_SUB('$this->fromDate', INTERVAL 1 DAY) AND DATE_ADD('$this->toDate', INTERVAL 1 DAY))
                                    OR (toDate BETWEEN DATE_SUB('$this->fromDate', INTERVAL 1 DAY) AND DATE_ADD('$this->toDate', INTERVAL 1 DAY))
                                    OR ('$this->fromDate' BETWEEN DATE_SUB(fromDate, INTERVAL 1 DAY) AND DATE_ADD(toDate, INTERVAL 1 DAY))
                                )";
            $overlapResult = mysqli_query($connect_var, $queryCheckOverlap);
            $overlapData = mysqli_fetch_assoc($overlapResult);
            
            if ($overlapData['overlap_count'] > 0) {
                $existingLeaveTypes = explode(',', $overlapData['leave_types']);
                if (!in_array($this->leaveType, $existingLeaveTypes)) {
                    echo json_encode(array(
                        "status" => "warning",
                        "message_text" => "Cannot apply different leave types on consecutive days. Existing leave type(s): " . $overlapData['leave_types']
                    ), JSON_FORCE_OBJECT);
                    mysqli_close($connect_var);
                    return;
                } else {
                    // Check if this is actually an overlapping leave or just consecutive
                    $queryExactOverlap = "SELECT COUNT(*) as exact_overlap 
                                        FROM tblApplyLeave 
                                        WHERE employeeID = '$this->empID' 
                                        AND status != 'Cancelled' AND status != 'Rejected'
                                        AND (
                                            (fromDate <= '$this->toDate' AND toDate >= '$this->fromDate')
                                        )";
                    $exactOverlapResult = mysqli_query($connect_var, $queryExactOverlap);
                    $exactOverlapData = mysqli_fetch_assoc($exactOverlapResult);
                    
                    if ($exactOverlapData['exact_overlap'] > 0) {
                        echo json_encode(array(
                            "status" => "warning",
                            "message_text" => "Leave application already exists for the selected date range"
                        ), JSON_FORCE_OBJECT);
                        mysqli_close($connect_var);
                        return;
                    }
                    // If no exact overlap, allow the consecutive leave of the same type
                }
            }

            $queryApplyLeave = "INSERT INTO tblApplyLeave (employeeID, fromDate, toDate, leaveDuration, typeOfLeave, reason, createdOn, status)VALUES ('$this->empID', '$this->fromDate', '$this->toDate', '$this->leaveDuration', '$this->leaveType', '$this->leaveReason', CURRENT_DATE(), 'Yet To Be Approved')";
            echo $queryApplyLeave;
            $rsd = mysqli_query($connect_var, $queryApplyLeave);
            if($rsd) {
                echo json_encode(array(
                    "status" => "success",
                    "message_text" => "Leave applied successfully"
                ), JSON_FORCE_OBJECT);
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message_text" => "Leave application failed"
                ), JSON_FORCE_OBJECT);
            }
            mysqli_close($connect_var);
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

function getLeaveBalance(array $data) {
    $leaveObject = new ApplyLeaveMaster;
    if($leaveObject->loadEmployeeDetails($data)) {
        $leaveObject->getLeaveBalanceInfo();
    } else {
        echo json_encode(array(
            "status" => "error",
            "message_text" => "Invalid Input Parameters"
        ), JSON_FORCE_OBJECT);
    }
}

function getLeaveHistory(array $data) {
    $leaveObject = new ApplyLeaveMaster;
    if($leaveObject->loadEmployeeDetails($data)) {
        $leaveObject->getLeaveHistoryInfo();
    } else {
        echo json_encode(array(
            "status" => "error",
            "message_text" => "Invalid Input Parameters"
        ), JSON_FORCE_OBJECT);
    }
}

?> 