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
    /**
     * Set manager ID for leave approval
     * @param string $managerID
     * @return bool
     */

    public function loadLeaveforApproval($decoded_items) {
        $this->employeeID = $decoded_items['employeeID']; 
        return true;
    }

    public function loadLeaveStatus($decoded_items) {
        if (!isset($decoded_items['applyLeaveID']) || !isset($decoded_items['status'])) {
            return false;
        }
        $this->applyLeaveID = $decoded_items['applyLeaveID'];
        $this->status = $decoded_items['status'];
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
                tblE.empID,
                tblL.fromDate,
                tblL.toDate,
                tblL.typeOfLeave,
                tblL.reason,
                tblL.createdOn,
                tblL.status,
                DATEDIFF(tblL.toDate, tblL.fromDate) + 1 as NoOfDays
            FROM 
                tblEmployee tblE
            INNER JOIN 
                tblApplyLeave tblL ON tblE.employeeID = tblL.employeeID
            WHERE 
                tblE.managerID = '" . mysqli_real_escape_string($connect_var, $this->employeeID) . "'  
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

    public function processLeaveStatus() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            if (!$connect_var) {
                throw new Exception("Database connection failed");
            }

            // Get leave details
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
                
                // Update leave status
                $updateQuery = "UPDATE tblApplyLeave 
                              SET status = '" . mysqli_real_escape_string($connect_var, $this->status) . "' 
                              WHERE applyLeaveID = '" . mysqli_real_escape_string($connect_var, $this->applyLeaveID) . "'";
                
                $updateResult = mysqli_query($connect_var, $updateQuery);
                
                if (!$updateResult) {
                    throw new Exception(mysqli_error($connect_var));
                }

                // Update leave balance only if status is Approved
                if ($this->status === 'Approved') {
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
                }
                
                mysqli_close($connect_var);
                
                $messageText = $this->status === 'Approved' 
                    ? "Leave approved and balance updated successfully"
                    : "Leave " . strtolower($this->status) . " successfully";
                
                echo json_encode(array(
                    "status" => "success",
                    "message_text" => $messageText,
                    "leaveID" => $leaveDetails['applyLeaveID'],
                    "leaveType" => $leaveDetails['typeOfLeave'],
                    "currentStatus" => $this->status
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


function getLeavesforApproval($decoded_items) {
    $leaveObject = new ApproveLeaveMaster();
    if($leaveObject->loadLeaveforApproval($decoded_items)){
        $leaveObject->getLeaveforApprovalInfo();
    }
    else{
        echo json_encode(array("status"=>"error","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }
}

function approvedLeave($decoded_items) {
    $leaveObject = new ApproveLeaveMaster();
    if($leaveObject->loadLeaveStatus($decoded_items)){
        $leaveObject->processLeaveStatus();
    }
    else{
        echo json_encode(array("status"=>"error","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }
}

function processRejectedLeave($decoded_items) {
    $leaveObject = new ApproveLeaveMaster();
    if($leaveObject->loadLeaveStatus($decoded_items)){
        $leaveObject->processLeaveStatus();
    }
    else{
        echo json_encode(array("status"=>"error","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }
}

function processHoldLeave($decoded_items) {
    $leaveObject = new ApproveLeaveMaster();
    if($leaveObject->loadLeaveStatus($decoded_items)){
        $leaveObject->processLeaveStatus();
    }
    else{
        echo json_encode(array("status"=>"error","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }
}

?>