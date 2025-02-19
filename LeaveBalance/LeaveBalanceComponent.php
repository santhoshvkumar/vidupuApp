<?php
namespace LeaveBalance;

class LeaveBalanceComponent {
    public $employeeID;
    
    public function loadEmployeeDetails(array $data) {
        $this->employeeID = $data['employeeID'];
        return true;
    }

    public function getLeaveBalanceInfo() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $queryLeaveBalance = "SELECT 
                                    e.employeeID,
                                    e.employeeName,
                                    me.branchID,
                                    b.branchName,
                                    lb.CasualLeave,
                                    lb.SpecialCasualLeave,
                                    lb.CompensatoryOff,
                                    lb.SpecialLeaveBloodDonation,
                                    lb.LeaveOnPrivateAffairs,
                                    lb.MedicalLeave,
                                    lb.PrivilegeLeave,
                                    lb.MaternityLeave,
                                    lb.Year
                                FROM tblEmployee e 
                                LEFT JOIN tblmapEmp me ON e.employeeID = me.employeeID 
                                LEFT JOIN tblBranch b ON me.branchID = b.branchID
                                LEFT JOIN tblLeaveBalance lb ON e.employeeID = lb.EmployeeID 
                                    AND lb.Year = YEAR(CURRENT_DATE)
                                WHERE e.employeeID = '$this->employeeID'";
                                
            $rsd = mysqli_query($connect_var, $queryLeaveBalance);
            $resultArr = array();
            $count = 0;
            
            while($rs = mysqli_fetch_assoc($rsd)) {
                $resultArr = $rs;
                if(isset($rs['employeeID'])) {
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
                    "record_count" => 0,
                    "message_text" => "No employee found with ID: $this->employeeID"
                ), JSON_FORCE_OBJECT);
            }
<<<<<<< HEAD
        } catch(Exception $e) {
=======
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
            $queryLeaveHistory = "SELECT applyLeaveID, fromDate, toDate, typeOfLeave, reason, status FROM tblApplyLeave WHERE employeeID = '$this->empID' ORDER by applyLeaveID DESC";
            $rsd = mysqli_query($connect_var, $queryLeaveHistory);
            $resultArr = Array();
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
                    "record_count" => $count,
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
            // Check for existing leave applications in the given period, including adjacent days
            $queryCheckOverlap = "SELECT COUNT(*) as overlap_count, GROUP_CONCAT(DISTINCT typeOfLeave) as leave_types 
                                FROM tblApplyLeave 
                                WHERE employeeID = '$this->empID' 
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
                    echo json_encode(array(
                        "status" => "warning",
                        "message_text" => "Leave application already exists for the selected date range"
                    ), JSON_FORCE_OBJECT);
                    mysqli_close($connect_var);
                    return;
                }
            }

            $queryApplyLeave = "INSERT INTO tblApplyLeave (employeeID, fromDate, toDate, typeOfLeave, reason, createdOn, status)VALUES ('$this->empID', '$this->fromDate', '$this->toDate', '$this->leaveType', '$this->leaveReason', CURRENT_DATE(), 'Yet To Be Approved')";
            $rsd = mysqli_query($connect_var, $queryApplyLeave);
            mysqli_close($connect_var);
            echo json_encode(array(
                "status" => "success",
                "message_text" => "Leave applied successfully"
            ), JSON_FORCE_OBJECT);
        } catch(PDOException $e) {
>>>>>>> 782a4156724660228da06b05b7b7a8d371c71960
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