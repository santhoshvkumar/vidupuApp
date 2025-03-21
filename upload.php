<?php
header('Content-Type: application/json');

// Define upload directory
$targetDir = "uploads/";

// Create the uploads directory if it doesn't exist
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

// Database connection parameters
$db_host = "localhost";
$db_user = "root";
$db_pass = "root";
$db_name = "tnscvidupuapp";
$db_port = 8889;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES)) {
        if (isset($_FILES['file'])) {
            $file = $_FILES['file'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                // Get file details
                $fileTmpPath = $file['tmp_name'];
                $fileName = $file['name'];
                $fileSize = $file['size'];
                $fileType = $file['type'];
                
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));

                // Get certificate type and leave ID
                $certificateType = isset($_POST['certificateType']) ? $_POST['certificateType'] : null;
                $applyLeaveID = isset($_POST['applyLeaveID']) ? $_POST['applyLeaveID'] : null;
                
                // Set appropriate target directory
                $targetSubDir = "uploads/";
                if (strtolower($certificateType) === 'medical') {
                    $targetSubDir .= "medical/";
                } else if (strtolower($certificateType) === 'fitness') {
                    $targetSubDir .= "fitness/";
                }

                // Create directory if it doesn't exist
                if (!is_dir($targetSubDir)) {
                    mkdir($targetSubDir, 0755, true);
                }

                // Create unique filename
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $destPath = $targetSubDir . $newFileName;

                // Allowed file extensions
                $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'pdf');
                if (in_array($fileExtension, $allowedExtensions)) {
                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                        // Connect to database
                        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
                        
                        if ($conn->connect_error) {
                            echo json_encode([
                                'status' => 'error',
                                'message' => 'Database connection failed'
                            ]);
                            exit;
                        }

                        if ($applyLeaveID && $certificateType) {
                            // Current timestamp for the upload time
                            $currentTimestamp = date('Y-m-d H:i:s');
                            
                            // Create SQL based on certificate type
                            if (strtolower($certificateType) === 'medical') {
                                $sql = "UPDATE tblApplyLeave SET MedicalCertificatePath = ?, MedicalCertificateUploadDate = ? WHERE applyLeaveID = ?";
                            } else if (strtolower($certificateType) === 'fitness') {
                                $sql = "UPDATE tblApplyLeave SET FitnessCertificatePath = ?, FitnessCertificateUploadDate = ? WHERE applyLeaveID = ?";
                            } else {
                                $sql = "UPDATE tblApplyLeave SET certificatePath = ?, certificateUploadDate = ? WHERE applyLeaveID = ?";
                            }

                            // Prepare and execute the query
                            $stmt = $conn->prepare($sql);
                            if ($stmt) {
                                $stmt->bind_param("ssi", $destPath, $currentTimestamp, $applyLeaveID);
                                
                                if ($stmt->execute()) {
                                    $affectedRows = $stmt->affected_rows;
                                    
                                    // Keep only the important success log
                                    if ($affectedRows > 0) {
                                        error_log("✅ CERTIFICATE SAVED SUCCESSFULLY: Type=" . $certificateType . ", LeaveID=" . $applyLeaveID . ", Path=" . $destPath);
                                    }
                                    
                                    echo json_encode([
                                        'status' => 'success',
                                        'message' => ucfirst($certificateType) . ' certificate uploaded successfully',
                                        'filePath' => $destPath,
                                        'originalName' => $fileName,
                                        'fileSize' => $fileSize,
                                        'fileType' => $fileType,
                                        'uploadDate' => $currentTimestamp,
                                        'certificateType' => $certificateType
                                    ]);
                                } else {
                                    echo json_encode([
                                        'status' => 'error',
                                        'message' => 'Database update failed'
                                    ]);
                                }
                                $stmt->close();
                            } else {
                                echo json_encode([
                                    'status' => 'error',
                                    'message' => 'Failed to prepare database statement'
                                ]);
                            }
                        } else {
                            echo json_encode([
                                'status' => 'error',
                                'message' => 'Missing applyLeaveID or certificateType'
                            ]);
                        }
                        $conn->close();
                    } else {
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Failed to move uploaded file'
                        ]);
                    }
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Invalid file type. Allowed: ' . implode(',', $allowedExtensions)
                    ]);
                }
            } else {
                $errorMessage = isset($uploadErrors[$file['error']]) 
                    ? $uploadErrors[$file['error']] 
                    : 'Unknown upload error';
                
                echo json_encode([
                    'status' => 'error',
                    'message' => 'File upload error: ' . $errorMessage
                ]);
            }
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No file field found in the request'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No files were uploaded'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method. Expected POST, got ' . $_SERVER['REQUEST_METHOD']
    ]);
}
?>






