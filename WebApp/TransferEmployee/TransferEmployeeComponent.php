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
    
    public function loadTransferEmployeeDetails(array $data){       
        if (isset($data['employeeID']) && isset($data['fromBranch']) && isset($data['toBranch']) && isset($data['fromDate']) && isset($data['toDate']) && isset($data['transferType']) && isset($data['organisationID']) && isset($data['createdBy']) && isset($data['isActive']) && isset($data['isImmediate'])) {
            $this->employeeID = $data['employeeID'];
            $this->fromBranch = $data['fromBranch'];
            $this->toBranch = $data['toBranch'];
            $this->fromDate = $data['fromDate'];
            $this->toDate = $data['toDate'];        
            $this->isPermanentTransfer = $data['transferType'];
            $this->organisationID = $data['organisationID'];    
            $this->createdBy = $data['createdBy'];
            $this->isActive = $data['isActive'];
            $this->isImmediate = $data['isImmediate'];
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
                $isActive = 1;
                $lastInsertId=0;
                $getIsImmediateTransfer = $this->isImmediate;
                if($getIsImmediateTransfer === "1") {
                    $isActive = 0;           
                }
    
                // Check for duplicate transfer - employee should not have overlapping transfer dates
                $queryCheckDuplicate = "SELECT COUNT(*) as count FROM tblTransferHistory 
                    WHERE employeeID = ? AND organisationID = ? 
                    AND ((fromDate <= ? AND toDate >= ?) OR (fromDate <= ? AND toDate >= ?) OR (fromDate >= ? AND toDate <= ?))";
                $stmtCheck = mysqli_prepare($connect_var, $queryCheckDuplicate);
                mysqli_stmt_bind_param($stmtCheck, "ssssssss", 
                    $this->employeeID, 
                    $this->organisationID, 
                    $this->fromDate, $this->fromDate, 
                    $this->toDate, $this->toDate, 
                    $this->fromDate, $this->toDate
                );
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
    
                if (count($data) > 0) {
                    foreach ($data as $row) {
                        $updateQuery = "UPDATE tblmapEmp SET branchID=?, transferHistoryID=? WHERE employeeID=? and organisationID=?";
                        $updateStmt = mysqli_prepare($connect_var, $updateQuery);
                        mysqli_stmt_bind_param(
                            $updateStmt,
                            "ssss",
                            $row['toBranch'],
                            $row['transferHistoryID'],
                            $row['employeeID'],
                            $this->organisationID
                        );
                        mysqli_stmt_execute($updateStmt);
                        mysqli_stmt_close($updateStmt);
                    }
                    if($row['transferType'] === 'Permanent'){
                        $updateTransferHistory = "UPDATE tblTransferHistory SET isActive=0 WHERE transferHistoryID=?";
                        $stmtUpdateHistory = mysqli_prepare($connect_var, $updateTransferHistory);
                        mysqli_stmt_bind_param($stmtUpdateHistory, "s", $row['transferHistoryID']);
                        mysqli_stmt_execute($stmtUpdateHistory);
                        mysqli_stmt_close($stmtUpdateHistory);
                    }
                    // 1. Find all transfers where systemDate is past toDate and still active
                    $queryPastTransfers = "SELECT transferHistoryID, fromBranch, employeeID 
                    FROM tblTransferHistory 
                    WHERE ? > toDate AND isActive = 1";
                    $stmtPast = mysqli_prepare($connect_var, $queryPastTransfers);
                    mysqli_stmt_bind_param($stmtPast, "s", $systemDate);
                    mysqli_stmt_execute($stmtPast);
                    $resultPast = mysqli_stmt_get_result($stmtPast);
                    $pastTransfers = mysqli_fetch_all($resultPast, MYSQLI_ASSOC);
                    mysqli_stmt_close($stmtPast);
                    // 2. For each, update tblmapEmp and tblTransferHistory
                    foreach ($pastTransfers as $row) {
                        // Update tblmapEmp: set branchID to fromBranch
                        $updateMapEmp = "UPDATE tblmapEmp SET branchID=? WHERE employeeID=? AND organisationID=?";
                        $stmtUpdateMap = mysqli_prepare($connect_var, $updateMapEmp);
                        mysqli_stmt_bind_param($stmtUpdateMap, "sss", $row['fromBranch'], $row['employeeID'], $this->organisationID);
                        mysqli_stmt_execute($stmtUpdateMap);
                        mysqli_stmt_close($stmtUpdateMap);
    
                        // Update tblTransferHistory: set isActive=0
                        $updateTransferHistory = "UPDATE tblTransferHistory SET isActive=0 WHERE transferHistoryID=?";
                        $stmtUpdateHistory = mysqli_prepare($connect_var, $updateTransferHistory);
                        mysqli_stmt_bind_param($stmtUpdateHistory, "s", $row['transferHistoryID']);
                        mysqli_stmt_execute($stmtUpdateHistory);
                        mysqli_stmt_close($stmtUpdateHistory);
                    }
                    echo json_encode(array(
                        "status" => "success",
                        "data" => $data
                    ));
                } else {
                    echo json_encode(array(
                        "status" => "error",
                        "message" => "No records found."
                    ));
                }
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
                    INNER JOIN tblEmployee e ON th.employeeID = e.empID
                    INNER JOIN tblBranch fb ON th.fromBranch = fb.branchID
                    INNER JOIN tblBranch tb ON th.toBranch = tb.branchID
                    INNER JOIN tblUser u ON th.createdBy = u.userID
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