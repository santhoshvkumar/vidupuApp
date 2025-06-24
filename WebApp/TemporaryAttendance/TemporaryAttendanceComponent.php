<?php
class TemporaryAttendanceComponent {
    public $isTempAttendance;
    public $employeeName;
    public $employeeID;
    public $empID;
    public $organisationID;
    public $currentDate;
    public $Reason;
    public $createdBy;
    public $picture;
    public $checkInTime;
    public $attendanceDate;
    public $checkInBranchID;
    public $checkOutTime;
    public $isLateCheckIN;


    public function loadTemporaryAttendance(array $data) {
        $this->isTempAttendance = $data['isTempAttendance'];
        $this->employeeID = $data['employeeID'];
        $this->organisationID = $data['organisationID'];
        $this->attendanceDate = $data['attendanceDate'];
        $this->checkInBranchID = $data['checkInBranchID'];
        $this->isLateCheckIN = $data['isLateCheckIN'];
        $this->Reason = $data['Reason'];
        $this->createdBy = $data['createdBy'];
        $this->picture = isset($data['picture']) ? $data['picture'] : '';
        
        // Load check-in and check-out times
        $this->checkInTime = isset($data['checkInTime']) ? $data['checkInTime'] : '';
        $this->checkOutTime = isset($data['checkOutTime']) ? $data['checkOutTime'] : '';

        // Check if a new file is being uploaded
        if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
            //relative path for upload directory: uploads/organisationID/employeeID/date/attendancePics
            $baseUploadDir = '../uploads/';
            // absolute path:
            // $baseUploadDir = '/data/server/live/API/public_html/vidupuApp/uploads/';
            
            // Create base directory if it doesn't exist
            if (!file_exists($baseUploadDir)) {
                mkdir($baseUploadDir, 0777, true);
            }
    
            $file = $_FILES['picture'];
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileError = $file['error'];
            $fileType = $file['type'];
            
            // Debug logging
            error_log("File upload debug - employeeID: " . $this->employeeID . ", organisationID: " . $this->organisationID . ", attendanceDate: " . $this->attendanceDate);
            
            // Get file extension
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Allowed file types
            $allowed = array('jpg', 'jpeg', 'png', 'gif');
            
            if (in_array($fileExt, $allowed)) {
                if ($fileError === 0) {
                    if ($fileSize < 5000000) { // 5MB limit
                        // Generate unique filename with attendance prefix
                        $fileNameNew = 'attendance_' . uniqid('', true) . "." . $fileExt;
                        
                        // For updates, use existing employeeID and organisationID, for creates we'll handle it after insertion
                        if (isset($this->employeeID) && !empty($this->employeeID) && isset($this->organisationID) && !empty($this->organisationID) && isset($this->attendanceDate) && !empty($this->attendanceDate)) {
                            // Update mode - use existing employeeID and organisationID
                            $organisationFolder = $baseUploadDir . $this->organisationID . '/';
                            $employeeFolder = $organisationFolder . $this->employeeID . '/attendancePics/';
                            $dateFolder = $employeeFolder . $this->attendanceDate . '/';
                            $fileDestination = $dateFolder . $fileNameNew;
                            $this->picture = 'uploads/' . $this->organisationID . '/' . $this->employeeID . '/attendancePics/' . $this->attendanceDate . '/' . $fileNameNew;
                            
                            error_log("Using final path: " . $fileDestination);
                        } else {
                            // Create mode - we'll need to update this after getting the employeeID and organisationID
                            $tempFolder = $baseUploadDir . 'temp_uploads/';
                            $fileDestination = $tempFolder . $fileNameNew;
                            $this->picture = 'uploads/temp_uploads/' . $fileNameNew;
                            
                            error_log("Using temp path: " . $fileDestination);
                        }
                        
                        // Create employee-specific folder
                        $empPictureFolder = dirname($fileDestination);
                        error_log("Creating folder: " . $empPictureFolder);
                        
                        if (!file_exists($empPictureFolder)) {
                            $mkdirResult = mkdir($empPictureFolder, 0777, true);
                            error_log("mkdir result: " . ($mkdirResult ? 'success' : 'failed'));
                        }
                        
                        $moveResult = move_uploaded_file($fileTmpName, $fileDestination);
                        error_log("move_uploaded_file result: " . ($moveResult ? 'success' : 'failed') . " from " . $fileTmpName . " to " . $fileDestination);
                    } else {
                        error_log("File too large: " . $fileSize);
                    }
                } else {
                    error_log("File error: " . $fileError);
                }
            } else {
                error_log("Invalid file type: " . $fileExt);
            }
        } else {
            error_log("No file uploaded or file upload error");
            if (isset($_FILES['picture'])) {
                error_log("File upload error code: " . $_FILES['picture']['error']);
            }
        }
        return true;
    }

    public function CreateEmployeeTemporaryAttendance() {
        include('config.inc');
        header('Content-Type: application/json');

        try {
            mysqli_begin_transaction($connect_var);

            // Check if employee already has attendance record for the given date
            $checkExistingAttendance = "SELECT attendanceID, checkInTime, checkOutTime 
                                       FROM tblAttendance 
                                       WHERE employeeID = ? 
                                       AND attendanceDate = ? 
                                       LIMIT 1";
            
            $checkStmt = mysqli_prepare($connect_var, $checkExistingAttendance);
            if (!$checkStmt) {
                throw new Exception("Database prepare statement failed: " . mysqli_error($connect_var));
            }
            
            mysqli_stmt_bind_param($checkStmt, "ss", $this->employeeID, $this->attendanceDate);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);
            
            if (mysqli_num_rows($checkResult) > 0) {
                $existingRecord = mysqli_fetch_assoc($checkResult);
                mysqli_stmt_close($checkStmt);
                mysqli_rollback($connect_var);
                
                $status = "error";
                $message = "Employee already has attendance record for this date. ";
                
                if ($existingRecord['checkInTime'] && $existingRecord['checkOutTime']) {
                    $message .= "Check-in: " . $existingRecord['checkInTime'] . ", Check-out: " . $existingRecord['checkOutTime'];
                } elseif ($existingRecord['checkInTime']) {
                    $message .= "Check-in: " . $existingRecord['checkInTime'] . " (No check-out recorded)";
                } else {
                    $message .= "Attendance record exists but no check-in time recorded";
                }
                
                echo json_encode([
                    "status" => $status,
                    "message_text" => $message
                ], JSON_FORCE_OBJECT);
                return;
            }
            
            mysqli_stmt_close($checkStmt);

            // Get branch check-in and check-out times to calculate late check-in and early check-out
            $branchQuery = "SELECT checkInTime, checkOutTime FROM tblBranch WHERE branchID = ? LIMIT 1";
            $branchStmt = mysqli_prepare($connect_var, $branchQuery);
            if (!$branchStmt) {
                throw new Exception("Database prepare statement failed: " . mysqli_error($connect_var));
            }
            
            mysqli_stmt_bind_param($branchStmt, "s", $this->checkInBranchID);
            mysqli_stmt_execute($branchStmt);
            $branchResult = mysqli_stmt_get_result($branchStmt);
            
            $isLateCheckIN = 0; // Default to not late
            $isEarlyCheckOut = 0; // Default to not early
            
            if (mysqli_num_rows($branchResult) > 0) {
                $branchData = mysqli_fetch_assoc($branchResult);
                $branchCheckInTime = $branchData['checkInTime'];
                $branchCheckOutTime = $branchData['checkOutTime'];
                
                // Calculate late check-in using the same logic as existing attendance operation
                if (!empty($this->checkInTime)) {
                    // Check for specific employee IDs first
                    if (in_array($this->employeeID, [72, 73, 75]) && $this->checkInTime > '08:10:00') {
                        $isLateCheckIN = 1;
                    } elseif (in_array($this->employeeID, [24, 27]) && $this->checkInTime > '11:10:00') {
                        $isLateCheckIN = 1;
                    } elseif (in_array($this->checkInBranchID, [1, 52]) && $this->checkInTime > '09:30:00') {
                        $isLateCheckIN = 1;
                    } elseif ($this->checkInBranchID >= 2 && $this->checkInBranchID <= 51 && $this->checkInTime > '09:25:00') {
                        $isLateCheckIN = 1;
                    } else {
                        $isLateCheckIN = 0;
                    }
                }
                
                // Calculate early check-out using the same logic as existing attendance operation
                if (!empty($this->checkOutTime)) {
                    // Check for specific employee IDs first
                    if (in_array($this->employeeID, [72, 73, 75]) && $this->checkOutTime < '15:00:00') {
                        $isEarlyCheckOut = 1;
                    } elseif (in_array($this->employeeID, [24, 27]) && $this->checkOutTime < '18:00:00') {
                        $isEarlyCheckOut = 1;
                    } elseif (in_array($this->checkInBranchID, [1, 52]) && $this->checkOutTime < '04:30:00') {
                        $isEarlyCheckOut = 1;
                    } elseif ($this->checkInBranchID >= 2 && $this->checkInBranchID <= 51 && $this->checkOutTime < '16:30:00') {
                        $isEarlyCheckOut = 1;
                    } else {
                        $isEarlyCheckOut = 0;
                    }
                }
            }
            
            mysqli_stmt_close($branchStmt);

            // Determine if this is a check-in or check-out based on which time is provided
            $isCheckIn = !empty($this->checkInTime);
            $isCheckOut = !empty($this->checkOutTime);
            
            // Set the appropriate time based on check type
            $checkInTimeValue = $isCheckIn ? $this->checkInTime : null;
            $checkOutTimeValue = $isCheckOut ? $this->checkOutTime : null;

            $queryCreateEmployeeTemporaryAttendance = "INSERT INTO tblAttendance (
                isTempAttendance, employeeID, organisationID, Reason, createdBy, picture, 
                checkInTime, checkOutTime, attendanceDate, checkInBranchID, isLateCheckIN, isEarlyCheckOut
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, ?)";

            $stmt = mysqli_prepare($connect_var, $queryCreateEmployeeTemporaryAttendance);
            if (!$stmt) {
                throw new Exception("Database prepare statement failed: " . mysqli_error($connect_var));
            }

            mysqli_stmt_bind_param($stmt, "issssssssiii",
                $this->isTempAttendance,
                $this->employeeID,
                $this->organisationID,
                $this->Reason,
                $this->createdBy,
                $this->picture,
                $checkInTimeValue,
                $checkOutTimeValue,
                $this->checkInBranchID,
                $isLateCheckIN,
                $isEarlyCheckOut
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error creating temporary attendance: " . mysqli_stmt_error($stmt));
            }
            
            $latestAttendanceID = mysqli_insert_id($connect_var);
            
            if (strpos($this->picture, 'uploads/temp_uploads/') === 0) {
                $baseUploadDir = '../uploads/';
                // Alternative absolute path (uncomment if needed):
                // $baseUploadDir = '/data/server/live/API/public_html/vidupuApp/uploads/';
                $tempFilePath = $baseUploadDir . 'temp_uploads/' . basename($this->picture);
                $newFolderPath = $baseUploadDir . $this->organisationID . '/' . $this->employeeID . '/attendancePics/' . $this->attendanceDate . '/';
                $newFilePath = $newFolderPath . basename($this->picture);
                
                error_log("Moving temp file - tempFilePath: " . $tempFilePath);
                error_log("Moving temp file - newFolderPath: " . $newFolderPath);
                error_log("Moving temp file - newFilePath: " . $newFilePath);
                
                if (!file_exists($newFolderPath)) {
                    $mkdirResult = mkdir($newFolderPath, 0777, true);
                    error_log("Creating new folder result: " . ($mkdirResult ? 'success' : 'failed'));
                }
                
                if (file_exists($tempFilePath)) {
                    error_log("Temp file exists, attempting to move");
                    if (rename($tempFilePath, $newFilePath)) {
                        error_log("File moved successfully");
                        $this->picture = 'uploads/' . $this->organisationID . '/' . $this->employeeID . '/attendancePics/' . $this->attendanceDate . '/' . basename($this->picture);
                        
                        $updateQuery = "UPDATE tblAttendance SET picture = ? WHERE attendanceId = ?";
                        $updateStmt = mysqli_prepare($connect_var, $updateQuery);
                        if ($updateStmt) {
                            mysqli_stmt_bind_param($updateStmt, "si", $this->picture, $latestAttendanceID);
                            mysqli_stmt_execute($updateStmt);
                            mysqli_stmt_close($updateStmt);
                            error_log("Database updated with new picture path: " . $this->picture);
                        }
                    } else {
                        error_log("Failed to move temp file");
                    }
                } else {
                    error_log("Temp file does not exist: " . $tempFilePath);
                }
            }

            mysqli_commit($connect_var);
            mysqli_stmt_close($stmt);
            
            $actionType = $isCheckIn ? 'check-in' : 'check-out';
            $lateEarlyInfo = '';
            
            if ($isCheckIn && $isLateCheckIN) {
                $lateEarlyInfo = ' (Late check-in)';
            } elseif ($isCheckOut && $isEarlyCheckOut) {
                $lateEarlyInfo = ' (Early check-out)';
            }
            
            echo json_encode(array(
                "status" => "success",
                "message" => "Temporary attendance {$actionType} created successfully{$lateEarlyInfo}",
                "attendanceID" => $latestAttendanceID,
                "isLateCheckIn" => $isLateCheckIN,
                "isEarlyCheckOut" => $isEarlyCheckOut
            ));

        } catch (Exception $e) {
            mysqli_rollback($connect_var);
            echo json_encode([
                "status" => "error", 
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        } finally {
            if (isset($connect_var)) {
                mysqli_close($connect_var);
            }
        }
    }

}

function CreateEmployeeTemporaryAttendance($decoded_items) {
    $TemporaryAttendanceObject = new TemporaryAttendanceComponent();
    if ($TemporaryAttendanceObject->loadTemporaryAttendance($decoded_items)) {
        $TemporaryAttendanceObject->CreateEmployeeTemporaryAttendance();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}