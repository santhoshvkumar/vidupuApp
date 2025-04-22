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
                    "message_text" => "Invalid input parameters",
                    "Data"=> $decoded_items
                ),
                JSON_FORCE_OBJECT
            );
        }
    }
);

$f3->route('POST /ExtendLeave',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            extendLeave($decoded_items);
        } else {
            echo json_encode(
                array(
                    "status" => "error Extend Leave",
                    "message_text" => "Invalid input parameters",
                    "Data"=> $decoded_items
                ),
                JSON_FORCE_OBJECT
            );
        }
    }
);

$f3->route('POST /GetLeaveBalance',
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

/*****************   Get Holidays  *******************/
$f3->route('POST /GetHolidays',
    function($f3) {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type');
        
        try {
            $leaveObject = new ApplyLeaveMaster;
            $leaveObject->getHolidays();
        } catch (Exception $e) {
            error_log("Get Holidays error: " . $e->getMessage());
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ));
        }
    }
);
/*****************  End Get Holidays *****************/

/*****************   Upload Medical Certificate  *******************/
$f3->route('POST /UploadCertificate',
    function($f3) {
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

// Add this route for getting certificate path
$f3->route('GET /GetCertificatePath',
    function($f3) {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        
        try {
            // Get parameters from query string
            $data = [
                'leaveId' => $f3->get('GET.leaveId'),
                'type' => $f3->get('GET.type') ?: 'Medical'
            ];
            
            // Check for required parameter
            if (empty($data['leaveId'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Missing leaveId parameter'
                ]);
                return;
            }
            
            getCertificatePath($data);
            
        } catch (Exception $e) {
            error_log("Certificate path error: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
);

// Apply Comp Off
$f3->route('POST /ApplyCompOff',
    function($f3) {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type');

        $data = json_decode($f3->get('BODY'), true);
        $leaveBalance = new ApplyLeaveMaster();
        $leaveBalance->applyCompOff($data);
    }
);

// Get Comp Off Leaves
$f3->route('GET /GetCompOffLeaves',
    function($f3) {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
        header('Access-Control-Allow-Headers: Content-Type');

        $employeeID = $f3->get('GET.employeeID');
        $leaveBalance = new ApplyLeaveMaster();
        $leaveBalance->employeeID = $employeeID;
        $leaveBalance->getCompOffLeaves();
    }
);