<?php

$f3->route('POST /ApplyLeave',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            applyLeave($decoded_items);
        } else {
            echo json_encode(
                array(
                    "status" => "error Leave Balance",
                    "message_text" => "Invalid input parameters"
                ),
                JSON_FORCE_OBJECT
            );
        }
    }
);

$f3->route('GET /GetLeaveBalance',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            getLeaveBalance($decoded_items);
        } else {
            echo json_encode(
                array(
                    "status" => "error Leave Balance",
                    "message_text" => "Invalid input parameters"
                ),
                JSON_FORCE_OBJECT
            );
        }
    }
);

$f3->route('POST /GetLeaveHistory',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            getLeaveHistory($decoded_items);
        } else {
            echo json_encode(
                array(
                    "status" => "error Leave Balance",
                    "message_text" => "Invalid input parameters"
                ),
                JSON_FORCE_OBJECT
            );
        }
    }
);

$f3->route('POST /UploadLeaveCertificate',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);           
        if (!$decoded_items == NULL) {
            uploadLeaveCertificate($decoded_items);
        } else {
            echo json_encode(
                array(
                    "status" => "error Upload Leave Certificate",  
                    "message_text" => "Invalid input parameters"
                ),
                JSON_FORCE_OBJECT
            );
        }
    }
);  

/*****************  End Upload Medical Certificate *****************/

/*****************   Get Certificate Paths  *******************/
$f3->route('GET /GetCertificatePaths/@applyLeaveID',
    function($f3, $params) {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type');
        
        try {
            // Validate request
            if (!isset($_POST['applyLeaveID']) || !isset($_POST['certificateType'])) {
                throw new Exception("Missing required fields");
            }

            if (!isset($_FILES['file'])) {
                throw new Exception("No file uploaded");
            }

            $data = [
                'applyLeaveID' => $_POST['applyLeaveID'],
                'certificateType' => $_POST['certificateType']
            ];

            uploadLeaveCertificate($data);

        } catch (Exception $e) {
            error_log("Upload error: " . $e->getMessage());
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ));
        }
    }
);
/*****************  End Upload Medical Certificate *****************/

/*****************   Get Certificate Paths  *******************/
$f3->route('GET /GetCertificatePaths/@applyLeaveID',
    function($f3, $params) {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
        header('Access-Control-Allow-Headers: Content-Type');
        
        try {
            $applyLeaveID = $params['applyLeaveID'];
            
            // Get certificate paths from database
            include('config.inc');
            $query = "SELECT MedicalCertificatePath, FitnessCertificatePath 
                     FROM tblApplyLeave 
                     WHERE applyLeaveID = ?";
            $stmt = mysqli_prepare($connect_var, $query);
            mysqli_stmt_bind_param($stmt, "s", $applyLeaveID);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                echo json_encode(array(
                    "status" => "success",
                    "medicalCertificatePath" => $row['MedicalCertificatePath'],
                    "fitnessCertificatePath" => $row['FitnessCertificatePath']
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message_text" => "No certificates found for this leave application"
                ));
            }
            
            mysqli_close($connect_var);
        } catch (Exception $e) {
            error_log("Get certificate error: " . $e->getMessage());
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ));
        }
    }
);
/*****************  End Get Certificate Paths *****************/

/*****************   Update Leave Status  *******************/
$f3->route('POST /updateLeaveStatus.php',
    function($f3) {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type');
        
        try {
            // Get the request data
            $data = json_decode($f3->get('BODY'), true);
            
            if (!$data) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
                return;
            }
            
            // Call the function in the component file
            updateLeaveStatus($data);
            
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
);
/*****************  End Update Leave Status *****************/