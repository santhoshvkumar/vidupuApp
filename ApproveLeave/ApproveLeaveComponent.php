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
        if (!isset($decoded_items['applyLeaveID']) || !isset($decoded_items['status'])) {
            throw new Exception("Missing required parameters: applyLeaveID and status");
        }
        
        $this->applyLeaveID = $decoded_items['applyLeaveID'];
        $this->status = $decoded_items['status'];
        
        // Check if this is a compensatory off leave
        include('config.inc');
        $checkQuery = "SELECT * FROM tblCompOff WHERE compOffID = ?";
        $stmt = mysqli_prepare($connect_var, $checkQuery);
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . mysqli_error($connect_var));
        }
        
        mysqli_stmt_bind_param($stmt, "s", $this->applyLeaveID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $this->isCompOff = true;
            $compOffDetails = mysqli_fetch_assoc($result);
            
            // Debug logging
            error_log("Compensatory off details: " . print_r($compOffDetails, true));
            
            if (!isset($compOffDetails['EmployeeID'])) {
                error_log("No EmployeeID found in compensatory off record. Available fields: " . implode(', ', array_keys($compOffDetails)));
                throw new Exception("Employee ID not found in compensatory off record");
            }
            
            $employeeID = $compOffDetails['EmployeeID'];
            $this->employeeID = $employeeID; // Set the class property
            
            error_log("Found comp off request for employee ID: " . $employeeID);
        } else {
            $this->isCompOff = false;
            // For regular leaves, we'll get the employeeID in processLeaveStatus
            error_log("Not a compensatory off leave, will process as regular leave");
        }
        
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
            
            // Check if isUsed and usedOn columns exist in tblCompOff
            $checkColumnsQuery = "SHOW COLUMNS FROM tblCompOff LIKE 'isUsed'";
            $columnsResult = mysqli_query($connect_var, $checkColumnsQuery);
            
            // If isUsed column doesn't exist, add it
            if (mysqli_num_rows($columnsResult) == 0) {
                error_log("Adding isUsed and usedOn columns to tblCompOff table");
                $addColumnsQuery = "ALTER TABLE tblCompOff 
                                  ADD COLUMN isUsed TINYINT(1) DEFAULT 0,
                                  ADD COLUMN usedOn DATETIME NULL";
                mysqli_query($connect_var, $addColumnsQuery);
            }
            
            // Determine if this is a comp off ID
            $checkQuery = "SELECT * FROM tblCompOff WHERE compOffID = ?";
            $stmt = mysqli_prepare($connect_var, $checkQuery);
            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . mysqli_error($connect_var));
            }
            
            mysqli_stmt_bind_param($stmt, "s", $this->applyLeaveID);
            $executeResult = mysqli_stmt_execute($stmt);
            
            if (!$executeResult) {
                throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
            }
            
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) === 0) {
                // This is not a comp off request, so return false to let other handlers process it
                error_log("No comp off found with ID: " . $this->applyLeaveID);
                return false;
            }
            
            $compOffDetails = mysqli_fetch_assoc($result);
            if (!isset($compOffDetails['EmployeeID'])) {
                error_log("No EmployeeID found in compensatory off record. Available fields: " . implode(', ', array_keys($compOffDetails)));
                throw new Exception("Employee ID not found in compensatory off record");
            }
            
            $employeeID = $compOffDetails['EmployeeID'];
            $this->employeeID = $employeeID; // Set the class property
            
            error_log("Found comp off request for employee ID: " . $employeeID);
            
            // Begin transaction
            mysqli_begin_transaction($connect_var);
            
            try {
                // Update the comp off status
                $updateStatusQuery = "UPDATE tblCompOff SET status = ? WHERE compOffID = ?";
                $stmt = mysqli_prepare($connect_var, $updateStatusQuery);
                if (!$stmt) {
                    throw new Exception("Prepare update status failed: " . mysqli_error($connect_var));
                }
                
                mysqli_stmt_bind_param($stmt, "ss", $this->status, $this->applyLeaveID);
                $executeResult = mysqli_stmt_execute($stmt);
                
                if (!$executeResult) {
                    throw new Exception("Status update failed: " . mysqli_stmt_error($stmt));
                }
                
                error_log("Updated comp off status to: " . $this->status);
                
                // If approved, update the leave balance
                if (strtolower($this->status) === 'approved') {
                    error_log("Status match found for 'Approved', proceeding with balance update");
                    // Add 1 day to CompensatoryOff balance
                    // Make sure employeeID is properly formatted - cast to int if needed
                    $empID = (int)$employeeID;
                    
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
                    $executeResult = mysqli_stmt_execute($stmt);
                    
                    if (!$executeResult) {
                        throw new Exception("Balance update failed: " . mysqli_stmt_error($stmt));
                    }
                    
                    error_log("Successfully updated compensatory off balance");
                } else {
                    error_log("Status is not 'approved' (case-insensitive), no balance update needed");
                }
                
                // Commit the transaction
                mysqli_commit($connect_var);
                
                echo json_encode(array(
                    "status" => "success",
                    "message_text" => (strtolower($this->status) === 'approved') ? 
                        "Compensatory off request approved and balance updated" : 
                        "Compensatory off request rejected",
                    "leaveType" => "Compensatory Off",
                    "duration" => 1
                ));
                
                // Return true to indicate we've processed this request
                return true;
                
            } catch (Exception $e) {
                mysqli_rollback($connect_var);
                error_log("Error in comp off transaction: " . $e->getMessage());
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Error in processCompOffStatus: " . $e->getMessage());
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ), JSON_FORCE_OBJECT);
            return true; // Indicate we've handled this even though it failed
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

            // Check if compOffID column exists in tblApplyLeave
            $checkColumnsQuery = "SHOW COLUMNS FROM tblApplyLeave LIKE 'compOffID'";
            $columnsResult = mysqli_query($connect_var, $checkColumnsQuery);
            
            // If compOffID column doesn't exist, add it
            if (mysqli_num_rows($columnsResult) == 0) {
                error_log("Adding compOffID column to tblApplyLeave table");
                $addColumnsQuery = "ALTER TABLE tblApplyLeave ADD COLUMN compOffID INT NULL";
                mysqli_query($connect_var, $addColumnsQuery);
            }

            // If this is a compensatory off leave, process it accordingly
            if ($this->isCompOff) {
                return $this->processCompOffStatus();
            }

            // Get leave details
            $queryGetLeave = "SELECT applyLeaveID, typeOfLeave, employeeID, status, 
                              DATEDIFF(toDate, fromDate) + 1 as NoOfDays, fromDate, 
                              leaveDuration, FitnessCertificatePath, NoOfDaysExtend";
                              
            // Check if compOffID column exists before adding it to query
            $checkIfColumnExists = mysqli_query($connect_var, "SHOW COLUMNS FROM tblApplyLeave LIKE 'compOffID'");
            if (mysqli_num_rows($checkIfColumnExists) > 0) {
                $queryGetLeave .= ", compOffID";
            }
            
            $queryGetLeave .= " FROM tblApplyLeave WHERE applyLeaveID = ?";
                             
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
                $compOffID = isset($leaveDetails['compOffID']) ? $leaveDetails['compOffID'] : null;
                
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
                        if ($row['status'] === 'ReApplied' && $this->status === 'Rejected') {
                            // If status was ReApplied, update to Approved
                            $statusUpdateQuery = "UPDATE tblApplyLeave 
                                SET status = 'Approved', 
                                    RejectReason = ? 
                                WHERE applyLeaveID = ?";
                            $stmt = mysqli_prepare($connect_var, $statusUpdateQuery);
                            mysqli_stmt_bind_param($stmt, "ss", $this->rejectionReason, $this->applyLeaveID);
                        } else if ($row['status'] === 'ReApplied' && $this->status === 'Approved') {
                            // For other statuses, use original update logic
                            if ($leaveType === 'Medical Leave'){
                                if( $row['isExtend'] == 1){
                                $statusUpdateQuery = "UPDATE tblApplyLeave 
                                    SET status = 'Approved', isExtend = 0, reasonForExtend = NULL, NoOfDaysExtend = NULL
                                    WHERE applyLeaveID = ?";
                                    $stmt = mysqli_prepare($connect_var, $statusUpdateQuery);
                                    mysqli_stmt_bind_param($stmt, "s", $this->applyLeaveID);
                                }
                                else{
                                    $statusUpdateQuery = "UPDATE tblApplyLeave 
                                    SET status = 'Cancelled'
                                    WHERE applyLeaveID = ?";
                                    $stmt = mysqli_prepare($connect_var, $statusUpdateQuery);
                                    mysqli_stmt_bind_param($stmt, "s", $this->applyLeaveID);
                                }
                            }
                            else{
                                $statusUpdateQuery = "UPDATE tblApplyLeave 
                                    SET status = 'Cancelled'
                                    WHERE applyLeaveID = ?";
                                    $decoded_items["status"] = "Cancelled";
                                    $stmt = mysqli_prepare($connect_var, $statusUpdateQuery);
                                    mysqli_stmt_bind_param($stmt, "s", $this->applyLeaveID);
                                   
                                    $updateQuery = $this->updatedLeaveBalance($decoded_items);
                            }
                          
                           
                            //echo $updateQuery;
                            if ($updateQuery) {
                                error_log("Executing balance update query: " . $updateQuery);
                                error_log("Leave duration: " . $leaveDuration);
                                error_log("Employee ID: " . $employeeID);
                                
                                $stmtQueryUpdate = mysqli_prepare($connect_var, $updateQuery);
                                mysqli_stmt_execute($stmtQueryUpdate);
                                mysqli_stmt_close($stmtQueryUpdate);
                            }
                        } else if ($row['status'] === 'Yet To Be Approved' && $this->status === 'Rejected') {
                            // For other statuses, use original update logic
                            $statusUpdateQuery = "UPDATE tblApplyLeave 
                                SET status = 'Rejected', 
                                    RejectReason = ? 
                                WHERE applyLeaveID = ?";
                            $stmt = mysqli_prepare($connect_var, $statusUpdateQuery);
                            mysqli_stmt_bind_param($stmt, "ss", $this->rejectionReason, $this->applyLeaveID);
                        } else if ($row['status'] === 'ExtendedApplied' && $this->status === 'Rejected') {
                            // If status was ReApplied, update to Approved
                            $date = new DateTime($fromDate);
                            $date->modify('+'.intval($leaveDuration - 1).' day');
                            $toDate = $date->format('Y-m-d');
                            $statusUpdateQuery = "UPDATE tblApplyLeave 
                                            SET status = 'Approved', isExtend = 0, reasonForExtend = NULL, NoOfDaysExtend = NULL, toDate = '$toDate' WHERE applyLeaveID = ?";
                            $stmt = mysqli_prepare($connect_var, $statusUpdateQuery);
                            mysqli_stmt_bind_param($stmt, "s", $this->applyLeaveID);
                        } 
                        
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    } 

                    mysqli_stmt_close($checkStmt);

                    // If approved, update the leave balance
                    if ($this->status === 'Approved' && $row['status'] != 'ReApplied') {
                        $canUpdateBalance = false;
                        // Initialize update query
                        $updateQuery = "UPDATE tblApplyLeave 
                                        SET status = 'Approved'
                                        WHERE applyLeaveID = '$this->applyLeaveID'";
                        // echo $updateQuery;
                        $stmt = mysqli_prepare($connect_var, $updateQuery);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);

                        // Check leave type conditions
                        switch ($leaveType) {
                            case "Privilege Leave":
                            case "Casual Leave":
                            case "Special Casual Leave":
                                $canUpdateBalance = true;
                                break;
                            
                            case "Compensatory Off":
                                // For Compensatory Off leaves, reduce the balance
                                $canUpdateBalance = true;
                                
                                // Update the comp off used status if we have a valid compOffID
                                if ($compOffID) {
                                    $updateCompOffQuery = "UPDATE tblCompOff SET isUsed = 1, usedOn = NOW() WHERE compOffID = ?";
                                    $compOffStmt = mysqli_prepare($connect_var, $updateCompOffQuery);
                                    mysqli_stmt_bind_param($compOffStmt, "s", $compOffID);
                                    $compOffResult = mysqli_stmt_execute($compOffStmt);
                                    
                                    if (!$compOffResult) {
                                        error_log("Failed to update comp off status: " . mysqli_stmt_error($compOffStmt));
                                    } else {
                                        error_log("Successfully marked comp off ID $compOffID as used");
                                    }
                                    
                                    mysqli_stmt_close($compOffStmt);
                                    
                                    // Explicitly get current balance first
                                    $getBalanceQuery = "SELECT CompensatoryOff FROM tblLeaveBalance WHERE employeeID = ?";
                                    $balanceCheckStmt = mysqli_prepare($connect_var, $getBalanceQuery);
                                    mysqli_stmt_bind_param($balanceCheckStmt, "s", $employeeID);
                                    mysqli_stmt_execute($balanceCheckStmt);
                                    $balanceResult = mysqli_stmt_get_result($balanceCheckStmt);
                                    
                                    if ($balanceRow = mysqli_fetch_assoc($balanceResult)) {
                                        $currentBalance = (int)$balanceRow['CompensatoryOff'];
                                        error_log("Current CompensatoryOff balance for employee $employeeID: $currentBalance");
                                        
                                        if ($currentBalance > 0) {
                                            // Reduce the CompensatoryOff balance by 1
                                            $updateBalanceQuery = "UPDATE tblLeaveBalance 
                                                                SET CompensatoryOff = CompensatoryOff - 1
                                                                WHERE employeeID = ?";
                                            $balanceStmt = mysqli_prepare($connect_var, $updateBalanceQuery);
                                            mysqli_stmt_bind_param($balanceStmt, "s", $employeeID);
                                            $balanceUpdateResult = mysqli_stmt_execute($balanceStmt);
                                            
                                            if (!$balanceUpdateResult) {
                                                error_log("Failed to reduce CompensatoryOff balance: " . mysqli_stmt_error($balanceStmt));
                                            } else {
                                                error_log("Successfully reduced CompensatoryOff balance for employee $employeeID from $currentBalance to " . ($currentBalance-1));
                                                
                                                // Add a direct non-parameterized query as a fallback
                                                $directQuery = "UPDATE tblLeaveBalance SET CompensatoryOff = " . ($currentBalance - 1) . " WHERE employeeID = $employeeID";
                                                error_log("Executing direct query as fallback: $directQuery");
                                                mysqli_query($connect_var, $directQuery);
                                                
                                                // Check if balance was actually updated
                                                $verifyQuery = "SELECT CompensatoryOff FROM tblLeaveBalance WHERE employeeID = $employeeID";
                                                $verifyResult = mysqli_query($connect_var, $verifyQuery);
                                                if ($verifyRow = mysqli_fetch_assoc($verifyResult)) {
                                                    $newBalance = (int)$verifyRow['CompensatoryOff'];
                                                    error_log("VERIFICATION: CompensatoryOff balance is now: $newBalance (should be " . ($currentBalance-1) . ")");
                                                    
                                                    // If balance did not change, force it with another query
                                                    if ($newBalance === $currentBalance) {
                                                        error_log("Balance did not change! Forcing update...");
                                                        $forceQuery = "UPDATE tblLeaveBalance SET CompensatoryOff = CompensatoryOff - 1 WHERE employeeID = $employeeID AND CompensatoryOff > 0";
                                                        mysqli_query($connect_var, $forceQuery);
                                                    }
                                                }
                                            }
                                            
                                            mysqli_stmt_close($balanceStmt);
                                        } else {
                                            error_log("Warning: Cannot reduce CompensatoryOff balance for employee $employeeID because current balance is $currentBalance");
                                        }
                                    } else {
                                        error_log("Failed to retrieve current CompensatoryOff balance for employee $employeeID");
                                    }
                                    
                                    mysqli_stmt_close($balanceCheckStmt);
                                } else {
                                    error_log("Warning: No compOffID found for Compensatory Off leave ID: $this->applyLeaveID, employee ID: $employeeID");
                                    
                                    // Debug: Check if the leave is actually a Compensatory Off type
                                    error_log("DEBUG: Leave type from database is: '$leaveType'");
                                    error_log("DEBUG: Is this really a Compensatory Off leave? " . ($leaveType === 'Compensatory Off' ? 'YES' : 'NO'));
                                    
                                    // Try to reduce balance directly without compOffID
                                    $debugQuery = "SELECT CompensatoryOff FROM tblLeaveBalance WHERE employeeID = $employeeID";
                                    $debugResult = mysqli_query($connect_var, $debugQuery);
                                    if ($debugRow = mysqli_fetch_assoc($debugResult)) {
                                        $debugBalance = (int)$debugRow['CompensatoryOff'];
                                        error_log("DEBUG: Current balance is $debugBalance for employee $employeeID");
                                        
                                        if ($debugBalance > 0) {
                                            $directUpdateQuery = "UPDATE tblLeaveBalance SET CompensatoryOff = CompensatoryOff - 1 WHERE employeeID = $employeeID AND CompensatoryOff > 0";
                                            error_log("DEBUG: Executing direct update: $directUpdateQuery");
                                            $directUpdateResult = mysqli_query($connect_var, $directUpdateQuery);
                                            error_log("DEBUG: Direct update result: " . ($directUpdateResult ? 'SUCCESS' : 'FAILED'));
                                        }
                                    }
                                }
                                break;
                            
                            case "Medical Leave":
                                if ($FitnessCertificatePath != null && $this->status != "Cancelled") {
                                    $decoded_items["numberOfDays"] = intval($noOfDaysExtend) + intval($leaveDuration);
                                    $canUpdateBalance = true;
                                } else {
                                    $canUpdateBalance = false;
                                }
                                break;
                        }
                        // Guess this will never be executed
                        if ($row['status'] === 'ExtendedApplied' && $FitnessCertificatePath != null) {
                            $decoded_items["numberOfDays"] = intval($noOfDaysExtend) + intval($leaveDuration);
                            $canUpdateBalance = true;
                        }       
                        if ($canUpdateBalance && $leaveType !== "Compensatory Off") {
                            $updateQuery = $this->updatedLeaveBalance($decoded_items);
                            if ($updateQuery) {
                                error_log("Executing balance update query: " . $updateQuery);
                                error_log("Leave duration: " . $leaveDuration);
                                error_log("Employee ID: " . $employeeID);
                                
                                $stmtQueryUpdate = mysqli_prepare($connect_var, $updateQuery);
                                mysqli_stmt_execute($stmtQueryUpdate);
                                mysqli_stmt_close($stmtQueryUpdate);
                            }
                        }
                    }
                    mysqli_commit($connect_var);
                    echo json_encode(array(
                        "status" => "success",
                        "message_text" => ($this->status === 'Approved') ? 
                            "Leave approved and balance updated successfully ".$decoded_items["numberOfDays"] : 
                            "Leave " . strtolower($this->status) . " successfully ".$decoded_items["numberOfDays"],
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