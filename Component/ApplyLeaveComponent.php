<?php
// ApplyLeaveComponent.php

// Include the database configuration file at the top (outside the class)
require_once 'config.inc.php'; // Ensure the path is correct

// ApplyLeaveComponent class definition
class ApplyLeaveComponent {
    
    // Method to get all leave applications
    // Method to get all leave applications
function getAllLeaveApplications($f3) {
    global $pdo;

    try {
        // SQL query to select fromDate, toDate, typeOfLeave, and calculate no. of days
        $stmt = $pdo->prepare("SELECT fromDate, toDate, typeOfLeave, DATEDIFF(toDate, fromDate) AS numDays FROM tblApplyLeave");
        $stmt->execute();
        
        // Fetch all leave applications
        $leaveApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debugging: Log the leave applications result
        error_log("Fetched Leave Applications: " . print_r($leaveApplications, true));

        // Return the results as JSON
        echo json_encode($leaveApplications);

    } catch (PDOException $e) {
        // In case of error, log the error message
        error_log("Error in getAllLeaveApplications: " . $e->getMessage());
        echo json_encode(['error' => 'An error occurred while fetching leave applications.']);
    }
}


    // Method to get leave applications by employee ID
    // Method to get leave applications by employee ID
function getLeaveApplicationsByEmployee($f3, $employeeID) {
    global $pdo;

    try {
        // Debugging: Check if employeeID is passed correctly
        error_log("Employee ID: " . $employeeID); // Logs to PHP error log

        // Ensure the employeeID is being passed as an integer or string based on your DB type
        // You can also perform validation or sanitization here

        // Modify the query to select fromDate, toDate, typeOfLeave, and calculate numDays
        $stmt = $pdo->prepare(
            "SELECT fromDate, toDate, typeOfLeave, DATEDIFF(toDate, fromDate) AS numDays 
            FROM tblApplyLeave 
            WHERE employeeID = :empID"
        );

        // Bind the employee ID parameter (use PDO::PARAM_STR if it's a string)
        $stmt->bindParam(':empID', $employeeID, PDO::PARAM_STR);  // Use PDO::PARAM_STR if employeeID is a string
        
        // Execute the query
        $stmt->execute();
        
        // Fetch all the leave applications for the employee
        $leaveApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Debugging: Check the result of the query
        error_log("Fetched Leave Applications for employeeID $employeeID: " . print_r($leaveApplications, true));

        // Return the results as JSON
        echo json_encode($leaveApplications);

    } catch (PDOException $e) {
        // In case of error, log the error message
        error_log("Error in getLeaveApplicationsByEmployee: " . $e->getMessage());
        echo json_encode(['error' => 'An error occurred while fetching leave applications for the employee.']);
    }
}

}
?>
