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
           //check leaves applied 
            $leaveTypeColumn = $this->leaveType === 'Medical Leave' ? 'MedicalLeave' :
                                ($this->leaveType === 'Privilege Leave' ? 'PrivilegeLeave' : 
                                ($this->leaveType === 'Privilege Leave (Medical grounds)' ? 'PrivilegeLeave' : 
                                ($this->leaveType === 'Casual Leave' ? 'CasualLeave' : 
                                ($this->leaveType === 'Special Casual Leave' ? 'SpecialCasualLeave' : ''))));
            if($leaveTypeColumn === 'MedicalLeave'||$leaveTypeColumn === 'PrivilegeLeave'||$leaveTypeColumn === 'Privilege Leave (Medical grounds)'){
                $queryPendingLeaves = "SELECT SUM(leaveDuration) as totalPending,
                                SUM(NoOfDaysExtend) as totalExtend
                                 FROM tblApplyLeave 
                                 WHERE employeeID = '$this->empID' 
                                 AND typeOfLeave = '$this->leaveType'
                                 AND status IN ('Yet To Be Approved', 'Approved', 'ExtendedApplied')";
            }
            else{
                $queryPendingLeaves = "SELECT SUM(leaveDuration) as totalPending,
                                0 as totalExtend
                                FROM tblApplyLeave 
                                 WHERE employeeID = '$this->empID' 
                                 AND typeOfLeave = '$this->leaveType'
                                 AND status IN ('Yet To Be Approved')";
            }
           
            $pendingResult = mysqli_query($connect_var, $queryPendingLeaves);
            if (!$pendingResult) {
            
                throw new Exception("Database query failed");
            }
            
            if ($row = mysqli_fetch_assoc($pendingResult)) {
                $totalPending = floatval($row['totalPending'] ?: 0);
                $totalExtend = floatval($row['totalExtend'] ?: 0);
                $totalPending = $totalPending + $totalExtend;
            } else {
                $totalPending = 0;
                $totalExtend = 0;
            }
            // Get current leave balance
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

            $queryLeaveBalance = "SELECT $leaveTypeColumn as balance 
                                FROM tblLeaveBalance 
                                WHERE employeeID = '$this->empID'";
            $balanceResult = mysqli_query($connect_var, $queryLeaveBalance);
            if (!$balanceResult) {
                throw new Exception("Database query failed");
            }
            
            if ($row = mysqli_fetch_assoc($balanceResult)) {
                $currentBalance = floatval($row['balance'] ?: 0);
            } else {
                $currentBalance = 0;
            }
            // Check if new application + pending applications exceed balance
            if (($totalPending + $this->leaveDuration) > $currentBalance) {
                $errorMessage = "Cannot apply for leave. Total pending applications ($totalPending days) plus new application ($this->leaveDuration days) exceeds available balance ($currentBalance days)";
               
                echo json_encode(array(
                    "status" => "warning",
                    "message_text" => $errorMessage
                ), JSON_FORCE_OBJECT);
                mysqli_close($connect_var);
                return;
            }
            // check for overlapping dates
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
                
                // Check for split leaves that would exceed available balance
                $querySplitLeaves = "SELECT fromDate, toDate, leaveDuration FROM tblApplyLeave 
                    WHERE employeeID = '$this->empID' 
                    AND typeOfLeave = '$this->leaveType' 
                    AND status != 'Cancelled' 
                    AND status != 'Rejected' 
                    AND fromDate >= '$yearStart' 
                    AND toDate <= '$yearEnd' 
                    ORDER BY fromDate";
                
                $splitResult = mysqli_query($connect_var, $querySplitLeaves);
                $totalStretch = 0;
                $lastEndDate = null;
                
                while ($row = mysqli_fetch_assoc($splitResult)) {
                    $leaveStart = new DateTime($row['fromDate']);
                    $leaveEnd = new DateTime($row['toDate']);
                    
                    if ($lastEndDate !== null) {
                        $lastEnd = new DateTime($lastEndDate);
                        $interval = $lastEnd->diff($leaveStart);
                        $daysBetween = $interval->days;
                        
                        if ($daysBetween <= 2) {
                            $totalStretch += $row['leaveDuration'];
                        } else {
                            $totalStretch = $row['leaveDuration'];
                        }
                    } else {
                        $totalStretch = $row['leaveDuration'];
                    }
                    
                    $lastEndDate = $row['toDate'];
                }
                
                $totalStretch += $this->leaveDuration;
                
                // Special check for Casual Leave (10 days limit)
                if ($this->leaveType === 'Casual Leave' && $totalStretch > 10) {
                    echo json_encode(array(
                        "status" => "warning",
                        "message_text" => "Cannot exceed 10 days of Casual Leave. Your total stretch would be $totalStretch days."
                    ), JSON_FORCE_OBJECT);
                    mysqli_close($connect_var);
                    return;
                }
                
                // Check if total stretch exceeds available balance for all leave types
                if ($totalStretch > $availableBalance) {
                    echo json_encode(array(
                        "status" => "warning",
                        "message_text" => "Cannot exceed available $this->leaveType balance. You have $availableBalance days available, but your total stretch would be $totalStretch days."
                    ), JSON_FORCE_OBJECT);
                    mysqli_close($connect_var);
                    return;
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
                $existingLeaveTypes = explode(',', $overlapData['leave_types']);
                if (!in_array($this->leaveType, $existingLeaveTypes)) {
                    echo json_encode(array(
                        "status" => "warning",
                        "message_text" => "Cannot apply different leave types on consecutive days. Existing leave type(s): " . $overlapData['leave_types']
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

