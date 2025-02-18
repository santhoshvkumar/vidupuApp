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
        } catch(Exception $e) {
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

?> 