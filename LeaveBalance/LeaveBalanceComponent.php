<?php

class ApplyLeaveMaster {
    public $empID;
    public $employeeName;
    public $leaveBalance;
    public $companyID;
    public $leaveId;
    public $certificateType;
    public $applyLeaveID;
    public $fromDate;
    public $toDate;
    public $leaveType;
    public $isextend;
    public $leaveDuration;
    public $leaveReason;    
    public $MedicalCertificatePath;
    public $noOfDaysExtend;
    public $reasonForExtend;
    
    public function loadEmployeeDetails(array $data) {
        $this->empID = $data['empID'];
        return true;
    }

    public function loadApplyLeaveDetails(array $data) {
        $this->empID = $data['empID'];
        $this->fromDate = $data['fromDate'];
        $this->toDate = $data['toDate'];
        $this->leaveType = $data['leaveType'];
        $this->leaveDuration = $data['leaveDuration'];
        $this->leaveReason = $data['leaveReason'];
        if (isset($data['MedicalCertificatePath'])) {
            $this->MedicalCertificatePath = $data['MedicalCertificatePath'];
        } 
        return true;
    }

    public function loadExtendLeaveDetails(array $data) {
        $this->applyLeaveID = $data['applyLeaveID'];
        $this->toDate = $data['toDate'];
        $this->noOfDaysExtend = $data['NoOfDaysExtend'];
        $this->reasonForExtend = $data['reasonForExtend'];
        $this->MedicalCertificatePath = $data['MedicalCertificatePath'];
        return true;
    }

    public function getLeaveBalanceInfo() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $queryLeaveBalance = "SELECT EmployeeID, CasualLeave, SpecialCasualLeave, CompensatoryOff, SpecialLeaveBloodDonation, LeaveOnPrivateAffairs, MedicalLeave, PrivilegeLeave FROM `tblLeaveBalance` WHERE EmployeeID ='$this->empID'";
                             
            $rsd = mysqli_query($connect_var, $queryLeaveBalance);
            $resultArr = array();
            $count = 0;
            
            while($rs = mysqli_fetch_assoc($rsd)) {
                $resultArr = $rs;
                $resultArr['TotalLeave'] = $rs['CasualLeave'] + $rs['MedicalLeave'] + $rs['PrivilegeLeave'] + $rs['SpecialCasualLeave'] + $rs['CompensatoryOff'] + $rs['SpecialLeaveBloodDonation'] + $rs['LeaveOnPrivateAffairs'];
                if(isset($rs['EmployeeID'])) {
                    $count++;
                }
            }
            
            mysqli_close($connect_var);

