<?php

/*****************   Generate Daily Attendance Report *******************/
$f3->route('GET /GenerateDailyReport',
    function($f3) {
        header('Content-Type: application/json');
        try {
            generateDailyReport();
        } catch(Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message" => $e->getMessage()
            ));
        }
    }
);

/*****************   Generate SAM Format Attendance Report *******************/
$f3->route('GET /GenerateSAMReport',
    function($f3) {
        header('Content-Type: application/json');
        try {
            generateSAMReport();
        } catch(Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message" => $e->getMessage()
            ));
        }
    }
);

?> 