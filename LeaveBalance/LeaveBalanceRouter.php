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