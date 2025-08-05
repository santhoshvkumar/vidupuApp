<?php
class NewspaperMaster {
    public $subscriptionID;
    public $employeeID;
    public $firstNewspaperID;
    public $secondNewspaperID;
    public $firstNewspaperCost;
    public $secondNewspaperCost;
    public $totalCost;
    public $month;
    public $year;
    public $billImage;
    public $organisationID;

    public function loadNewspaperData($decoded_items) {
        if (isset($decoded_items['subscriptionID'])) {
            $this->subscriptionID = $decoded_items['subscriptionID'];
        }
        if (isset($decoded_items['employeeID'])) {
            $this->employeeID = $decoded_items['employeeID'];
        }
        if (isset($decoded_items['organisationID'])) {
            $this->organisationID = $decoded_items['organisationID'];
        }
        if (isset($decoded_items['month'])) {
            $this->month = $decoded_items['month'];
        }
        if (isset($decoded_items['year'])) {
            $this->year = $decoded_items['year'];
        }
        return true;
    }

    public function getNewspaperAllowancesByOrganisationID($data) {
        include('config.inc');
        header('Content-Type: application/json');
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        try {
            // Validate required fields
            if (!isset($data['organisationID']) || empty($data['organisationID'])) {
                throw new Exception("Organisation ID is required");
            }

            $organisationID = $data['organisationID'];
            $month = isset($data['month']) ? $data['month'] : date('m');
            $year = isset($data['year']) ? $data['year'] : date('Y');

            // Check if the requested month is current month or future month
            $currentMonth = date('m');
            $currentYear = date('Y');
            
            if (($year > $currentYear) || ($year == $currentYear && $month >= $currentMonth)) {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Can only view newspaper allowances for previous months"
                ), JSON_FORCE_OBJECT);
                return;
            }

            // Get all newspaper allowances with employee details
            $query = "SELECT 
                        n.subscriptionID,
                        n.employeeID,
                        n.firstNewspaperID,
                        n.secondNewspaperID,
                        n.firstNewspaperCost,
                        n.secondNewspaperCost,
                        n.totalCost,
                        n.month,
                        n.year,
                        n.billImage,
                        n.status,
                        n.createdDate,
                        e.employeeName,
                        e.empID,
                        b.branchName,
                        n1.newspaperName as firstNewspaperName,
                        n2.newspaperName as secondNewspaperName
                    FROM tblNewspaperAllowance n
                    LEFT JOIN tblEmployee e ON n.employeeID = e.employeeID
                    LEFT JOIN tblmapEmp m ON e.employeeID = m.employeeID
                    LEFT JOIN tblBranch b ON m.branchID = b.branchID
                    LEFT JOIN tblNewspaper n1 ON n.firstNewspaperID = n1.newspaperID
                    LEFT JOIN tblNewspaper n2 ON n.secondNewspaperID = n2.newspaperID
                    WHERE m.organisationID = ? 
                    AND n.month = ? 
                    AND n.year = ?
                    AND e.isActive = 1
                    ORDER BY e.employeeName";

