<?php
$f3->route('POST /DashboardAttendanceDetails',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items =  json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            DashboardAttendanceDetails($decoded_items);
        else
            echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
); 
$f3->route('POST /DashboardAttendanceForHeadOffice',

    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if($decoded_items != NULL) {
            $dashboardComponent = new DashboardComponent();
            if ($dashboardComponent->loadDashboardAttendanceForHeadOffice($decoded_items)) {
                $dashboardComponent->DashboardAttendanceForHeadOffice($decoded_items);
            } else {
                echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
            }
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    }
);
$f3->route('GET /DashboardGetAllSection',

    function($f3){
                DashboardGetAllSection();

    }
);
$f3->route('POST /DashboardAttendanceDetailsforAll',
    function($f3){
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if($decoded_items != NULL) {
            $dashboardComponent = new DashboardComponent();
            if ($dashboardComponent->loadDashboardAttendanceDetailsforAll($decoded_items)) {
                $dashboardComponent->DashboardAttendanceDetailsforAll();
            } else {
                echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
            }
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    }
);

$f3->route('POST /GetPendingLeaveRequestsList',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if($decoded_items != NULL) {
            GetPendingLeaveRequestsList($decoded_items);
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    }
);

$f3->route('POST /GetPendingLeaveRequestsListForAll',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if($decoded_items != NULL) {
            GetPendingLeaveRequestsListForAll($decoded_items);
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    }
);

$f3->route('POST /DashboardAttendanceDetailsEnhanced',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if($decoded_items != NULL) {
            DashboardAttendanceDetailsEnhanced($decoded_items);
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    }
);

$f3->route('POST /DashboardEmployeeListEnhanced',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if($decoded_items != NULL) {
            DashboardEmployeeListEnhanced($decoded_items);
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    }
);


?>