<?php

class EmployeeComponent {

    // Function to handle employee login
    public function login($f3) {
        // Get the database connection from Fat-Free framework container
        $db = $f3->get('DB');
        
        // Get empID and employeePassword from query parameters
        $empID = $f3->get('GET.employee_id');  // Assuming 'employee_id' in the query string
        $password = $f3->get('GET.password');  // Assuming 'password' in the query string
        
        // Debugging: Print the empID and password
        echo "Employee ID: " . $empID . "\n";
        echo "Password: " . $password . "\n";
        
        // Check if empID and password are provided
        if (empty($empID) || empty($password)) {
            echo json_encode(["error" => "Employee ID and password are required."]);
            return;
        }
        
        // Query the database to check the credentials
        $stmt = $db->prepare("SELECT * FROM tblemployee WHERE empID = :empID AND employeePassword = :password");
        $stmt->bindParam(':empID', $empID, PDO::PARAM_STR);
        $stmt->bindParam(':password', $password, PDO::PARAM_STR);
        
        // Execute the query
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If a result is found, the credentials are valid
        if ($result) {
            // Return success message with employee data (excluding password)
            unset($result['employeePassword']); // Avoid sending the password in the response
            echo json_encode(["success" => true, "message" => "Login successful.", "employee_data" => $result]);
        } else {
            // If no result found, the credentials are invalid
            echo json_encode(["error" => "Invalid Employee ID or password."]);
        }
    }

    // Function to get all employees
    public function getEmployees($f3) {
        $db = $f3->get('DB');
        $result = $db->exec('SELECT * FROM tblemployee');
        echo json_encode($result);
    }

    // Function to get a specific employee by empID
    public function getEmployee($f3) {
        $db = $f3->get('DB');
        $empID = $f3->get('PARAMS.empID');
        $stmt = $db->prepare('SELECT * FROM tblemployee WHERE empID = :empID');
        $stmt->bindParam(':empID', $empID, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($result);
    }

    // Function to add a new employee
    public function addEmployee($f3) {
        $db = $f3->get('DB');
        $data = json_decode($f3->get('BODY'), true);

        if (empty($data['empID']) || empty($data['employeeName']) || empty($data['employeePassword'])) {
            echo json_encode(["error" => "Missing required fields."]);
            return;
        }

        $stmt = $db->prepare('INSERT INTO tblemployee (empID, employeeName, employeePassword) VALUES (:empID, :employeeName, :employeePassword)');
        $stmt->bindParam(':empID', $data['empID']);
        $stmt->bindParam(':employeeName', $data['employeeName']);
        $stmt->bindParam(':employeePassword', $data['employeePassword']);
        
        $stmt->execute();
        echo json_encode(["success" => true, "message" => "Employee added successfully."]);
    }

    // Function to update an employee by empID
    public function updateEmployee($f3) {
        $db = $f3->get('DB');
        $empID = $f3->get('PARAMS.empID');
        $data = json_decode($f3->get('BODY'), true);

        $stmt = $db->prepare('UPDATE tblemployee SET employeeName = :employeeName, employeePassword = :employeePassword WHERE empID = :empID');
        $stmt->bindParam(':empID', $empID);
        $stmt->bindParam(':employeeName', $data['employeeName']);
        $stmt->bindParam(':employeePassword', $data['employeePassword']);
        
        $stmt->execute();
        echo json_encode(["success" => true, "message" => "Employee updated successfully."]);
    }

    // Function to delete an employee by empID
    public function deleteEmployee($f3) {
        $db = $f3->get('DB');
        $empID = $f3->get('PARAMS.empID');
        $stmt = $db->prepare('DELETE FROM tblemployee WHERE empID = :empID');
        $stmt->bindParam(':empID', $empID);
        $stmt->execute();
        echo json_encode(["success" => true, "message" => "Employee deleted successfully."]);
    }
}
