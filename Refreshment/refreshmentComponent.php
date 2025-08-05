<?php
class RefreshmentMaster {
    public $organisationID;
    public $empID;
    public $employeeID;
    public $employeeName;
    public $fromDate;
    public $toDate;
    public $month;
    public $year;

    public function loadRefreshmentData($decoded_items){
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

    public function getRefreshmentAllowancesByOrganisationID($data) {
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
            $month = isset($data['month']) ? $data['month'] : (isset($data['Month']) ? $data['Month'] : date('m'));
            $year = isset($data['year']) ? $data['year'] : date('Y');
            
            // Check if the requested month is future month (not current or past)
            $currentMonth = date('m');
            $currentYear = date('Y');
            
            if ($year > $currentYear || ($year == $currentYear && $month > $currentMonth)) {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Can only view refreshment allowances for current and previous months"
                ), JSON_FORCE_OBJECT);
                return;
            }

            // Get all employees from tblEmployee
            $query = "SELECT 
                        e.employeeID,
    e.empID,
    e.employeeName,
                        e.isWashingAllowance,
                        e.isPhysicallyHandicapped,
                        e.isTemporary,
                        b.branchName
                    FROM tblEmployee e
                    LEFT JOIN tblmapEmp m ON e.employeeID = m.employeeID
                    LEFT JOIN tblBranch b ON m.branchID = b.branchID
                    WHERE m.organisationID = ? AND e.isActive = 1
                    ORDER BY e.employeeName";

                $stmt = mysqli_prepare($connect_var, $query);
                if (!$stmt) {
                    throw new Exception("Failed to prepare statement: " . mysqli_error($connect_var));
                }
                
