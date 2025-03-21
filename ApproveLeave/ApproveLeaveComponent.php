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
    public $startDate;
    public $endDate;
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
                tblL.MedicalCertificatePath,
                tblL.FitnessCertificatePath,
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
            $queryGetLeave = "SELECT applyLeaveID, typeOfLeave, employeeID, status, 
                              DATEDIFF(toDate, fromDate) + 1 as NoOfDays 
                              FROM tblApplyLeave 
                              WHERE applyLeaveID = ?";
                             
            $stmt = mysqli_prepare($connect_var, $queryGetLeave);
            mysqli_stmt_bind_param($stmt, "s", $this->applyLeaveID);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $leaveDetails = mysqli_fetch_assoc($result);
                $leaveType = $leaveDetails['typeOfLeave'];
                $employeeID = $leaveDetails['employeeID'];
                $leaveDuration = $leaveDetails['NoOfDays'];
                // Update leave status
                $updateQuery = "UPDATE tblApplyLeave 
                              SET status = '" . mysqli_real_escape_string($connect_var, $this->status) . "' 
                              WHERE applyLeaveID = '" . mysqli_real_escape_string($connect_var, $this->applyLeaveID) . "'";
                
                $updateResult = mysqli_query($connect_var, $updateQuery);
                
                if (!$updateResult) {
                    throw new Exception(mysqli_error($connect_var));
                }

                // Get user's Expo push token
                $queryUserToken = "SELECT userToken FROM tblEmployee WHERE employeeID = ?";
                $stmt = mysqli_prepare($connect_var, $queryUserToken);
                mysqli_stmt_bind_param($stmt, "s", $employeeID);
                mysqli_stmt_execute($stmt);
                $tokenResult = mysqli_stmt_get_result($stmt);
                $userTokenData = mysqli_fetch_assoc($tokenResult);
                
                if ($userTokenData && !empty($userTokenData['userToken'])) {
                    // Prepare notification message
                    $notificationMessage = "Your leave request has been " . strtolower($this->status);
                    
                    // Send push notification via Expo
                    $ch = curl_init('https://exp.host/--/api/v2/push/send');
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                    ]);
                    
                    $notificationData = [
                        'to' => $userTokenData['userToken'],
                        'title' => 'Leave Status Update',
                        'body' => $notificationMessage,
                        'data' => [
                            'leaveID' => $this->applyLeaveID,
                            'status' => $this->status
                        ]
                    ];
                    
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notificationData));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    
                    $response = curl_exec($ch);
                    curl_close($ch);
                }

                // Update leave balance only if status is Approved
                if ($this->status === 'Approved') {
                    // Special handling for Maternity Leave
                    if ($leaveType === 'Maternity Leave') {
                        // Update numberOfMaternityApplicable in tblEmployee
                        $updateEmployeeQuery = "UPDATE tblEmployee 
                                             SET numberOfMaternityApplicable = numberOfMaternityApplicable - 1 
                                             WHERE employeeID = ?";
                        $stmt = mysqli_prepare($connect_var, $updateEmployeeQuery);
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

                    // Regular leave balance update
                    $updateQuery = "";
                    switch ($leaveType) {
                        case 'Casual Leave':
                            $updateQuery = "UPDATE tblLeaveBalance SET CasualLeave = CasualLeave - $leaveDuration WHERE employeeID = ?";
                            break;
                        case 'Special Casual Leave':
                            $updateQuery = "UPDATE tblLeaveBalance SET SpecialCasualLeave = SpecialCasualLeave - $leaveDuration WHERE employeeID = ?";
                            break;
                        case 'Compensatory Off':
                            $updateQuery = "UPDATE tblLeaveBalance SET CompensatoryOff = CompensatoryOff - $leaveDuration WHERE employeeID = ?";
                            break;
                        case 'Special Leave for Blood Donation':
                            $updateQuery = "UPDATE tblLeaveBalance SET SpecialLeaveBloodDonation = SpecialLeaveBloodDonation - $leaveDuration WHERE employeeID = ?";
                            break;
                        case 'Leave on Private Affairs':
                            $updateQuery = "UPDATE tblLeaveBalance SET LeaveOnPrivateAffairs = LeaveOnPrivateAffairs - $leaveDuration WHERE employeeID = ?";
                            break;
                        case 'Medical Leave':
                            $updateQuery = "UPDATE tblLeaveBalance SET 	MedicalLeave = 	MedicalLeave - $leaveDuration WHERE employeeID = ?";
                            break;
                        case 'Privilege Leave':
                            $updateQuery = "UPDATE tblLeaveBalance SET PrivilegeLeave = PrivilegeLeave - $leaveDuration WHERE employeeID = ?";
                            break;
                        case 'Maternity Leave':
                            $updateQuery = "UPDATE tblLeaveBalance SET MaternityLeave = MaternityLeave - $leaveDuration WHERE employeeID = ?";
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

    public function processMaternityLeaveStatus() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            if (!$connect_var) {
                throw new Exception("Database connection failed");
            }

            // Get leave details
            $queryGetLeave = "SELECT applyLeaveID, typeOfLeave, employeeID, status, 
                              DATEDIFF(toDate, fromDate) + 1 as NoOfDays 
                              FROM tblApplyLeave 
                              WHERE applyLeaveID = ? AND typeOfLeave = 'Maternity Leave'";
                             
            $stmt = mysqli_prepare($connect_var, $queryGetLeave);
            mysqli_stmt_bind_param($stmt, "s", $this->applyLeaveID);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $leaveDetails = mysqli_fetch_assoc($result);
                $employeeID = $leaveDetails['employeeID'];
                $leaveDuration = $leaveDetails['NoOfDays'];

                // Start transaction
                mysqli_begin_transaction($connect_var);
                
                try {
                    // Update leave status
                    $updateLeaveQuery = "UPDATE tblApplyLeave 
                                       SET status = ? 
                                       WHERE applyLeaveID = ?";
                    $stmt = mysqli_prepare($connect_var, $updateLeaveQuery);
                    mysqli_stmt_bind_param($stmt, "ss", $this->status, $this->applyLeaveID);
                    mysqli_stmt_execute($stmt);

                    // Update maternity count in leave balance table (not employee table)
                    $updateEmployeeQuery = "UPDATE tblLeaveBalance 
                                          SET numberOfMaternityApplicable = numberOfMaternityApplicable - 1,
                                              MaternityLeave = MaternityLeave - ? 
                                          WHERE employeeID = ?";
                    $stmt = mysqli_prepare($connect_var, $updateEmployeeQuery);
                    mysqli_stmt_bind_param($stmt, "is", $leaveDuration, $employeeID);
                    mysqli_stmt_execute($stmt);

                    // Commit transaction
                    mysqli_commit($connect_var);

                    echo json_encode(array(
                        "status" => "success",
                        "message_text" => "Maternity leave approved successfully",
                        "leaveID" => $leaveDetails['applyLeaveID']
                    ));

                } catch (Exception $e) {
                    // Rollback transaction on error
                    mysqli_rollback($connect_var);
                    throw $e;
                }
            } else {
                echo json_encode(array(
                    "status" => "failure",
                    "message_text" => "Invalid maternity leave request"
                ), JSON_FORCE_OBJECT);
            }
            
            mysqli_close($connect_var);

        } catch(Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ), JSON_FORCE_OBJECT);
        }
    }

    public function getApproveLeaveList() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            // Modify the SQL query to include MedicalCertificatePath and ensure it's not being filtered out
            $queryApproveLeave = "SELECT a.applyLeaveID, a.employeeID as empID, e.name as employeeName, 
                                   a.fromDate, a.toDate, a.leaveDuration as NoOfDays, a.typeOfLeave, 
                                   a.reason, a.createdOn, a.status, a.MedicalCertificatePath, a.FitnessCertificatePath 
                                   FROM tblApplyLeave a
                                   JOIN tblEmployee e ON a.employeeID = e.empID
                                   WHERE a.status = 'Yet To Be Approved' 
                                   AND e.reportingPerson = '$this->employeeID'";
                                   
            $rsd = mysqli_query($connect_var, $queryApproveLeave);
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
                    "message_text" => "No leave request found for approval."
                ), JSON_FORCE_OBJECT);
            }
        } catch(PDOException $e) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ), JSON_FORCE_OBJECT);
        }
    }


    public function loadApprovalHistoryParams($decoded_items) {
        $this->employeeID = $decoded_items['employeeID'];
        
        // Set date range if provided
        if (isset($decoded_items['startDate'])) {
            $this->startDate = $decoded_items['startDate'];
        }
        
        if (isset($decoded_items['endDate'])) {
            $this->endDate = $decoded_items['endDate'];
        }
        
        return true;
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

function approveMaternityLeave($decoded_items) {
    $leaveObject = new ApproveLeaveMaster();
    if($leaveObject->loadLeaveStatus($decoded_items)){
        $leaveObject->processMaternityLeaveStatus();
    }
    else{
        echo json_encode(array(
            "status" => "error",
            "message_text" => "Invalid Input Parameters"
        ), JSON_FORCE_OBJECT);
    }
}

function getApprovalHistory($decoded_items) {
    $leaveObject = new ApproveLeaveMaster();
    if($leaveObject->loadApprovalHistoryParams($decoded_items)){
        $leaveObject->getApprovalHistoryInfo();
    }
    else{
        echo json_encode(array("status"=>"error","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }
}

?>