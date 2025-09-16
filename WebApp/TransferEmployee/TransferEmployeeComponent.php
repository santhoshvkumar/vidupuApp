<?php
class TransferEmployeeComponent{
    public $employeeID;   
    public $fromBranch;    
    public $toBranch;
    public $fromDate;
    public $toDate;
    public $isPermanentTransfer;
    public $organisationID;    
    public $createdBy;
    public $isActive;
    public $isImmediate;
    public $transferHistoryID;
    public $dataOfTransfer;
    
    public function loadTransferEmployeeDetails(array $data){       
        // Check required fields
        if (isset($data['employeeID']) && isset($data['fromBranch']) && isset($data['toBranch']) && isset($data['fromDate']) && isset($data['transferType']) && isset($data['organisationID']) && isset($data['createdBy']) && isset($data['isActive']) && isset($data['isImmediate'])) {
            
            $this->employeeID = $data['employeeID'];
            $this->fromBranch = $data['fromBranch'];
            $this->toBranch = $data['toBranch'];
            $this->fromDate = $data['fromDate'];
            $this->isPermanentTransfer = $data['transferType'];
            $this->organisationID = $data['organisationID'];    
            $this->createdBy = $data['createdBy'];
            $this->isActive = $data['isActive'];
            $this->isImmediate = $data['isImmediate'];
            
            // For permanent transfers, toDate is optional (can be NULL)
            if ($data['transferType'] === 'Permanent') {
                $this->toDate = null;
            } else {
                // For temporary transfers, toDate is required
                if (isset($data['toDate'])) {
                    $this->toDate = $data['toDate'];
                } else {
                    return false; // toDate is required for temporary transfers
                }
            }
            
            return true;
        } else {
            return false;
        }
    }
    