            if($count > 0) {
                echo json_encode(array(
                    "status" => "success",
                    "result" => $resultArr,
                    "record_count" => $count
                ));
            } else {
                echo json_encode(array(
                    "status" => "failure",
                    "record_count" => $count,
                    "message_text" => "No leave balance found for employee ID: $this->empID"
                ), JSON_FORCE_OBJECT);
            }
        } catch(PDOException $e) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ), JSON_FORCE_OBJECT);
        }
    }
    public function getLeaveHistoryInfo() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $queryLeaveHistory = "SELECT applyLeaveID, fromDate, toDate, leaveDuration, typeOfLeave, 
                                  reason, status, RejectReason, MedicalCertificatePath, FitnessCertificatePath, NoOfDaysExtend, reasonForExtend 
                                  FROM tblApplyLeave 
                                  WHERE employeeID = '$this->empID'
                                  ORDER by applyLeaveID DESC";
            $rsd = mysqli_query($connect_var, $queryLeaveHistory);
            $resultArr = array();
            $count = 0;
            while($rs = mysqli_fetch_assoc($rsd)) {
                $resultArr[] = $rs;
                $count++;
            }
            mysqli_close($connect_var);

            if($count > 0) {
                echo json_encode(array(
                    "status" => "success",
                    "result" => $resultArr,
                    "record_count" => $count
                ));
            } else {
                echo json_encode(array(
                    "status" => "failure",
                    "record_count" => $count,
                    "message_text" => "No leave history found for employee ID: $this->empID"
                ), JSON_FORCE_OBJECT);
            }
        } catch(PDOException $e) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ), JSON_FORCE_OBJECT);
        }
    }
    public function applyForLeave() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            // Map leave types to their balance columns
            $leaveTypeBalanceMap = array(
                'Medical Leave' => 'MedicalLeave',
                'Privilege Leave' => 'PrivilegeLeave',
                'Privilege Leave (Medical grounds)' => 'PrivilegeLeave',
                'Casual Leave' => 'CasualLeave',
                'Special Casual Leave' => 'SpecialCasualLeave',
                'Compensatory Off' => 'CompensatoryOff',
                'Special Leave Blood Donation' => 'SpecialLeaveBloodDonation',
                'Leave On Private Affairs' => 'LeaveOnPrivateAffairs'
            );

            $leaveTypeColumn = isset($leaveTypeBalanceMap[$this->leaveType]) ? $leaveTypeBalanceMap[$this->leaveType] : null;
            
            if (!$leaveTypeColumn) {
                echo json_encode(array(
                    "status" => "error",
                    "message_text" => "Invalid leave type: " . $this->leaveType
                ), JSON_FORCE_OBJECT);
                mysqli_close($connect_var);
                return;
            }
            
            // Check if this is a Privilege Leave type
            $isPLType = ($this->leaveType === 'Privilege Leave' || $this->leaveType === 'Privilege Leave (Medical grounds)');
            
            // First, get current balance from database
            $queryLeaveBalance = "SELECT $leaveTypeColumn as balance 
                                FROM tblLeaveBalance 
                                WHERE employeeID = '$this->empID'";
            $balanceResult = mysqli_query($connect_var, $queryLeaveBalance);
            if (!$balanceResult) {
                error_log("Balance query failed: " . mysqli_error($connect_var));
                throw new Exception("Database query failed");
            }
            
            if ($row = mysqli_fetch_assoc($balanceResult)) {
                $currentBalance = floatval($row['balance'] ?: 0);
                error_log("Current balance: " . $currentBalance);
            } else {
                $currentBalance = 0;
                error_log("No balance found");
            }

            // Now check used leaves - handle PL types specially
            if ($isPLType) {
                // For PL types (regular and medical grounds), we need to sum both together
                $queryPendingAndUsed = "SELECT
                    SUM(CASE WHEN status IN ('Yet To Be Approved', 'ExtendedApplied') THEN leaveDuration ELSE 0 END) as pendingDays,
                    SUM(CASE WHEN status = 'Approved' THEN leaveDuration ELSE 0 END) as approvedDays,
                    SUM(CASE WHEN status IN ('Yet To Be Approved', 'Approved', 'ExtendedApplied') THEN NoOfDaysExtend ELSE 0 END) as extendedDays
                FROM tblApplyLeave
                WHERE employeeID = '$this->empID'
                AND (typeOfLeave = 'Privilege Leave' OR typeOfLeave = 'Privilege Leave (Medical grounds)')";
            } else {
                // For other leave types, just check the specific type
                $queryPendingAndUsed = "SELECT
                    SUM(CASE WHEN status IN ('Yet To Be Approved', 'ExtendedApplied') THEN leaveDuration ELSE 0 END) as pendingDays,
                    SUM(CASE WHEN status = 'Approved' THEN leaveDuration ELSE 0 END) as approvedDays,
                    SUM(CASE WHEN status IN ('Yet To Be Approved', 'Approved', 'ExtendedApplied') THEN NoOfDaysExtend ELSE 0 END) as extendedDays
                FROM tblApplyLeave
                WHERE employeeID = '$this->empID'
                AND typeOfLeave = '$this->leaveType'";
            }
            
            $pendingResult = mysqli_query($connect_var, $queryPendingAndUsed);
            if (!$pendingResult) {
                error_log("Used leaves query failed: " . mysqli_error($connect_var));
                throw new Exception("Database query failed");
            }
            
            $pendingDays = 0;
            $approvedDays = 0;
            $extendedDays = 0;
            
            if ($row = mysqli_fetch_assoc($pendingResult)) {
                $pendingDays = floatval($row['pendingDays'] ?: 0);
                $approvedDays = floatval($row['approvedDays'] ?: 0);
                $extendedDays = floatval($row['extendedDays'] ?: 0);
            }
            
            $totalUsedAndPending = $pendingDays + $approvedDays + $extendedDays;
            
            // Check if new application would exceed balance
            $totalRequested = $totalUsedAndPending + $this->leaveDuration;
            error_log("Total requested (used+pending+new): " . $totalRequested . 
                      " (used+pending: " . $totalUsedAndPending . 
                      ", new: " . $this->leaveDuration . ")");
            
            if ($totalRequested > $currentBalance) {
                $errorMessage = "Cannot apply for leave. You have already applied for " . 
                                "$totalUsedAndPending days. Adding $this->leaveDuration more days " . 
                                "would exceed your available balance of $currentBalance days.";
                
                if ($isPLType) {
                    $errorMessage .= " Insufficient Privilege Leave Balance.";
                }
                
                error_log("Validation failed: " . $errorMessage);
                
                echo json_encode(array(
                    "status" => "warning",
                    "message_text" => $errorMessage
                ), JSON_FORCE_OBJECT);
                mysqli_close($connect_var);
                return;
            }
            
            // Check for overlapping dates
            $queryCheckOverlap = "SELECT COUNT(*) as overlap_count
                                FROM tblApplyLeave 
                                WHERE employeeID = '$this->empID' 
                                AND typeOfLeave = '$this->leaveType'
                                AND status IN ('Yet To Be Approved', 'Approved', 'ExtendedApplied', 'ReApplied')
                                AND (
                                    (fromDate BETWEEN '$this->fromDate' AND '$this->toDate')
                                    OR (toDate BETWEEN '$this->fromDate' AND '$this->toDate')
                                    OR ('$this->fromDate' BETWEEN fromDate AND toDate)
                                )";
            
            $overlapResult = mysqli_query($connect_var, $queryCheckOverlap);
            if (!$overlapResult) {
                error_log("Error in overlap check query: " . mysqli_error($connect_var));
                throw new Exception("Database query failed");
            }
            
            $overlapData = mysqli_fetch_assoc($overlapResult);
            
            if ($overlapData['overlap_count'] > 0) {
                $errorMessage = "Cannot apply for leave. The selected dates overlap with existing leave applications";
                echo json_encode(array(
                    "status" => "warning",
                    "message_text" => $errorMessage
                ), JSON_FORCE_OBJECT);
                mysqli_close($connect_var);
                return;
            }

            // Debug query to see all leaves
            $debugQuery = "SELECT applyLeaveID, fromDate, toDate, leaveDuration, typeOfLeave, status
                         FROM tblApplyLeave 
                         WHERE employeeID = '$this->empID' 
                         AND typeOfLeave = '$this->leaveType'
                         AND status IN ('Yet To Be Approved', 'Approved', 'ExtendedApplied', 'ReApplied')
                         ORDER BY fromDate";
            
            $debugResult = mysqli_query($connect_var, $debugQuery);
            while ($row = mysqli_fetch_assoc($debugResult)) {
            }
            

            // For Casual Leave validation
            if ($this->leaveType === 'Casual Leave') {
                // Get current year's start and mid dates
                $currentYear = date('Y');
                $yearStart = "$currentYear-01-01";
                $yearMid = "$currentYear-07-01";
                $yearEnd = "$currentYear-12-31";
                
                // Get available leave balance for all types
                $queryBalance = "SELECT CasualLeave, MedicalLeave, PrivilegeLeave, SpecialCasualLeave, CompensatoryOff, SpecialLeaveBloodDonation, LeaveOnPrivateAffairs 
                                FROM tblLeaveBalance 
                                WHERE EmployeeID = '$this->empID'";
                $balanceResult = mysqli_query($connect_var, $queryBalance);
                $balanceData = mysqli_fetch_assoc($balanceResult);
                
                // Map leave types to their balance columns
                $leaveTypeBalanceMap = array(
                    'Casual Leave' => 'CasualLeave',
                    'Medical Leave' => 'MedicalLeave',
                    'Privilege Leave' => 'PrivilegeLeave',
                    'Special Casual Leave' => 'SpecialCasualLeave',
                    'Compensatory Off' => 'CompensatoryOff',
                    'Special Leave Blood Donation' => 'SpecialLeaveBloodDonation',
                    'Leave On Private Affairs' => 'LeaveOnPrivateAffairs'
                );
                
                $availableBalance = floatval($balanceData[$leaveTypeBalanceMap[$this->leaveType]]);
                
                // Get all casual leave applications (active, not cancelled/rejected)
                $queryCasualLeaves = "SELECT fromDate, toDate, leaveDuration 
                    FROM tblApplyLeave 
                    WHERE employeeID = '$this->empID' 
                    AND typeOfLeave = 'Casual Leave' 
                    AND status NOT IN ('Cancelled', 'Rejected')
                    AND fromDate >= '$yearStart' 
                    AND toDate <= '$yearEnd' 
                    ORDER BY fromDate";
                
                $casualResult = mysqli_query($connect_var, $queryCasualLeaves);
                
                // Collect all leave days including the new application
                $leaveDays = array();
                
                // Add the new application dates to our collection
                $newFromDate = new DateTime($this->fromDate);
                $newToDate = new DateTime($this->toDate);
                $newToDate->setTime(23, 59, 59); // Set to end of day
                
                // Add each day of the new application to the collection
                $currentDate = clone $newFromDate;
                while ($currentDate <= $newToDate) {
                    $dateString = $currentDate->format('Y-m-d');
                    $leaveDays[$dateString] = true;
                    $currentDate->modify('+1 day');
                }
                
                // Add all existing leave application days to the collection
                while ($row = mysqli_fetch_assoc($casualResult)) {
                    $fromDate = new DateTime($row['fromDate']);
                    $toDate = new DateTime($row['toDate']);
                    $toDate->setTime(23, 59, 59); // Set to end of day
                    
                    $currentDate = clone $fromDate;
                    while ($currentDate <= $toDate) {
                        $dateString = $currentDate->format('Y-m-d');
                        $leaveDays[$dateString] = true;
                        $currentDate->modify('+1 day');
                    }
                }
                
                // Find the earliest and latest leave dates
                if (count($leaveDays) > 0) {
                    $leaveDayKeys = array_keys($leaveDays);
                    sort($leaveDayKeys);
                    
                    $earliestLeaveDay = new DateTime(reset($leaveDayKeys));
                    $latestLeaveDay = new DateTime(end($leaveDayKeys));
                    
                    // Now check for continuous leave periods (including holidays)
                    $continuousLeavePeriods = array();
                    $currentPeriodStart = clone $earliestLeaveDay;
                    $currentPeriodEnd = clone $earliestLeaveDay;
                    $previousDay = clone $earliestLeaveDay;
                    
                    foreach ($leaveDayKeys as $index => $dateString) {
                        if ($index === 0) continue; // Skip the first day as it's already handled
                        
                        $currentDay = new DateTime($dateString);
                        $dayDiff = $previousDay->diff($currentDay)->days;
                        
                        // If gap between days is 3 or more, it's a new period
                        if ($dayDiff > 3) {
                            // Save the completed period
                            $continuousLeavePeriods[] = array(
                                'start' => clone $currentPeriodStart,
                                'end' => clone $currentPeriodEnd,
                                'days' => $currentPeriodEnd->diff($currentPeriodStart)->days + 1
                            );
                            
                            // Start a new period
                            $currentPeriodStart = clone $currentDay;
                        }
                        
                        $currentPeriodEnd = clone $currentDay;
                        $previousDay = clone $currentDay;
                    }
                    
                    // Add the last period
                    $continuousLeavePeriods[] = array(
                        'start' => clone $currentPeriodStart,
                        'end' => clone $currentPeriodEnd,
                        'days' => $currentPeriodEnd->diff($currentPeriodStart)->days + 1
                    );
                    
                    // Check for any period exceeding 10 days
                    foreach ($continuousLeavePeriods as $period) {
                        if ($period['days'] > 10) {
                            echo json_encode(array(
                                "status" => "warning",
                                "message_text" => "Cannot apply for this Casual Leave. The total calendar days (including holidays) would exceed the 10-day limit. Your continuous leave period would be " . $period['days'] . " days from " . $period['start']->format('Y-m-d') . " to " . $period['end']->format('Y-m-d') . "."
                            ), JSON_FORCE_OBJECT);
                            mysqli_close($connect_var);
                            return;
                        }
                    }
                    
                    // Calculate total calendar days between earliest and latest leave (inclusive)
                    $totalCalendarDays = $latestLeaveDay->diff($earliestLeaveDay)->days + 1;
                    
                    // Create array of all dates between earliest and latest
                    $allDatesInRange = array();
                    $currentDate = clone $earliestLeaveDay;
                    while ($currentDate <= $latestLeaveDay) {
                        $dateString = $currentDate->format('Y-m-d');
                        $allDatesInRange[] = $dateString;
                        $currentDate->modify('+1 day');
                    }
                    
                    // Find consecutive periods by checking for gaps less than 3 days
                    $consecutivePeriods = array();
                    $currentPeriod = array();
                    $previousDate = null;
                    
                    foreach ($leaveDayKeys as $dateString) {
                        $currentDate = new DateTime($dateString);
                        
                        if ($previousDate === null) {
                            $currentPeriod[] = $dateString;
                        } else {
                            $previousDateTime = new DateTime($previousDate);
                            $daysBetween = $previousDateTime->diff($currentDate)->days;
                            
                            // If the gap is 3 days or less, consider it part of the same period
                            if ($daysBetween <= 3) {
                                // Fill in the gap with dates
                                $gapDate = clone $previousDateTime;
                                $gapDate->modify('+1 day');
                                while ($gapDate < $currentDate) {
                                    $currentPeriod[] = $gapDate->format('Y-m-d');
                                    $gapDate->modify('+1 day');
                                }
                                $currentPeriod[] = $dateString;
                            } else {
                                // This is a new period
                                if (count($currentPeriod) > 0) {
                                    $consecutivePeriods[] = $currentPeriod;
                                }
                                $currentPeriod = array($dateString);
                            }
                        }
                        
                        $previousDate = $dateString;
                    }
                    
                    // Add the last period
                    if (count($currentPeriod) > 0) {
                        $consecutivePeriods[] = $currentPeriod;
                    }
                    
                    // Check each consecutive period for exceeding 10 days
                    foreach ($consecutivePeriods as $period) {
                        $periodLength = count($period);
                        if ($periodLength > 10) {
                            echo json_encode(array(
                                "status" => "warning",
                                "message_text" => "Cannot apply for this Casual Leave. Your continuous leave period (including holidays) would be $periodLength days, which exceeds the 10-day limit."
                            ), JSON_FORCE_OBJECT);
                            mysqli_close($connect_var);
                            return;
                        }
                    }
                }
                
                // Check total casual leaves taken in the year (only for Casual Leave)
                if ($this->leaveType === 'Casual Leave') {
                    $queryCasualLeaves = "SELECT 
                        SUM(CASE 
                            WHEN fromDate >= '$yearStart' AND toDate <= '$yearMid' THEN leaveDuration
                            ELSE 0 
                        END) as first_half_leaves,
                        SUM(CASE 
                            WHEN fromDate >= '$yearMid' AND toDate <= '$yearEnd' THEN leaveDuration
                            ELSE 0 
                        END) as second_half_leaves
                        FROM tblApplyLeave 
                        WHERE employeeID = '$this->empID' 
                        AND typeOfLeave = 'Casual Leave'
                        AND status != 'Cancelled'
                        AND fromDate >= '$yearStart'";
                    $casualResult = mysqli_query($connect_var, $queryCasualLeaves);
                    $casualData = mysqli_fetch_assoc($casualResult);
                    
                    $firstHalfLeaves = floatval($casualData['first_half_leaves']);
                    $secondHalfLeaves = floatval($casualData['second_half_leaves']);
                    
                    // Check if the leave spans across half years
                    $isFirstHalf = strtotime($this->fromDate) < strtotime($yearMid);
                    $isSecondHalf = strtotime($this->toDate) >= strtotime($yearMid);
                    
                    if ($isFirstHalf && $isSecondHalf) {
                        echo json_encode(array(
                            "status" => "warning",
                            "message_text" => "Casual Leave cannot span across half years"
                        ), JSON_FORCE_OBJECT);
                        mysqli_close($connect_var);
                        return;
                    }
                     // Validate against half-year limits
                if ($isFirstHalf) {
                    if (($firstHalfLeaves + $this->leaveDuration) > 10) {
                        echo json_encode(array(
                            "status" => "warning",
                            "message_text" => "Cannot exceed 10 days of Casual Leave in first half of the year"
                        ), JSON_FORCE_OBJECT);
                        mysqli_close($connect_var);
                        return;
                    }
                } else {
                    $availableSecondHalf = 10 + (10 - $firstHalfLeaves); // Unused first half leaves added to second half
                    if (($secondHalfLeaves + $this->leaveDuration) > $availableSecondHalf) {
                        echo json_encode(array(
                            "status" => "warning",
                            "message_text" => "Exceeds available Casual Leave balance for second half of the year"
                        ), JSON_FORCE_OBJECT);
                            mysqli_close($connect_var);
                            return;
                        }
                    }
                }
            }

            // Check for existing leave applications in the given period, including adjacent days
            $queryCheckOverlap = "SELECT COUNT(*) as overlap_count, GROUP_CONCAT(DISTINCT typeOfLeave) as leave_types 
                                FROM tblApplyLeave 
                                WHERE employeeID = '$this->empID' 
                                AND status != 'Cancelled' AND status != 'Rejected'
                                AND (
                                    (fromDate BETWEEN DATE_SUB('$this->fromDate', INTERVAL 1 DAY) AND DATE_ADD('$this->toDate', INTERVAL 1 DAY))
                                    OR (toDate BETWEEN DATE_SUB('$this->fromDate', INTERVAL 1 DAY) AND DATE_ADD('$this->toDate', INTERVAL 1 DAY))
                                    OR ('$this->fromDate' BETWEEN DATE_SUB(fromDate, INTERVAL 1 DAY) AND DATE_ADD(toDate, INTERVAL 1 DAY))
                                )";
            $overlapResult = mysqli_query($connect_var, $queryCheckOverlap);
            $overlapData = mysqli_fetch_assoc($overlapResult);
            
            if ($overlapData['overlap_count'] > 0) {
                $existingLeaveTypes = !empty($overlapData['leave_types']) ? explode(',', $overlapData['leave_types']) : [];
                if (!in_array($this->leaveType, $existingLeaveTypes)) {
                    echo json_encode(array(
                        "status" => "warning",
                        "message_text" => "Cannot apply different leave types on consecutive days. Existing leave type(s): " . ($overlapData['leave_types'] ?? 'None')
                    ), JSON_FORCE_OBJECT);
                    mysqli_close($connect_var);
                    return;
                } else {
                    // Check if this is actually an overlapping leave or just consecutive
                    $queryExactOverlap = "SELECT COUNT(*) as exact_overlap 
                                        FROM tblApplyLeave 
                                        WHERE employeeID = '$this->empID' 
                                        AND status != 'Cancelled' AND status != 'Rejected'
                                        AND (
                                            (fromDate <= '$this->toDate' AND toDate >= '$this->fromDate')
                                        )";
                    $exactOverlapResult = mysqli_query($connect_var, $queryExactOverlap);
                    $exactOverlapData = mysqli_fetch_assoc($exactOverlapResult);
                    
                    if ($exactOverlapData['exact_overlap'] > 0) {
                        echo json_encode(array(
                            "status" => "warning",
                            "message_text" => "Leave application already exists for the selected date range"
                        ), JSON_FORCE_OBJECT);
                        mysqli_close($connect_var);
                        return;
                    }
                    // If no exact overlap, allow the consecutive leave of the same type
                }
            }
            if ($this->MedicalCertificatePath !== null && $this->MedicalCertificatePath !== 'null') {
                $queryApplyLeave = "INSERT INTO tblApplyLeave (
                    employeeID, 
                    fromDate, 
                    toDate, 
                    leaveDuration, 
                    typeOfLeave, 
                    reason, 
                    createdOn, 
                    status,
                    MedicalCertificatePath,
                    MedicalCertificateUploadDate
                ) VALUES (?, ?, ?, ?, ?, ?, CURRENT_DATE(), 'Yet To Be Approved', ?, CURRENT_DATE())";
                
                $stmt = mysqli_prepare($connect_var, $queryApplyLeave);
                mysqli_stmt_bind_param($stmt, "sssssss",
                    $this->empID,
                    $this->fromDate,
                    $this->toDate,
                    $this->leaveDuration,
                    $this->leaveType,
                    $this->leaveReason,
                    $this->MedicalCertificatePath
                );
            } else {
                $queryApplyLeave = "INSERT INTO tblApplyLeave (
                    employeeID, 
                    fromDate, 
                    toDate, 
                    leaveDuration, 
                    typeOfLeave, 
                    reason, 
                    createdOn, 
                    status
                ) VALUES (?, ?, ?, ?, ?, ?, CURRENT_DATE(), 'Yet To Be Approved')";
                
                $stmt = mysqli_prepare($connect_var, $queryApplyLeave);
                mysqli_stmt_bind_param($stmt, "ssssss",
                    $this->empID,
                    $this->fromDate,
                    $this->toDate,
                    $this->leaveDuration,
                    $this->leaveType,
                    $this->leaveReason
                );
            }

            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(array(
                    "status" => "success",
                    "message" => "Leave application submitted successfully",
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error submitting leave application"
                ));
            }

            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);
        } catch(PDOException $e) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ), JSON_FORCE_OBJECT);
        }
    }
    public function extendLeaveInfo() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $updateExtendLeave = "UPDATE tblApplyLeave SET toDate = '$this->toDate', reasonForExtend = '$this->reasonForExtend', isExtend=1, NoOfDaysExtend = '$this->noOfDaysExtend', status = 'ExtendedApplied',  MedicalCertificatePath = '$this->MedicalCertificatePath', MedicalCertificateUploadDate=CURRENT_DATE() WHERE applyLeaveID = '$this->applyLeaveID'";
            $result = mysqli_query($connect_var, $updateExtendLeave);
            if ($result) {
                echo json_encode(array(
                    "status" => "success",
                    "message" => "Leave extended successfully"
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error extending leave"
                ));
            }
        } catch(PDOException $e) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ), JSON_FORCE_OBJECT);
        }
    }

    

    public function getCertificatePathInfo() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            // Get parameters
            $leaveId = $this->leaveId;
            $type = $this->certificateType ? $this->certificateType : 'Medical';
            
            if (!$leaveId) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Missing leaveId parameter'
                ]);
                return;
            }
            
            // Determine which column to query based on certificate type
            $column = ($type === 'Medical') ? 'MedicalCertificatePath' : 'FitnessCertificatePath';
            
            // Query to get certificate path
            $queryGetCertPath = "SELECT $column FROM tblApplyLeave WHERE applyLeaveID = '$leaveId'";
            $result = mysqli_query($connect_var, $queryGetCertPath);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $path = $row[$column];
                
                if ($path && $path !== 'null' && $path !== '') {
                    echo json_encode([
                        'status' => 'success',
                        'path' => $path
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No certificate found for this leave'
                    ]);
                }
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Leave request not found'
                ]);
            }
            
            mysqli_close($connect_var);
        } catch(Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function loadCertificateParams(array $data) {
        $this->leaveId = isset($data['leaveId']) ? $data['leaveId'] : null;
        $this->certificateType = isset($data['type']) ? $data['type'] : 'Medical';
        return true;
    }
}

