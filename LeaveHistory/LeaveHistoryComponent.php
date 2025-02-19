<?php
namespace LeaveHistory;

require_once __DIR__ . '/../config.inc';

class LeaveHistoryComponent {
    // Get all leave history
    public function getAllLeaveHistory($f3) {
        global $connect_var;  // Using the mysqli connection from config.inc

        try {
            $query = "SELECT * FROM tblApplyLeave ORDER BY createdOn DESC";
            $result = mysqli_query($connect_var, $query);
            $leaveHistory = mysqli_fetch_all($result, MYSQLI_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'data' => $leaveHistory
            ]);

        } catch (Exception $e) {
            error_log("Error in getAllLeaveHistory: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to fetch leave history'
            ]);
        }
    }

    // Get leave history for specific employee
    public function getLeaveHistoryByEmployee($f3, $employeeID) {
        global $connect_var;

        try {
            $query = "SELECT * FROM tblApplyLeave WHERE employeeID = '" . mysqli_real_escape_string($connect_var, $employeeID) . "' ORDER BY createdOn DESC";
            $result = mysqli_query($connect_var, $query);
            $leaveHistory = mysqli_fetch_all($result, MYSQLI_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'data' => $leaveHistory
            ]);

        } catch (Exception $e) {
            error_log("Error in getLeaveHistoryByEmployee: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to fetch leave history'
            ]);
        }
    }

    // Cancel leave
    public function cancelLeave($f3) {
        global $connect_var;
        
        try {
            $rawData = $f3->get('BODY');
            error_log("Received data: " . $rawData);
            
            $data = json_decode($rawData, true);
            
            if (!isset($data['applyLeaveID']) || !isset($data['employeeID']) || !isset($data['typeOfLeave'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Missing required fields',
                    'received_data' => $data
                ]);
                return;
            }

            $leaveID = $data['applyLeaveID'];
            $employeeID = $data['employeeID'];
            $typeOfLeave = $data['typeOfLeave'];

            // Check current status first
            $checkQuery = "SELECT status FROM tblApplyLeave WHERE applyLeaveID = " . (int)$leaveID;
            $checkResult = mysqli_query($connect_var, $checkQuery);
            $currentRecord = mysqli_fetch_assoc($checkResult);

            if (!$currentRecord) {
                throw new \Exception("Leave record not found");
            }

            if ($currentRecord['status'] === 'Cancelled') {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Leave is already cancelled'
                ]);
                return;
            }

            // Start transaction
            mysqli_begin_transaction($connect_var);

            // Update leave status to cancelled
            $updateQuery = "UPDATE tblApplyLeave 
                           SET status = 'Cancelled' 
                           WHERE applyLeaveID = " . (int)$leaveID . " 
                           AND employeeID = '" . mysqli_real_escape_string($connect_var, $employeeID) . "'
                           AND status != 'Cancelled'";
            
            error_log("Executing update query: " . $updateQuery);
            $updateResult = mysqli_query($connect_var, $updateQuery);
            
            if (!$updateResult) {
                throw new \Exception("Failed to update leave status: " . mysqli_error($connect_var));
            }

            // Map leave types to database columns
            $leaveColumnMap = [
                'Casual Leave' => 'CasualLeave',
                'Special Casual Leave' => 'SpecialCasualLeave',
                'Compensatory Off' => 'CompensatoryOff',
                'Special Leave Blood Donation' => 'SpecialLeaveBloodDonation',
                'Leave On Private Affairs' => 'LeaveOnPrivateAffairs',
                'Medical Leave' => 'MedicalLeave',
                'Privilege Leave' => 'PrivilegeLeave',
                'Maternity Leave' => 'MaternityLeave'
            ];

            $columnName = $leaveColumnMap[$typeOfLeave];

            // Increment leave balance
            $balanceQuery = "UPDATE tblleavebalance 
                            SET $columnName = $columnName + 1 
                            WHERE EmployeeID = '" . mysqli_real_escape_string($connect_var, $employeeID) . "' 
                            AND Year = YEAR(CURRENT_DATE)";
            
            error_log("Executing balance update query: " . $balanceQuery);
            $balanceResult = mysqli_query($connect_var, $balanceQuery);
            
            if (!$balanceResult) {
                throw new \Exception("Failed to update leave balance: " . mysqli_error($connect_var));
            }

            // Commit transaction
            mysqli_commit($connect_var);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Leave cancelled successfully',
                'updated_leave_id' => $leaveID
            ]);

        } catch (\Exception $e) {
            // Rollback on error
            mysqli_rollback($connect_var);
            error_log("Error in cancelLeave: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to cancel leave: ' . $e->getMessage()
            ]);
        }
    }
}
?>