        public function TransferEmployeeDetails() {
            include('config.inc');
            header('Content-Type: application/json');
        
            try {
                $data = [];
                $currentDate = date('Y-m-d');
                $isActive = 1;  // All transfers should be active when created
                $lastInsertId=0;
                $getIsImmediateTransfer = $this->isImmediate;
    
                // Check for duplicate transfer - employee should not have overlapping transfer dates
                if ($this->isPermanentTransfer === 'Permanent') {
                    // For permanent transfers, only check if there's another transfer on the exact same date
                    $queryCheckDuplicate = "SELECT COUNT(*) as count FROM tblTransferHistory 
                        WHERE employeeID = ? AND organisationID = ? AND isActive = 1
                        AND fromDate = ?";
                    $stmtCheck = mysqli_prepare($connect_var, $queryCheckDuplicate);
                    mysqli_stmt_bind_param($stmtCheck, "sss", 
                        $this->employeeID, 
                        $this->organisationID, 
                        $this->fromDate
                    );
                } else {
                    // For temporary transfers, only check if there's another transfer on the exact same date
                    $queryCheckDuplicate = "SELECT COUNT(*) as count FROM tblTransferHistory 
                        WHERE employeeID = ? AND organisationID = ? AND isActive = 1
                        AND fromDate = ?";
                    $stmtCheck = mysqli_prepare($connect_var, $queryCheckDuplicate);
                    mysqli_stmt_bind_param($stmtCheck, "sss", 
                        $this->employeeID, 
                        $this->organisationID, 
                        $this->fromDate
                    );
                }
                mysqli_stmt_execute($stmtCheck);
                $resultCheck = mysqli_stmt_get_result($stmtCheck);
                $duplicateCount = mysqli_fetch_assoc($resultCheck)['count'];
                mysqli_stmt_close($stmtCheck);
                
                if ($duplicateCount > 0) {
                    echo json_encode(array(
                        "status" => "error",
                        "message" => "Employee already has a transfer scheduled for the specified date range."
                    ));
                    return;
                }

                // Deactivate any previous active transfers for this employee
                $queryDeactivatePrevious = "UPDATE tblTransferHistory 
                    SET isActive = 0 
                    WHERE employeeID = ? AND organisationID = ? AND isActive = 1";
                $stmtDeactivate = mysqli_prepare($connect_var, $queryDeactivatePrevious);
                mysqli_stmt_bind_param($stmtDeactivate, "ss", 
                    $this->employeeID, 
                    $this->organisationID
                );
                mysqli_stmt_execute($stmtDeactivate);
                mysqli_stmt_close($stmtDeactivate);

//insert transfer history
                $queryInsertTransferHistory = "INSERT INTO tblTransferHistory ( employeeID, fromBranch, toBranch, fromDate, toDate, transferType, organisationID, createdOn, createdBy, isActive, isImmediateTransfer) VALUES (?,?,?,?,?,?,?,?,?,?,?);";
                $queryStatement = mysqli_prepare($connect_var, $queryInsertTransferHistory);
                mysqli_stmt_bind_param($queryStatement, "sssssssssss",
                    $this->employeeID,
                    $this->fromBranch,
                    $this->toBranch,
                    $this->fromDate,
                    $this->toDate,
                    $this->isPermanentTransfer,
                    $this->organisationID,
                    $currentDate,
                    $this->createdBy,
                    $isActive,
                    $this->isImmediate
                );
                if (mysqli_stmt_execute($queryStatement)) {
                    $lastInsertId = mysqli_insert_id($connect_var);
                }
               if($getIsImmediateTransfer === "1") {
                    $queryUpdateMapEmployee = "UPDATE tblmapEmp SET branchID=?, transferHistoryID=? WHERE employeeID=? and organisationID=? ";
                    $queryUpdateMapStatement = mysqli_prepare($connect_var, $queryUpdateMapEmployee);
                    mysqli_stmt_bind_param($queryUpdateMapStatement, "ssss", $this->toBranch, $lastInsertId, $this->employeeID, $this->organisationID);
                    mysqli_stmt_execute($queryUpdateMapStatement);
                    mysqli_stmt_close($queryUpdateMapStatement);
                }
    
                if ($lastInsertId !== 0) {
                    echo json_encode(array(
                        "status" => "success",
                        "message" => "Transfer of Employee Done Successfully"
                    ));
                } else {
                    echo json_encode(array(
                        "status" => "error",
                        "message" => "Error in Transfering."
                    ));
                }
                mysqli_stmt_close($queryStatement);
                mysqli_close($connect_var);
            } catch (Exception $e) {
                echo json_encode([
                    "status" => "error", 
                    "message_text" => $e->getMessage()
                ], JSON_FORCE_OBJECT);
            }
        }
    
