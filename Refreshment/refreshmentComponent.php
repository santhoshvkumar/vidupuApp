<?php
class RefreshmentMaster {
    public $organisationID;
    public $empID;
    public $employeeID;
    public $employeeName;
    public $fromDate;
    public $toDate;

    public function loadAppliedNewspaperAllowanceDetails($decoded_items){
        if (isset($decoded_items['fromDate'])) {
            $this->fromDate = $decoded_items['fromDate'];
        }
        if (isset($decoded_items['toDate'])) {
            $this->toDate = $decoded_items['toDate'];
        }
        if (isset($decoded_items['organisationID'])) {
            $this->organisationID = $decoded_items['organisationID'];
        }        
        return true;
    }

    public function getAppliedNewspaperAllowanceDetails(){
        include('config.inc');
        header('Content-Type: application/json');
        try{
            $query = "SELECT 
    a.employeeID,
    e.empID,
    e.employeeName,
    CONCAT_WS(', ', n1.NewspaperName, n2.NewspaperName) AS SubscribedNewsPapers,
    CONCAT_WS(', ', a.firstNewspaperCost, a.secondNewspaperCost) AS SubscribedNewsPaperCost,
    a.totalCost,
    a.month as forTheMonthOf,
    a.year,
    a.billImage,
    a.createdDate AS appliedDate
FROM 
    tblNewspaperAllowance a
LEFT JOIN 
    tblEmployee e ON a.employeeID = e.employeeID
LEFT JOIN 
    tblNewspaper n1 ON a.firstNewspaperID = n1.newspaperID
LEFT JOIN 
    tblNewspaper n2 ON a.secondNewspaperID = n2.newspaperID
WHERE a.createdDate BETWEEN ? AND ? AND a.organisationID = ?";
                $stmt = mysqli_prepare($connect_var, $query);
                if (!$stmt) {
                    throw new Exception("Failed to prepare statement: " . mysqli_error($connect_var));
                }
                
                mysqli_stmt_bind_param($stmt, "ssi", $this->fromDate, $this->toDate, $this->organisationID);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to execute statement: " . mysqli_error($connect_var));
                }
                $result = mysqli_stmt_get_result($stmt);
                if (!$result) {
                    throw new Exception("Failed to get result: " . mysqli_error($connect_var));
                }
                $data = [];
                
                while ($row = mysqli_fetch_assoc($result)) {
                    $data[] = $row;
                }
                
                if (count($data) > 0) {
                    echo json_encode([
                        "status" => "success",
                        "message" => "Newspaper allowance details fetched successfully",
                        "record_count" => count($data),
                        "data" => $data
                    ]);
                } else {
                    echo json_encode([
                        "status" => "error",
                        "message" => "No newspaper allowance details found"
                    ], JSON_FORCE_OBJECT);
                }           
               
            } catch (Exception $e) {
                error_log("Error in getAppliedNewspaperAllowanceDetails: " . $e->getMessage());
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

            // Get employee details including allowance flags
            $employeeQuery = "SELECT employeeID, isPhysicallyHandicapped, isWashingAllowance 
                            FROM tblEmployee 
                            WHERE employeeID = ?";
            $stmt = mysqli_prepare($connect_var, $employeeQuery);
            if (!$stmt) {
                throw new Exception("Failed to prepare employee query: " . mysqli_error($connect_var));
            }
            mysqli_stmt_bind_param($stmt, "s", $data['employeeID']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $employee = mysqli_fetch_assoc($result);

            if (!$employee) {
                throw new Exception("Employee not found");
            }

            // Get total working days for the month
            $workingDaysQuery = "SELECT noOfWorkingDays, monthName 
                               FROM tblWorkingDays 
                               WHERE workingDayID = ? AND Year = ?";
            $stmt = mysqli_prepare($connect_var, $workingDaysQuery);
            if (!$stmt) {
                throw new Exception("Failed to prepare working days query: " . mysqli_error($connect_var));
            }
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
            if (!$stmt) {
                throw new Exception("Failed to prepare leave query: " . mysqli_error($connect_var));
            }
            mysqli_stmt_bind_param($stmt, "sii", $data['employeeID'], $data['month'], $data['year']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $leaveDays = mysqli_fetch_assoc($result);

            $approvedLeaveDays = $leaveDays['leaveDays'];
            $eligibleDays = $totalWorkingDays - $approvedLeaveDays;
            
            // Calculate individual allowances
            $amountPerDay = 90.00; // Fixed amount per day for refreshment
            $refreshmentAmount = $eligibleDays * $amountPerDay;
            
            // Calculate washing allowance if eligible
            $washingAmount = 0.00;
            if ($employee['isWashingAllowance'] == 1) {
                $washingAmount = $totalWorkingDays * 25.00; // ₹25 per working day
            }
            
            // Calculate physically handicapped allowance if eligible
            $physicallyHandicappedAmount = 0.00;
            if ($employee['isPhysicallyHandicapped'] == 1) {
                $physicallyHandicappedAmount = 2500.00; // Fixed ₹2500 per month
            }
            
            // Calculate medical allowance (only in May and December)
            $medicalAmount = 0.00;
            if ($data['month'] == 5 || $data['month'] == 12) {
                $medicalAmount = 6000.00;
            }
            
            // Calculate total amount
            $totalAmount = $refreshmentAmount + $washingAmount + $physicallyHandicappedAmount + $medicalAmount;

            // Check if refreshment record already exists
            $checkQuery = "SELECT RefreshmentID FROM tblRefreshment 
                          WHERE EmployeeID = ? AND Month = ? AND Year = ?";
            $stmt = mysqli_prepare($connect_var, $checkQuery);
            if (!$stmt) {
                throw new Exception("Failed to prepare check query: " . mysqli_error($connect_var));
            }
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
                                  RefreshmentAmount = ?,
                                  WashingAmount = ?,
                                  PhysicallyHandicappedAmount = ?,
                                  MedicalAmount = ?,
                                  TotalAmount = ?,
                                  Status = 'Pending'
                              WHERE EmployeeID = ? AND Month = ? AND Year = ?";
                
                $stmt = mysqli_prepare($connect_var, $updateQuery);
                if (!$stmt) {
                    throw new Exception("Failed to prepare update query: " . mysqli_error($connect_var));
                }
                mysqli_stmt_bind_param($stmt, "iiiddddddsis", 
                    $totalWorkingDays,
                    $approvedLeaveDays,
                    $eligibleDays,
                    $amountPerDay,
                    $refreshmentAmount,
                    $washingAmount,
                    $physicallyHandicappedAmount,
                    $medicalAmount,
                    $totalAmount,
                    $data['employeeID'],
                    $data['month'],
                    $data['year']
                );
            } else {
                // Insert new record
                $insertQuery = "INSERT INTO tblRefreshment 
                              (EmployeeID, Month, Year, TotalWorkingDays, 
                               ApprovedLeaveDays, EligibleDays, AmountPerDay, 
                               RefreshmentAmount, WashingAmount, 
                               PhysicallyHandicappedAmount, MedicalAmount,
                               TotalAmount, Status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
                
                $stmt = mysqli_prepare($connect_var, $insertQuery);
                if (!$stmt) {
                    throw new Exception("Failed to prepare insert query: " . mysqli_error($connect_var));
                }
                mysqli_stmt_bind_param($stmt, "siiiiiiddddd", 
                    $data['employeeID'],
                    $data['month'],
                    $data['year'],
                    $totalWorkingDays,
                    $approvedLeaveDays,
                    $eligibleDays,
                    $amountPerDay,
                    $refreshmentAmount,
                    $washingAmount,
                    $physicallyHandicappedAmount,
                    $medicalAmount,
                    $totalAmount
                );
            }

            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(array(
                    "status" => "success",
                    "message_text" => "Allowances calculated successfully",
                    "result" => array(
                        "monthName" => $monthName,
                        "totalWorkingDays" => $totalWorkingDays,
                        "approvedLeaveDays" => $approvedLeaveDays,
                        "eligibleDays" => $eligibleDays,
                        "allowances" => array(
                            "refreshment" => array(
                                "amountPerDay" => $amountPerDay,
                                "totalAmount" => $refreshmentAmount
                            ),
                            "washing" => array(
                                "enabled" => (bool)$employee['isWashingAllowance'],
                                "amountPerDay" => 25.00,
                                "totalAmount" => $washingAmount
                            ),
                            "physicallyHandicapped" => array(
                                "enabled" => (bool)$employee['isPhysicallyHandicapped'],
                                "amount" => $physicallyHandicappedAmount
                            ),
                            "medical" => array(
                                "amount" => $medicalAmount
                            )
                        ),
                        "totalAmount" => $totalAmount
                    )
                ));
            } else {
                throw new Exception("Failed to save allowance data: " . mysqli_error($connect_var));
            }

        } catch (Exception $e) {
            error_log("Error in calculateRefreshmentAllowance: " . $e->getMessage());
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

function getAppliedNewspaperAllowanceDetails($data) {
    $refreshmentObject = new RefreshmentMaster();
    if ($refreshmentObject->loadAppliedNewspaperAllowanceDetails($data)) {  
        $refreshmentObject->getAppliedNewspaperAllowanceDetails();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
?>
