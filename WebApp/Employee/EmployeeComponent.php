<?php

class EmployeeComponent{
    public $employeeID;
    public $employeeRole;
    public $EmployeePassword;
    public $PresentBranchID;
    public $NewBranchID;
    public $interChangeDate;
    public function loadEmployeeDetails(array $data){
        if (isset($data['employeeID']) && isset($data['currentBranchID'])) {
            $this->employeeID = $data['employeeID'];
            $this->PresentBranchID = $data['currentBranchID'];
            $this->NewBranchID = $data['newBranchID'];
            $this->interChangeDate = $data['interChangeDate'];
            return true;
        } else {
            return false;
        }
    }

    public function EmployeeDetails() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            // Initialize an array to hold the results
            $data = [];
    
            // 1. Fetch all dashboard details
            $queryEmployeeDetails = "SELECT * FROM tblDashboardDetails";
            $rsd = mysqli_query($connect_var, $queryEmployeeDetails);
            $EmployeeDetails = [];
            while ($row = mysqli_fetch_assoc($rsd)) {
                $EmployeeDetails[] = $row;
            }
            $data['EmployeeDetails'] = $EmployeeDetails;    
            
    
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

    public function AllEmployeeDetails() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
    
            // 1. Get all active employees Name, ID and BranchID
            $queryAllEmployeeDetails = "SELECT tblE.employeeID, tblE.employeeName, tblME.branchID FROM 
            tblEmployee tblE JOIN tblmapEmp tblME ON tblE.employeeID = tblME.employeeID";
            $result = mysqli_query($connect_var, $queryAllEmployeeDetails);            

            // Initialize an array to hold all employee details
            $employees = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row; // Add each row to the employees array
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

    public function UpdateEmployeeDetails($decoded_items) {
        include('config.inc');
        header('Content-Type: application/json');

        try {
            $data = [];    

            // 1. Update a branch of Employee due to Transfer to employee
            $queryUpdateBranchofEmployee = "UPDATE tblmapEmp SET branchID = ? WHERE employeeID = ?;";
            $stmt = mysqli_prepare($connect_var, $queryUpdateBranchofEmployee);
            mysqli_stmt_bind_param($stmt, "ss", $this->NewBranchID, $this->employeeID);
            mysqli_stmt_execute($stmt);

            $currentDate = date('Y-m-d');
            // 2. Insert a branch inter change history
            $queryInsertBranchInterChangeHistory = "INSERT INTO tblBranchInterChange (employeeID, fromBranchID, toBranchID, createdOn, AssignedDate) VALUES (?, ?, ?, ?, ?);";
            $stmtInterChangeHistory = mysqli_prepare($connect_var, $queryInsertBranchInterChangeHistory);
            mysqli_stmt_bind_param($stmtInterChangeHistory, "sssss", $this->employeeID, $this->PresentBranchID, $this->NewBranchID, $currentDate, $this->interChangeDate);
            mysqli_stmt_execute($stmtInterChangeHistory);

            // Check if the update was successful
            if (mysqli_stmt_affected_rows($stmtInterChangeHistory) > 0) {
                echo json_encode([
                    "status" => "success",
                    "message_text" => "Branch inter change history inserted successfully."
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message_text" => "No rows updated. Check if the employeeID exists."
                ], JSON_FORCE_OBJECT);
            }
           
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
    
} // Close the DashboardComponent class

function EmployeeDetails() {
    $EmployeeComponent = new EmployeeComponent();
    $EmployeeComponent->AllEmployeeDetails();
}


function UpdateEmployeeDetails($decoded_items) {
    $EmployeeObject = new EmployeeComponent();
    if ($EmployeeObject->loadEmployeeDetails($decoded_items)) {
        // Pass the $decoded_items to the UpdateEmployeeDetails method
        $EmployeeObject->UpdateEmployeeDetails($decoded_items);
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}

