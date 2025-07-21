<?php
$f3->route('GET /PendingLeaveRequests',
    function($f3) {
        header('Content-Type: application/json');
        
        // Get parameters from query string
        $organisationID = $f3->get('GET.organisationID');
        $today = $f3->get('GET.today');
        
        if ($organisationID != NULL) {
            $data = [
                'organisationID' => $organisationID,
                'today' => $today ?: date('Y-m-d')
            ];
            
            $pendingLeaveRequestsComponent = new PendingLeaveRequestsComponent();
            if ($pendingLeaveRequestsComponent->loadPendingLeaveRequests($data)) {
                $pendingLeaveRequestsComponent->GetPendingLeaveRequests();
            } else {
                echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
            }
        } else {
            echo json_encode(array("status" => "error", "message_text" => "organisationID parameter is required"), JSON_FORCE_OBJECT);
        }
    }
);
?> 