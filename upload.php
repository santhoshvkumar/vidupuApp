<?php
header('Content-Type: application/json');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug information
error_log("=== Request Debug Info ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Content Type: " . (isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : 'not set'));

// Log all headers
$headers = getallheaders();
error_log("All Headers: " . print_r($headers, true));

$targetDir = "uploads/";
error_log("Upload directory path: " . realpath($targetDir));
error_log("Upload directory exists: " . (is_dir($targetDir) ? 'yes' : 'no'));
error_log("Upload directory writable: " . (is_writable($targetDir) ? 'yes' : 'no'));

// Create the uploads directory if it doesn't exist
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
    error_log("Created uploads directory");
}

// Log PHP settings
error_log("PHP Upload Settings:");
error_log("upload_max_filesize: " . ini_get('upload_max_filesize'));
error_log("post_max_size: " . ini_get('post_max_size'));
error_log("max_file_uploads: " . ini_get('max_file_uploads'));

$db_host = "localhost";
$db_user = "root";
$db_pass = "root";
$db_name = "tnscvidupuapp";
$db_port = 8889;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add these debug lines
    error_log("=== Detailed Request Debug ===");
    error_log("Content-Type Header: " . $_SERVER['CONTENT_TYPE']);
    error_log("Request Headers:");
    foreach (getallheaders() as $name => $value) {
        error_log("$name: $value");
    }
    error_log("POST Variables: " . print_r($_POST, true));
    error_log("FILES Variables: " . print_r($_FILES, true));
    error_log("Raw input: " . file_get_contents('php://input'));
    
    // Log raw post data
    error_log("Raw POST data: " . file_get_contents('php://input'));
    
    // Log form data
    error_log("POST Data: " . print_r($_POST, true));
    error_log("FILES Data: " . print_r($_FILES, true));

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

                // Create separate folders for medical and fitness certificates
                $certificateType = isset($_POST['certificateType']) ? $_POST['certificateType'] : null;
                $targetDir = "uploads/";
                if (strtolower($certificateType) === 'medical') {
                    $targetDir .= "medical/";
                } else if (strtolower($certificateType) === 'fitness') {
                    $targetDir .= "fitness/";
                }

                // Create directory if it doesn't exist
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                    error_log("Created directory: " . $targetDir);
                }

                // Sanitize file name and create a unique name
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $destPath = $targetDir . $newFileName;

                // Allowed file extensions
                $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'pdf');
                if (in_array($fileExtension, $allowedExtensions)) {
                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                        // Database connection
                        $conn = new mysqli("localhost", "root", "root", "tnscvidupuapp", 8889);
                        
                        if ($conn->connect_error) {
                            error_log("Database connection failed: " . $conn->connect_error);
                            echo json_encode([
                                'status' => 'error',
                                'message' => 'Database connection failed'
                            ]);
                            exit;
                        }

                        // Get applyLeaveID and certificateType from POST data
                        $applyLeaveID = isset($_POST['applyLeaveID']) ? $_POST['applyLeaveID'] : null;
                        $currentTimestamp = date('Y-m-d H:i:s');

                        if ($applyLeaveID && $certificateType) {
                            // SQL query based on certificate type
                            if (strtolower($certificateType) === 'medical') {
                                $sql = "UPDATE tblApplyLeave 
                                       SET MedicalCertificatePath = ?,
                                           MedicalCertificateUploadDate = ?
                                       WHERE applyLeaveID = ?";
                            } else if (strtolower($certificateType) === 'fitness') {
                                $sql = "UPDATE tblApplyLeave 
                                       SET FitnessCertificatePath = ?,
                                           FitnessCertificateUploadDate = ?
                                       WHERE applyLeaveID = ?";
                            } else {
                                error_log("Invalid certificate type: " . $certificateType);
                                echo json_encode([
                                    'status' => 'error',
                                    'message' => 'Invalid certificate type'
                                ]);
                                exit;
                            }

                            // Prepare and execute the query
                            $stmt = $conn->prepare($sql);
                            if ($stmt) {
                                $stmt->bind_param("ssi", $destPath, $currentTimestamp, $applyLeaveID);
                                
                                if ($stmt->execute()) {
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
                                    error_log("Database update failed: " . $stmt->error);
                                    echo json_encode([
                                        'status' => 'error',
                                        'message' => 'Database update failed',
                                        'error_details' => $stmt->error
                                    ]);
                                }
                                $stmt->close();
                            } else {
                                error_log("Failed to prepare statement: " . $conn->error);
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
                        error_log("Failed to move uploaded file to: " . $destPath);
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Failed to move uploaded file',
                            'error_details' => error_get_last()['message']
                        ]);
                    }
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Invalid file type. Allowed: ' . implode(',', $allowedExtensions)
                    ]);
                }
            } else {
                // Log specific upload error
                $uploadErrors = array(
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
                );
                $errorMessage = isset($uploadErrors[$file['error']]) 
                    ? $uploadErrors[$file['error']] 
                    : 'Unknown upload error';
                
                error_log("File upload error: " . $errorMessage);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'File upload error: ' . $errorMessage
                ]);
            }
        } else {
            error_log("'file' field not found in FILES array");
            echo json_encode([
                'status' => 'error',
                'message' => 'No file field found in the request'
            ]);
        }
    } else {
        error_log("No files were uploaded");
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