            mysqli_stmt_bind_param($stmt, "i", $organisationID);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to execute statement: " . mysqli_error($connect_var));
                }

                $result = mysqli_stmt_get_result($stmt);
                if (!$result) {
                    throw new Exception("Failed to get result: " . mysqli_error($connect_var));
                }

                $data = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $employeeID = $row['employeeID'];
                
                // Calculate working days for the month
                $workingDaysQuery = "SELECT noOfWorkingDays, monthName 
                                   FROM tblworkingdays
                                   WHERE workingDayID = ? AND Year = ?";
                $workingStmt = mysqli_prepare($connect_var, $workingDaysQuery);
                mysqli_stmt_bind_param($workingStmt, "ii", $month, $year);
                mysqli_stmt_execute($workingStmt);
                $workingResult = mysqli_stmt_get_result($workingStmt);
                $workingDays = mysqli_fetch_assoc($workingResult);
                $totalWorkingDays = $workingDays ? $workingDays['noOfWorkingDays'] : 0;

                // Calculate approved leave days (simpler approach)
                $leaveQuery = "SELECT COUNT(*) as leaveDays 
                              FROM tblApplyLeave 
                              WHERE employeeID = ? 
                              AND status = 'Approved'
                              AND (isExtend = 0 OR isExtend IS NULL)
                              AND (
                                  (MONTH(fromDate) = ? AND YEAR(fromDate) = ?) OR
                                  (MONTH(toDate) = ? AND YEAR(toDate) = ?) OR
                                  (fromDate <= ? AND toDate >= ?)
                              )";
                $leaveStmt = mysqli_prepare($connect_var, $leaveQuery);
                $monthStartDate = sprintf('%04d-%02d-01', $year, $month);
                $monthEndDate = date('Y-m-t', strtotime($monthStartDate));
                mysqli_stmt_bind_param($leaveStmt, "siiiiss", $employeeID, $month, $year, $month, $year, $monthEndDate, $monthStartDate);
                mysqli_stmt_execute($leaveStmt);
                $leaveResult = mysqli_stmt_get_result($leaveStmt);
                $leaveDays = mysqli_fetch_assoc($leaveResult);
                $approvedLeaveDays = $leaveDays ? $leaveDays['leaveDays'] : 0;
                
                // Debug logging
                error_log("Employee $employeeID - Leave Days: $approvedLeaveDays");

                // Calculate training days (check-in at training branch - branch ID 56)
                $trainingQuery = "SELECT COUNT(DISTINCT attendanceDate) as trainingDays 
                                 FROM tblAttendance 
                                 WHERE employeeID = ? 
                                 AND MONTH(attendanceDate) = ? 
                                 AND YEAR(attendanceDate) = ? 
                                 AND checkInBranchID = 56 
                                 AND checkInTime IS NOT NULL";
                $trainingStmt = mysqli_prepare($connect_var, $trainingQuery);
                mysqli_stmt_bind_param($trainingStmt, "sii", $employeeID, $month, $year);
                mysqli_stmt_execute($trainingStmt);
                $trainingResult = mysqli_stmt_get_result($trainingStmt);
                $trainingDays = mysqli_fetch_assoc($trainingResult);
                $totalTrainingDays = $trainingDays ? $trainingDays['trainingDays'] : 0;

                // Calculate absent days (simpler approach - just count days with no check-in)
                $absentQuery = "SELECT COUNT(DISTINCT attendanceDate) as absentDays 
                               FROM tblAttendance 
                               WHERE employeeID = ? 
                               AND MONTH(attendanceDate) = ? 
                               AND YEAR(attendanceDate) = ? 
                               AND checkInTime IS NULL";
                $absentStmt = mysqli_prepare($connect_var, $absentQuery);
                mysqli_stmt_bind_param($absentStmt, "sii", $employeeID, $month, $year);
                mysqli_stmt_execute($absentStmt);
                $absentResult = mysqli_stmt_get_result($absentStmt);
                $absentDays = mysqli_fetch_assoc($absentResult);
                $totalAbsentDays = $absentDays ? $absentDays['absentDays'] : 0;

                // Calculate eligible days
                $eligibleDays = $totalWorkingDays - $approvedLeaveDays - $totalTrainingDays - $totalAbsentDays;
                $eligibleDays = max(0, $eligibleDays);
                
                // Calculate allowances
                $amountPerDay = 90.00;
                $refreshmentAmount = $eligibleDays * $amountPerDay;
                
                // Only apply refreshment allowance for permanent employees (isTemporary = 0)
                if ($row['isTemporary'] == 1) {
                    $refreshmentAmount = 0.00;
                }
                
                // Calculate washing allowance if eligible
                $washingAmount = 0.00;
                if ($row['isWashingAllowance'] == 1) {
                    $washingAmount = $totalWorkingDays * 25.00;
                }
                
                // Calculate physically handicapped allowance if eligible
                $physicallyHandicappedAmount = 0.00;
                if ($row['isPhysicallyHandicapped'] == 1) {
                    $physicallyHandicappedAmount = 2500.00;
                }
                
                // Calculate medical allowance (only in May and December)
                $medicalAmount = 0.00;
                if ($month == 5 || $month == 12) {
                    $medicalAmount = 6000.00;
                }
                
                // Calculate total amount
                $totalAmount = $refreshmentAmount + $washingAmount + $physicallyHandicappedAmount + $medicalAmount;

                // Check if record exists in tblRefreshment to get status
                $statusQuery = "SELECT Status FROM tblRefreshment 
                               WHERE EmployeeID = ? AND Month = ? AND Year = ?";
                $statusStmt = mysqli_prepare($connect_var, $statusQuery);
                mysqli_stmt_bind_param($statusStmt, "sii", $row['employeeID'], $month, $year);
                mysqli_stmt_execute($statusStmt);
                $statusResult = mysqli_stmt_get_result($statusStmt);
                $statusRow = mysqli_fetch_assoc($statusResult);
                $status = $statusRow ? $statusRow['Status'] : 'Pending';

                $data[] = array(
                    'employeeID' => (int)$row['employeeID'],
                    'empID' => $row['empID'],
                    'employeeName' => $row['employeeName'],
                    'branchName' => $row['branchName'],
                    'isWashingAllowance' => (int)$row['isWashingAllowance'],
                    'isPhysicallyHandicapped' => (int)$row['isPhysicallyHandicapped'],
                    'isTemporary' => (int)$row['isTemporary'],
                    'noOfWorkingDays' => (int)$totalWorkingDays,
                    'leaveDaysInMonth' => (string)$approvedLeaveDays,
                    'trainingDays' => (string)$totalTrainingDays,
                    'absentDays' => (string)$totalAbsentDays,
                    'eligibleDays' => (string)$eligibleDays,
                    'TotalRefreshmentAmount' => number_format((float)$refreshmentAmount, 2, '.', ''),
                    'WashingAllowanceAmount' => number_format((float)$washingAmount, 2, '.', ''),
                    'PhysicallyChallangedAllowance' => (int)$physicallyHandicappedAmount,
                    'MedicalAmount' => number_format((float)$medicalAmount, 2, '.', ''),
                    'TotalAllowances' => number_format((float)$totalAmount, 2, '.', ''),
                    'status' => $status
                );
                }
                
                if (count($data) > 0) {
                    echo json_encode([
                        "status" => "success",
                    "message" => "Refreshment allowances calculated successfully",
                        "record_count" => count($data),
                        "data" => $data
                    ]);
                } else {
                    echo json_encode([
                        "status" => "error",
                    "message" => "No employees found for this organization"
                    ], JSON_FORCE_OBJECT);
                }           
               
            } catch (Exception $e) {
            error_log("Error in getRefreshmentAllowancesByOrganisationID: " . $e->getMessage());
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

            $employeeID = $data['employeeID'];
            $month = $data['month'];
            $year = $data['year'];

            // Get employee details including allowance flags
            $employeeQuery = "SELECT employeeID, isPhysicallyHandicapped, isWashingAllowance, isTemporary 
                            FROM tblEmployee 
                            WHERE employeeID = ?";
            $stmt = mysqli_prepare($connect_var, $employeeQuery);
            if (!$stmt) {
                throw new Exception("Failed to prepare employee query: " . mysqli_error($connect_var));
            }
            mysqli_stmt_bind_param($stmt, "s", $employeeID);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $employee = mysqli_fetch_assoc($result);

            if (!$employee) {
                throw new Exception("Employee not found");
            }

            // Get total working days for the month
            $workingDaysQuery = "SELECT noOfWorkingDays, monthName 
                               FROM tblworkingdays
                               WHERE workingDayID = ? AND Year = ?";
            $stmt = mysqli_prepare($connect_var, $workingDaysQuery);
            if (!$stmt) {
                throw new Exception("Failed to prepare working days query: " . mysqli_error($connect_var));
            }
            mysqli_stmt_bind_param($stmt, "ii", $month, $year);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $workingDays = mysqli_fetch_assoc($result);

            if (!$workingDays) {
                throw new Exception("Working days not found for the specified month and year");
            }

            $totalWorkingDays = $workingDays['noOfWorkingDays'];
            $monthName = $workingDays['monthName'];

            // Get approved leave days (simpler approach)
            $leaveQuery = "SELECT COUNT(*) as leaveDays 
                          FROM tblApplyLeave 
                          WHERE employeeID = ? 
                          AND status = 'Approved'
                          AND (isExtend = 0 OR isExtend IS NULL)
                          AND (
                              (MONTH(fromDate) = ? AND YEAR(fromDate) = ?) OR
                              (MONTH(toDate) = ? AND YEAR(toDate) = ?) OR
                              (fromDate <= ? AND toDate >= ?)
                          )";
            
            $stmt = mysqli_prepare($connect_var, $leaveQuery);
            if (!$stmt) {
                throw new Exception("Failed to prepare leave query: " . mysqli_error($connect_var));
            }
            $monthStartDate = sprintf('%04d-%02d-01', $year, $month);
            $monthEndDate = date('Y-m-t', strtotime($monthStartDate));
            mysqli_stmt_bind_param($stmt, "siiiiss", $employeeID, $month, $year, $month, $year, $monthEndDate, $monthStartDate);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $leaveDays = mysqli_fetch_assoc($result);

            $approvedLeaveDays = $leaveDays['leaveDays'];

            // Get training days (check-in at training branch - branch ID 56)
            $trainingQuery = "SELECT COUNT(DISTINCT attendanceDate) as trainingDays 
                             FROM tblAttendance 
                             WHERE employeeID = ? 
                             AND MONTH(attendanceDate) = ? 
                             AND YEAR(attendanceDate) = ? 
                             AND checkInBranchID = 56 
                             AND checkInTime IS NOT NULL";
            
            $stmt = mysqli_prepare($connect_var, $trainingQuery);
            if (!$stmt) {
                throw new Exception("Failed to prepare training query: " . mysqli_error($connect_var));
            }
            mysqli_stmt_bind_param($stmt, "sii", $employeeID, $month, $year);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $trainingDays = mysqli_fetch_assoc($result);

            $totalTrainingDays = $trainingDays['trainingDays'];

            // Get absent days (simpler approach - just count days with no check-in)
            $absentQuery = "SELECT COUNT(DISTINCT attendanceDate) as absentDays 
                           FROM tblAttendance 
                           WHERE employeeID = ? 
                           AND MONTH(attendanceDate) = ? 
                           AND YEAR(attendanceDate) = ? 
                           AND checkInTime IS NULL";
            
            $stmt = mysqli_prepare($connect_var, $absentQuery);
            if (!$stmt) {
                throw new Exception("Failed to prepare absent query: " . mysqli_error($connect_var));
            }
            mysqli_stmt_bind_param($stmt, "sii", $employeeID, $month, $year);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $absentDays = mysqli_fetch_assoc($result);

            $totalAbsentDays = $absentDays['absentDays'];

            // Calculate eligible days
            $eligibleDays = $totalWorkingDays - $approvedLeaveDays - $totalTrainingDays - $totalAbsentDays;
            $eligibleDays = max(0, $eligibleDays);
            
            // Calculate individual allowances
            $amountPerDay = 90.00; // Fixed amount per day for refreshment
            $refreshmentAmount = $eligibleDays * $amountPerDay;
            
            // Only apply refreshment allowance for permanent employees (isTemporary = 0)
            if ($employee['isTemporary'] == 1) {
                $refreshmentAmount = 0.00;
            }
            
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
            if ($month == 5 || $month == 12) {
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
            mysqli_stmt_bind_param($stmt, "sii", $employeeID, $month, $year);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                // Update existing record
                $updateQuery = "UPDATE tblRefreshment 
                              SET TotalWorkingDays = ?,
                                  ApprovedLeaveDays = ?,
                                  TrainingDays = ?,
                                  AbsentDays = ?,
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
                mysqli_stmt_bind_param($stmt, "iiiiiddddddsis", 
                    $totalWorkingDays,
                    $approvedLeaveDays,
                    $totalTrainingDays,
                    $totalAbsentDays,
                    $eligibleDays,
                    $amountPerDay,
                    $refreshmentAmount,
                    $washingAmount,
                    $physicallyHandicappedAmount,
                    $medicalAmount,
                    $totalAmount,
                    $employeeID,
                    $month,
                    $year
                );
            } else {
                // Insert new record
                $insertQuery = "INSERT INTO tblRefreshment 
                              (EmployeeID, Month, Year, TotalWorkingDays, 
                               ApprovedLeaveDays, TrainingDays, AbsentDays, EligibleDays, AmountPerDay, 
                               RefreshmentAmount, WashingAmount, 
                               PhysicallyHandicappedAmount, MedicalAmount,
                               TotalAmount, Status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
                
                $stmt = mysqli_prepare($connect_var, $insertQuery);
                if (!$stmt) {
                    throw new Exception("Failed to prepare insert query: " . mysqli_error($connect_var));
                }
                mysqli_stmt_bind_param($stmt, "siiiiiiiiddddd", 
                    $employeeID,
                    $month,
                    $year,
                    $totalWorkingDays,
                    $approvedLeaveDays,
                    $totalTrainingDays,
                    $totalAbsentDays,
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
                        "trainingDays" => $totalTrainingDays,
                        "absentDays" => $totalAbsentDays,
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

    public function approveRefreshmentAllowance($data) {
        include('config.inc');
        header('Content-Type: application/json');
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    
        try {
            // Validate required fields
            if (!isset($data['employeeID']) || !isset($data['month']) || !isset($data['year'])) {
                throw new Exception("Missing required fields");
            }

            $employeeID = $data['employeeID'];
            $month = $data['month'];
            $year = $data['year'];

            // First check if record exists, if not create it
            $checkQuery = "SELECT RefreshmentID FROM tblRefreshment 
                          WHERE EmployeeID = ? AND Month = ? AND Year = ?";
            $checkStmt = mysqli_prepare($connect_var, $checkQuery);
            if (!$checkStmt) {
                throw new Exception("Failed to prepare check query: " . mysqli_error($connect_var));
            }
            mysqli_stmt_bind_param($checkStmt, "sii", $employeeID, $month, $year);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);
            
            if (mysqli_num_rows($checkResult) == 0) {
                // Record doesn't exist, calculate amounts and create it
                
                // Get employee details
                $employeeQuery = "SELECT isPhysicallyHandicapped, isWashingAllowance, isTemporary 
                                FROM tblEmployee 
                                WHERE employeeID = ?";
                $employeeStmt = mysqli_prepare($connect_var, $employeeQuery);
                mysqli_stmt_bind_param($employeeStmt, "s", $employeeID);
                mysqli_stmt_execute($employeeStmt);
                $employeeResult = mysqli_stmt_get_result($employeeStmt);
                $employee = mysqli_fetch_assoc($employeeResult);
                
                if (!$employee) {
                    throw new Exception("Employee not found");
                }
                
                // Get working days
                $workingDaysQuery = "SELECT noOfWorkingDays FROM tblworkingdays
                                   WHERE workingDayID = ? AND Year = ?";
                $workingStmt = mysqli_prepare($connect_var, $workingDaysQuery);
                mysqli_stmt_bind_param($workingStmt, "ii", $month, $year);
                mysqli_stmt_execute($workingStmt);
                $workingResult = mysqli_stmt_get_result($workingStmt);
                $workingDays = mysqli_fetch_assoc($workingResult);
                $totalWorkingDays = $workingDays ? $workingDays['noOfWorkingDays'] : 0;
                
                // Get approved leave days
                $leaveQuery = "SELECT COUNT(*) as leaveDays 
                              FROM tblApplyLeave 
                              WHERE employeeID = ? 
                              AND status = 'Approved'
                              AND (isExtend = 0 OR isExtend IS NULL)
                              AND (
                                  (MONTH(fromDate) = ? AND YEAR(fromDate) = ?) OR
                                  (MONTH(toDate) = ? AND YEAR(toDate) = ?) OR
                                  (fromDate <= ? AND toDate >= ?)
                              )";
                $leaveStmt = mysqli_prepare($connect_var, $leaveQuery);
                $monthStartDate = sprintf('%04d-%02d-01', $year, $month);
                $monthEndDate = date('Y-m-t', strtotime($monthStartDate));
                mysqli_stmt_bind_param($leaveStmt, "siiiiss", $employeeID, $month, $year, $month, $year, $monthEndDate, $monthStartDate);
                mysqli_stmt_execute($leaveStmt);
                $leaveResult = mysqli_stmt_get_result($leaveStmt);
                $leaveDays = mysqli_fetch_assoc($leaveResult);
                $approvedLeaveDays = $leaveDays ? $leaveDays['leaveDays'] : 0;
                
                // Get training days
                $trainingQuery = "SELECT COUNT(DISTINCT attendanceDate) as trainingDays 
                                 FROM tblAttendance 
                                 WHERE employeeID = ? 
                                 AND MONTH(attendanceDate) = ? 
                                 AND YEAR(attendanceDate) = ? 
                                 AND checkInBranchID = 56 
                                 AND checkInTime IS NOT NULL";
                $trainingStmt = mysqli_prepare($connect_var, $trainingQuery);
                mysqli_stmt_bind_param($trainingStmt, "sii", $employeeID, $month, $year);
                mysqli_stmt_execute($trainingStmt);
                $trainingResult = mysqli_stmt_get_result($trainingStmt);
                $trainingDays = mysqli_fetch_assoc($trainingResult);
                $totalTrainingDays = $trainingDays ? $trainingDays['trainingDays'] : 0;
                
                // Get absent days
                $absentQuery = "SELECT COUNT(DISTINCT attendanceDate) as absentDays 
                               FROM tblAttendance 
                               WHERE employeeID = ? 
                               AND MONTH(attendanceDate) = ? 
                               AND YEAR(attendanceDate) = ? 
                               AND checkInTime IS NULL";
                $absentStmt = mysqli_prepare($connect_var, $absentQuery);
                mysqli_stmt_bind_param($absentStmt, "sii", $employeeID, $month, $year);
                mysqli_stmt_execute($absentStmt);
                $absentResult = mysqli_stmt_get_result($absentStmt);
                $absentDays = mysqli_fetch_assoc($absentResult);
                $totalAbsentDays = $absentDays ? $absentDays['absentDays'] : 0;
                
                // Calculate eligible days
                $eligibleDays = $totalWorkingDays - $approvedLeaveDays - $totalTrainingDays - $totalAbsentDays;
                $eligibleDays = max(0, $eligibleDays);
                
                // Calculate amounts
                $amountPerDay = 90.00;
                $refreshmentAmount = $eligibleDays * $amountPerDay;
                
                // Only apply refreshment allowance for permanent employees
                if ($employee['isTemporary'] == 1) {
                    $refreshmentAmount = 0.00;
                }
                
                // Calculate washing allowance
                $washingAmount = 0.00;
                if ($employee['isWashingAllowance'] == 1) {
                    $washingAmount = $totalWorkingDays * 25.00;
                }
                
                // Calculate physically handicapped allowance
                $physicallyHandicappedAmount = 0.00;
                if ($employee['isPhysicallyHandicapped'] == 1) {
                    $physicallyHandicappedAmount = 2500.00;
                }
                
                // Calculate medical allowance
                $medicalAmount = 0.00;
                if ($month == 5 || $month == 12) {
                    $medicalAmount = 6000.00;
                }
                
                // Calculate total amount
                $totalAmount = $refreshmentAmount + $washingAmount + $physicallyHandicappedAmount + $medicalAmount;
                
                // Create record with calculated amounts
                $insertQuery = "INSERT INTO tblRefreshment 
                              (EmployeeID, Month, Year, TotalWorkingDays, ApprovedLeaveDays, TrainingDays, 
                               EligibleDays, AmountPerDay, RefreshmentAmount, WashingAmount, 
                               PhysicallyHandicappedAmount, MedicalAmount, TotalAmount, Status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Approved')";
                $insertStmt = mysqli_prepare($connect_var, $insertQuery);
                if (!$insertStmt) {
                    throw new Exception("Failed to prepare insert query: " . mysqli_error($connect_var));
                }
                mysqli_stmt_bind_param($insertStmt, "siiiiiiiddddd", 
                    $employeeID, $month, $year, $totalWorkingDays, $approvedLeaveDays, $totalTrainingDays,
                    $eligibleDays, $amountPerDay, $refreshmentAmount, $washingAmount,
                    $physicallyHandicappedAmount, $medicalAmount, $totalAmount
                );
                
                if (mysqli_stmt_execute($insertStmt)) {
                    echo json_encode(array(
                        "status" => "success",
                        "message_text" => "Refreshment allowance calculated and approved successfully"
                    ));
                } else {
                    throw new Exception("Failed to create and approve allowance: " . mysqli_error($connect_var));
                }
            } else {
                // Record exists, update it
                $updateQuery = "UPDATE tblRefreshment 
                              SET Status = 'Approved'
                              WHERE EmployeeID = ? AND Month = ? AND Year = ?";
                
                $stmt = mysqli_prepare($connect_var, $updateQuery);
                if (!$stmt) {
                    throw new Exception("Failed to prepare update query: " . mysqli_error($connect_var));
                }
                mysqli_stmt_bind_param($stmt, "sii", $employeeID, $month, $year);
                
                if (mysqli_stmt_execute($stmt)) {
                    if (mysqli_affected_rows($connect_var) > 0) {
                        echo json_encode(array(
                            "status" => "success",
                            "message_text" => "Refreshment allowance approved successfully"
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
            }

        } catch (Exception $e) {
            error_log("Error in approveRefreshmentAllowance: " . $e->getMessage());
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

    public function bulkApproveRefreshmentAllowances($data) {
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

            // Get all employees for the organization
            $employeeQuery = "SELECT 
                                e.employeeID,
                                e.isWashingAllowance,
                                e.isPhysicallyHandicapped,
                                e.isTemporary
                            FROM tblEmployee e
                            LEFT JOIN tblmapEmp m ON e.employeeID = m.employeeID
                            WHERE m.organisationID = ? AND e.isActive = 1";
            
            $employeeStmt = mysqli_prepare($connect_var, $employeeQuery);
            if (!$employeeStmt) {
                throw new Exception("Failed to prepare employee query: " . mysqli_error($connect_var));
            }
            mysqli_stmt_bind_param($employeeStmt, "i", $organisationID);
            mysqli_stmt_execute($employeeStmt);
            $employeeResult = mysqli_stmt_get_result($employeeStmt);
            
            $approvedCount = 0;
            $errors = [];
            
            while ($employee = mysqli_fetch_assoc($employeeResult)) {
                $employeeID = $employee['employeeID'];
                
                try {
                    // Check if record already exists
                    $checkQuery = "SELECT RefreshmentID FROM tblRefreshment 
                                  WHERE EmployeeID = ? AND Month = ? AND Year = ?";
                    $checkStmt = mysqli_prepare($connect_var, $checkQuery);
                    mysqli_stmt_bind_param($checkStmt, "sii", $employeeID, $month, $year);
                    mysqli_stmt_execute($checkStmt);
                    $checkResult = mysqli_stmt_get_result($checkStmt);
                    
                    if (mysqli_num_rows($checkResult) == 0) {
                        // Record doesn't exist, calculate and create it
                        
                        // Get working days
                        $workingDaysQuery = "SELECT noOfWorkingDays FROM tblworkingdays
                                           WHERE workingDayID = ? AND Year = ?";
                        $workingStmt = mysqli_prepare($connect_var, $workingDaysQuery);
                        mysqli_stmt_bind_param($workingStmt, "ii", $month, $year);
                        mysqli_stmt_execute($workingStmt);
                        $workingResult = mysqli_stmt_get_result($workingStmt);
                        $workingDays = mysqli_fetch_assoc($workingResult);
                        $totalWorkingDays = $workingDays ? $workingDays['noOfWorkingDays'] : 0;
                        
                        // Get approved leave days
                        $leaveQuery = "SELECT COUNT(*) as leaveDays 
                                      FROM tblApplyLeave 
                                      WHERE employeeID = ? 
                                      AND status = 'Approved'
                                      AND (isExtend = 0 OR isExtend IS NULL)
                                      AND (
                                          (MONTH(fromDate) = ? AND YEAR(fromDate) = ?) OR
                                          (MONTH(toDate) = ? AND YEAR(toDate) = ?) OR
                                          (fromDate <= ? AND toDate >= ?)
                                      )";
                        $leaveStmt = mysqli_prepare($connect_var, $leaveQuery);
                        $monthStartDate = sprintf('%04d-%02d-01', $year, $month);
                        $monthEndDate = date('Y-m-t', strtotime($monthStartDate));
                        mysqli_stmt_bind_param($leaveStmt, "siiiiss", $employeeID, $month, $year, $month, $year, $monthEndDate, $monthStartDate);
                        mysqli_stmt_execute($leaveStmt);
                        $leaveResult = mysqli_stmt_get_result($leaveStmt);
                        $leaveDays = mysqli_fetch_assoc($leaveResult);
                        $approvedLeaveDays = $leaveDays ? $leaveDays['leaveDays'] : 0;
                        
                        // Get training days
                        $trainingQuery = "SELECT COUNT(DISTINCT attendanceDate) as trainingDays 
                                         FROM tblAttendance 
                                         WHERE employeeID = ? 
                                         AND MONTH(attendanceDate) = ? 
                                         AND YEAR(attendanceDate) = ? 
                                         AND checkInBranchID = 56 
                                         AND checkInTime IS NOT NULL";
                        $trainingStmt = mysqli_prepare($connect_var, $trainingQuery);
                        mysqli_stmt_bind_param($trainingStmt, "sii", $employeeID, $month, $year);
                        mysqli_stmt_execute($trainingStmt);
                        $trainingResult = mysqli_stmt_get_result($trainingStmt);
                        $trainingDays = mysqli_fetch_assoc($trainingResult);
                        $totalTrainingDays = $trainingDays ? $trainingDays['trainingDays'] : 0;
                        
                        // Get absent days
                        $absentQuery = "SELECT COUNT(DISTINCT attendanceDate) as absentDays 
                                       FROM tblAttendance 
                                       WHERE employeeID = ? 
                                       AND MONTH(attendanceDate) = ? 
                                       AND YEAR(attendanceDate) = ? 
                                       AND checkInTime IS NULL";
                        $absentStmt = mysqli_prepare($connect_var, $absentQuery);
                        mysqli_stmt_bind_param($absentStmt, "sii", $employeeID, $month, $year);
                        mysqli_stmt_execute($absentStmt);
                        $absentResult = mysqli_stmt_get_result($absentStmt);
                        $absentDays = mysqli_fetch_assoc($absentResult);
                        $totalAbsentDays = $absentDays ? $absentDays['absentDays'] : 0;
                        
                        // Calculate eligible days
                        $eligibleDays = $totalWorkingDays - $approvedLeaveDays - $totalTrainingDays - $totalAbsentDays;
                        $eligibleDays = max(0, $eligibleDays);
                        
                        // Calculate amounts
                        $amountPerDay = 90.00;
                        $refreshmentAmount = $eligibleDays * $amountPerDay;
                        
                        // Only apply refreshment allowance for permanent employees
                        if ($employee['isTemporary'] == 1) {
                            $refreshmentAmount = 0.00;
                        }
                        
                        // Calculate washing allowance
                        $washingAmount = 0.00;
                        if ($employee['isWashingAllowance'] == 1) {
                            $washingAmount = $totalWorkingDays * 25.00;
                        }
                        
                        // Calculate physically handicapped allowance
                        $physicallyHandicappedAmount = 0.00;
                        if ($employee['isPhysicallyHandicapped'] == 1) {
                            $physicallyHandicappedAmount = 2500.00;
                        }
                        
                        // Calculate medical allowance
                        $medicalAmount = 0.00;
                        if ($month == 5 || $month == 12) {
                            $medicalAmount = 6000.00;
                        }
                        
                        // Calculate total amount
                        $totalAmount = $refreshmentAmount + $washingAmount + $physicallyHandicappedAmount + $medicalAmount;
                        
                        // Create record with calculated amounts
                        $insertQuery = "INSERT INTO tblRefreshment 
                                      (EmployeeID, Month, Year, TotalWorkingDays, ApprovedLeaveDays, TrainingDays, 
                                       EligibleDays, AmountPerDay, RefreshmentAmount, WashingAmount, 
                                       PhysicallyHandicappedAmount, MedicalAmount, TotalAmount, Status) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Approved')";
                        $insertStmt = mysqli_prepare($connect_var, $insertQuery);
                        if (!$insertStmt) {
                            throw new Exception("Failed to prepare insert query for employee $employeeID: " . mysqli_error($connect_var));
                        }
                        mysqli_stmt_bind_param($insertStmt, "siiiiiiiddddd", 
                            $employeeID, $month, $year, $totalWorkingDays, $approvedLeaveDays, $totalTrainingDays,
                            $eligibleDays, $amountPerDay, $refreshmentAmount, $washingAmount,
                            $physicallyHandicappedAmount, $medicalAmount, $totalAmount
                        );
                        
                        if (mysqli_stmt_execute($insertStmt)) {
                            $approvedCount++;
                        } else {
                            $errors[] = "Failed to create record for employee $employeeID: " . mysqli_error($connect_var);
                        }
                    } else {
                        // Record exists, update status to approved
                        $updateQuery = "UPDATE tblRefreshment 
                                      SET Status = 'Approved'
                                      WHERE EmployeeID = ? AND Month = ? AND Year = ?";
                        $updateStmt = mysqli_prepare($connect_var, $updateQuery);
                        mysqli_stmt_bind_param($updateStmt, "sii", $employeeID, $month, $year);
                        
                        if (mysqli_stmt_execute($updateStmt) && mysqli_affected_rows($connect_var) > 0) {
                            $approvedCount++;
                        }
                    }
                } catch (Exception $employeeError) {
                    $errors[] = "Error processing employee $employeeID: " . $employeeError->getMessage();
                }
            }
            
            $response = array(
                "status" => "success",
                "message_text" => "Bulk approval completed successfully",
                "approved_count" => $approvedCount
            );
            
            if (!empty($errors)) {
                $response["errors"] = $errors;
                $response["message_text"] = "Bulk approval completed with some errors";
            }
            
            echo json_encode($response);
    
        } catch (Exception $e) {
            error_log("Error in bulkApproveRefreshmentAllowances: " . $e->getMessage());
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

    public function rejectRefreshmentAllowance($data) {
        include('config.inc');
        header('Content-Type: application/json');
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        try {
            // Validate required fields
            if (!isset($data['employeeID']) || !isset($data['month']) || !isset($data['year'])) {
                throw new Exception("Missing required fields");
            }

            $employeeID = $data['employeeID'];
            $month = $data['month'];
            $year = $data['year'];

            // Check if record exists
            $checkQuery = "SELECT RefreshmentID FROM tblRefreshment 
                          WHERE EmployeeID = ? AND Month = ? AND Year = ?";
            $checkStmt = mysqli_prepare($connect_var, $checkQuery);
            if (!$checkStmt) {
                throw new Exception("Failed to prepare check query: " . mysqli_error($connect_var));
            }
            mysqli_stmt_bind_param($checkStmt, "sii", $employeeID, $month, $year);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);
            
            if (mysqli_num_rows($checkResult) == 0) {
                // Record doesn't exist, create it with rejected status
                $insertQuery = "INSERT INTO tblRefreshment 
                              (EmployeeID, Month, Year, Status) 
                              VALUES (?, ?, ?, 'Rejected')";
                $insertStmt = mysqli_prepare($connect_var, $insertQuery);
                if (!$insertStmt) {
                    throw new Exception("Failed to prepare insert query: " . mysqli_error($connect_var));
                }
                mysqli_stmt_bind_param($insertStmt, "sii", $employeeID, $month, $year);
                
                if (mysqli_stmt_execute($insertStmt)) {
                    echo json_encode(array(
                        "status" => "success",
                        "message_text" => "Refreshment allowance rejected successfully"
                    ));
                } else {
                    throw new Exception("Failed to reject allowance: " . mysqli_error($connect_var));
                }
            } else {
                // Record exists, update status to rejected
                $updateQuery = "UPDATE tblRefreshment 
                              SET Status = 'Rejected'
                              WHERE EmployeeID = ? AND Month = ? AND Year = ?";
                
                $stmt = mysqli_prepare($connect_var, $updateQuery);
                if (!$stmt) {
                    throw new Exception("Failed to prepare update query: " . mysqli_error($connect_var));
                }
                mysqli_stmt_bind_param($stmt, "sii", $employeeID, $month, $year);
                
                if (mysqli_stmt_execute($stmt)) {
                    if (mysqli_affected_rows($connect_var) > 0) {
                        echo json_encode(array(
                            "status" => "success",
                            "message_text" => "Refreshment allowance rejected successfully"
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
            }

        } catch (Exception $e) {
            error_log("Error in rejectRefreshmentAllowance: " . $e->getMessage());
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