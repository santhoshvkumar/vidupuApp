<?php

class EmployeeComponent{
    public $employeeID;
    public $employeeRole;
    public $EmployeePassword;
    public $branchID;

    public function loadEmployeeDetails(array $data){
        $this->employeeID = $data['employeeID'];
        $this->branchID = $data['branchID'];
        return true;
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
            $queryAllEmployeeDetails = "SELECT e.employeeID, e.employeeName, b.branchID FROM 
            tblEmployee e JOIN tblmapemp b ON e.employeeID = b.employeeID";
            $result = mysqli_query($connect_var, $queryAllEmployeeDetails);            

            // Initialize an array to hold all employee details
            $employees = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $employees[] = $row; // Add each row to the employees array
            }
            $data['allemployees'] = $employees; // Store the array of employees            

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

            // Ensure the required fields are present in the input
            if (isset($decoded_items['branchID']) && isset($decoded_items['employeeID'])) {
                $this->employeeID = $decoded_items['employeeID'];
                $this->branchID = $decoded_items['branchID']; // Assuming branchID is being used as EmployeePassword

                // 1. Update a branch of Employee due to Transfer to employee
                $queryUpdateBranchofEmployee = "UPDATE tblmapemp SET branchID = ? WHERE employeeID = ?;";
                $stmt = mysqli_prepare($connect_var, $queryUpdateBranchofEmployee);
                mysqli_stmt_bind_param($stmt, "ss", $this->branchID, $this->employeeID);
                mysqli_stmt_execute($stmt);

                // Check if the update was successful
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    echo json_encode([
                        "status" => "success",
                        "data" => $data
                    ]);
                } else {
                    echo json_encode([
                        "status" => "error",
                        "message_text" => "No rows updated. Check if the employeeID exists."
                    ], JSON_FORCE_OBJECT);
                }
            } else {
                echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
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

