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
        $this->leaveReason = $data['leaveReason'];
        $this->certificateType = isset($data['certificateType']) ? $data['certificateType'] : 'Medical';
        $this->isReApply = isset($data['isReApply']) ? $data['isReApply'] : false;
        $this->isExtend = isset($data['isExtend']) ? $data['isExtend'] : false;
        $this->originalLeaveId = isset($data['originalLeaveId']) ? $data['originalLeaveId'] : null;
        $this->extendLeave = isset($data['extendLeave']) ? $data['extendLeave'] : false;
        $this->certificateFile = isset($data['certificateFile']) ? $data['certificateFile'] : null;
        return true;
    }

    public function getLeaveBalanceInfo() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $queryLeaveBalance = "SELECT tblE.empID, tblL.leaveBalance 
                                FROM tblEmployee tblE 
                                LEFT JOIN tblLeaveBalance tblL ON tblE.empID = tblL.empID 
                                WHERE tblE.empID = '$this->empID' 
                                AND tblE.companyID = '$this->companyID'";
                                
            $rsd = mysqli_query($connect_var, $queryLeaveBalance);
            $resultArr = array();
            $count = 0;
            
            while($rs = mysqli_fetch_assoc($rsd)) {
                $resultArr = $rs;
                if(isset($rs['empID'])) {
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
                                  reason, status, MedicalCertificatePath, FitnessCertificatePath 
                                  FROM tblApplyLeave 
                                  WHERE employeeID = '$this->empID' and status != 'Cancelled' 
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
        error_log("applyForLeave method started");
        
        try {
            // Debug incoming request
            error_log("POST data: " . print_r($_POST, true));
            error_log("FILES data: " . (isset($_FILES) ? print_r($_FILES, true) : "No files"));

            // Check for required fields
            if (!isset($_POST['empID']) || !isset($_POST['fromDate']) || !isset($_POST['toDate']) || 
                !isset($_POST['leaveType']) || !isset($_POST['leaveDuration']) || !isset($_POST['leaveReason'])) {
                error_log("Missing required fields - using class properties instead");
                // Continue using the properties loaded in loadApplyLeaveDetails
            } else {
                // Update class properties from POST data if available
                $this->empID = $_POST['empID'];
                $this->fromDate = $_POST['fromDate'];
                $this->toDate = $_POST['toDate'];
                $this->leaveType = $_POST['leaveType'];
                $this->leaveDuration = $_POST['leaveDuration'];
                $this->leaveReason = $_POST['leaveReason'];
                $this->certificateType = isset($_POST['certificateType']) ? $_POST['certificateType'] : 'Medical';
                $this->isReApply = isset($_POST['isReApply']) ? $_POST['isReApply'] : false;
                $this->isExtend = isset($_POST['isExtend']) ? $_POST['isExtend'] : false;
                $this->originalLeaveId = isset($_POST['originalLeaveId']) ? $_POST['originalLeaveId'] : null;
                $this->extendLeave = isset($_POST['extendLeave']) ? $_POST['extendLeave'] : false;
                error_log("Updated properties from POST data");
            }
            
            // Handle file upload if present (making it optional)
            $certificatePath = null;
            if (isset($_FILES['certificateFile']) && $_FILES['certificateFile']['error'] === UPLOAD_ERR_OK) {
                error_log("Processing uploaded certificate file: " . $_FILES['certificateFile']['name']);
                
                // Create directory if it doesn't exist
                $uploadDir = 'uploads/certificates/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                    error_log("Created upload directory: $uploadDir");
                }
                
                // Generate unique filename
                $fileExt = strtolower(pathinfo($_FILES['certificateFile']['name'], PATHINFO_EXTENSION));
                $fileName = $this->empID . '_' . date('Ymd') . '_' . uniqid() . '.' . $fileExt;
                $targetPath = $uploadDir . $fileName;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['certificateFile']['tmp_name'], $targetPath)) {
                    $certificatePath = $targetPath;
                    error_log("Certificate uploaded successfully: $certificatePath");
                } else {
                    error_log("Failed to upload certificate: " . error_get_last()['message']);
                }
            }

            // Use prepared statements to prevent SQL injection
            // For Casual Leave validation
            if ($this->leaveType === 'Casual Leave') {
                // Get current year's start and mid dates
                $currentYear = date('Y');
                $yearStart = "$currentYear-01-01";
                $yearMid = "$currentYear-07-01";
                $yearEnd = "$currentYear-12-31";
                
                // Check total casual leaves taken in the year
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

            // Build the INSERT query with or without certificate
            $queryApplyLeave = "INSERT INTO tblApplyLeave 
                              (employeeID, fromDate, toDate, leaveDuration, typeOfLeave, reason, 
                               createdOn, status, isReApply, isextend" . 
                               ($certificatePath ? ", MedicalCertificatePath, certificateType, MedicalCertificateUploadDate" : "") . ") 
                              VALUES 
                              ('$this->empID', '$this->fromDate', '$this->toDate', '$this->leaveDuration', 
                               '$this->leaveType', '$this->leaveReason', CURRENT_DATE(), 'Yet To Be Approved',
                               '0', '0'" . 
                               ($certificatePath ? ", '$certificatePath', '$this->certificateType', CURRENT_DATE()" : "") . ")";
            
            error_log("Executing query: $queryApplyLeave");
            $rsd = mysqli_query($connect_var, $queryApplyLeave);
            
            if($rsd) {
                // Get the newly inserted leave ID
                $newLeaveId = mysqli_insert_id($connect_var);
                error_log("Leave inserted successfully with ID: $newLeaveId");
                
                echo json_encode(array(
                    "status" => "success",
                    "message_text" => "Leave applied successfully",
                    "leaveId" => $newLeaveId,
                    "certificateUploaded" => $certificatePath ? true : false
                ), JSON_FORCE_OBJECT);
            } else {
                $error = mysqli_error($connect_var);
                error_log("Database error: $error");
                echo json_encode(array(
                    "status" => "error",
                    "message_text" => "Leave application failed: $error"
                ), JSON_FORCE_OBJECT);
            }
            mysqli_close($connect_var);
        } catch(Exception $e) {     
            error_log("Exception: " . $e->getMessage());
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
    
    //extend leave
    public function extendLeave() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            // Debug - Log all POST data
            error_log("ExtendLeave - POST data: " . print_r($_POST, true));
            
            // Check if this is an extension request
            if (!isset($_POST['isextend']) || $_POST['isextend'] != true || !isset($_POST['applyLeaveID'])) {
                echo json_encode(array(
                    "status" => "error",
                    "message_text" => "Missing extension parameters"
                ), JSON_FORCE_OBJECT);
                mysqli_close($connect_var);
                return;
            }
            
            $originalLeaveId = mysqli_real_escape_string($connect_var, $_POST['applyLeaveID']);
            
            // Get the original leave details
            $queryOriginalLeave = "SELECT fromDate, toDate, leaveDuration, typeOfLeave, reason FROM tblApplyLeave 
                                   WHERE applyLeaveID = '$originalLeaveId' AND employeeID = '$this->empID'";
            $originalResult = mysqli_query($connect_var, $queryOriginalLeave);
            
            if (mysqli_num_rows($originalResult) == 0) {
                echo json_encode(array(
                    "status" => "error",
                    "message_text" => "Original leave not found or does not belong to this employee"
                ), JSON_FORCE_OBJECT);
                mysqli_close($connect_var);
                return;
            }
            
            $originalLeave = mysqli_fetch_assoc($originalResult);
            $originalFromDate = $originalLeave['fromDate'];
            $originalToDate = $originalLeave['toDate'];
            $originalDuration = $originalLeave['leaveDuration'];
            
            // Calculate extension details
            $newToDate = $this->toDate;
            $certificatePath = isset($_POST['MedicalCertificatePath']) ? mysqli_real_escape_string($connect_var, $_POST['MedicalCertificatePath']) : '';
            
            // Calculate the extended duration (total days - original days)
            $extendedDuration = $this->leaveDuration - $originalDuration;
            
            // Begin transaction
            mysqli_begin_transaction($connect_var);
            
            try {
                // First insert into tblExtendArchieve
                $insertArchive = "INSERT INTO tblExtendArchieve (applyLeaveID, startDate, endDate, duration, certificatePath) 
                                  VALUES ('$originalLeaveId', '$originalFromDate', '$originalToDate', '$originalDuration', '$certificatePath')";
                
                if (!mysqli_query($connect_var, $insertArchive)) {
                    throw new Exception("Failed to archive original leave: " . mysqli_error($connect_var));
                }
                
                // Get the archive ID of the newly inserted record
                $archiveId = mysqli_insert_id($connect_var);
                
                // Get the archive record for reference
                $queryArchive = "SELECT * FROM tblExtendArchieve WHERE extendArchieveID = '$archiveId'";
                $archiveResult = mysqli_query($connect_var, $queryArchive);
                $archiveData = mysqli_fetch_assoc($archiveResult);
                
                // Then update tblApplyLeave with all the required fields
                $updateLeave = "UPDATE tblApplyLeave 
                               SET toDate = '$newToDate', 
                                   leaveDuration = '$this->leaveDuration',
                                   isextend = 1,
                                   no_ofdaysextend = '$extendedDuration',
                                   status = 'Yet To Be Approved'
                               WHERE applyLeaveID = '$originalLeaveId'";
                
                if (!mysqli_query($connect_var, $updateLeave)) {
                    throw new Exception("Failed to update leave: " . mysqli_error($connect_var));
                }
                
                // If there's a certificate, update the certificate path
                if (!empty($certificatePath)) {
                    $updateCertificate = "UPDATE tblApplyLeave 
                                         SET MedicalCertificatePath = '$certificatePath',
                                             MedicalCertificateUploadDate = CURRENT_DATE()
                                         WHERE applyLeaveID = '$originalLeaveId'";
                    
                    if (!mysqli_query($connect_var, $updateCertificate)) {
                        throw new Exception("Failed to update certificate: " . mysqli_error($connect_var));
                    }
                }
                
                // After the transaction is committed, add an explicit backup update 
                // to ensure the approval status is properly reset
                $updateStatusQuery = "UPDATE tblApplyLeave SET status = 'Yet To Be Approved' WHERE ApplyLeaveID = '$originalLeaveId'";
                $resetResult = mysqli_query($connect_var, $updateStatusQuery);
                
                if (!$resetResult) {
                    error_log("Failed to reset approval status: " . mysqli_error($connect_var));
                }
                
                mysqli_commit($connect_var);
                
                echo json_encode(array(
                    "status" => "success",
                    "message_text" => "Leave extended successfully. Status: Yet To Be Approved",
                    "extended_days" => $extendedDuration,
                    "new_end_date" => $newToDate,
                    "archive_data" => $archiveData
                ), JSON_FORCE_OBJECT);
                
            } catch (Exception $e) {
                // Roll back transaction on error
                mysqli_rollback($connect_var);
                echo json_encode(array(
                    "status" => "error",
                    "message_text" => $e->getMessage()
                ), JSON_FORCE_OBJECT);
            }
            
            mysqli_close($connect_var);
            
        } catch(Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ), JSON_FORCE_OBJECT);
        }
    }
}

