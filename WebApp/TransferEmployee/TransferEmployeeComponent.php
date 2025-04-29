<?php
class TransferEmployeeComponent{
    public $empID;   
    public $branchID;    
    public $createdOn;
    public $createdBy;
    public $AssignedDate;
    public function loadTransferEmployeeDetails(array $data){       
        if (isset($data['empID']) && isset($data['branchID']) && isset($data['createdOn']) && isset($data['createdBy']) && isset($data['AssignedDate'])) {
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

} // End of 
function TransferEmployeeDetails($decoded_items) {
    $EmployeeObject = new TransferEmployeeComponent();
    if ($EmployeeObject->loadTransferEmployeeDetails($decoded_items)) {
        $EmployeeObject->TransferEmployeeDetails($decoded_items);
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
?>