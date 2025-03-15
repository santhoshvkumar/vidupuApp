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

    private function getPublicUrl($filePath) {
        // Convert server file path to public URL
        // Assuming your uploads directory is accessible via web
        $baseUrl = 'http://your-domain.com/vidupuApi/uploads/certificates/';
        $fileName = basename($filePath);
        return $baseUrl . $fileName;
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

            $queryCheckOverlap = "SELECT COUNT(*) as overlap_count, GROUP_CONCAT(DISTINCT typeOfLeave) as leave_types 
                                FROM tblApplyLeave 
                                WHERE employeeID = ? 
                                AND status NOT IN ('Cancelled', 'Rejected')
                                AND (
                                    (fromDate BETWEEN DATE_SUB(?, INTERVAL 1 DAY) AND DATE_ADD(?, INTERVAL 1 DAY))
                                    OR (toDate BETWEEN DATE_SUB(?, INTERVAL 1 DAY) AND DATE_ADD(?, INTERVAL 1 DAY))
                                    OR (? BETWEEN DATE_SUB(fromDate, INTERVAL 1 DAY) AND DATE_ADD(toDate, INTERVAL 1 DAY))
                                )";
            
            $stmt = mysqli_prepare($connect_var, $queryCheckOverlap);
            mysqli_stmt_bind_param($stmt, "ssssss", 
                $this->empID, 
                $this->fromDate, $this->toDate,
                $this->fromDate, $this->toDate,
                $this->fromDate);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $overlapData = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
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
                }
            }

            $queryApplyLeave = "INSERT INTO tblApplyLeave (employeeID, fromDate, toDate, leaveDuration, typeOfLeave, reason, createdOn, status) 
                               VALUES (?, ?, ?, ?, ?, ?, CURRENT_DATE(), 'Yet To Be Approved')";
            
            $stmt = mysqli_prepare($connect_var, $queryApplyLeave);
            mysqli_stmt_bind_param($stmt, "sssdss", 
                $this->empID, 
                $this->fromDate, 
                $this->toDate, 
                $this->leaveDuration, 
                $this->leaveType, 
                $this->leaveReason);
            
            if(mysqli_stmt_execute($stmt)) {
                error_log("Leave application successful");
                echo json_encode(array(
                    "status" => "success",
                    "message_text" => "Leave applied successfully"
                ), JSON_FORCE_OBJECT);
            } else {
                error_log("Leave application failed: " . mysqli_error($connect_var));
                throw new Exception("Leave application failed: " . mysqli_error($connect_var));
            }
            
            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);
        } 
        catch(Exception $e) {
            error_log("Exception in apply leave: " . $e->getMessage());
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

            $applyLeaveID = $data['applyLeaveID'];
            $certificateType = $data['certificateType'];

            // Define upload directory with correct Windows path
            $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'certificates' . DIRECTORY_SEPARATOR;
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    throw new Exception("Failed to create upload directory");
                }
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
            $fileName = $applyLeaveID . '_' . $certificateType . '_' . time() . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;

            // Store relative path in database (more portable)
            $dbPath = 'uploads/certificates/' . $fileName;

            // Move and validate file
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception('Failed to save file. Path: ' . $targetPath);
            }

            // Update database
            $columnName = ($certificateType === 'Medical') ? 'MedicalCertificatePath' : 'FitnessCertificatePath';
            $dateColumn = ($certificateType === 'Medical') ? 'MedicalCertificateUploadDate' : 'FitnessCertificateUploadDate';

            $queryUpdateCertificate = "UPDATE tblApplyLeave 
                                     SET $columnName = ?,
                                         $dateColumn = CURRENT_TIMESTAMP
                                     WHERE applyLeaveID = ?";

            $stmt = mysqli_prepare($connect_var, $queryUpdateCertificate);
            mysqli_stmt_bind_param($stmt, "si", $dbPath, $applyLeaveID);
            
            if(mysqli_stmt_execute($stmt)) {
                // Get the server URL dynamically
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'];
                $baseUrl = $protocol . $host . '/Vidupu/vidupuApi/';
                
                echo json_encode(array(
                    "status" => "success",
                    "message_text" => "Certificate uploaded successfully",
                    "file_path" => $dbPath,
                    "public_url" => $baseUrl . $dbPath
                ));
            } else {
                throw new Exception('Failed to update database: ' . mysqli_error($connect_var));
            }
            
            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);

        } catch(Exception $e) {
            error_log("Exception in uploadLeaveCertificate: " . $e->getMessage());
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ));
        }
    }

    private function getPublicUrl($dbPath) {
        // Get the server URL dynamically
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = $protocol . $host . '/Vidupu/vidupuApi/';
        return $baseUrl . $dbPath;
    }
}

function applyLeave(array $data) {
    $leaveObject = new ApplyLeaveMaster();
    if($leaveObject->loadApplyLeaveDetails($data)) {
        $leaveObject->applyForLeave();
    } else {
        echo json_encode(array(
            "status" => "error",
            "message_text" => "Invalid Input Parameters"
        ), JSON_FORCE_OBJECT);
    }
}

function getLeaveBalance(array $data) {
    $leaveObject = new ApplyLeaveMaster();
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
    $leaveObject = new ApplyLeaveMaster();
    if($leaveObject->loadEmployeeDetails($data)) {
        $leaveObject->getLeaveHistoryInfo();
    } else {
        echo json_encode(array(
            "status" => "error",
            "message_text" => "Invalid Input Parameters"
        ), JSON_FORCE_OBJECT);
    }
}

function uploadLeaveCertificate(array $data) {
    $leaveObject = new ApplyLeaveMaster();
    $leaveObject->uploadLeaveCertificate($data);
}

?>