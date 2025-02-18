<?php
// ApplyLeaveComponent.php

// Include the database configuration file at the top (outside the class)
require_once 'config.inc.php'; // Ensure the path is correct

// ApplyLeaveComponent class definition
namespace Component;

class ApplyLeaveComponent {
    
    // Method to get all leave applications
    public function getAllLeaveApplications($f3) {
        global $pdo;

        try {
            $stmt = $pdo->prepare("SELECT * FROM tblApplyLeave ORDER BY createdOn DESC");
            $stmt->execute();
            $leaveApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'data' => $leaveApplications
            ]);

        } catch (PDOException $e) {
            error_log("Error in getAllLeaveApplications: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to fetch leave applications'
            ]);
        }
    }

    // Method to get leave applications by employee ID
    public function getLeaveApplicationsByEmployee($f3, $employeeID) {
        global $pdo;

        try {
            $stmt = $pdo->prepare("SELECT * FROM tblApplyLeave WHERE employeeID = :employeeID ORDER BY createdOn DESC");
            $stmt->bindParam(':employeeID', $employeeID, PDO::PARAM_STR);
            $stmt->execute();
            $leaveApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'data' => $leaveApplications
            ]);

        } catch (PDOException $e) {
            error_log("Error in getLeaveApplicationsByEmployee: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to fetch leave applications'
            ]);
        }
    }

    public function checkLeaveStatus($f3) {
        global $pdo;
        
        try {
            // Ensure user is logged in
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
                return;
            }

            $userId = $_SESSION['user_id'];
            
            $stmt = $pdo->prepare("SELECT * FROM tblApplyLeave WHERE employeeID = :userId ORDER BY createdOn DESC");
            $stmt->bindParam(':userId', $userId, PDO::PARAM_STR);
            $stmt->execute();
            $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'data' => $leaves
            ]);

        } catch (PDOException $e) {
            error_log("Error in checkLeaveStatus: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to fetch leave status'
            ]);
        }
    }
// method to save the applied leave
    public function applyLeave($f3) {
        global $pdo;
        
        try {
            // Get POST data
            $data = [
                'employeeID' => $f3->get('POST.employeeID'),
                'fromDate' => $f3->get('POST.fromDate'),
                'toDate' => $f3->get('POST.toDate'),
                'typeOfLeave' => $f3->get('POST.typeOfLeave'),
                'reason' => $f3->get('POST.reason')
            ];

            // Validate required fields
            if (empty($data['employeeID']) || empty($data['fromDate']) || 
                empty($data['toDate']) || empty($data['typeOfLeave']) || 
                empty($data['reason'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'All fields are required'
                ]);
                return;
            }

            // Validate dates
            $fromDate = new DateTime($data['fromDate']);
            $toDate = new DateTime($data['toDate']);
            
            if ($fromDate > $toDate) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'From date cannot be later than to date'
                ]);
                return;
            }

            // Prepare INSERT query
            $query = "INSERT INTO tblApplyLeave (
                employeeID, 
                fromDate, 
                toDate, 
                typeOfLeave, 
                reason, 
                createdOn, 
                status, 
                isReApply, 
                reasonStat
            ) VALUES (
                :employeeID,
                :fromDate,
                :toDate,
                :typeOfLeave,
                :reason,
                NOW(),
                'Pending',
                0,
                NULL
            )";

            $stmt = $pdo->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':employeeID', $data['employeeID'], PDO::PARAM_STR);
            $stmt->bindParam(':fromDate', $data['fromDate'], PDO::PARAM_STR);
            $stmt->bindParam(':toDate', $data['toDate'], PDO::PARAM_STR);
            $stmt->bindParam(':typeOfLeave', $data['typeOfLeave'], PDO::PARAM_STR);
            $stmt->bindParam(':reason', $data['reason'], PDO::PARAM_STR);

            // Execute the query
            $stmt->execute();

            // Get the inserted ID
            $applyLeaveID = $pdo->lastInsertId();

            echo json_encode([
                'status' => 'success',
                'message' => 'Leave application submitted successfully',
                'applyLeaveID' => $applyLeaveID
            ]);

        } catch (PDOException $e) {
            error_log("Error in applyLeave: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to submit leave application'
            ]);
        }
    }
}
?>