function applyLeave(array $data) {
    $leaveObject = new ApplyLeaveMaster;
    if($leaveObject->loadApplyLeaveDetails($data)) {
        $leaveObject->applyForLeave();
    } else {
        echo json_encode(array(
            "status" => "error",
            "message_text" => "Invalid Input Parameters",
            "Data"=> $data
        ), JSON_FORCE_OBJECT);
    }
}

function getLeaveBalance(array $data) {
    $leaveObject = new ApplyLeaveMaster;
    if($leaveObject->loadEmployeeDetails($data)) {
        $leaveObject->getLeaveBalanceInfo();
    } else {
        echo json_encode(array(
            "status" => "error",
            "message_text" => "Invalid Input Parameters"
        ), JSON_FORCE_OBJECT);
    }
}

function getLeaveHistory(array $data) {
    $leaveObject = new ApplyLeaveMaster;
    if($leaveObject->loadEmployeeDetails($data)) {
        $leaveObject->getLeaveHistoryInfo();
    } else {
        echo json_encode(array(
            "status" => "error",
            "message_text" => "Invalid Input Parameters"
        ), JSON_FORCE_OBJECT);
    }
}

function extendLeave(array $data) {
    $leaveObject = new ApplyLeaveMaster;
    if($leaveObject->loadExtendLeaveDetails($data)) {
        $leaveObject->extendLeaveInfo();
    } else {
        echo json_encode(array(
            "status" => "error",
            "message_text" => "Invalid Input Parameters",
            "Data"=> $data
        ), JSON_FORCE_OBJECT);
    }
}

function getCertificatePath(array $data) {
    $leaveObject = new ApplyLeaveMaster;
    if($leaveObject->loadCertificateParams($data)) {
        $leaveObject->getCertificatePathInfo();
    } else {
        echo json_encode(array(
            "status" => "error",
            "message" => "Invalid Input Parameters"
        ), JSON_FORCE_OBJECT);
    }
}

?> 

