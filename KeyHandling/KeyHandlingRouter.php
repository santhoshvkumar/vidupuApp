<?php

// Submit Key Handover
$f3->route('POST /SubmitKeyHandover',
    function($f3) {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type');
        
        try {
            $data = json_decode($f3->get('BODY'), true);
            if (!$data) {
                throw new Exception("Invalid input parameters");
            }
            
            $keyHandling = new KeyHandlingComponent();
            $keyHandling->submitKeyHandover($data);
            
        } catch (Exception $e) {
            error_log("Submit Key Handover error: " . $e->getMessage());
            echo json_encode(array(
                "status" => "error",
                "message" => $e->getMessage()
            ));
        }
    }
);

// Get Key Handover History
$f3->route('POST /GetKeyHandoverHistory',
    function($f3) {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type');
        
        try {
            $data = json_decode($f3->get('BODY'), true);
            if (!$data || !isset($data['employeeID'])) {
                throw new Exception("Missing employeeID parameter");
            }
            
            $keyHandling = new KeyHandlingComponent();
            $keyHandling->getKeyHandoverHistory($data['employeeID']);
            
        } catch (Exception $e) {
            error_log("Get Key Handover History error: " . $e->getMessage());
            echo json_encode(array(
                "status" => "error",
                "message" => $e->getMessage()
            ));
        }
    }
); 