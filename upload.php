<?php
// Set proper headers to allow cross-origin requests and prevent CORS issues
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Allow longer execution time for file uploads
ini_set('max_execution_time', 300); // 5 minutes
ini_set('memory_limit', '256M');

// Define upload directory with explicit full path
$targetDir = __DIR__ . "/uploads/";

// Create the uploads directory 
if (!is_dir($targetDir)) {
    if (!mkdir($targetDir, 0755, true)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to create upload directory'
        ]);
        exit;
    }
    error_log("Created main uploads directory: " . $targetDir);
}

// Pre-create subdirectories at startup
$medicalDir = $targetDir . "medical/";
$fitnessDir = $targetDir . "fitness/";

if (!is_dir($medicalDir) && !mkdir($medicalDir, 0755, true)) {
    error_log("Warning: Failed to pre-create medical directory");
}

if (!is_dir($fitnessDir) && !mkdir($fitnessDir, 0755, true)) {
    error_log("Warning: Failed to pre-create fitness directory");
}


// Check if request contains any data
if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'The uploaded file exceeds the post_max_size directive in php.ini'
    ]);
    exit;
}

// Use config.inc for database connection parameters
include('config.inc');

try {
    // Check if connect_var exists from config
    if (!isset($connect_var) || !$connect_var) {
        // Attempt to create a direct connection
        include('direct_db_connect.php');
    }
} catch (Exception $e) {
    error_log("DB Connection error: " . $e->getMessage());
}

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
                        // Try to establish database connection
                        try {
                            if (!isset($connect_var) || !$connect_var) {
                                // If not already set, create a direct connection
                                $connect_var = new mysqli("localhost", "root", "root", "tnscvidupuapp", 8889);
                            }
                            
                            if ($connect_var->connect_error) {
                                throw new Exception("Database connection failed: " . $connect_var->connect_error);
                            }
                            
                            if ($applyLeaveID && $certificateType) {
                                // Current timestamp for the upload time
                                $currentTimestamp = date('Y-m-d H:i:s');
                                
                                // Create SQL based on certificate type
                                if (strtolower($certificateType) === 'medical') {
                                    $sql = "UPDATE tblApplyLeave SET MedicalCertificatePath = ?, MedicalCertificateUploadDate = ? WHERE applyLeaveID = ?";
                                } else if (strtolower($certificateType) === 'fitness') {
                                    $sql = "UPDATE tblApplyLeave SET FitnessCertificatePath = ?, FitnessCertificateUploadDate = ?, status = 'Yet To Be Approved' WHERE applyLeaveID = ?";
                                } else {
                                    $sql = "UPDATE tblApplyLeave SET certificatePath = ?, certificateUploadDate = ? WHERE applyLeaveID = ?";
                                }
                                
                                // Prepare and execute the query
                                $stmt = $connect_var->prepare($sql);
                                if ($stmt) {
                                    $stmt->bind_param("ssi", $destPath, $currentTimestamp, $applyLeaveID);
                                    
                                    if ($stmt->execute()) {
                                        error_log("✅ CERTIFICATE SAVED SUCCESSFULLY: Type=" . $certificateType . ", LeaveID=" . $applyLeaveID);
                                        
                                        // Update the leave status to ensure it appears in the approval queue
                                        if (strtolower($certificateType) === 'fitness') {
                                            $updateStatusQuery = "UPDATE tblApplyLeave 
                                                               SET status = 'Yet To Be Approved' 
                                                               WHERE applyLeaveID = ?";
                                                               
                                            $statusStmt = $connect_var->prepare($updateStatusQuery);
                                            if ($statusStmt) {
                                                $statusStmt->bind_param("i", $applyLeaveID);
                                                $statusStmt->execute();
                                                error_log("Updated leave status to 'Yet To Be Approved' for leave ID: " . $applyLeaveID);
                                                $statusStmt->close();
                                            }
                                        }
                                        
                                        // Return success response
                                        echo json_encode([
                                            'status' => 'success',
                                            'message' => ucfirst($certificateType) . ' certificate uploaded successfully',
                                            'filePath' => $destPath
                                        ]);
                                        
                                        $stmt->close();
                                    } else {
                                        error_log("Database error: " . $stmt->error);
                                        echo json_encode([
                                            'status' => 'error',
                                            'message' => 'Database update failed',
                                            'dbError' => $stmt->error
                                        ]);
                                    }
                                } else {
                                    error_log("Statement preparation failed: " . $connect_var->error);
                                    echo json_encode([
                                        'status' => 'error',
                                        'message' => 'Database statement preparation failed',
                                        'dbError' => $connect_var->error
                                    ]);
                                }
                            } else {
                                throw new Exception("Missing applyLeaveID or certificateType");
                            }
                        } catch (Exception $e) {
                            // If database operation fails, still consider file upload as success
                            // This prevents the app from crashing with network request failed error
                            error_log("DB Error but file was uploaded: " . $e->getMessage());
                            echo json_encode([
                                'status' => 'success',
                                'message' => ucfirst($certificateType) . ' certificate uploaded but database update failed',
                                'filePath' => $destPath,
                                'dbError' => $e->getMessage()
                            ]);
                        }
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