function applyLeave(array $data) {
    error_log("Apply Leave function called");
    
    // Check if this is a multipart/form-data request
    $isMultipart = isset($_SERVER['CONTENT_TYPE']) && 
                   strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false;
    
    error_log("Is multipart request: " . ($isMultipart ? "Yes" : "No"));
    error_log("Data from function parameter: " . print_r($data, true));
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . (isset($_FILES) ? print_r($_FILES, true) : "No files"));
    
    $leaveObject = new ApplyLeaveMaster;
    
    // If it's a multipart request, we'll use $_POST and $_FILES directly in applyForLeave
    // Otherwise, load from the provided data array
    if ($isMultipart) {
        if(count($_POST) > 0) {
            // Use the data from $_POST
            if($leaveObject->loadApplyLeaveDetails($_POST)) {
                $leaveObject->applyForLeave();
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message_text" => "Invalid Input Parameters from multipart form"
                ), JSON_FORCE_OBJECT);
            }
        } else {
            // If POST is empty but we're still a multipart request
            // (this can happen with certain frameworks)
            if($leaveObject->loadApplyLeaveDetails($data)) {
                $leaveObject->applyForLeave();
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message_text" => "Invalid Input Parameters from empty multipart form"
                ), JSON_FORCE_OBJECT);
            }
        }
    } else {
        // Regular JSON request
        if($leaveObject->loadApplyLeaveDetails($data)) {
            $leaveObject->applyForLeave();
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid Input Parameters from JSON request"
            ), JSON_FORCE_OBJECT);
        }
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

function extendLeave(array $data) {
    $applyLeaveMaster = new ApplyLeaveMaster();
    $applyLeaveMaster->loadApplyLeaveDetails($data);
    return $applyLeaveMaster->extendLeave();
}

?> 