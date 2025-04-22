<?php
/*****************   Get Leave for Approval  *******************/
$f3->route('POST /GetLeavesforApproval',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            getLeavesforApproval($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error Getting Leave for Approval",
                "message_text" => "Invalid input parameters"
            ), JSON_FORCE_OBJECT);
        }
    }
);
/*****************  End Get Leave for Approval *****************/

/*****************  Approved Leave *****************/
$f3->route('POST /ApprovedLeave',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            // First try to process as comp off
            $leaveObject = new ApproveLeaveMaster();
            if($leaveObject->loadLeaveStatus($decoded_items)){
                if($leaveObject->processCompOffStatus()) {
                    return; // Already processed as comp off
                }
                // If not a comp off, process as regular leave
                $leaveObject->processLeaveStatus();
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message_text" => "Invalid input parameters"
                ), JSON_FORCE_OBJECT);
            }
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ), JSON_FORCE_OBJECT);
        }
    }
);
/*****************  End Approved Leave *****************/

/*****************  Rejected Leave *****************/
$f3->route('POST /RejectedLeave',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            // First try to process as comp off
            $leaveObject = new ApproveLeaveMaster();
            if($leaveObject->loadLeaveStatus($decoded_items)){
                if($leaveObject->processCompOffStatus()) {
                    return; // Already processed as comp off
                }
                // If not a comp off, process as regular leave
                $leaveObject->processLeaveStatus();
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message_text" => "Invalid input parameters"
                ), JSON_FORCE_OBJECT);
            }
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ), JSON_FORCE_OBJECT);
        }
    }
);
/*****************  End Rejected Leave *****************/

/*****************  Hold Leave *****************/
$f3->route('POST /HoldLeave',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            processHoldLeave($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ), JSON_FORCE_OBJECT);
        }
    }
);

/*****************  Approve Maternity Leave *****************/
$f3->route('POST /ApproveMaternityLeave',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            approveMaternityLeave($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ), JSON_FORCE_OBJECT);
        }
    }
);

/*****************  Get Approval History *****************/
$f3->route('POST /GetApprovalHistory',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            getApprovalHistory($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ), JSON_FORCE_OBJECT);
        }
    }
);
/*****************  End Get Approval History *****************/

?>


