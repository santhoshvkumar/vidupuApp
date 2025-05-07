<?php
// Set security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Validate file parameter
if (!isset($_GET['file']) || empty($_GET['file'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(array(
        "status" => "error",
        "message_text" => "No file specified"
    ));
    exit;
}

$filename = basename($_GET['file']); // Get only the filename, remove any path
$filepath = 'reports/' . $filename;

// Validate file exists and is within reports directory
if (!file_exists($filepath) || !is_file($filepath)) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(array(
        "status" => "error",
        "message_text" => "File not found"
    ));
    exit;
}

// Validate file extension
$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if ($extension !== 'csv') {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(array(
        "status" => "error",
        "message_text" => "Invalid file type"
    ));
    exit;
}

// Set headers for download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output file
readfile($filepath);

// Delete the file after download
unlink($filepath);
?> 