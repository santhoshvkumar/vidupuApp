<?php
class RefreshmentMaster {
    public function getNewspaperDetails() {
        include('config.inc');
        header('Content-Type: application/json');
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        try {
            $query = "SELECT 
                        NewspaperID as id,
                        NewspaperName as name,
                        Cost as cost
                    FROM tblNewspaper 
                    ORDER BY NewspaperID DESC";

            $result = mysqli_query($connect_var, $query);
            $newspapers = array();
            
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $newspapers[] = array(
                        'id' => $row['id'],
                        'name' => $row['name'],
                        'cost' => number_format((float)$row['cost'], 2, '.', '')
                    );
                }
                
                echo json_encode(array(
                    "status" => "success",
                    "record_count" => count($newspapers),
                    "result" => $newspapers
                ));
            } else {
                echo json_encode(array(
                    "status" => "failure",
                    "message_text" => "Failed to fetch newspaper details"
                ));
            }
        } catch (Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ));
        }

        mysqli_close($connect_var);
    }

    public function submitNewspaperAllowance($data) {
        include('config.inc');
        header('Content-Type: application/json');
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        try {
            // Validate required fields
            if (!isset($data['employeeID']) || empty($data['employeeID'])) {
                throw new Exception("Employee ID is required");
            }

            if (!isset($data['firstNewspaperID']) || !is_numeric($data['firstNewspaperID'])) {
                throw new Exception("First newspaper selection is invalid");
            }

            if (!isset($data['secondNewspaperID']) || !is_numeric($data['secondNewspaperID'])) {
                throw new Exception("Second newspaper selection is invalid");
            }

            if (!isset($data['month']) || !is_numeric($data['month']) || $data['month'] < 1 || $data['month'] > 12) {
                throw new Exception("Invalid month selected");
            }

            if (!isset($data['year']) || !is_numeric($data['year']) || $data['year'] < 2000 || $data['year'] > 2100) {
                throw new Exception("Invalid year selected");
            }

            if (!isset($data['billImage']) || empty($data['billImage'])) {
                throw new Exception("Bill image is required");
            }

            // Check if employee exists
            $checkEmployeeQuery = "SELECT EmployeeID FROM tblEmployee WHERE EmployeeID = ?";
            $stmt = mysqli_prepare($connect_var, $checkEmployeeQuery);
            mysqli_stmt_bind_param($stmt, "s", $data['employeeID']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) === 0) {
                throw new Exception("Invalid employee ID");
            }

            // Check if employee already submitted for this month
            $checkQuery = "SELECT SubscriptionID FROM tblNewspaperAllowance 
                          WHERE EmployeeID = ? AND Month = ? AND Year = ?";
            $stmt = mysqli_prepare($connect_var, $checkQuery);
            mysqli_stmt_bind_param($stmt, "sii", $data['employeeID'], $data['month'], $data['year']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                throw new Exception("You have already submitted newspaper selection for this month");
            }

            // Get newspaper costs
            $query = "SELECT NewspaperID, Cost FROM tblNewspaper 
                     WHERE NewspaperID IN (?, ?)";
            $stmt = mysqli_prepare($connect_var, $query);
            mysqli_stmt_bind_param($stmt, "ii", $data['firstNewspaperID'], $data['secondNewspaperID']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            $costs = array();
            while ($row = mysqli_fetch_assoc($result)) {
                $costs[$row['NewspaperID']] = $row['Cost'];
            }

            if (count($costs) !== 2) {
                throw new Exception("Invalid newspaper selection");
            }

            $firstNewspaperCost = $costs[$data['firstNewspaperID']];
            $secondNewspaperCost = $costs[$data['secondNewspaperID']];
            $totalCost = $firstNewspaperCost + $secondNewspaperCost;

            // Insert into tblNewspaperAllowance
            $insertQuery = "INSERT INTO tblNewspaperAllowance 
                          (EmployeeID, FirstNewspaperID, SecondNewspaperID, 
                           FirstNewspaperCost, SecondNewspaperCost, TotalCost,
                           Month, Year, BillImage, CreatedDate) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = mysqli_prepare($connect_var, $insertQuery);
            mysqli_stmt_bind_param($stmt, "siidddiis", 
                $data['employeeID'],
                $data['firstNewspaperID'],
                $data['secondNewspaperID'],
                $firstNewspaperCost,
                $secondNewspaperCost,
                $totalCost,
                $data['month'],
                $data['year'],
                $data['billImage']
            );

            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(array(
                    "status" => "success",
                    "message_text" => "Newspaper allowance submitted successfully",
                    "allowance_id" => mysqli_insert_id($connect_var)
                ));
            } else {
                throw new Exception("Failed to submit newspaper allowance: " . mysqli_error($connect_var));
            }

        } catch (Exception $e) {
            error_log("Error in submitNewspaperAllowance: " . $e->getMessage());
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ));
        }

        mysqli_close($connect_var);
    }

    public function calculateRefreshmentAllowance($data) {
        include('config.inc');
        header('Content-Type: application/json');
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        try {
            // Validate required fields
            if (!isset($data['employeeID']) || !isset($data['month']) || !isset($data['year'])) {
                throw new Exception("Missing required fields");
            }

            // Get total working days for the month
            $workingDaysQuery = "SELECT noOfWorkingDays, monthName 
                               FROM tblWorkingDays 
                               WHERE workingDayID = ? AND Year = ?";
            $stmt = mysqli_prepare($connect_var, $workingDaysQuery);
            mysqli_stmt_bind_param($stmt, "ii", $data['month'], $data['year']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $workingDays = mysqli_fetch_assoc($result);

            if (!$workingDays) {
                throw new Exception("Working days not found for the specified month and year");
            }

            $totalWorkingDays = $workingDays['noOfWorkingDays'];
            $monthName = $workingDays['monthName'];

            // Get approved leave days - considering only approved leaves within the month
            $leaveQuery = "SELECT COUNT(*) as leaveDays 
                          FROM tblApplyLeave 
                          WHERE employeeID = ? 
                          AND MONTH(fromDate) = ? 
                          AND YEAR(fromDate) = ? 
                          AND status = 'Approved'
                          AND (isExtend = 0 OR isExtend IS NULL)"; // Exclude extended leaves
            
            $stmt = mysqli_prepare($connect_var, $leaveQuery);
            mysqli_stmt_bind_param($stmt, "sii", $data['employeeID'], $data['month'], $data['year']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $leaveDays = mysqli_fetch_assoc($result);

            $approvedLeaveDays = $leaveDays['leaveDays'];
            $eligibleDays = $totalWorkingDays - $approvedLeaveDays;
            $amountPerDay = 90.00; // Fixed amount per day
            $totalAmount = $eligibleDays * $amountPerDay;

            // Check if refreshment record already exists
            $checkQuery = "SELECT RefreshmentID FROM tblRefreshment 
                          WHERE EmployeeID = ? AND Month = ? AND Year = ?";
            $stmt = mysqli_prepare($connect_var, $checkQuery);
            mysqli_stmt_bind_param($stmt, "sii", $data['employeeID'], $data['month'], $data['year']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                // Update existing record
                $updateQuery = "UPDATE tblRefreshment 
                              SET TotalWorkingDays = ?,
                                  ApprovedLeaveDays = ?,
                                  EligibleDays = ?,
                                  AmountPerDay = ?,
                                  TotalAmount = ?,
                                  Status = 'Pending'
                              WHERE EmployeeID = ? AND Month = ? AND Year = ?";
                
                $stmt = mysqli_prepare($connect_var, $updateQuery);
                mysqli_stmt_bind_param($stmt, "iiiddsii", 
                    $totalWorkingDays,
                    $approvedLeaveDays,
                    $eligibleDays,
                    $amountPerDay,
                    $totalAmount,
                    $data['employeeID'],
                    $data['month'],
                    $data['year']
                );
            } else {
                // Insert new record
                $insertQuery = "INSERT INTO tblRefreshment 
                              (EmployeeID, Month, Year, TotalWorkingDays, 
                               ApprovedLeaveDays, EligibleDays, AmountPerDay, TotalAmount, Status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
                
                $stmt = mysqli_prepare($connect_var, $insertQuery);
                mysqli_stmt_bind_param($stmt, "siiiiidd", 
                    $data['employeeID'],
                    $data['month'],
                    $data['year'],
                    $totalWorkingDays,
                    $approvedLeaveDays,
                    $eligibleDays,
                    $amountPerDay,
                    $totalAmount
                );
            }

            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(array(
                    "status" => "success",
                    "message_text" => "Refreshment allowance calculated successfully",
                    "result" => array(
                        "monthName" => $monthName,
                        "totalWorkingDays" => $totalWorkingDays,
                        "approvedLeaveDays" => $approvedLeaveDays,
                        "eligibleDays" => $eligibleDays,
                        "amountPerDay" => $amountPerDay,
                        "totalAmount" => $totalAmount
                    )
                ));
            } else {
                throw new Exception("Failed to save refreshment allowance");
            }

        } catch (Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ));
        }

        mysqli_close($connect_var);
    }

    public function getNewspaperHistory($employeeID) {
        include('config.inc');
        header('Content-Type: application/json');
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    
        try {
            $query = "SELECT 
                        na.SubscriptionID as id,
                        na.EmployeeID as employeeID,
                        na.FirstNewspaperID,
                        na.SecondNewspaperID,
                        na.FirstNewspaperCost,
                        na.SecondNewspaperCost,
                        na.TotalCost as totalAmount,
                        na.Month,
                        na.Year,
                        na.BillImage,
                        na.CreatedDate,
                        n1.NewspaperName as firstNewspaperName,
                        n2.NewspaperName as secondNewspaperName
                    FROM tblNewspaperAllowance na
                    LEFT JOIN tblNewspaper n1 ON na.FirstNewspaperID = n1.NewspaperID
                    LEFT JOIN tblNewspaper n2 ON na.SecondNewspaperID = n2.NewspaperID
                    WHERE na.EmployeeID = ?
                    ORDER BY na.CreatedDate DESC";
    
            $stmt = mysqli_prepare($connect_var, $query);
            mysqli_stmt_bind_param($stmt, "s", $employeeID);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $history = array();
            while ($row = mysqli_fetch_assoc($result)) {
                $history[] = array(
                    'id' => $row['id'],
                    'employeeID' => $row['employeeID'],
                    'firstNewspaperID' => $row['FirstNewspaperID'],
                    'secondNewspaperID' => $row['SecondNewspaperID'],
                    'firstNewspaperName' => $row['firstNewspaperName'],
                    'secondNewspaperName' => $row['secondNewspaperName'],
                    'firstNewspaperCost' => number_format((float)$row['FirstNewspaperCost'], 2, '.', ''),
                    'secondNewspaperCost' => number_format((float)$row['SecondNewspaperCost'], 2, '.', ''),
                    'totalAmount' => number_format((float)$row['totalAmount'], 2, '.', ''),
                    'month' => $row['Month'],
                    'year' => $row['Year'],
                    'billImage' => $row['BillImage'],
                    'createdDate' => $row['CreatedDate']
                    // 'status' => $row['status'] // <-- removed
                );
            }
            
            echo json_encode(array(
                "status" => "success",
                "record_count" => count($history),
                "result" => $history
            ));
    
        } catch (Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ));
        }
    
        mysqli_close($connect_var);
    }
}

function getNewspaperDetails() {
    $refreshmentObject = new RefreshmentMaster();
    $refreshmentObject->getNewspaperDetails();
}

function getNewspaperHistory($employeeID) {
    $refreshmentObject = new RefreshmentMaster();
    $refreshmentObject->getNewspaperHistory($employeeID);
}

function submitNewspaperSubscription($data) {
    $refreshmentObject = new RefreshmentMaster();
    $refreshmentObject->submitNewspaperSubscription($data);
}

function calculateRefreshmentAllowance($data) {
    $refreshmentObject = new RefreshmentMaster();
    $refreshmentObject->calculateRefreshmentAllowance($data);
}
?>
