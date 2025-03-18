<?php
header('Content-Type: application/json');

$targetDir = "uploads/";
echo "Checking if the directory exists...";

// Create the uploads directory if it doesn't exist
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Get file details
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Sanitize file name and create a unique name
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;

        // Allowed file extensions
        $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif');
        if (in_array($fileExtension, $allowedExtensions)) {
            $destPath = $targetDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'File uploaded successfully.',
                    'filePath' => $destPath
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error moving the uploaded file.'
                ]);
            }
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Upload failed. Allowed file types: ' . implode(',', $allowedExtensions)
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No file uploaded or an error occurred during upload.'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method. haha'
    ]);
}
?>
