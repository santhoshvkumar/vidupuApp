<?php

class ApplyLeaveMaster {
    public $empID;
    public $employeeName;
    public $leaveBalance;
    public $companyID;
    public $fromDate;
    public $toDate;
    public $leaveType;
    public $leaveDuration;
    public $leaveReason;
    
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
        $this->leaveReason = $data['reason'];
        return true;
    }

    public function getLeaveBalanceInfo() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $queryLeaveBalance = "SELECT tblE.empID, tblL.leaveBalance 
                                FROM tblEmployee tblE 
                                LEFT JOIN tblLeaveBalance tblL ON tblE.empID = tblL.empID 
                                WHERE tblE.empID = ? 
                                AND tblE.companyID = ?";
                                 
            $stmt = mysqli_prepare($connect_var, $queryLeaveBalance);
            mysqli_stmt_bind_param($stmt, "ss", $this->empID, $this->companyID);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $resultArr = array();
            $count = 0;
            
            while($rs = mysqli_fetch_assoc($result)) {
                $resultArr = $rs;
                if(isset($rs['empID'])) {
                    $count++;
                }
            }
            
            mysqli_stmt_close($stmt);
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
                    "message_text" => "No leave balance found for employee ID: " . htmlspecialchars($this->empID)
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
            $queryLeaveHistory = "SELECT applyLeaveID, fromDate, toDate, leaveDuration, 
                                typeOfLeave, reason, status, 
                                MedicalCertificatePath, MedicalCertificateUploadDate,
                                FitnessCertificatePath, FitnessCertificateUploadDate 
                                FROM tblApplyLeave 
                                WHERE employeeID = ? 
                                AND status != 'Cancelled' 
                                ORDER BY applyLeaveID DESC";
                                
            $stmt = mysqli_prepare($connect_var, $queryLeaveHistory);
            mysqli_stmt_bind_param($stmt, "s", $this->empID);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $resultArr = array();
            $count = 0;
            
            while($rs = mysqli_fetch_assoc($result)) {
                // Convert file paths to URLs
                if (!empty($rs['MedicalCertificatePath'])) {
                    $rs['MedicalCertificateUrl'] = $this->getPublicUrl($rs['MedicalCertificatePath']);
                }
                if (!empty($rs['FitnessCertificatePath'])) {
                    $rs['FitnessCertificateUrl'] = $this->getPublicUrl($rs['FitnessCertificatePath']);
                }
                $resultArr[] = $rs;
                $count++;
            }
            
            mysqli_stmt_close($stmt);
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
                    "message_text" => "No leave history found for employee ID: " . htmlspecialchars($this->empID)
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
            error_log("Attempting to apply leave - Type: " . $this->leaveType . 
                     ", EmpID: " . $this->empID . 
                     ", From: " . $this->fromDate . 
                     ", To: " . $this->toDate);

            // Check for consecutive PLs if this is a Privilege Leave
            if ($this->leaveType === 'Privilege Leave') {
                $queryLastTwoLeaves = "SELECT typeOfLeave 
                                    FROM tblApplyLeave 
                                    WHERE employeeID = ? 
                                    AND status NOT IN ('Cancelled', 'Rejected')
                                    ORDER BY applyLeaveID DESC
                                    LIMIT 2";
                
                $stmt = mysqli_prepare($connect_var, $queryLastTwoLeaves);
                mysqli_stmt_bind_param($stmt, "s", $this->empID);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                $lastTwoLeaves = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $lastTwoLeaves[] = $row['typeOfLeave'];
                }
                mysqli_stmt_close($stmt);

                // Show warning only if both last leaves were PL
                if (count($lastTwoLeaves) == 2 && 
                    $lastTwoLeaves[0] === 'Privilege Leave' && 
                    $lastTwoLeaves[1] === 'Privilege Leave') {
                    echo json_encode(array(
                        "status" => "warning",
                        "message_text" => "You are applying for Privilege Leave after two consecutive PLs. Do you want to continue?",
                        "require_confirmation" => true
                    ), JSON_FORCE_OBJECT);
                    mysqli_close($connect_var);
                    return;
                }
            }

            if ($this->leaveType === 'Casual Leave') {
                $currentYear = date('Y');
                $yearStart = "$currentYear-01-01";
                $yearMid = "$currentYear-07-01";
                $yearEnd = "$currentYear-12-31";
                
                $queryCasualLeaves = "SELECT 
                    SUM(CASE 
                        WHEN fromDate >= ? AND toDate <= ? THEN leaveDuration
                        ELSE 0 
                    END) as first_half_leaves,
                    SUM(CASE 
                        WHEN fromDate >= ? AND toDate <= ? THEN leaveDuration
                        ELSE 0 
                    END) as second_half_leaves
                    FROM tblApplyLeave 
                    WHERE employeeID = ? 
                    AND typeOfLeave = 'Casual Leave'
                    AND status != 'Cancelled'
                    AND fromDate >= ?";
                    
                $stmt = mysqli_prepare($connect_var, $queryCasualLeaves);
                mysqli_stmt_bind_param($stmt, "ssssss", 
                    $yearStart, $yearMid, 
                    $yearMid, $yearEnd,
                    $this->empID, $yearStart);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $casualData = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);
                
                $firstHalfLeaves = floatval($casualData['first_half_leaves']);
                $secondHalfLeaves = floatval($casualData['second_half_leaves']);
                
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
                    $availableSecondHalf = 10 + (10 - $firstHalfLeaves);
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

            // Checking for overlapping leave
            $queryCheckOverlap = "SELECT COUNT(*) as overlap_count, GROUP_CONCAT(DISTINCT typeOfLeave) as leave_types 
                                FROM tblApplyLeave 
                                WHERE employeeID = ? 
                                AND ((fromDate BETWEEN ? AND ?) 
                                OR (toDate BETWEEN ? AND ?)) 
                                AND status != 'Cancelled'";
            $stmt = mysqli_prepare($connect_var, $queryCheckOverlap);
            mysqli_stmt_bind_param($stmt, "sssss", $this->empID, $this->fromDate, $this->toDate, $this->fromDate, $this->toDate);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $overlap = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            if ($overlap['overlap_count'] > 0) {
                echo json_encode(array(
                    "status" => "warning",
                    "message_text" => "Leave overlaps with previously applied leaves. Types: " . $overlap['leave_types']
                ), JSON_FORCE_OBJECT);
                mysqli_close($connect_var);
                return;
            }

            // Insert Leave Application
            $queryInsertLeave = "INSERT INTO tblApplyLeave (employeeID, fromDate, toDate, leaveDuration, typeOfLeave, reason, status)
                                VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
            $stmt = mysqli_prepare($connect_var, $queryInsertLeave);
            mysqli_stmt_bind_param($stmt, "ssssss", $this->empID, $this->fromDate, $this->toDate, $this->leaveDuration, $this->leaveType, $this->leaveReason);
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(array(
                    "status" => "success",
                    "message_text" => "Leave applied successfully"
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message_text" => "Error applying for leave: " . mysqli_error($connect_var)
                ));
            }
            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);
        } catch (Exception $e) {
            error_log("Exception in applyForLeave: " . $e->getMessage());
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ), JSON_FORCE_OBJECT);
        }
    }

    public function uploadLeaveCertificate(array $data) {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            if (!isset($data['applyLeaveID']) || !isset($data['certificateType'])) {
                throw new Exception("Missing required fields");
            }

            if (!isset($_FILES['file'])) {
                throw new Exception('No file uploaded');
            }

            $file = $_FILES['file'];

            // Validate file
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('File upload error: ' . $file['error']);
            }

            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            // Validate file type
            $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'];
            if (!in_array($fileExtension, $allowedTypes)) {
                throw new Exception('Invalid file type. Allowed types: ' . implode(', ', $allowedTypes));
            }

            // Generate unique filename
            $fileName = $data['applyLeaveID'] . '_' . $data['certificateType'] . '_' . time() . '.' . $fileExtension;
            $targetPath = 'uploads/certificates/' . $fileName;

            // Move and validate file
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception('Failed to save file. Path: ' . $targetPath);
            }

            // Update database with the file path
            $columnName = ($data['certificateType'] === 'Medical') ? 'MedicalCertificatePath' : 'FitnessCertificatePath';
            $dateColumn = ($data['certificateType'] === 'Medical') ? 'MedicalCertificateUploadDate' : 'FitnessCertificateUploadDate';

            $queryUpdateCertificate = "UPDATE tblApplyLeave 
                                     SET $columnName = ?, 
                                         $dateColumn = CURRENT_TIMESTAMP
                                     WHERE applyLeaveID = ?";
            $stmt = mysqli_prepare($connect_var, $queryUpdateCertificate);
            mysqli_stmt_bind_param($stmt, "si", $targetPath, $data['applyLeaveID']);
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(array(
                    "status" => "success",
                    "message_text" => "Certificate uploaded successfully",
                    "file_path" => $targetPath
                ));
            } else {
                throw new Exception('Failed to update database: ' . mysqli_error($connect_var));
            }
            
            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);
        } catch (Exception $e) {
            error_log("Exception in uploadLeaveCertificate: " . $e->getMessage());
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ), JSON_FORCE_OBJECT);
        }
    }
    
    
}
?>
