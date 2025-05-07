<?php

/*****************   Generate Daily Attendance Report *******************/
$f3->route('GET /GenerateDailyAttendanceReport',
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
/*****************  End Generate Daily Attendance Report *****************/

?> 