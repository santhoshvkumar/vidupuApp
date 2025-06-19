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
    public $isImmediate;
    public $dataOfTransfer;
    public function loadTransferEmployeeDetails(array $data){       
        if (isset($data['employeeID']) && isset($data['fromBranch']) && isset($data['toBranch']) && isset($data['fromDate']) && isset($data['toDate']) && isset($data['isPermanentTransfer']) && isset($data['organisationID']) && isset($data['createdBy']) && isset($data['isImmediate'])) {
            $this->employeeID = $data['employeeID'];
            $this->fromBranch = $data['fromBranch'];
            $this->toBranch = $data['toBranch'];
            $this->fromDate = $data['fromDate'];
            $this->toDate = $data['toDate'];        
            $this->isPermanentTransfer = $data['isPermanentTransfer'];
            $this->organisationID = $data['organisationID'];    
            $this->createdBy = $data['createdBy'];
            $this->isImmediate = $data['isImmediate'];
            return true;
        } else {
            return false;
        }
    }
    public function loadAutoTransfer(array $data) {
        $this->dataOfTransfer = $decoded_items['dataOfTransfer'];
        return true;
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

            // select * tblTransfer => employeeID, fromDate, toDate -> Error command 
            $queryInsertTransferHistory = "INSERT INTO tblTransferHistory ( employeeID, fromBranch, toBranch, fromDate, toDate, isPermanentTransfer, organisationID, createdOn, createdBy, isActive, isImmediateTransfer) VALUES (?,?,?,?,?,?,?,?,?,?,?);";
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

            $querySystemTransfer = "SELECT transferHistoryID, fromBranch, toBranch, employeeID, toDate 
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

} // End of Class
function TransferEmployeeDetails($decoded_items) {
    $EmployeeObject = new TransferEmployeeComponent();
    if ($EmployeeObject->loadTransferEmployeeDetails($decoded_items)) {
        $EmployeeObject->TransferEmployeeDetails();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
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

?>