                public function autoTransferProcess() {
            include('config.inc');
            header('Content-Type: application/json');
        
            try {
                $data = [];
                $systemDate = $this->dataOfTransfer;
                $transfersProcessed = 0;
                $allProcessedTransfers = [];

                /* Previous Implementation - Commented for reference
                $querySystemTransfer = "SELECT transferHistoryID, fromBranch, toBranch, employeeID, toDate, transferType 
                    FROM tblTransferHistory 
                    WHERE ? BETWEEN fromDate AND toDate 
                    AND isActive = 1";
                $stmt = mysqli_prepare($connect_var, $querySystemTransfer);
                mysqli_stmt_bind_param($stmt, "s", $systemDate);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
                mysqli_stmt_close($stmt);
                */

                // New
                // First process returns for expired transfers from previous day
                $queryExpiredTransfers = "SELECT th.transferHistoryID, th.fromBranch, th.employeeID 
                    FROM tblTransferHistory th
                    WHERE DATE(?) > DATE(th.toDate)
                    AND th.isActive = 1 
                    AND th.organisationID = ?
                    AND th.transferType != 'Permanent'";
                $stmtExpired = mysqli_prepare($connect_var, $queryExpiredTransfers);
                mysqli_stmt_bind_param($stmtExpired, "ss", $systemDate, $this->organisationID);
                mysqli_stmt_execute($stmtExpired);
                $resultExpired = mysqli_stmt_get_result($stmtExpired);
                $expiredTransfers = mysqli_fetch_all($resultExpired, MYSQLI_ASSOC);
                mysqli_stmt_close($stmtExpired);
    
                // Process returns
                foreach ($expiredTransfers as $transfer) {
                    // Return employee to original branch
                    $updateMapEmp = "UPDATE tblmapEmp 
                        SET branchID = ?, transferHistoryID = NULL 
                        WHERE employeeID = ? 
                        AND organisationID = ?";
                    $stmtUpdateMap = mysqli_prepare($connect_var, $updateMapEmp);
                    mysqli_stmt_bind_param($stmtUpdateMap, "sss", 
                        $transfer['fromBranch'], 
                        $transfer['employeeID'], 
                        $this->organisationID
                    );
                    mysqli_stmt_execute($stmtUpdateMap);
                    mysqli_stmt_close($stmtUpdateMap);

                    // Deactivate the transfer
                    $updateTransferHistory = "UPDATE tblTransferHistory 
                        SET isActive = 0 
                        WHERE transferHistoryID = ?";
                    $stmtUpdateHistory = mysqli_prepare($connect_var, $updateTransferHistory);
                    mysqli_stmt_bind_param($stmtUpdateHistory, "s", $transfer['transferHistoryID']);
                    mysqli_stmt_execute($stmtUpdateHistory);
                    mysqli_stmt_close($stmtUpdateHistory);

                    $transfer['status'] = 'returned';
                    $allProcessedTransfers[] = $transfer;
                    $transfersProcessed++;
                }

                // Then process new/active transfers for current date
                // Handle both permanent transfers (toDate IS NULL) and temporary transfers
                $queryActiveTransfers = "SELECT transferHistoryID, fromBranch, toBranch, employeeID, toDate, transferType 
                    FROM tblTransferHistory 
                    WHERE DATE(fromDate) = DATE(?) 
                    AND isActive = 1 
                    AND organisationID = ?";
                $stmtActive = mysqli_prepare($connect_var, $queryActiveTransfers);
                mysqli_stmt_bind_param($stmtActive, "ss", $systemDate, $this->organisationID);
                mysqli_stmt_execute($stmtActive);
                $resultActive = mysqli_stmt_get_result($stmtActive);
                $activeTransfers = mysqli_fetch_all($resultActive, MYSQLI_ASSOC);
                mysqli_stmt_close($stmtActive);

                foreach ($activeTransfers as $transfer) {
                    // Update employee branch
                    $updateQuery = "UPDATE tblmapEmp 
                        SET branchID = ?, transferHistoryID = ? 
                        WHERE employeeID = ? 
                        AND organisationID = ?";
                    $updateStmt = mysqli_prepare($connect_var, $updateQuery);
                    mysqli_stmt_bind_param($updateStmt, "ssss",
                        $transfer['toBranch'],
                        $transfer['transferHistoryID'],
                        $transfer['employeeID'],
                        $this->organisationID
                    );
                    mysqli_stmt_execute($updateStmt);
                    mysqli_stmt_close($updateStmt);

                    // If permanent transfer, deactivate it
                    if ($transfer['transferType'] === 'Permanent') {
                        // For permanent transfers, we don't deactivate immediately
                        // They remain active until the employee gets another transfer
                        // or until manually deactivated
                    }

                    $transfer['status'] = 'transferred';
                    $allProcessedTransfers[] = $transfer;
                    $transfersProcessed++;
                }

                if ($transfersProcessed > 0) {
                    echo json_encode(array(
                        "status" => "success",
                        "message" => "Successfully processed " . $transfersProcessed . " transfers",
                        "data" => $allProcessedTransfers
                    ));
                } else {
                    echo json_encode(array(
                        "status" => "success",
                        "message" => "No transfers to process for the given date",
                        "data" => []
                    ));
                }
                mysqli_close($connect_var);
            } catch (Exception $e) {
                echo json_encode([
                    "status" => "error", 
                    "message_text" => $e->getMessage()
                ], JSON_FORCE_OBJECT);
            }
        }
    
    //delete transfer history

