<?php

class LeaveReasonComponent{
    public $reasonID;
    public $reasonType;
    public $reasonDetails;
    public $createdOn;
    public $createdBy;
    public $isActive;

    public function loadLeaveReasonDetails(array $data){ 
        if (isset($data['reasonType']) && isset($data['reasonDetails'])) {
            $this->reasonType = $data['reasonType'];
            $this->reasonDetails = $data['reasonDetails'];
            return true;
        } else {
            return false;
        }
    }
    public function loadAddLeaveReasonDetails(array $data){
        if (isset($data['reasonType']) && isset($data['reasonDetails']) && isset($data['createdBy'])){
            $this->reasonType = $data['reasonType'];
            $this->reasonDetails = $data['reasonDetails'];
            $this->createdBy = $data['createdBy'];
            return true;
        } else {
            return false;
        }
    }
    public function loadUpdateLeaveReasonDetails(array $data) {
        if (isset($data['reasonID']) && isset($data['isActive'])) {
            $this->reasonID = $data['reasonID'];
            $this->isActive = $data['isActive'];
            return true;
        } else {
            error_log("Missing required parameters. Received data: " . json_encode($data));
            return false;
        }
    }
    public function GetAllLeaveReasonDetails() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
    
            // 1. Get all active employees Name, ID and BranchID
            $queryGetLeaveReasonDetails = "SELECT   
    tblR.reasonID,
    tblR.reasonType,
    tblR.reasonDetails,
    tblR.createdOn,
    tblR.createdBy,
    tblR.isActive
FROM 
    tblReason tblR
;
";
            $result = mysqli_query($connect_var, $queryGetLeaveReasonDetails);            

            // Initialize an array to hold all leave reason details
            $LeaveReason = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row; // Add each row to the leave reason array
            }
            echo json_encode([
                "status" => "success",
                "data" => $data
            ]);
    
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
    public function AddLeaveReasonDetails() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            mysqli_begin_transaction($connect_var);

            // 1. Insert into tblEmployee
            $queryLeaveReason = "INSERT INTO tblReason (
                reasonType, reasonDetails, createdOn, createdBy, isActive
            ) VALUES (?, ?, CURDATE(), ?, 1)";
            
            $stmt = mysqli_prepare($connect_var, $queryLeaveReason);
            mysqli_stmt_bind_param($stmt, "sss",
                $this->reasonType,
                $this->reasonDetails,
                $this->createdBy
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error inserting leave reason: " . mysqli_error($connect_var));
            }
            
            // Get the auto-incremented reasonID
            $reasonID = mysqli_insert_id($connect_var);
            mysqli_stmt_close($stmt);
            mysqli_commit($connect_var);
            
            echo json_encode([
                "status" => "success",
                "message" => "Leave reason added successfully",
                "reasonID" => $reasonID
            ]);

        } catch (Exception $e) {
            mysqli_rollback($connect_var);
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
    public function UpdateLeaveReasonDetailsBasedonReasonID($decoded_items) {
        include('config.inc');
        header('Content-Type: application/json');
        error_log("UpdateLeaveReasonDetailsBasedonReasonID: " . json_encode($decoded_items));
        try {
            if (!$this->loadUpdateLeaveReasonDetails($decoded_items)) {
                throw new Exception("Thappu");
            }
    
            // Update the leave reason status
            $query = "UPDATE tblReason SET isActive = ? WHERE reasonID = ?";
            $stmt = mysqli_prepare($connect_var, $query);
            
            if (!$stmt) {           
                throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
            }

            mysqli_stmt_bind_param($stmt, "ii", $this->isActive, $this->reasonID);

            if (!mysqli_stmt_execute($stmt)) {  
                throw new Exception("Database execute failed: " . mysqli_error($connect_var));
            }

            if (mysqli_stmt_affected_rows($stmt) > 0) {
                echo json_encode([
                    "status" => "success",
                    "message" => "Leave reason status updated successfully"
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message_text" => "No leave reason found with the given ID"
                ], JSON_FORCE_OBJECT);  
            }

            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);
        } catch (Exception $e) {
            error_log("Error in UpdateLeaveReasonDetailsBasedonReasonID: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
}       
function GetAllLeaveReasonDetails() {
    $LeaveReasonComponent = new LeaveReasonComponent();
    $LeaveReasonComponent->GetAllLeaveReasonDetails();
}
function UpdateLeaveReasonDetailsBasedonReasonID($decoded_items) {
    $UpdateLeaveReasonDetailsBasedonReasonIDObject = new LeaveReasonComponent();
    if ($UpdateLeaveReasonDetailsBasedonReasonIDObject->loadUpdateLeaveReasonDetails($decoded_items)) {
        $UpdateLeaveReasonDetailsBasedonReasonIDObject->UpdateLeaveReasonDetailsBasedonReasonID($decoded_items);
    } else {    
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function AddLeaveReasonDetails($decoded_items) {
    $LeaveReasonObject = new LeaveReasonComponent();
    if ($LeaveReasonObject->loadAddLeaveReasonDetails($decoded_items)) {
        $LeaveReasonObject->AddLeaveReasonDetails();
    } else {
        echo json_encode([
            "status" => "error", 
            "message_text" => "Invalid Input Parameters"
        ], JSON_FORCE_OBJECT);
    }
}
?>

