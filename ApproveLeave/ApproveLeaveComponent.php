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
        
        // Set date range if provided
        if (isset($decoded_items['startDate']) && !empty($decoded_items['startDate'])) {
            $this->startDate = $decoded_items['startDate'];
        }
        
        if (isset($decoded_items['endDate']) && !empty($decoded_items['endDate'])) {
            $this->endDate = $decoded_items['endDate'];
        }
        
        return true;
    }

    public function loadLeaveStatus($decoded_items) {
        $this->applyLeaveID = $decoded_items['applyLeaveID'];
        $this->status = $decoded_items['status'];
        if (isset($decoded_items['rejectionReason']) && !empty($decoded_items['rejectionReason'])) {
            $this->rejectionReason = $decoded_items['rejectionReason'];
        }
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
                tblL.NoOfDaysExtend,
                tblL.reasonForExtend,
                tblL.MedicalCertificatePath,
                tblL.FitnessCertificatePath,
                DATEDIFF(tblL.toDate, tblL.fromDate) + 1 as NoOfDays,
                tblL.leaveDuration
            FROM 
                tblEmployee tblE
            INNER JOIN 
                tblApplyLeave tblL ON tblE.employeeID = tblL.employeeID
            WHERE 
                tblE.managerID = '" . mysqli_real_escape_string($connect_var, $this->employeeID) . "'  
                AND (tblL.status = 'Yet To Be Approved' OR tblL.status = 'ReApplied' or tblL.status = 'ExtendedApplied')";
            
            // Add date filters if provided
            if (isset($this->startDate) && !empty($this->startDate)) {
                $queryLeaveApproval .= " AND tblL.fromDate >= '" . 
                    mysqli_real_escape_string($connect_var, $this->startDate) . "'";
            }
            
            if (isset($this->endDate) && !empty($this->endDate)) {
                $queryLeaveApproval .= " AND tblL.toDate <= '" . 
                    mysqli_real_escape_string($connect_var, $this->endDate) . "'";
            }
            
            // Order by most recent first
            $queryLeaveApproval .= " ORDER BY tblL.createdOn DESC";

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

            error_log("Starting processLeaveStatus for leave ID: " . $this->applyLeaveID);

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
                $leaveType = trim($leaveDetails['typeOfLeave']);
                $employeeID = $leaveDetails['employeeID'];
                $leaveDuration = $leaveDetails['NoOfDays'];
                $decoded_items = array(
                    'applyLeaveID' => $this->applyLeaveID,
                    'typeOfLeave' => $leaveType,
                    'numberOfDays' => $leaveDuration,
                    'status' => $this->status,
                    'employeeID' => $employeeID
                );
                // Debug logging
                error_log("Leave details found:");
                error_log("Raw leave type: '" . $leaveDetails['typeOfLeave'] . "'");
                error_log("Trimmed leave type: '" . $leaveType . "'");
                error_log("Employee ID: " . $employeeID);
                error_log("Leave duration: " . $leaveDuration);

                // Begin transaction
                mysqli_begin_transaction($connect_var);

                try {
                    // First check the current status
                    $checkStatusQuery = "SELECT status FROM tblApplyLeave WHERE applyLeaveID = ?";
                    $checkStmt = mysqli_prepare($connect_var, $checkStatusQuery);
                    mysqli_stmt_bind_param($checkStmt, "s", $this->applyLeaveID);
                    mysqli_stmt_execute($checkStmt);
                    $result = mysqli_stmt_get_result($checkStmt);

                    if ($row = mysqli_fetch_assoc($result)) {
                        if ($row['status'] === 'ReApplied' && $this->status === 'Rejected') {
                            // If status was ReApplied, update to Approved
                            $statusUpdateQuery = "UPDATE tblApplyLeave 
                                SET status = 'Approved', 
                                    RejectReason = ? 
                                WHERE applyLeaveID = ?";
                            $stmt = mysqli_prepare($connect_var, $statusUpdateQuery);
                            mysqli_stmt_bind_param($stmt, "ss", $this->rejectionReason, $this->applyLeaveID);
                            echo $updateQuery;
                        } else if ($row['status'] === 'ReApplied' && $this->status === 'Approved') {
                            // For other statuses, use original update logic
                            $statusUpdateQuery = "UPDATE tblApplyLeave 
                                SET status = 'Cancelled'
                                WHERE applyLeaveID = ?";
                            $stmt = mysqli_prepare($connect_var, $statusUpdateQuery);
                            mysqli_stmt_bind_param($stmt, "s", $this->applyLeaveID);
                        } else if ($row['status'] === 'Yet To Be Approved' && $this->status === 'Rejected') {
                            // For other statuses, use original update logic
                            $statusUpdateQuery = "UPDATE tblApplyLeave 
                                SET status = 'Rejected', 
                                    RejectReason = ? 
                                WHERE applyLeaveID = ?";
                            $stmt = mysqli_prepare($connect_var, $statusUpdateQuery);
                            mysqli_stmt_bind_param($stmt, "ss", $this->rejectionReason, $this->applyLeaveID);
                        }
                        
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    } 

                    mysqli_stmt_close($checkStmt);

                    // If approved, update the leave balance
                    if ($this->status === 'Approved' && $row['status'] != 'ReApplied') {
                        // Initialize update query
                        $updateQuery = "UPDATE tblApplyLeave 
                                SET status = 'Approved'
                                WHERE applyLeaveID = '$this->applyLeaveID'";
                        //echo $updateQuery;
                        $stmt = mysqli_prepare($connect_var, $updateQuery);
                        // mysqli_stmt_bind_param($stmt, "s", $this->applyLeaveID);
                        mysqli_stmt_execute($stmt);
                        // Handle all leave types
                        /*if ($leaveType === 'Privilege Leave' || $leaveType === 'Privilege Leave (Medical grounds)') {
                            $updateQuery = "UPDATE tblLeaveBalance SET PrivilegeLeave = PrivilegeLeave - ? WHERE employeeID = ?";
                        } elseif ($leaveType === 'Casual Leave') {
                            $updateQuery = "UPDATE tblLeaveBalance SET CasualLeave = CasualLeave - ? WHERE employeeID = ?";
                        } elseif ($leaveType === 'Special Casual Leave') {
                            $updateQuery = "UPDATE tblLeaveBalance SET SpecialCasualLeave = SpecialCasualLeave - ? WHERE employeeID = ?";
                        } elseif ($leaveType === 'Compensatory Off') {
                            $updateQuery = "UPDATE tblLeaveBalance SET CompensatoryOff = CompensatoryOff - ? WHERE employeeID = ?";
                        } elseif ($leaveType === 'Special Leave for Blood Donation') {
                            $updateQuery = "UPDATE tblLeaveBalance SET SpecialLeaveBloodDonation = SpecialLeaveBloodDonation - ? WHERE employeeID = ?";
                        } elseif ($leaveType === 'Leave on Private Affairs') {
                            $updateQuery = "UPDATE tblLeaveBalance SET LeaveOnPrivateAffairs = LeaveOnPrivateAffairs - ? WHERE employeeID = ?";
                        } elseif ($leaveType === 'Medical Leave') {
                            $updateQuery = "UPDATE tblLeaveBalance SET MedicalLeave = MedicalLeave - ? WHERE employeeID = ?";
                        } elseif ($leaveType === 'Maternity Leave') {
                            $updateQuery = "UPDATE tblLeaveBalance SET MaternityLeave = MaternityLeave - ? WHERE employeeID = ?";
                        }*/
                        $updateQuery = $this->updatedLeaveBalance($decoded_items);
                        echo $updateQuery;
                        if ($updateQuery) {
                            error_log("Executing balance update query: " . $updateQuery);
                            error_log("Leave duration: " . $leaveDuration);
                            error_log("Employee ID: " . $employeeID);
                            
                            $stmt = mysqli_prepare($connect_var, $updateQuery);
                            mysqli_stmt_bind_param($stmt, "is", $leaveDuration, $employeeID);
                            
                            if (!mysqli_stmt_execute($stmt)) {
                                throw new Exception("Failed to update leave balance: " . mysqli_error($connect_var));
                            }
                        }
                    }

                    // Commit transaction
                    mysqli_commit($connect_var);

                    echo json_encode(array(
                        "status" => "success",
                        "message_text" => ($this->status === 'Approved') ? 
                            "Leave approved and balance updated successfully" : 
                            "Leave " . strtolower($this->status) . " successfully",
                        "leaveType" => $leaveType,
                        "duration" => $leaveDuration
                    ));

                } catch (Exception $e) {
                    mysqli_rollback($connect_var);
                    throw $e;
                }

            } else {
                throw new Exception("No leave found with ID: " . $this->applyLeaveID);
            }

        } catch(Exception $e) {
            error_log("Error in processLeaveStatus: " . $e->getMessage());
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage(),
                "debug_info" => array(
                    "leave_type" => isset($leaveType) ? $leaveType : null,
                    "leave_id" => $this->applyLeaveID
                )
            ), JSON_FORCE_OBJECT);
        } finally {
            if (isset($connect_var)) {
                mysqli_close($connect_var);
            }
        }
    }
    public function updatedLeaveBalance($decoded_items) {
        $this->applyLeaveID = $decoded_items['applyLeaveID'];
        $this->typeOfLeave = $decoded_items['typeOfLeave'];
        $this->numberOfDays = $decoded_items['numberOfDays'];
        $this->status = $decoded_items['status'];
        $this->employeeID = $decoded_items['employeeID'];    
 
        if (isset($decoded_items['status'] == "Approved")) {
            $updateQuery = "UPDATE tblLeaveBalance
            SET $typeOfLeave = $typeOfLeave - $numberOfDays
            WHERE employeeID = $employeeID";        }
        elseif(isset($decoded_items['status'] == ("Cancelled" || "Rejected"))) {
            $updateQuery = "UPDATE tblLeaveBalance
            SET $typeOfLeave = $typeOfLeave + $numberOfDays
            WHERE employeeID = $employeeID";        }            
        return $updateQuery;
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
                                   WHERE (a.status = 'Yet To Be Approved' or a.status = 'ReApplied' or a.status = 'ExtendedApplied')
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
        return true;
    }

    public function getApprovalHistoryInfo() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            if (!$connect_var) {
                throw new Exception("Database connection failed");
            }

            $query = "SELECT 
                tblL.applyLeaveID,
                tblE.employeeID,
                tblE.employeeName,
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
                AND tblL.status IN ('Approved', 'Rejected')
            ORDER BY tblL.createdOn DESC 
            LIMIT 50";

            $rsd = mysqli_query($connect_var, $query);
            
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
                    "message_text" => "No approval history found"
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