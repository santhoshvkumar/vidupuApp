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

    public function loadLeaveStatus($decoded_items) {
        try {
            // Validate input parameters
            if (!isset($decoded_items['applyLeaveID']) || empty($decoded_items['applyLeaveID'])) {
                throw new Exception("Missing required parameter: applyLeaveID");
            }
            if (!isset($decoded_items['status']) || empty($decoded_items['status'])) {
                throw new Exception("Missing required parameter: status");
            }

            $this->applyLeaveID = $decoded_items['applyLeaveID'];
            $this->status = $decoded_items['status'];
            $this->rejectionReason = isset($decoded_items['rejectionReason']) ? $decoded_items['rejectionReason'] : null;

            include('config.inc');
            if (!$connect_var) {
                throw new Exception("Database connection failed");
            }

            // 1. Check tblCompOff first
            $checkCompOffQuery = "SELECT EmployeeID FROM tblCompOff WHERE compOffID = ?";
            $stmtCompOff = mysqli_prepare($connect_var, $checkCompOffQuery);
            if (!$stmtCompOff) {
                 mysqli_close($connect_var);
                throw new Exception("Prepare statement failed (CompOff): " . mysqli_error($connect_var));
            }
            mysqli_stmt_bind_param($stmtCompOff, "s", $this->applyLeaveID);
            if (!mysqli_stmt_execute($stmtCompOff)) {
                mysqli_stmt_close($stmtCompOff);
                mysqli_close($connect_var);
                throw new Exception("Execute statement failed (CompOff): " . mysqli_stmt_error($stmtCompOff));
            }
            $resultCompOff = mysqli_stmt_get_result($stmtCompOff);

            if ($resultCompOff && mysqli_num_rows($resultCompOff) > 0) {
                // It's a compensatory leave
                $compOffDetails = mysqli_fetch_assoc($resultCompOff);
                $this->employeeID = $compOffDetails['EmployeeID'];
                $this->isCompOff = true;
                mysqli_stmt_close($stmtCompOff);
                mysqli_close($connect_var);
                error_log("Leave ID " . $this->applyLeaveID . " identified as Comp Off for employee " . $this->employeeID);
                return true;
            }
            mysqli_stmt_close($stmtCompOff); // Close statement if no comp off found


            // 2. If not in tblCompOff, check tblApplyLeave
            $checkRegularLeaveQuery = "SELECT employeeID FROM tblApplyLeave WHERE applyLeaveID = ?";
            $stmtRegular = mysqli_prepare($connect_var, $checkRegularLeaveQuery);
             if (!$stmtRegular) {
                mysqli_close($connect_var);
                throw new Exception("Prepare statement failed (ApplyLeave): " . mysqli_error($connect_var));
            }
            mysqli_stmt_bind_param($stmtRegular, "s", $this->applyLeaveID);
             if (!mysqli_stmt_execute($stmtRegular)) {
                mysqli_stmt_close($stmtRegular);
                mysqli_close($connect_var);
                throw new Exception("Execute statement failed (ApplyLeave): " . mysqli_stmt_error($stmtRegular));
            }
            $resultRegular = mysqli_stmt_get_result($stmtRegular);

            if ($resultRegular && mysqli_num_rows($resultRegular) > 0) {
                // It's a regular leave-
                $leaveDetails = mysqli_fetch_assoc($resultRegular);
                $this->employeeID = $leaveDetails['employeeID'];
                $this->isCompOff = false;
                mysqli_stmt_close($stmtRegular);
                mysqli_close($connect_var);
                 error_log("Leave ID " . $this->applyLeaveID . " identified as Regular Leave for employee " . $this->employeeID);
                return true;
            }
             mysqli_stmt_close($stmtRegular); // Close statement if no regular leave found

            // 3. If not found in either table
            mysqli_close($connect_var);
            throw new Exception("Leave record not found with ID: " . $this->applyLeaveID);

        } catch (Exception $e) {
            error_log("Error in loadLeaveStatus: " . $e->getMessage());
            // Ensure connection is closed on exception
            if (isset($connect_var) && mysqli_ping($connect_var)) {
                 mysqli_close($connect_var);
            }
            // Re-throw the exception to be caught by the calling router function
             throw $e; 
        }
    }

    public function getLeaveforApprovalInfo() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            if (!$connect_var) {
                throw new Exception("Database connection failed");
            }

            // Regular leave approval query
            $queryLeaveApproval = "SELECT 
                tblE.employeeID,
                tblE.employeeName,
                tblL.applyLeaveID as id,
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
                tblL.leaveDuration,
                'leave' as recordType
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
            
            // Compensatory off approval query
            $compOffApprovalQuery = "SELECT 
                tblE.employeeID,
                tblE.employeeName,
                tblC.compOffID as id,
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
                1 as leaveDuration,
                'compoff' as recordType
            FROM 
                tblEmployee tblE
            INNER JOIN 
                tblCompOff tblC ON tblE.employeeID = tblC.EmployeeID
            WHERE 
                tblE.managerID = '" . mysqli_real_escape_string($connect_var, $this->employeeID) . "'  
                AND tblC.status = 'Yet To Be Approved'";
            
            // Add date filters for comp off if provided
            if (isset($this->startDate) && !empty($this->startDate)) {
                $compOffApprovalQuery .= " AND tblC.date >= '" . 
                    mysqli_real_escape_string($connect_var, $this->startDate) . "'";
            }
            
            if (isset($this->endDate) && !empty($this->endDate)) {
                $compOffApprovalQuery .= " AND tblC.date <= '" . 
                    mysqli_real_escape_string($connect_var, $this->endDate) . "'";
            }
            
            // Combine the queries
            $combinedQuery = "($queryLeaveApproval) UNION ($compOffApprovalQuery) ORDER BY createdOn DESC";

            $rsd = mysqli_query($connect_var, $combinedQuery);
            
            if (!$rsd) {
                throw new Exception(mysqli_error($connect_var));
            }

            $resultArr = array();
            $count = 0;
            
            while($rs = mysqli_fetch_assoc($rsd)) {
                // Normalize field names for backward compatibility
                if (!isset($rs['applyLeaveID']) && isset($rs['id'])) {
                    $rs['applyLeaveID'] = $rs['id'];
                }
                
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

    public function processCompOffStatus() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            if (!$connect_var) {
                throw new Exception("Database connection failed");
            }
            
            // Add debug logging
            error_log("Starting processCompOffStatus for ID: " . $this->applyLeaveID);
            error_log("Status received: '" . $this->status . "'");
            
            // Begin transaction
            mysqli_begin_transaction($connect_var);
            
            try {
                // First verify the comp off exists and is not already used
                $verifyQuery = "SELECT isUsed, usedOn FROM tblCompOff WHERE compOffID = ?";
                $verifyStmt = mysqli_prepare($connect_var, $verifyQuery);
                if (!$verifyStmt) {
                    throw new Exception("Prepare statement failed: " . mysqli_error($connect_var));
                }
                
                mysqli_stmt_bind_param($verifyStmt, "s", $this->applyLeaveID);
                mysqli_stmt_execute($verifyStmt);
                $verifyResult = mysqli_stmt_get_result($verifyStmt);
                
                if ($verifyRow = mysqli_fetch_assoc($verifyResult)) {
                    if ($verifyRow['isUsed'] == 1) {
                        throw new Exception("This compensatory off has already been used");
                    }
                    
                    // Update the comp off status and mark as used
                    $updateCompOffQuery = "UPDATE tblCompOff 
                                         SET status = ?, 
                                             isUsed = 1, 
                                             usedOn = CURRENT_TIMESTAMP 
                                         WHERE compOffID = ?";
                    $stmt = mysqli_prepare($connect_var, $updateCompOffQuery);
                    if (!$stmt) {
                        throw new Exception("Prepare update statement failed: " . mysqli_error($connect_var));
                    }
                    
                    mysqli_stmt_bind_param($stmt, "ss", $this->status, $this->applyLeaveID);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Failed to execute comp off update: " . mysqli_stmt_error($stmt));
                    }
                    
                    // Verify the update
                    $verifyAfterQuery = "SELECT isUsed, usedOn, status FROM tblCompOff WHERE compOffID = ?";
                    $verifyAfterStmt = mysqli_prepare($connect_var, $verifyAfterQuery);
                    mysqli_stmt_bind_param($verifyAfterStmt, "s", $this->applyLeaveID);
                    mysqli_stmt_execute($verifyAfterStmt);
                    $verifyAfterResult = mysqli_stmt_get_result($verifyAfterStmt);
                    
                    if ($verifyAfterRow = mysqli_fetch_assoc($verifyAfterResult)) {
                        error_log("Verification - isUsed: " . $verifyAfterRow['isUsed'] . 
                                ", usedOn: " . $verifyAfterRow['usedOn'] . 
                                ", status: " . $verifyAfterRow['status']);
                        if ($verifyAfterRow['isUsed'] != 1) {
                            throw new Exception("Failed to update isUsed flag");
                        }
                    }
                    
                    // If approved, update the leave balance
                    if (strtolower($this->status) === 'approved') {
                        error_log("Status match found for 'Approved', proceeding with balance update");
                        $empID = (int)$this->employeeID;
                        
                        // Check if the employee exists in the leave balance table
                        $checkBalanceQuery = "SELECT employeeID FROM tblLeaveBalance WHERE employeeID = ?";
                        $checkStmt = mysqli_prepare($connect_var, $checkBalanceQuery);
                        if (!$checkStmt) {
                            throw new Exception("Check balance prepare failed: " . mysqli_error($connect_var));
                        }
                        
                        mysqli_stmt_bind_param($checkStmt, "i", $empID);
                        mysqli_stmt_execute($checkStmt);
                        $balanceResult = mysqli_stmt_get_result($checkStmt);
                        
                        if (mysqli_num_rows($balanceResult) === 0) {
                            throw new Exception("Employee ID " . $empID . " not found in leave balance table");
                        }
                        
                        error_log("Updating leave balance for employee ID: " . $empID);
                        
                        $updateBalanceQuery = "UPDATE tblLeaveBalance 
                                             SET CompensatoryOff = CompensatoryOff + 1
                                             WHERE employeeID = ?";
                        $stmt = mysqli_prepare($connect_var, $updateBalanceQuery);
                        if (!$stmt) {
                            throw new Exception("Update balance prepare failed: " . mysqli_error($connect_var));
                        }
                        
                        mysqli_stmt_bind_param($stmt, "i", $empID);
                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception("Balance update failed: " . mysqli_stmt_error($stmt));
                        }
                        
                        error_log("Successfully updated compensatory off balance");
                    }
                    
                    // Commit the transaction
                    mysqli_commit($connect_var);
                    
                    echo json_encode(array(
                        "status" => "success",
                        "message_text" => (strtolower($this->status) === 'approved') ? 
                            "Compensatory off request approved and balance updated" : 
                            "Compensatory off request rejected",
                        "leaveType" => "Compensatory Off",
                        "duration" => 1,
                        "isUsed" => 1,
                        "usedOn" => date('Y-m-d H:i:s')
                    ));
                    
                    return true;
                    
                } else {
                    throw new Exception("No compensatory off found with ID: " . $this->applyLeaveID);
                }
                
            } catch (Exception $e) {
                mysqli_rollback($connect_var);
                error_log("Error in comp off transaction: " . $e->getMessage());
                throw $e;
            } finally {
                if (isset($stmt)) mysqli_stmt_close($stmt);
                if (isset($verifyStmt)) mysqli_stmt_close($verifyStmt);
                if (isset($verifyAfterStmt)) mysqli_stmt_close($verifyAfterStmt);
                if (isset($checkStmt)) mysqli_stmt_close($checkStmt);
            }
            
        } catch (Exception $e) {
            error_log("Error in processCompOffStatus: " . $e->getMessage());
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ), JSON_FORCE_OBJECT);
            return true;
        } finally {
            if (isset($connect_var)) {
                mysqli_close($connect_var);
            }
        }
    }

    public function processLeaveStatus() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            if (!$connect_var) {
                throw new Exception("Database connection failed");
            }

            // If this is a compensatory off leave, process it accordingly
            if ($this->isCompOff === true) {
                return $this->processCompOffStatus();
            }

            // Get leave details
            $queryGetLeave = "SELECT applyLeaveID, typeOfLeave, employeeID, status, 
                              DATEDIFF(toDate, fromDate) + 1 as NoOfDays, fromDate, 
                              leaveDuration, FitnessCertificatePath, NoOfDaysExtend
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
                $fromDate = $leaveDetails['fromDate'];
                $leaveDuration = $leaveDetails['leaveDuration'];
                $FitnessCertificatePath = $leaveDetails['FitnessCertificatePath'];
                $noOfDaysExtend = $leaveDetails['NoOfDaysExtend'];
                
                $decoded_items = array(
                    'applyLeaveID' => $this->applyLeaveID,
                    'typeOfLeave' => $leaveType,
                    'numberOfDays' => $leaveDuration,
                    'status' => $this->status,
                    'employeeID' => $employeeID
                );
                
                // Begin transaction
                mysqli_begin_transaction($connect_var);

                try {
                    // First check the current status
                    $checkStatusQuery = "SELECT status, isExtend FROM tblApplyLeave WHERE applyLeaveID = ?";
                    $checkStmt = mysqli_prepare($connect_var, $checkStatusQuery);
                    mysqli_stmt_bind_param($checkStmt, "s", $this->applyLeaveID);
                    mysqli_stmt_execute($checkStmt);
                    $result = mysqli_stmt_get_result($checkStmt);

                    if ($row = mysqli_fetch_assoc($result)) {
                        if ($row['status'] === 'Yet To Be Approved') {
                            // Handle new leave approval/rejection
                            if ($this->status === 'Approved') {
                                $statusUpdateQuery = "UPDATE tblApplyLeave 
                                    SET status = 'Approved'
                                    WHERE applyLeaveID = ?";
                                $stmt = mysqli_prepare($connect_var, $statusUpdateQuery);
                                mysqli_stmt_bind_param($stmt, "s", $this->applyLeaveID);
                            } else if ($this->status === 'Rejected') {
                                $statusUpdateQuery = "UPDATE tblApplyLeave 
                                    SET status = 'Rejected',
                                        RejectReason = ? 
                                    WHERE applyLeaveID = ?";
                                $stmt = mysqli_prepare($connect_var, $statusUpdateQuery);
                                mysqli_stmt_bind_param($stmt, "ss", $this->rejectionReason, $this->applyLeaveID);
                            }
                            
                            if (isset($stmt)) {
                                mysqli_stmt_execute($stmt);
                                mysqli_stmt_close($stmt);
                            }
                            
                            // If approved, update the leave balance conditionally
                            if ($this->status === 'Approved') {
                                $deductBalance = true; // Assume deduction by default for approved leaves

                                // Check if it's Medical Leave or PL (Medical Grounds)
                                if ($leaveType === 'Medical Leave' || $leaveType === 'Privilege Leave (Medical grounds)') {
                                    // For these types, only deduct if Fitness Certificate is present
                                    if (empty($FitnessCertificatePath) || $FitnessCertificatePath === 'null') {
                                        $deductBalance = false; // Do not deduct if fitness cert is missing
                                        error_log("Skipping balance deduction for $leaveType (ID: $this->applyLeaveID) - Fitness Certificate missing.");
                                    } else {
                                         error_log("Proceeding with balance deduction for $leaveType (ID: $this->applyLeaveID) - Fitness Certificate found.");
                                    }
                                } else {
                                     error_log("Proceeding with balance deduction for non-medical leave type: $leaveType (ID: $this->applyLeaveID).");
                                }

                                // Proceed with balance deduction if flagged
                                if ($deductBalance) {
                                    $updateQuery = $this->updatedLeaveBalance($decoded_items);
                                    if ($updateQuery) {
                                        $stmtQueryUpdate = mysqli_prepare($connect_var, $updateQuery);
                                        if (!$stmtQueryUpdate) {
                                            throw new Exception("Prepare statement failed for balance update: " . mysqli_error($connect_var));
                                        }
                                        if (!mysqli_stmt_execute($stmtQueryUpdate)) {
                                             throw new Exception("Execute statement failed for balance update: " . mysqli_stmt_error($stmtQueryUpdate));
                                        }
                                        mysqli_stmt_close($stmtQueryUpdate);
                                        error_log("Successfully deducted balance for leave ID: $this->applyLeaveID");
                                    } else {
                                         error_log("No balance update query generated for leave ID: $this->applyLeaveID");
                                    }
                                }
                            }
                        } else if ($row['status'] === 'ReApplied') {
                            // Handle reapplied leaves
                            if ($this->status === 'Approved') {
                                $statusUpdateQuery = "UPDATE tblApplyLeave 
                                    SET status = 'Cancelled'
                                    WHERE applyLeaveID = ?";
                                $stmt = mysqli_prepare($connect_var, $statusUpdateQuery);
                                mysqli_stmt_bind_param($stmt, "s", $this->applyLeaveID);
                            } else if ($this->status === 'Rejected') {
                                $statusUpdateQuery = "UPDATE tblApplyLeave 
                                    SET status = 'Approved',
                                        RejectReason = ? 
                                    WHERE applyLeaveID = ?";
                                $stmt = mysqli_prepare($connect_var, $statusUpdateQuery);
                                mysqli_stmt_bind_param($stmt, "ss", $this->rejectionReason, $this->applyLeaveID);
                            }
                            
                            if (isset($stmt)) {
                                mysqli_stmt_execute($stmt);
                                mysqli_stmt_close($stmt);
                            }
                        } else if ($row['status'] === 'ExtendedApplied') {
                            // Handle extended leaves
                            if ($this->status === 'Rejected') {
                                $date = new DateTime($fromDate);
                                $date->modify('+'.intval($leaveDuration - 1).' day');
                                $toDate = $date->format('Y-m-d');
                                $statusUpdateQuery = "UPDATE tblApplyLeave 
                                    SET status = 'Approved', 
                                        isExtend = 0, 
                                        reasonForExtend = NULL, 
                                        NoOfDaysExtend = NULL, 
                                        toDate = ? 
                                    WHERE applyLeaveID = ?";
                                $stmt = mysqli_prepare($connect_var, $statusUpdateQuery);
                                mysqli_stmt_bind_param($stmt, "ss", $toDate, $this->applyLeaveID);
                                mysqli_stmt_execute($stmt);
                                mysqli_stmt_close($stmt);
                            }
                        }
                    }

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
                "message_text" => $e->getMessage()
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
                throw new Exception("Invalid leave type: " . $this->typeOfLeave);
        }
        if ($this->status == "Approved") {
                    $updateQuery = "UPDATE tblLeaveBalance
                        SET $this->typeOfLeave = $this->typeOfLeave - $this->numberOfDays
                        WHERE employeeID = $this->employeeID";        
        }
        elseif($this->status == ("Cancelled" || "Rejected")) {
            $updateQuery = "UPDATE tblLeaveBalance
            SET $this->typeOfLeave = $this->typeOfLeave + $this->numberOfDays
            WHERE employeeID = $this->employeeID";        
        }
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

            // Regular leave query
            $regularLeaveQuery = "SELECT 
                tblL.applyLeaveID as id,
                tblE.employeeID,
                tblE.employeeName,
                tblE.empID,
                tblL.fromDate as fromDate,
                tblL.toDate,
                tblL.typeOfLeave as leaveType,
                tblL.reason,
                tblL.createdOn,
                tblL.status,
                tblL.MedicalCertificatePath,
                tblL.FitnessCertificatePath,
                DATEDIFF(tblL.toDate, tblL.fromDate) + 1 as NoOfDays,
                'leave' as recordType
            FROM 
                tblEmployee tblE
            INNER JOIN 
                tblApplyLeave tblL ON tblE.employeeID = tblL.employeeID
            WHERE 
                tblE.managerID = '" . mysqli_real_escape_string($connect_var, $this->employeeID) . "'  
                AND tblL.status IN ('Approved', 'Rejected')";

            // Compensatory off leave query
            $compOffQuery = "SELECT 
                tblC.compOffID as id,
                tblE.employeeID,
                tblE.employeeName,
                tblE.empID,
                tblC.date as fromDate,
                tblC.date as toDate,
                'Compensatory Off' as leaveType,
                tblC.reason,
                tblC.createdOn,
                tblC.status,
                NULL as MedicalCertificatePath,
                NULL as FitnessCertificatePath,
                1 as NoOfDays,
                'compoff' as recordType
            FROM 
                tblEmployee tblE
            INNER JOIN 
                tblCompOff tblC ON tblE.employeeID = tblC.EmployeeID
            WHERE 
                tblE.managerID = '" . mysqli_real_escape_string($connect_var, $this->employeeID) . "'  
                AND tblC.status IN ('Approved', 'Rejected')";

            // Combine queries with UNION
            $combinedQuery = "($regularLeaveQuery) UNION ($compOffQuery) ORDER BY createdOn DESC LIMIT 50";

            $rsd = mysqli_query($connect_var, $combinedQuery);
            
            if (!$rsd) {
                throw new Exception(mysqli_error($connect_var));
            }

            $resultArr = array();
            $count = 0;
            
            while($rs = mysqli_fetch_assoc($rsd)) {
                // Normalize field names to maintain backward compatibility
                if (!isset($rs['applyLeaveID']) && isset($rs['id'])) {
                    $rs['applyLeaveID'] = $rs['id'];
                }
                if (!isset($rs['typeOfLeave']) && isset($rs['leaveType'])) {
                    $rs['typeOfLeave'] = $rs['leaveType'];
                }
                
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
    try {
        // Validate required parameters
        if (!isset($decoded_items['applyLeaveID']) || empty($decoded_items['applyLeaveID'])) {
            throw new Exception("Missing required parameter: applyLeaveID");
        }
        
        if (!isset($decoded_items['status']) || empty($decoded_items['status'])) {
            throw new Exception("Missing required parameter: status");
        }
        
        $leaveObject = new ApproveLeaveMaster();
        if($leaveObject->loadLeaveStatus($decoded_items)){
            $leaveObject->processLeaveStatus();
        } else {
            throw new Exception("Failed to load leave status");
        }
    } catch (Exception $e) {
        error_log("Error in approvedLeave: " . $e->getMessage());
        echo json_encode(array(
            "status" => "error",
            "message_text" => $e->getMessage()
        ), JSON_FORCE_OBJECT);
    }
}

