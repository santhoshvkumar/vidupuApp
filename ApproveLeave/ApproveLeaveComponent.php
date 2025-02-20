<?php
class ApproveLeaveMaster {
    public $applyLeaveID;
    public $fromDate;
    public $toDate;
    public $typeOfLeave;
    public $numberOfDays;
    public $createdOn;
    public $reason;
    public $status;
    public $employeeID;
    public $managerID;

    /**
     * Set manager ID for leave approval
     * @param string $managerID
     * @return bool
     */
    public function setManagerID($managerID) {
        $this->managerID = $managerID;
        return true;
    }

    public function loadLeaveforApproval($decoded_items) {
        $this->employeeID = $decoded_items['employeeID']; 
        return true;
    }

    public function loadApprovedLeave($decoded_items) {
        $this->applyLeaveID = $decoded_items['applyLeaveID'];
        $this->typeOfLeave = $decoded_items['typeOfLeave'];
        return true;
    }

    public function getLeaveforApprovalInfo() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            if (!$connect_var) {
                throw new Exception("Database connection failed");
            }

            $queryLeaveApproval = "SELECT 
                tblE.employeeID,
                tblE.employeeName,
                tblL.applyLeaveID,
                tblL.fromDate,
                tblL.toDate,
                tblL.typeOfLeave,
                tblL.reason,
                tblL.createdOn,
                tblL.status
            FROM 
                tblEmployee tblE
            INNER JOIN 
                tblApplyLeave tblL ON tblE.employeeID = tblL.employeeID
            WHERE 
                tblE.employeeID = '" . mysqli_real_escape_string($connect_var, $this->managerID) . "'  
                AND tblL.status = 'Yet To Be Approved'";
    
            $rsd = mysqli_query($connect_var, $queryLeaveApproval);
            
            if (!$rsd) {
                throw new Exception(mysqli_error($connect_var));
            }
    
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
                    "record_count" => 0,
                    "message_text" => "No leaves pending for approval"
                ), JSON_FORCE_OBJECT);
            }
    
        } catch(Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ), JSON_FORCE_OBJECT);
        }
    }

    public function approvedLeaveInfo() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            if (!$connect_var) {
                throw new Exception("Database connection failed");
            }

            // Get leave details for the approved leave
            $queryGetLeave = "SELECT applyLeaveID, typeOfLeave, employeeID, status 
                             FROM tblApplyLeave 
                             WHERE applyLeaveID = '" . mysqli_real_escape_string($connect_var, $this->applyLeaveID) . "'";
                             
            $rsd = mysqli_query($connect_var, $queryGetLeave);
            
            if (!$rsd) {
                throw new Exception(mysqli_error($connect_var));
            }
            
            if (mysqli_num_rows($rsd) > 0) {
                $leaveDetails = mysqli_fetch_assoc($rsd);
                $leaveType = $leaveDetails['typeOfLeave'];
                $employeeID = $leaveDetails['employeeID'];
                
                // Update leave status to Approved
                $updateQuery = "UPDATE tblApplyLeave 
                              SET status = 'Approved' 
                              WHERE applyLeaveID = '" . mysqli_real_escape_string($connect_var, $this->applyLeaveID) . "'";
                
                $updateResult = mysqli_query($connect_var, $updateQuery);
                
                if (!$updateResult) {
                    throw new Exception(mysqli_error($connect_var));
                }

                // Determine which leave balance to update based on leave type
                $updateQuery = "";
                switch ($leaveType) {
                    case 'Casual Leave':
                        $updateQuery = "UPDATE tblLeaveBalance SET CasualLeave = CasualLeave - 1 WHERE employeeID = ?";
                        break;
                    case 'Special Casual Leave':
                        $updateQuery = "UPDATE tblLeaveBalance SET SpecialCasualLeave = SpecialCasualLeave - 1 WHERE employeeID = ?";
                        break;
                    case 'Compensatory Off':
                        $updateQuery = "UPDATE tblLeaveBalance SET CompensatoryOff = CompensatoryOff - 1 WHERE employeeID = ?";
                        break;
                    case 'Special Leave for Blood Donation':
                        $updateQuery = "UPDATE tblLeaveBalance SET SpecialLeaveBloodDonation = SpecialLeaveBloodDonation - 1 WHERE employeeID = ?";
                        break;
                    case 'Leave on Private Affairs':
                        $updateQuery = "UPDATE tblLeaveBalance SET LeaveOnPrivateAffairs = LeaveOnPrivateAffairs - 1 WHERE employeeID = ?";
                        break;
                    case 'Medical Leave':
                        $updateQuery = "UPDATE tblLeaveBalance SET MedicalLeave = MedicalLeave - 1 WHERE employeeID = ?";
                        break;
                    case 'Privilege Leave':
                        $updateQuery = "UPDATE tblLeaveBalance SET PrivilegeLeave = PrivilegeLeave - 1 WHERE employeeID = ?";
                        break;
                    case 'Maternity Leave':
                        $updateQuery = "UPDATE tblLeaveBalance SET MaternityLeave = MaternityLeave - 1 WHERE employeeID = ?";
                        break;
                    default:
                        throw new Exception("Invalid leave type: " . $leaveType);
                }
                
                // Prepare and execute the update query
                $stmt = mysqli_prepare($connect_var, $updateQuery);
                if (!$stmt) {
                    throw new Exception(mysqli_error($connect_var));
                }
                
                mysqli_stmt_bind_param($stmt, "s", $employeeID);
                $updateResult = mysqli_stmt_execute($stmt);
                
                if (!$updateResult) {
                    throw new Exception(mysqli_error($connect_var));
                }
                
                mysqli_stmt_close($stmt);
                mysqli_close($connect_var);
                
                echo json_encode(array(
                    "status" => "success",
                    "message_text" => "Leave approved and balance updated successfully",
                    "leaveID" => $leaveDetails['applyLeaveID'],
                    "leaveType" => $leaveDetails['typeOfLeave'],
                    "currentStatus" => "Approved"
                ));
                
            } else {
                echo json_encode(array(
                    "status" => "failure", 
                    "message_text" => "No leave found with ID: " . $this->applyLeaveID
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

function getLeaveforApproval($decoded_items) {
    $leaveObject = new ApproveLeaveMaster();
    $leaveObject->loadLeaveforApproval($decoded_items);
    $leaveObject->getLeaveforApprovalInfo();
}

function approvedLeave($decoded_items) {
    $leaveObject = new ApproveLeaveMaster();
    $leaveObject->loadApprovedLeave($decoded_items);
    $leaveObject->approvedLeaveInfo();
}

