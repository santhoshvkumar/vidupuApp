<?php
class TransferEmployeeComponent{
    public $empID;   
    public $branchID;    
    public $branchName;
    public $createdOn;
    public $createdBy;
    public $AssignedDate;
    public $transferMethod;
    public $managerID;
    public $userName;
    public function loadTransferEmployeeDetails(array $data){       
        if (isset($data['empID']) && isset($data['branchID']) && isset($data['createdOn']) && isset($data['createdBy']) && isset($data['AssignedDate']) && isset($data['transferMethod'])) {
            $this->empID = $data['empID'];
            $this->branchID = $data['branchID'];
            $this->createdOn = $data['createdOn'];
            $this->createdBy = $data['createdBy'];
            $this->AssignedDate = $data['AssignedDate'];                       
            return true;
        } else {
            return false;
        }
    }
    public function loadTemporaryTransfer(array $data){       
        if (isset($data['empID']) && isset($data['branchName']) && isset($data['transferMethod']) && isset($data['userName'])) {
            $this->empID = $data['empID'];
            $this->branchName = $data['branchName'];
            $this->transferMethod = $data['transferMethod'];
            $this->userName = $data['userName'];
            return true;
        } else {
            return false;
        }
    }

    public function loadPermanentTransfer(array $data){
        if (isset($data['empID']) && isset($data['branchName']) && isset($data['transferMethod']) && isset($data['managerID'])) {
            $this->empID = $data['empID'];
            $this->branchName = $data['branchName'];
            $this->transferMethod = $data['transferMethod'];
            $this->managerID = $data['managerID'];
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
    
            // 1. Get all active employees Name, ID and BranchID
            $queryTransferEmployeeDetails = "INSERT INTO tblBranchInterChange (empID, branchID, createdOn, createdBy, AssignedDate) VALUES (?, ?, ?, ?, ?);";
            $stmt = mysqli_prepare($connect_var, $queryTransferEmployeeDetails);
            mysqli_stmt_bind_param($stmt, "sssss",
                $this->empID,
                $this->branchID,
                $this->createdOn,
                $this->createdBy,
                $this->AssignedDate
            );

            if (mysqli_stmt_execute($stmt)) {
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
            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error", 
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
    public function TemporaryTransfer() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            if($this->transferMethod == "Temporary Transfer"){  
                // Start transaction
                mysqli_begin_transaction($connect_var);
                
                try {
                    // 1. Get the new branchID
                    $queryGetBranchID = "SELECT branchID FROM tblBranch WHERE branchName = ?";
                    $stmt1 = mysqli_prepare($connect_var, $queryGetBranchID);
                    mysqli_stmt_bind_param($stmt1, "s", $this->branchName);
                    mysqli_stmt_execute($stmt1);
                    $result = mysqli_stmt_get_result($stmt1);
                    $row = mysqli_fetch_assoc($result);
                    $newBranchID = $row['branchID'];
                    mysqli_stmt_close($stmt1);

                    // 2. Get employeeID, current branchID and employee name
                    $queryGetEmployee = "SELECT e.employeeID, e.employeeName, m.branchID as currentBranchID 
                                       FROM tblEmployee e 
                                       JOIN tblmapEmp m ON e.employeeID = m.employeeID 
                                       WHERE e.empID = ?";
                    $stmt2 = mysqli_prepare($connect_var, $queryGetEmployee);
                    mysqli_stmt_bind_param($stmt2, "s", $this->empID);
                    mysqli_stmt_execute($stmt2);
                    $result = mysqli_stmt_get_result($stmt2);
                    $row = mysqli_fetch_assoc($result);
                    $employeeID = $row['employeeID'];
                    $employeeName = $row['employeeName'];
                    $currentBranchID = $row['currentBranchID'];
                    mysqli_stmt_close($stmt2);

                    // 3. Update the employee's branch
                    $queryUpdateBranch = "UPDATE tblmapEmp SET branchID = ? WHERE employeeID = ?";
                    $stmt3 = mysqli_prepare($connect_var, $queryUpdateBranch);
                    mysqli_stmt_bind_param($stmt3, "ss", $newBranchID, $employeeID);
                    mysqli_stmt_execute($stmt3);
                    mysqli_stmt_close($stmt3);

                    // 4. Record the transfer in history
                    $queryInsertHistory = "INSERT INTO tbltransferhistory (
                        emplD,
                        employeeName,
                        existingBranch,
                        newBranch,
                        createdOn,
                        createdBy
                    ) VALUES (?, ?, ?, ?, NOW(), ?)";
                    
                    $stmt4 = mysqli_prepare($connect_var, $queryInsertHistory);
                    mysqli_stmt_bind_param($stmt4, "sssss", 
                        $this->empID,
                        $employeeName,
                        $currentBranchID,
                        $newBranchID,
                        $this->userName
                    );
                    mysqli_stmt_execute($stmt4);
                    mysqli_stmt_close($stmt4);

                    // Commit transaction
                    mysqli_commit($connect_var);

                    echo json_encode(array(
                        "status" => "success",
                        "message" => "Employee Transferred successfully"
                    ));
                } catch (Exception $e) {
                    // Rollback transaction on error
                    mysqli_rollback($connect_var);
                    throw $e;
                }
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Invalid Transfer Method"
                ));
            }
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error", 
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        } finally {
            if (isset($connect_var)) {
                mysqli_close($connect_var);
            }
        }
    }
    public function PermanentTransfer() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
            if($this->transferMethod == "Permanent Transfer"){  
                // Start transaction
                mysqli_begin_transaction($connect_var);
                
                try {
                    // 1. Get employeeID first
                    $queryGetEmployeeID = "SELECT employeeID FROM tblEmployee WHERE empID = ?";
                    $stmt = mysqli_prepare($connect_var, $queryGetEmployeeID);
                    mysqli_stmt_bind_param($stmt, "s", $this->empID);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $row = mysqli_fetch_assoc($result);
                    $employeeID = $row['employeeID'];
                    mysqli_stmt_close($stmt);

                    // 2. Get branchID
                    $queryGetBranchID = "SELECT branchID FROM tblBranch WHERE branchName = ?";
                    $stmt = mysqli_prepare($connect_var, $queryGetBranchID);
                    mysqli_stmt_bind_param($stmt, "s", $this->branchName);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $row = mysqli_fetch_assoc($result);
                    $branchID = $row['branchID'];
                    mysqli_stmt_close($stmt);

                    // 3. Update branch mapping
                    $queryUpdateBranch = "UPDATE tblmapEmp SET branchID = ? WHERE employeeID = ?";
                    $stmt = mysqli_prepare($connect_var, $queryUpdateBranch);
                    mysqli_stmt_bind_param($stmt, "ss", $branchID, $employeeID);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    // 4. Update managerID
                    $queryUpdateManager = "UPDATE tblEmployee SET managerID = ? WHERE employeeID = ?";
                    $stmt = mysqli_prepare($connect_var, $queryUpdateManager);
                    mysqli_stmt_bind_param($stmt, "ss", $this->managerID, $employeeID);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    // Commit transaction
                    mysqli_commit($connect_var);

                    echo json_encode(array(
                        "status" => "success",
                        "message" => "Employee Transferred successfully"
                    ));
                } catch (Exception $e) {
                    // Rollback transaction on error
                    mysqli_rollback($connect_var);
                    throw $e;
                }
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Invalid Transfer Method"
                ));
            }
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error", 
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        } finally {
            if (isset($connect_var)) {
                mysqli_close($connect_var);
            }
        }
    }

} // End of 
function TransferEmployeeDetails($decoded_items) {
    $EmployeeObject = new TransferEmployeeComponent();
    if ($EmployeeObject->loadTransferEmployeeDetails($decoded_items)) {
        $EmployeeObject->TransferEmployeeDetails($decoded_items);
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function TemporaryTransfer($decoded_items) {
    $EmployeeObject = new TransferEmployeeComponent();
    if ($EmployeeObject->loadTemporaryTransfer($decoded_items)) {
        $EmployeeObject->TemporaryTransfer($decoded_items);
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function PermanentTransfer($decoded_items) {
    $EmployeeObject = new TransferEmployeeComponent();
    if ($EmployeeObject->loadPermanentTransfer($decoded_items)) {
        $EmployeeObject->PermanentTransfer($decoded_items);
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
?>