            $stmt = mysqli_prepare($connect_var, $query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . mysqli_error($connect_var));
            }

            mysqli_stmt_bind_param($stmt, "iii", $organisationID, $month, $year);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to execute statement: " . mysqli_error($connect_var));
            }

            $result = mysqli_stmt_get_result($stmt);
            if (!$result) {
                throw new Exception("Failed to get result: " . mysqli_error($connect_var));
            }

            $data = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = array(
                    'subscriptionID' => (int)$row['subscriptionID'],
                    'employeeID' => $row['employeeID'],
                    'empID' => $row['empID'],
                    'employeeName' => $row['employeeName'],
                    'branchName' => $row['branchName'],
                    'firstNewspaperID' => $row['firstNewspaperID'] ? (int)$row['firstNewspaperID'] : null,
                    'secondNewspaperID' => $row['secondNewspaperID'] ? (int)$row['secondNewspaperID'] : null,
                    'firstNewspaperName' => $row['firstNewspaperName'],
                    'secondNewspaperName' => $row['secondNewspaperName'],
                    'firstNewspaperCost' => $row['firstNewspaperCost'] ? number_format((float)$row['firstNewspaperCost'], 2, '.', '') : '0.00',
                    'secondNewspaperCost' => $row['secondNewspaperCost'] ? number_format((float)$row['secondNewspaperCost'], 2, '.', '') : '0.00',
                    'totalCost' => number_format((float)$row['totalCost'], 2, '.', ''),
                    'month' => (int)$row['month'],
                    'year' => (int)$row['year'],
                    'billImage' => $row['billImage'] ? $row['billImage'] : null,
                    'status' => $row['status'] ?? 'Pending',
                    'createdDate' => $row['createdDate']
                );
            }

            if (count($data) > 0) {
                echo json_encode([
                    "status" => "success",
                    "message" => "Newspaper allowances retrieved successfully",
                    "record_count" => count($data),
                    "data" => $data
                ]);
            } else {
                echo json_encode([
                    "status" => "success",
                    "message" => "No newspaper allowances found for this month",
                    "record_count" => 0,
                    "data" => []
                ]);
            }

        } catch (Exception $e) {
            error_log("Error in getNewspaperAllowancesByOrganisationID: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        } finally {
            if (isset($connect_var)) {
                mysqli_close($connect_var);
            }
        }
    }

    public function approveNewspaperAllowance($data) {
        include('config.inc');
        header('Content-Type: application/json');
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        try {
            // Validate required fields
            if (!isset($data['subscriptionID'])) {
                throw new Exception("Subscription ID is required");
            }

            $subscriptionID = $data['subscriptionID'];

            // Update newspaper allowance status to approved
            $updateQuery = "UPDATE tblNewspaperAllowance 
                          SET status = 'Approved', approvedDate = NOW()
                          WHERE subscriptionID = ?";
            
            $stmt = mysqli_prepare($connect_var, $updateQuery);
            if (!$stmt) {
                throw new Exception("Failed to prepare update query: " . mysqli_error($connect_var));
            }
            mysqli_stmt_bind_param($stmt, "i", $subscriptionID);
            
            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_affected_rows($connect_var) > 0) {
                    echo json_encode(array(
                        "status" => "success",
                        "message_text" => "Newspaper allowance approved successfully"
                    ));
                } else {
                    echo json_encode(array(
                        "status" => "error",
                        "message_text" => "No record found to approve"
                    ));
                }
            } else {
                throw new Exception("Failed to approve allowance: " . mysqli_error($connect_var));
            }

        } catch (Exception $e) {
            error_log("Error in approveNewspaperAllowance: " . $e->getMessage());
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ));
        } finally {
            if (isset($connect_var)) {
                mysqli_close($connect_var);
            }
        }
    }

    public function rejectNewspaperAllowance($data) {
        include('config.inc');
        header('Content-Type: application/json');
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        try {
            // Validate required fields
            if (!isset($data['subscriptionID'])) {
                throw new Exception("Subscription ID is required");
            }

            $subscriptionID = $data['subscriptionID'];

            // Update newspaper allowance status to rejected
            $updateQuery = "UPDATE tblNewspaperAllowance 
                          SET status = 'Rejected', rejectedDate = NOW()
                          WHERE subscriptionID = ?";
            
            $stmt = mysqli_prepare($connect_var, $updateQuery);
            if (!$stmt) {
                throw new Exception("Failed to prepare update query: " . mysqli_error($connect_var));
            }
            mysqli_stmt_bind_param($stmt, "i", $subscriptionID);
            
            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_affected_rows($connect_var) > 0) {
                    echo json_encode(array(
                        "status" => "success",
                        "message_text" => "Newspaper allowance rejected successfully"
                    ));
                } else {
                    echo json_encode(array(
                        "status" => "error",
                        "message_text" => "No record found to reject"
                    ));
                }
            } else {
                throw new Exception("Failed to reject allowance: " . mysqli_error($connect_var));
            }

        } catch (Exception $e) {
            error_log("Error in rejectNewspaperAllowance: " . $e->getMessage());
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ));
        } finally {
            if (isset($connect_var)) {
                mysqli_close($connect_var);
            }
        }
    }

    public function deleteNewspaperAllowance($data) {
        include('config.inc');
        header('Content-Type: application/json');
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        try {
            // Validate required fields
            if (!isset($data['subscriptionID'])) {
                throw new Exception("Subscription ID is required");
            }

            $subscriptionID = $data['subscriptionID'];

            // Get bill image path before deletion
            $getImageQuery = "SELECT billImage FROM tblNewspaperAllowance WHERE subscriptionID = ?";
            $getImageStmt = mysqli_prepare($connect_var, $getImageQuery);
            mysqli_stmt_bind_param($getImageStmt, "i", $subscriptionID);
            mysqli_stmt_execute($getImageStmt);
            $imageResult = mysqli_stmt_get_result($getImageStmt);
            $imageRow = mysqli_fetch_assoc($imageResult);
            $billImage = $imageRow ? $imageRow['billImage'] : null;

            if (!$imageRow) {
                echo json_encode(array(
                    "status" => "error",
                    "message_text" => "No record found"
                ));
                return;
            }

            // Delete the bill image file if it exists
            $fileDeleted = false;
            if ($billImage && file_exists($billImage)) {
                if (unlink($billImage)) {
                    $fileDeleted = true;
                }
            }

            // Update the record to set billImage to NULL
            $updateQuery = "UPDATE tblNewspaperAllowance SET billImage = NULL WHERE subscriptionID = ?";
            
            $stmt = mysqli_prepare($connect_var, $updateQuery);
            if (!$stmt) {
                throw new Exception("Failed to prepare update query: " . mysqli_error($connect_var));
            }
            mysqli_stmt_bind_param($stmt, "i", $subscriptionID);
            
            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_affected_rows($connect_var) > 0) {
                    $message = "Bill image deleted successfully";
                    if ($fileDeleted) {
                        $message .= " and file removed from server";
                    } else {
                        $message .= " (file was not found on server)";
                    }
                    
                    echo json_encode(array(
                        "status" => "success",
                        "message_text" => $message
                    ));
                } else {
                    echo json_encode(array(
                        "status" => "error",
                        "message_text" => "Failed to update record"
                    ));
                }
            } else {
                throw new Exception("Failed to update allowance: " . mysqli_error($connect_var));
            }

        } catch (Exception $e) {
            error_log("Error in deleteNewspaperAllowance: " . $e->getMessage());
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ));
        } finally {
            if (isset($connect_var)) {
                mysqli_close($connect_var);
            }
        }
    }

    public function bulkApproveNewspaperAllowances($data) {
        include('config.inc');
        header('Content-Type: application/json');
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        try {
            // Validate required fields
            if (!isset($data['organisationID']) || !isset($data['month']) || !isset($data['year'])) {
                throw new Exception("Missing required fields");
            }

            $organisationID = $data['organisationID'];
            $month = $data['month'];
            $year = $data['year'];

            // Update all pending newspaper allowances for the organization to approved
            $updateQuery = "UPDATE tblNewspaperAllowance n
                          JOIN tblEmployee e ON n.employeeID = e.employeeID
                          JOIN tblmapEmp m ON e.employeeID = m.employeeID
                          SET n.status = 'Approved', n.approvedDate = NOW()
                          WHERE m.organisationID = ? 
                          AND n.month = ? 
                          AND n.year = ? 
                          AND (n.status = 'Pending' OR n.status IS NULL)";
            
            $stmt = mysqli_prepare($connect_var, $updateQuery);
            if (!$stmt) {
                throw new Exception("Failed to prepare bulk update query: " . mysqli_error($connect_var));
            }
            mysqli_stmt_bind_param($stmt, "iii", $organisationID, $month, $year);
            
            if (mysqli_stmt_execute($stmt)) {
                $affectedRows = mysqli_affected_rows($connect_var);
                echo json_encode(array(
                    "status" => "success",
                    "message_text" => "Bulk approval completed successfully",
                    "approved_count" => $affectedRows
                ));
            } else {
                throw new Exception("Failed to bulk approve allowances: " . mysqli_error($connect_var));
            }

        } catch (Exception $e) {
            error_log("Error in bulkApproveNewspaperAllowances: " . $e->getMessage());
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ));
        } finally {
            if (isset($connect_var)) {
                mysqli_close($connect_var);
            }
        }
    }
}
?> 