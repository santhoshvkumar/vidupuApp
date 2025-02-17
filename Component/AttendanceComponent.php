<?php

class AttendanceComponent {

    // Method to get all attendance records
    public function getAllAttendance($f3) {
        // Get the PDO connection from the Fat-Free container
        $db = $f3->get('DB');

        // Query to fetch all attendance records
        $query = "SELECT * FROM tblAttendance";
        
        // Execute the query
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        // Fetch all rows as an associative array
        $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Send the data as JSON response
        echo json_encode($attendanceRecords);
    }

    // Method to get attendance by employee ID
    public function getAttendanceByEmployee($f3) {
        // Get the PDO connection from the Fat-Free container
        $db = $f3->get('DB');

        // Get the empID from the URL parameters
        $empID = $f3->get('PARAMS.empID');

        // Query to fetch attendance record by empID
        $query = "SELECT * FROM tblAttendance WHERE empID = :empID";

        // Prepare and execute the query
        $stmt = $db->prepare($query);
        $stmt->bindParam(':empID', $empID, PDO::PARAM_STR);
        $stmt->execute();

        // Fetch the result
        $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Send the data as JSON response
        echo json_encode($attendance);
    }

    // Method to add a new attendance record
    public function addAttendance($f3) {
        // Get the PDO connection from the Fat-Free container
        $db = $f3->get('DB');

        // Get the data from the request body
        $data = json_decode(file_get_contents("php://input"), true);

        // Prepare and execute the insert query
        $query = "INSERT INTO tblAttendance (empID, attendanceDate, checkInTime, checkOutTime) 
                  VALUES (:empID, :attendanceDate, :checkInTime, :checkOutTime)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':empID', $data['empID'], PDO::PARAM_STR);
        $stmt->bindParam(':attendanceDate', $data['attendanceDate'], PDO::PARAM_STR);
        $stmt->bindParam(':checkInTime', $data['checkInTime'], PDO::PARAM_STR);
        $stmt->bindParam(':checkOutTime', $data['checkOutTime'], PDO::PARAM_STR);

        // Execute the query and check if successful
        if ($stmt->execute()) {
            echo json_encode(["message" => "Attendance added successfully"]);
        } else {
            echo json_encode(["error" => "Error inserting attendance"]);
        }
    }

    // Method to update attendance record
    public function updateAttendance($f3) {
        // Get the PDO connection from the Fat-Free container
        $db = $f3->get('DB');

        // Get the attendanceID from the URL parameters
        $attendanceID = $f3->get('PARAMS.attendanceID');

        // Get the data from the request body
        $data = json_decode(file_get_contents("php://input"), true);

        // Prepare and execute the update query
        $query = "UPDATE tblAttendance 
                  SET checkInTime = :checkInTime, checkOutTime = :checkOutTime 
                  WHERE attendanceID = :attendanceID";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':attendanceID', $attendanceID, PDO::PARAM_INT);
        $stmt->bindParam(':checkInTime', $data['checkInTime'], PDO::PARAM_STR);
        $stmt->bindParam(':checkOutTime', $data['checkOutTime'], PDO::PARAM_STR);

        // Execute the query and check if successful
        if ($stmt->execute()) {
            echo json_encode(["message" => "Attendance updated successfully"]);
        } else {
            echo json_encode(["error" => "Error updating attendance"]);
        }
    }

    // Method to delete an attendance record
    public function deleteAttendance($f3) {
        // Get the PDO connection from the Fat-Free container
        $db = $f3->get('DB');

        // Get the attendanceID from the URL parameters
        $attendanceID = $f3->get('PARAMS.attendanceID');

        // Prepare and execute the delete query
        $query = "DELETE FROM tblAttendance WHERE attendanceID = :attendanceID";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':attendanceID', $attendanceID, PDO::PARAM_INT);

        // Execute the query and check if successful
        if ($stmt->execute()) {
            echo json_encode(["message" => "Attendance record deleted successfully"]);
        } else {
            echo json_encode(["error" => "Error deleting attendance record"]);
        }
    }
}