function processRejectedLeave($decoded_items) {
    try {
        // Validate required parameters
        if (!isset($decoded_items['applyLeaveID']) || empty($decoded_items['applyLeaveID'])) {
            throw new Exception("Missing required parameter: applyLeaveID");
        }
        
        if (!isset($decoded_items['status']) || empty($decoded_items['status'])) {
            throw new Exception("Missing required parameter: status");
        }
        
        $leaveObject = new ApproveLeaveMaster();
        if($leaveObject->loadLeaveStatus($decoded_items)){
            $leaveObject->processLeaveStatus();
        } else {
            throw new Exception("Failed to load leave status");
        }
    } catch (Exception $e) {
        error_log("Error in processRejectedLeave: " . $e->getMessage());
        echo json_encode(array(
            "status" => "error",
            "message_text" => $e->getMessage()
        ), JSON_FORCE_OBJECT);
    }
}

function processHoldLeave($decoded_items) {
    try {
        // Validate required parameters
        if (!isset($decoded_items['applyLeaveID']) || empty($decoded_items['applyLeaveID'])) {
            throw new Exception("Missing required parameter: applyLeaveID");
        }
        
        if (!isset($decoded_items['status']) || empty($decoded_items['status'])) {
            throw new Exception("Missing required parameter: status");
        }
        
        $leaveObject = new ApproveLeaveMaster();
        if($leaveObject->loadLeaveStatus($decoded_items)){
            $leaveObject->processLeaveStatus();
        } else {
            throw new Exception("Failed to load leave status");
        }
    } catch (Exception $e) {
        error_log("Error in processHoldLeave: " . $e->getMessage());
        echo json_encode(array(
            "status" => "error",
            "message_text" => $e->getMessage()
        ), JSON_FORCE_OBJECT);
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