    public function DeleteTransferEmployee() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $queryDeleteTransferHistory = "DELETE FROM tblTransferHistory WHERE transferHistoryID = ?";
            $queryStatement = mysqli_prepare($connect_var, $queryDeleteTransferHistory);
            mysqli_stmt_bind_param($queryStatement, "i", $this->transferHistoryID);

            if (mysqli_stmt_execute($queryStatement)) {
                echo json_encode(array(
                    "status" => "success",
                    "message" => "Employee transfer deleted successfully"
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error deleting employee transfer"
                ));
            }
            mysqli_stmt_close($queryStatement);
            mysqli_close($connect_var);
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error", 
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }

    public function GetAllEmployeeTransfers() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $query = "SELECT 
                        th.transferHistoryID,
                        th.employeeID,
                        e.employeeName,
                        e.employeePhone,
                        e.Designation,
                        fb.branchName as fromBranchName,
                        tb.branchName as toBranchName,
                        th.fromDate,
                        th.toDate,
                        th.transferType,
                        th.organisationID,
                        th.createdOn,
                        th.createdBy,
                        u.userName as createdByName,
                        th.isActive,
                        th.isImmediateTransfer
                    FROM tblTransferHistory th
                    LEFT JOIN tblEmployee e ON th.employeeID = e.employeeID AND e.isActive = 1
                    LEFT JOIN tblBranch fb ON th.fromBranch = fb.branchID
                    LEFT JOIN tblBranch tb ON th.toBranch = tb.branchID
                    LEFT JOIN tblUser u ON th.createdBy = u.userID
                    WHERE th.isActive = 1 AND th.organisationID = ?
                    ORDER BY th.createdOn DESC";
            
            $stmt = mysqli_prepare($connect_var, $query);
            if (!$stmt) {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Database prepare failed: " . mysqli_error($connect_var)
                ));
                return;
            }
            
            mysqli_stmt_bind_param($stmt, "s", $this->organisationID);
            
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                $transfers = [];
                
                while ($row = mysqli_fetch_assoc($result)) {
                    $transfers[] = $row;
                }
                
                echo json_encode(array(
                    "status" => "success",
                    "data" => $transfers
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error fetching transfers: " . mysqli_stmt_error($stmt)
                ));
            }
            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error", 
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }

    public function loadAutoTransfer(array $data) {
        if (isset($data['dataOfTransfer']) && isset($data['organisationID'])) {
            $this->dataOfTransfer = $data['dataOfTransfer'];
            return true;
        }
        return false;
    }

} // End of Class

function TransferEmployeeDetails($decoded_items) {
    $EmployeeObject = new TransferEmployeeComponent();
    if ($EmployeeObject->loadTransferEmployeeDetails($decoded_items)) {
        $EmployeeObject->TransferEmployeeDetails();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}

function DeleteTransferEmployee($decoded_items) {
    $EmployeeObject = new TransferEmployeeComponent();
    if (isset($decoded_items['transferHistoryID'])) {
        $EmployeeObject->transferHistoryID = intval($decoded_items['transferHistoryID']);
        $EmployeeObject->DeleteTransferEmployee();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters - transferHistoryID is required"), JSON_FORCE_OBJECT);
    }
}

function AutoTransfer($decoded_items) {
    try {
        $transferObject = new TransferEmployeeComponent();
        if($transferObject->loadAutoTransfer($decoded_items)){
            $transferObject->autoTransferProcess();
        }
        else{
            echo json_encode(array("status"=>"error","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
        }
    } catch(Exception $e) {
        echo json_encode(array(
            "status" => "error",
            "message_text" => "Failed to process auto checkout: " . $e->getMessage()
        ), JSON_FORCE_OBJECT);
    }
}

function GetAllEmployeeTransfers($decoded_items) {
    $EmployeeObject = new TransferEmployeeComponent();
    if (isset($decoded_items['organisationID'])) {
        $EmployeeObject->organisationID = $decoded_items['organisationID'];
        $EmployeeObject->GetAllEmployeeTransfers();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters - organisationID is required"), JSON_FORCE_OBJECT);
    }
}
?>