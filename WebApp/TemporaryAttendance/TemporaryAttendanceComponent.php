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
        $this->picture = $data['picture'];

        // Check if a new file is being uploaded
        if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
            // Use absolute path for upload directory
            $baseUploadDir = '/data/server/live/API/public_html/vidupuApp/uploads/TemporaryAttendance/';
            
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
            
            // Get file extension
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Allowed file types
            $allowed = array('jpg', 'jpeg', 'png', 'gif');
            
            if (in_array($fileExt, $allowed)) {
                if ($fileError === 0) {
                    if ($fileSize < 5000000) { // 5MB limit
                        // Generate unique filename
                        $fileNameNew = uniqid('logo_', true) . "." . $fileExt;
                        
                        // For updates, use existing organisationID, for creates we'll handle it after insertion
                        if (isset($this->empID) && !empty($this->empID)) {
                            // Update mode - use existing organisationID
                            $organisationFolder = $baseUploadDir . $this->empID . '/';
                            $fileDestination = $organisationFolder . $fileNameNew;
                            $this->picture = 'uploads/TemporaryAttendance/' . $this->empID . '/' . $fileNameNew;
                        } else {
                            // Create mode - we'll need to update this after getting the organisationID
                            $tempFolder = $baseUploadDir . 'temp/';
                            $fileDestination = $tempFolder . $fileNameNew;
                            $this->picture = 'uploads/TemporaryAttendance/temp/' . $fileNameNew;
                        }
                        
                        // Create organisation-specific folder
                        $empPictureFolder = dirname($fileDestination);
                        if (!file_exists($empPictureFolder)) {
                            mkdir($empPictureFolder, 0777, true);
                        }
                        
                        move_uploaded_file($fileTmpName, $fileDestination);
                    }
                }
            }
        }
        return true;
    }

    public function CreateEmployeeTemporaryAttendance() {
        include('config.inc');
        header('Content-Type: application/json');

        try {
            mysqli_begin_transaction($connect_var);

            $queryCreateEmployeeTemporaryAttendance = "INSERT INTO tblAttendance (
                isTempAttendance, employeeID, organisationID, Reason, createdBy, picture, checkInTime, attendanceDate, checkInBranchID, isLateCheckIN
            ) VALUES (?, ?, ?, ?, ?, ?, CURTIME(), CURDATE(), ?, ?)";

            $stmt = mysqli_prepare($connect_var, $queryCreateEmployeeTemporaryAttendance);
            if (!$stmt) {
                throw new Exception("Database prepare statement failed: " . mysqli_error($connect_var));
            }

            mysqli_stmt_bind_param($stmt, "isssissi",
                $this->isTempAttendance,
                $this->employeeID,
                $this->organisationID,
                $this->Reason,
                $this->createdBy,
                $this->picture,
                $this->checkInBranchID,
                $this->isLateCheckIN
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error creating temporary attendance: " . mysqli_stmt_error($stmt));
            }
            
            $latestAttendanceID = mysqli_insert_id($connect_var);
            
            if (strpos($this->picture, 'uploads/TemporaryAttendance/temp/') === 0) {
                $baseUploadDir = '/data/server/live/API/public_html/vidupuApp/uploads/TemporaryAttendance/';
                $tempFilePath = $baseUploadDir . 'temp/' . basename($this->picture);
                $newFolderPath = $baseUploadDir . $latestAttendanceID . '/';
                $newFilePath = $newFolderPath . basename($this->picture);
                
                if (!file_exists($newFolderPath)) {
                    mkdir($newFolderPath, 0777, true);
                }
                
                if (file_exists($tempFilePath)) {
                    if (rename($tempFilePath, $newFilePath)) {
                        $this->picture = 'uploads/TemporaryAttendance/' . $latestAttendanceID . '/' . basename($this->picture);
                        
                        $updateQuery = "UPDATE tblAttendance SET picture = ? WHERE attendanceId = ?";
                        $updateStmt = mysqli_prepare($connect_var, $updateQuery);
                        if ($updateStmt) {
                            mysqli_stmt_bind_param($updateStmt, "si", $this->picture, $latestAttendanceID);
                            mysqli_stmt_execute($updateStmt);
                            mysqli_stmt_close($updateStmt);
                        }
                    }
                }
            }

            mysqli_commit($connect_var);
            mysqli_stmt_close($stmt);
            
            echo json_encode(array(
                "status" => "success",
                "message" => "Temporary attendance created successfully",
                "attendanceID" => $latestAttendanceID
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