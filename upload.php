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
$newspaperDir = $targetDir . "newspaper/";

if (!is_dir($medicalDir) && !mkdir($medicalDir, 0755, true)) {
    error_log("Warning: Failed to pre-create medical directory");
}

if (!is_dir($fitnessDir) && !mkdir($fitnessDir, 0755, true)) {
    error_log("Warning: Failed to pre-create fitness directory");
}

if (!is_dir($newspaperDir) && !mkdir($newspaperDir, 0755, true)) {
    error_log("Warning: Failed to pre-create newspaper directory");
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

// Define upload error messages
$uploadErrors = array(
    UPLOAD_ERR_OK => 'No error',
    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
    UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
);

// Image compression function
function compressImage($source, $destination, $quality = 80, $maxWidth = 1920, $maxHeight = 1080) {
    // Get image info
    $imageInfo = getimagesize($source);
    if (!$imageInfo) {
        return false;
    }
    
    $mimeType = $imageInfo['mime'];
    $originalWidth = $imageInfo[0];
    $originalHeight = $imageInfo[1];
    
    // Create image resource based on type
    switch ($mimeType) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            break;
        default:
            return false; // Unsupported image type
    }
    
    if (!$image) {
        return false;
    }
    
    // Calculate new dimensions while maintaining aspect ratio
    $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
    $newWidth = $originalWidth * $ratio;
    $newHeight = $originalHeight * $ratio;
    
    // Only resize if the image is larger than max dimensions
    if ($originalWidth > $maxWidth || $originalHeight > $maxHeight) {
        // Create new image with calculated dimensions
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($mimeType == 'image/png' || $mimeType == 'image/gif') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Resize the image
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        
        // Save the compressed image
        $result = false;
        switch ($mimeType) {
            case 'image/jpeg':
                $result = imagejpeg($newImage, $destination, $quality);
                break;
            case 'image/png':
                // PNG compression level (0-9, where 9 is highest compression)
                $pngQuality = 9 - round(($quality / 100) * 9);
                $result = imagepng($newImage, $destination, $pngQuality);
                break;
            case 'image/gif':
                $result = imagegif($newImage, $destination);
                break;
        }
        
        // Clean up memory
        imagedestroy($newImage);
        imagedestroy($image);
        
        return $result;
    } else {
        // Image is already within size limits, just compress quality
        $result = false;
        switch ($mimeType) {
            case 'image/jpeg':
                $result = imagejpeg($image, $destination, $quality);
                break;
            case 'image/png':
                $pngQuality = 9 - round(($quality / 100) * 9);
                $result = imagepng($image, $destination, $pngQuality);
                break;
            case 'image/gif':
                $result = imagegif($image, $destination);
                break;
        }
        
        imagedestroy($image);
        return $result;
    }
}

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
    // --- PROFILE PHOTO UPLOAD HANDLING FIRST ---
    $profilePhotoField = isset($_FILES['profilePhoto']) ? $_FILES['profilePhoto'] : null;
    $profileEmployeeID = isset($_POST['employeeID']) ? $_POST['employeeID'] : null;
    if ($profilePhotoField && $profileEmployeeID) {
        $file = $profilePhotoField;
        if ($file['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $file['tmp_name'];
            $fileName = $file['name'];
            $fileNameCmps = explode('.', $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            $profileDir = $targetDir . 'profile_photos/' . $profileEmployeeID . '/';
            if (!is_dir($profileDir)) {
                mkdir($profileDir, 0755, true);
            }
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $destPath = $profileDir . $newFileName;
            $dbPath = 'uploads/profile_photos/' . $profileEmployeeID . '/' . $newFileName;
            $allowedExtensions = array('jpg', 'jpeg', 'png');
            if (in_array($fileExtension, $allowedExtensions)) {
                // Compress the image before saving
                if (compressImage($fileTmpPath, $destPath, 85, 800, 800)) {
                    echo json_encode([
                        'status' => 'success',
                        'filePath' => $dbPath,
                        'message' => 'Profile photo uploaded and compressed successfully'
                    ]);
                    exit;
                } else {
                    // Fallback to regular upload if compression fails
                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                        echo json_encode([
                            'status' => 'success',
                            'filePath' => $dbPath,
                            'message' => 'Profile photo uploaded successfully (compression failed)'
                        ]);
                        exit;
                    } else {
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Failed to upload profile photo'
                        ]);
                        exit;
                    }
                }
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid file type for profile photo. Allowed: jpg,jpeg,png'
                ]);
                exit;
            }
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Profile photo upload error: ' . $file['error']
            ]);
            exit;
        }
    }
    // --- OTHER FILE UPLOAD LOGIC (medical, fitness, etc) ---
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
                $employeeID = isset($_POST['employeeID']) ? $_POST['employeeID'] : null;
                
                // Set appropriate target directory with employee ID
                $targetSubDir = "uploads/";
                if (strtolower($certificateType) === 'medical') {
                    $targetSubDir .= "medical/" . $employeeID . "/";
                } else if (strtolower($certificateType) === 'fitness') {
                    $targetSubDir .= "fitness/" . $employeeID . "/";
                } else if (strtolower($certificateType) === 'newspaper') {
                    $targetSubDir .= "newspaper/" . $employeeID . "/";
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
                    // Compress images (not PDFs) before saving
                    $uploadSuccess = false;
                    if (in_array($fileExtension, array('jpg', 'jpeg', 'png', 'gif'))) {
                        // Compress image files
                        $uploadSuccess = compressImage($fileTmpPath, $destPath, 80, 1920, 1080);
                    } else {
                        // For PDFs, use regular file move
                        $uploadSuccess = move_uploaded_file($fileTmpPath, $destPath);
                    }
                    
                    if ($uploadSuccess) {
                        // Try to establish database connection
                        try {
                            
                            if ($connect_var->connect_error) {
                                throw new Exception("Database connection failed: " . $connect_var->connect_error);
                            }
                            
                            // Handle newspaper bills differently since they don't have applyLeaveID
                            if (strtolower($certificateType) === 'newspaper') {
                                // Return success response for newspaper bills without database update
                                echo json_encode([
                                    'status' => 'success',
                                    'message' => 'Newspaper bill uploaded successfully',
                                    'filePath' => $destPath
                                ]);
                            } else if ($applyLeaveID && $certificateType) {
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
                        // If compression failed, try regular file move as fallback
                        if (in_array($fileExtension, array('jpg', 'jpeg', 'png', 'gif'))) {
                            if (move_uploaded_file($fileTmpPath, $destPath)) {
                                // Try to establish database connection for fallback upload
                                try {
                                    if ($connect_var->connect_error) {
                                        throw new Exception("Database connection failed: " . $connect_var->connect_error);
                                    }
                                    
                                    // Handle newspaper bills differently since they don't have applyLeaveID
                                    if (strtolower($certificateType) === 'newspaper') {
                                        echo json_encode([
                                            'status' => 'success',
                                            'message' => 'Newspaper bill uploaded successfully (compression failed, used fallback)',
                                            'filePath' => $destPath
                                        ]);
                                    } else if ($applyLeaveID && $certificateType) {
                                        $currentTimestamp = date('Y-m-d H:i:s');
                                        
                                        if (strtolower($certificateType) === 'medical') {
                                            $sql = "UPDATE tblApplyLeave SET MedicalCertificatePath = ?, MedicalCertificateUploadDate = ? WHERE applyLeaveID = ?";
                                        } else if (strtolower($certificateType) === 'fitness') {
                                            $sql = "UPDATE tblApplyLeave SET FitnessCertificatePath = ?, FitnessCertificateUploadDate = ?, status = 'Yet To Be Approved' WHERE applyLeaveID = ?";
                                        } else {
                                            $sql = "UPDATE tblApplyLeave SET certificatePath = ?, certificateUploadDate = ? WHERE applyLeaveID = ?";
                                        }
                                        
                                        $stmt = $connect_var->prepare($sql);
                                        if ($stmt) {
                                            $stmt->bind_param("ssi", $destPath, $currentTimestamp, $applyLeaveID);
                                            
                                            if ($stmt->execute()) {
                                                error_log("✅ CERTIFICATE SAVED SUCCESSFULLY (FALLBACK): Type=" . $certificateType . ", LeaveID=" . $applyLeaveID);
                                                
                                                if (strtolower($certificateType) === 'fitness') {
                                                    $updateStatusQuery = "UPDATE tblApplyLeave SET status = 'Yet To Be Approved' WHERE applyLeaveID = ?";
                                                    $statusStmt = $connect_var->prepare($updateStatusQuery);
                                                    if ($statusStmt) {
                                                        $statusStmt->bind_param("i", $applyLeaveID);
                                                        $statusStmt->execute();
                                                        $statusStmt->close();
                                                    }
                                                }
                                                
                                                echo json_encode([
                                                    'status' => 'success',
                                                    'message' => ucfirst($certificateType) . ' certificate uploaded successfully (compression failed, used fallback)',
                                                    'filePath' => $destPath
                                                ]);
                                                
                                                $stmt->close();
                                            } else {
                                                echo json_encode([
                                                    'status' => 'error',
                                                    'message' => 'Database update failed',
                                                    'dbError' => $stmt->error
                                                ]);
                                            }
                                        } else {
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
                                    error_log("DB Error but file was uploaded (fallback): " . $e->getMessage());
                                    echo json_encode([
                                        'status' => 'success',
                                        'message' => ucfirst($certificateType) . ' certificate uploaded but database update failed (compression failed, used fallback)',
                                        'filePath' => $destPath,
                                        'dbError' => $e->getMessage()
                                    ]);
                                }
                            } else {
                                echo json_encode([
                                    'status' => 'error',
                                    'message' => 'Failed to upload file (both compression and fallback failed)'
                                ]);
                            }
                        } else {
                            echo json_encode([
                                'status' => 'error',
                                'message' => 'Failed to upload file'
                            ]);
                        }
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






