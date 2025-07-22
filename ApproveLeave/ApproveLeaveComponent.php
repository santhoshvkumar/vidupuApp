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
    public $isCompOff;
    public $rejectionReason;
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
//added compOffID and isCompOff in loadLeaveStatus
    public function loadLeaveStatus($decoded_items) {
        $this->isCompOff = isset($decoded_items['isCompOff']) ? $decoded_items['isCompOff'] : false;
        
        if ($this->isCompOff) {
            $this->applyLeaveID = $decoded_items['compOffID'];
        } else {
            $this->applyLeaveID = $decoded_items['applyLeaveID'];
        }
        
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

            // Log initial request details once
            error_log("Processing leave request - ID: " . $this->applyLeaveID . 
                     ", Type: " . ($this->isCompOff ? "Comp Off" : "Regular Leave") . 
                     ", Status: " . $this->status);

            //added for comp off Leave approval
            if ($this->isCompOff) {
                // Begin transaction for comp off
                mysqli_begin_transaction($connect_var);
                try {
                    // Update comp off status using compOffID
                    $updateQuery = "UPDATE tblCompOff SET status = ?";
                    if ($this->status === 'Rejected' && isset($this->rejectionReason)) {
                        $updateQuery .= ", rejectedReason = ?";
                    }
                    $updateQuery .= " WHERE compOffID = ?";
                    
                    $stmt = mysqli_prepare($connect_var, $updateQuery);
                    if (!$stmt) {
                        throw new Exception("Failed to prepare statement: " . mysqli_error($connect_var));
                    }

                    if ($this->status === 'Rejected' && isset($this->rejectionReason)) {
                        mysqli_stmt_bind_param($stmt, "sss", $this->status, $this->rejectionReason, $this->applyLeaveID);
                    } else {
                        mysqli_stmt_bind_param($stmt, "ss", $this->status, $this->applyLeaveID);
                    }
                    
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Failed to update comp off status: " . mysqli_error($connect_var));
                    }

                    // If approved, update the leave balance
                    if ($this->status === 'Approved') {
                        $updateBalanceQuery = "UPDATE tblLeaveBalance 
                                             SET CompensatoryOff = CompensatoryOff + 1 
                                             WHERE employeeID = (SELECT employeeID FROM tblCompOff WHERE compOffID = ?)";
                        
                        $stmtBalance = mysqli_prepare($connect_var, $updateBalanceQuery);
                        if (!$stmtBalance) {
                            throw new Exception("Failed to prepare balance update statement: " . mysqli_error($connect_var));
                        }

                        mysqli_stmt_bind_param($stmtBalance, "s", $this->applyLeaveID);
                        
                        if (!mysqli_stmt_execute($stmtBalance)) {
                            throw new Exception("Failed to update leave balance: " . mysqli_error($connect_var));
                        }
                        mysqli_stmt_close($stmtBalance);
                    }

                    mysqli_stmt_close($stmt);
                    mysqli_commit($connect_var);
                    
                    echo json_encode(array(
                        "status" => "success",
                        "message_text" => "Comp off request " . strtolower($this->status) . " successfully"
                    ));
                    return;

                } catch (Exception $e) {
                    mysqli_rollback($connect_var);
                    error_log("Error in comp off processing: " . $e->getMessage());
                    throw $e;
                }
            }

            // Regular leave processing continues here...
            // Get leave details
            $queryGetLeave = "SELECT applyLeaveID, typeOfLeave, employeeID, status, 
                              DATEDIFF(toDate, fromDate) + 1 as NoOfDays, fromDate, 
                              leaveDuration, FitnessCertificatePath, NoOfDaysExtend
                              FROM tblApplyLeave 
                              WHERE applyLeaveID = ?";
                             
            $stmt = mysqli_prepare($connect_var, $queryGetLeave);
            if (!$stmt) {
                throw new Exception("Failed to prepare leave details statement: " . mysqli_error($connect_var));
            }

            mysqli_stmt_bind_param($stmt, "s", $this->applyLeaveID);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to execute leave details query: " . mysqli_error($connect_var));
            }

            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $leaveDetails = mysqli_fetch_assoc($result);
                $leaveType = trim($leaveDetails['typeOfLeave']);
                $employeeID = $leaveDetails['employeeID'];
                $fromDate = $leaveDetails['fromDate'];
                $leaveDuration = $leaveDetails['leaveDuration'];
                $FitnessCertificatePath = $leaveDetails['FitnessCertificatePath'];
                $noOfDaysExtend = $leaveDetails['NoOfDaysExtend'];
                
                mysqli_free_result($result);
                mysqli_stmt_close($stmt);

                // Begin transaction
                mysqli_begin_transaction($connect_var);

                try {
                    // First check the current status
                    $checkStatusQuery = "SELECT status, isExtend FROM tblApplyLeave WHERE applyLeaveID = ?";
                    $checkStmt = mysqli_prepare($connect_var, $checkStatusQuery);
                    if (!$checkStmt) {
                        throw new Exception("Failed to prepare status check statement: " . mysqli_error($connect_var));
                    }

                    mysqli_stmt_bind_param($checkStmt, "s", $this->applyLeaveID);
                    if (!mysqli_stmt_execute($checkStmt)) {
                        throw new Exception("Failed to execute status check query: " . mysqli_error($connect_var));
                    }

                    $result = mysqli_stmt_get_result($checkStmt);
                    $row = mysqli_fetch_assoc($result);
                    mysqli_free_result($result);
                    mysqli_stmt_close($checkStmt);

                    if ($row) {
                        $statusUpdateQuery = "";
                        $stmt = null;
                        
                        // Process based on current status
                        if ($row['status'] === 'ReApplied' && $this->status === 'Rejected') {
                            $statusUpdateQuery = "UPDATE tblApplyLeave 
                                SET status = 'Approved', 
                                    RejectReason = ? 
                                WHERE applyLeaveID = ?";
                            $stmt = mysqli_prepare($connect_var, $statusUpdateQuery);
                            mysqli_stmt_bind_param($stmt, "ss", $this->rejectionReason, $this->applyLeaveID);
                        } else if ($row['status'] === 'ReApplied' && $this->status === 'Approved') {
                            if ($leaveType === 'Medical Leave' && $row['isExtend'] == 1) {
                                $statusUpdateQuery = "UPDATE tblApplyLeave 
                                    SET status = 'Approved', isExtend = 0, reasonForExtend = NULL, NoOfDaysExtend = NULL
                                    WHERE applyLeaveID = ?";
                                $stmt = mysqli_prepare($connect_var, $statusUpdateQuery);
                                mysqli_stmt_bind_param($stmt, "s", $this->applyLeaveID);
                            } else {
                                $statusUpdateQuery = "UPDATE tblApplyLeave 
                                    SET status = 'Cancelled'
                                    WHERE applyLeaveID = ?";
                                $stmt = mysqli_prepare($connect_var, $statusUpdateQuery);
                                mysqli_stmt_bind_param($stmt, "s", $this->applyLeaveID);
                            }
                        } else if ($row['status'] === 'Yet To Be Approved' && $this->status === 'Rejected') {
                            $statusUpdateQuery = "UPDATE tblApplyLeave 
                                SET status = 'Rejected', 
                                    RejectReason = ? 
                                WHERE applyLeaveID = ?";
                            $stmt = mysqli_prepare($connect_var, $statusUpdateQuery);
                            mysqli_stmt_bind_param($stmt, "ss", $this->rejectionReason, $this->applyLeaveID);
                        } else if ($row['status'] === 'Yet To Be Approved' && $this->status === 'Approved') {
                            $statusUpdateQuery = "UPDATE tblApplyLeave 
                                SET status = 'Approved'
                                WHERE applyLeaveID = ?";
                            $stmt = mysqli_prepare($connect_var, $statusUpdateQuery);
                            mysqli_stmt_bind_param($stmt, "s", $this->applyLeaveID);
                        } else if ($row['status'] === 'ExtendedApplied' && $this->status === 'Rejected') {
                            $date = new DateTime($fromDate);
                            $date->modify('+'.intval($leaveDuration - 1).' day');
                            $toDate = $date->format('Y-m-d');
                            $statusUpdateQuery = "UPDATE tblApplyLeave 
                                            SET status = 'Rejected', isExtend = 0, reasonForExtend = NULL, NoOfDaysExtend = NULL, toDate = '$toDate', RejectReason = ? 
                                            WHERE applyLeaveID = ?";
                            $stmt = mysqli_prepare($connect_var, $statusUpdateQuery);
                            mysqli_stmt_bind_param($stmt, "ss", $this->rejectionReason, $this->applyLeaveID);
                        } else if ($row['status'] === 'ExtendedApplied' && $this->status === 'Approved') {
                            // For ExtendedApplied approval, approve the extended leave
                            $statusUpdateQuery = "UPDATE tblApplyLeave 
                                            SET status = 'Approved', isExtend = 1, reasonForExtend = NULL, NoOfDaysExtend = NULL
                                            WHERE applyLeaveID = ?";
                            $stmt = mysqli_prepare($connect_var, $statusUpdateQuery);
                            mysqli_stmt_bind_param($stmt, "s", $this->applyLeaveID);
                        } else if ($this->status === 'Cancelled') {
                            // Handle cancellation for any current status
                            $statusUpdateQuery = "UPDATE tblApplyLeave 
                                            SET status = 'Cancelled'
                                            WHERE applyLeaveID = ?";
                            $stmt = mysqli_prepare($connect_var, $statusUpdateQuery);
                            mysqli_stmt_bind_param($stmt, "s", $this->applyLeaveID);
                        }

                        if ($stmt && !mysqli_stmt_execute($stmt)) {
                            throw new Exception("Failed to update leave status: " . mysqli_error($connect_var));
                        }
                        
                        if ($stmt) {
                            mysqli_stmt_close($stmt);
                        }

                        // Handle leave balance updates based on status and leave type
                        $shouldUpdateBalance = true;
                        $balanceAction = 'deduct'; // Default action
                        
                        // Check if this leave was previously approved and had balance deducted
                        $wasPreviouslyApproved = ($row['status'] === 'Approved' || $row['status'] === 'Cancelled' || $row['status'] === 'ReApplied' || $row['status'] === 'ExtendedApplied');
                        
                        if ($this->status === 'Approved') {
                            // For approved leaves, check if deduction is needed
                            if ($leaveType === 'Medical Leave' || $leaveType === 'Privilege Leave (Medical grounds)') {
                                // For medical leaves, only deduct if Fitness Certificate is present
                                if (empty($FitnessCertificatePath) || $FitnessCertificatePath === 'null') {
                                    $shouldUpdateBalance = false; // Do not deduct if fitness cert is missing
                                    error_log("Skipping balance deduction for $leaveType (ID: $this->applyLeaveID) - Fitness Certificate missing.");
                                } else {
                                    // If this was previously approved, we should restore balance instead of deducting
                                    if ($wasPreviouslyApproved) {
                                        $balanceAction = 'restore';
                                        error_log("Restoring balance for previously approved $leaveType (ID: $this->applyLeaveID) - Fitness Certificate found.");
                                    } else {
                                        $balanceAction = 'deduct';
                                        error_log("Proceeding with balance deduction for $leaveType (ID: $this->applyLeaveID) - Fitness Certificate found.");
                                    }
                                }
                            } else {
                                // For non-medical leaves, check if previously approved
                                if ($wasPreviouslyApproved) {
                                    $balanceAction = 'restore';
                                    error_log("Restoring balance for previously approved non-medical leave type: $leaveType (ID: $this->applyLeaveID).");
                                } else {
                                    $balanceAction = 'deduct';
                                    error_log("Proceeding with balance deduction for non-medical leave type: $leaveType (ID: $this->applyLeaveID).");
                                }
                            }
                        } else if ($this->status === 'Rejected' || $this->status === 'Cancelled') {
                            // For rejected/cancelled leaves, check if we need to restore balance
                            if ($leaveType === 'Medical Leave' || $leaveType === 'Privilege Leave (Medical grounds)') {
                                // For medical leaves, only restore if Fitness Certificate was present (meaning balance was deducted)
                                if (!empty($FitnessCertificatePath) && $FitnessCertificatePath !== 'null') {
                                    $balanceAction = 'restore';
                                    error_log("Restoring balance for $leaveType (ID: $this->applyLeaveID) - Fitness Certificate was present.");
                                } else {
                                    $shouldUpdateBalance = false; // No balance was deducted, so no need to restore
                                    error_log("Skipping balance restoration for $leaveType (ID: $this->applyLeaveID) - No balance was deducted initially.");
                                }
                            } else {
                                // For non-medical leaves, always restore balance
                                $balanceAction = 'restore';
                                error_log("Restoring balance for non-medical leave type: $leaveType (ID: $this->applyLeaveID).");
                            }
                        }

                        // Update balance if needed
                        if ($shouldUpdateBalance) {
                            $leaveBalanceParams = [
                                'applyLeaveID' => $this->applyLeaveID,
                                'typeOfLeave' => $leaveType,
                                'numberOfDays' => $leaveDuration,
                                'status' => $this->status,
                                'employeeID' => $employeeID,
                                'balanceAction' => $balanceAction
                            ];
                            
                            $balanceResult = $this->updatedLeaveBalance($leaveBalanceParams);
                            if ($balanceResult['status'] === 'failure') {
                                throw new Exception("Failed to update leave balance: " . $balanceResult['message']);
                            }
                            
                            // Execute the balance update query
                            if (!mysqli_query($connect_var, $balanceResult['query'])) {
                                throw new Exception("Failed to execute leave balance update: " . mysqli_error($connect_var));
                            }
                        }
                    }

                    mysqli_commit($connect_var);
                    
                    echo json_encode(array(
                        "status" => "success",
                        "message_text" => "Leave request " . strtolower($this->status) . " successfully"
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
                "message_text" => $e->getMessage()
            ));
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
        $balanceAction = isset($decoded_items['balanceAction']) ? $decoded_items['balanceAction'] : 'deduct';
        
        // Map leave types to database column names
        switch ($this->typeOfLeave) {
            case "Privilege Leave":
                $this->typeOfLeave = "PrivilegeLeave";
                break;
            case "Casual Leave":
                $this->typeOfLeave = "CasualLeave";
                break;
            case "Special Casual Leave":
                $this->typeOfLeave = "SpecialCasualLeave";
                break;
            case "Compensatory Off":
                $this->typeOfLeave = "CompensatoryOff";
                break;
            case "Medical Leave":
                $this->typeOfLeave = "MedicalLeave";
                break;
            case "Maternity Leave":
                $this->typeOfLeave = "MaternityLeave";
                break;
            case "Privilege Leave (Medical grounds)":
                $this->typeOfLeave = "PrivilegeLeave";
                break;
            default:
                return array(
                    'status' => 'failure',
                    'message' => "Invalid leave type: " . $this->typeOfLeave
                );
        }

        // Prepare the update query based on balance action
        if ($balanceAction === "deduct") {
            $updateQuery = "UPDATE tblLeaveBalance 
                SET `{$this->typeOfLeave}` = `{$this->typeOfLeave}` - {$this->numberOfDays} 
                WHERE employeeID = '{$this->employeeID}'";
        } else if ($balanceAction === "restore") {
            $updateQuery = "UPDATE tblLeaveBalance 
                SET `{$this->typeOfLeave}` = `{$this->typeOfLeave}` + {$this->numberOfDays} 
                WHERE employeeID = '{$this->employeeID}'";
        } else {
            return array(
                'status' => 'failure',
                'message' => "Invalid balance action: " . $balanceAction
            );
        }

        return array(
            'status' => 'success',
            'query' => $updateQuery
        );
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
                              DATEDIFF(toDate, fromDate) + 1 as NoOfDays,
                              leaveDuration
                              FROM tblApplyLeave 
                              WHERE applyLeaveID = ? AND typeOfLeave = 'Maternity Leave'";
                             
            $stmt = mysqli_prepare($connect_var, $queryGetLeave);
            mysqli_stmt_bind_param($stmt, "s", $this->applyLeaveID);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $leaveDetails = mysqli_fetch_assoc($result);
                $employeeID = $leaveDetails['employeeID'];
                $leaveDuration = $leaveDetails['leaveDuration'];

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

    public function getCompOffForApprovalInfo() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            if (!$connect_var) {
                throw new Exception("Database connection failed");
            }

            $queryCompOffApproval = "SELECT 
                tblE.employeeID,
                tblE.employeeName,
                tblC.compOffID as applyLeaveID,
                tblE.empID,
                tblC.date as fromDate,
                tblC.date as toDate,
                'Compensatory Off' as typeOfLeave,
                tblC.reason,
                tblC.createdOn,
                tblC.status,
                NULL as NoOfDaysExtend,
                NULL as reasonForExtend,
                NULL as MedicalCertificatePath,
                NULL as FitnessCertificatePath,
                1 as NoOfDays,
                1 as leaveDuration
            FROM 
                tblEmployee tblE
            INNER JOIN 
                tblCompOff tblC ON tblE.employeeID = tblC.employeeID
            WHERE 
                tblE.managerID = '" . mysqli_real_escape_string($connect_var, $this->employeeID) . "'  
                AND tblC.status = 'Yet To Be Approved'";

            // Add date filters if provided
            if (isset($this->startDate) && !empty($this->startDate)) {
                $queryCompOffApproval .= " AND tblC.date >= '" . 
                    mysqli_real_escape_string($connect_var, $this->startDate) . "'";
            }
            
            if (isset($this->endDate) && !empty($this->endDate)) {
                $queryCompOffApproval .= " AND tblC.date <= '" . 
                    mysqli_real_escape_string($connect_var, $this->endDate) . "'";
            }

            // Order by most recent first
            $queryCompOffApproval .= " ORDER BY tblC.createdOn DESC";
            
            $rsd = mysqli_query($connect_var, $queryCompOffApproval);
            
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
                    "message_text" => "No comp off requests pending for approval"
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

function getCompOffForApproval($decoded_items) {
    $leaveObject = new ApproveLeaveMaster();
    if($leaveObject->loadLeaveforApproval($decoded_items)){
        $leaveObject->getCompOffForApprovalInfo();
    }
    else{
        echo json_encode(array("status"=>"error","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }
}

?>