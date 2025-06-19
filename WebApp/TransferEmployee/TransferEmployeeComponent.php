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
    public function TransferEmployeeDetails() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
            $currentDate = date('Y-m-d');
            $isActive = 1;
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

                $debugQuery = sprintf(
                    "UPDATE tblmapEmp SET branchID='%s', transferHistoryID='%s' WHERE employeeID='%s' and organisationID='%s'",
                    $this->toBranch,
                    $lastInsertId,
                    $this->employeeID,
                    $this->organisationID
                );
                echo "Debug Query: $debugQuery\n";
            }

            if (mysqli_stmt_execute($queryStatement)) {
                echo json_encode(array(
                    "status" => "success",
                    "message" => "Employee added successfully"
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error adding employee"
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

} // End of 
function TransferEmployeeDetails($decoded_items) {
    $EmployeeObject = new TransferEmployeeComponent();
    if ($EmployeeObject->loadTransferEmployeeDetails($decoded_items)) {
        $EmployeeObject->TransferEmployeeDetails();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
?>