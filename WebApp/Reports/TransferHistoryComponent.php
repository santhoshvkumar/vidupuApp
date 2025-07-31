<?php
class TransferHistoryComponent {
    public $employeeID;
    public $organisationID;
    
    public function loadTransferHistoryDetails(array $data) {
        if (isset($data['employeeID']) && isset($data['organisationID'])) {
            $this->employeeID = $data['employeeID'];
            $this->organisationID = $data['organisationID'];
            return true;
        } else {
            return false;
        }
    }
    
    public function getTransferHistory() {
        include('config.inc');
        header('Content-Type: application/json');
        
        try {
            $employeeID = mysqli_real_escape_string($connect_var, $this->employeeID);
            $organisationID = mysqli_real_escape_string($connect_var, $this->organisationID);
            
            error_log("TransferHistory: EmployeeID = $employeeID, OrganisationID = $organisationID");
            
            // Get employee details with branch mapping from tblmapEmp
            $employeeQuery = "SELECT e.*, b.branchName, b.branchID as currentBranchID,
                                    CASE 
                                        WHEN e.employeePhoto IS NOT NULL AND e.employeePhoto != '' 
                                        THEN CONCAT('Uploads/profile_photos/', e.employeePhoto)
                                        ELSE NULL 
                                    END as employeePhoto
                             FROM tblEmployee e 
                             LEFT JOIN tblmapEmp me ON e.employeeID = me.employeeID
                             LEFT JOIN tblBranch b ON me.branchID = b.branchID
                             WHERE e.employeeID = ? AND e.organisationID = ?";
            
            $employeeStmt = mysqli_prepare($connect_var, $employeeQuery);
            mysqli_stmt_bind_param($employeeStmt, "si", $employeeID, $organisationID);
            mysqli_stmt_execute($employeeStmt);
            $employeeResult = mysqli_stmt_get_result($employeeStmt);
            $employee = mysqli_fetch_assoc($employeeResult);
            
            if (!$employee) {
                echo json_encode(array(
                    "status" => "error",
                    "message_text" => "Employee not found"
                ), JSON_FORCE_OBJECT);
                return;
            }
            
            // Get transfer history (show all transfers, not just active ones)
            $transferQuery = "SELECT th.*, 
                                    b1.branchName as fromBranchName,
                                    b2.branchName as toBranchName
                             FROM tblTransferHistory th
                             LEFT JOIN tblBranch b1 ON th.fromBranch = b1.branchID
                             LEFT JOIN tblBranch b2 ON th.toBranch = b2.branchID
                             WHERE th.employeeID = ?
                             ORDER BY th.fromDate DESC";
            
            $transferStmt = mysqli_prepare($connect_var, $transferQuery);
            mysqli_stmt_bind_param($transferStmt, "s", $employeeID);
            mysqli_stmt_execute($transferStmt);
            $transferResult = mysqli_stmt_get_result($transferStmt);
            
            error_log("TransferHistory: Transfer query executed. Found " . mysqli_num_rows($transferResult) . " records");
            
            // Debug: Check if any records were found
            if (mysqli_num_rows($transferResult) == 0) {
                error_log("TransferHistory: No transfer records found for employeeID: $employeeID");
            }
            
            $transfers = array();
            while ($row = mysqli_fetch_assoc($transferResult)) {
                $transfers[] = array(
                    'transferID' => $row['transferHistoryID'],
                    'transferDate' => $row['fromDate'],
                    'fromBranch' => $row['fromBranchName'] ?: 'N/A',
                    'toBranch' => $row['toBranchName'] ?: 'N/A',
                    'transferType' => $row['transferType'] ?: 'N/A',
                    'toDate' => $row['toDate'] ?: 'N/A',
                    'isImmediateTransfer' => $row['isImmediateTransfer'] ? 'Yes' : 'No',
                    'isActive' => $row['isActive'] ? 'Active' : 'Inactive'
                );
            }
            
            // Ensure transfers is always a sequential array, not an associative array
            $transfers = array_values($transfers);
            
            // Ensure transfers is always an array, not an object
            if (empty($transfers)) {
                $transfers = array();
            }
            
            $response = array(
                'status' => 'success',
                'data' => array(
                    'employee' => array(
                        'employeeID' => $employee['employeeID'],
                        'empID' => $employee['empID'],
                        'employeeName' => $employee['employeeName'],
                        'employeePhone' => $employee['employeePhone'],
                        'Designation' => $employee['Designation'],
                        'branchName' => $employee['branchName'],
                        'branchID' => $employee['currentBranchID'],
                        'employeePhoto' => $employee['employeePhoto'] ?: null
                    ),
                    'transfers' => $transfers
                )
            );
            
            error_log("TransferHistory: Sending response with " . count($transfers) . " transfers");
            echo json_encode($response, JSON_FORCE_OBJECT);
            
        } catch (Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Database error: " . $e->getMessage()
            ), JSON_FORCE_OBJECT);
        }
        
        mysqli_close($connect_var);
    }
}
?> 