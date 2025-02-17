<?php
class LoginComponent {
    private $db;

    public function __construct($f3) {
        $this->db = $f3->get('DB');
        // Log DB connection status
        error_log("DB Connection initialized: " . ($this->db ? "Success" : "Failed"));
    }

    // Method to handle login
    public function login($f3) {
        // Get the raw input
        $rawBody = file_get_contents('php://input');
        error_log("Raw request body in component: " . $rawBody);
        
        // Parse JSON data
        $data = json_decode($rawBody, true);
        if (!$data) {
            error_log("Failed to parse JSON in component");
            throw new Exception("Invalid request data");
        }
        
        $phoneNumber = $data['phoneNumber'];
        $password = $data['password'];

        try {
            // Log the received data for debugging
            error_log("Attempting login for phoneNumber: " . $phoneNumber);
            
            // SQL query to find the employee by phoneNumber
            $query = "SELECT * FROM tblemployee WHERE employeePhone = :phoneNumber";
            error_log("SQL Query: " . $query);
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':phoneNumber', $phoneNumber, PDO::PARAM_STR);
            $stmt->execute();

            // Fetch the employee record
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Query result: " . ($employee ? "User found" : "No user found"));

            // If no matching employee is found, return an error
            if (!$employee) {
                error_log("No employee found for phoneNumber: " . $phoneNumber);
                $f3->status(401);
                echo json_encode(['error' => 'Employee not found']);
                return;
            }

            // Compare the password
            error_log("Comparing passwords - Input: " . $password . ", Stored: " . $employee['employeePassword']);
            if ($password === $employee['employeePassword']) {
                // Format the response data
                $responseData = [
                    'empID' => $employee['empID'],
                    'name' => $employee['employeeName'],
                    'role' => $employee['employeeRole'] ?? 'employee',
                    'branch' => $employee['branchName'] ?? 'NSC Bose Road, Parrys'
                ];
                
                error_log("Login successful for user: " . $responseData['name']);
                // Return success response
                echo json_encode([
                    'success' => true,
                    'employee' => $responseData
                ]);
            } else {
                error_log("Password mismatch for phoneNumber: " . $phoneNumber);
                // Password doesn't match
                $f3->status(401);
                echo json_encode(['error' => 'Incorrect password']);
            }
        } catch (PDOException $e) {
            // Log the detailed error for debugging
            error_log("Database error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Return generic error to client
            $f3->status(500);
            echo json_encode([
                'error' => 'Database error',
                'message' => 'An error occurred while processing your request'
            ]);
        }
    }
}
?>
