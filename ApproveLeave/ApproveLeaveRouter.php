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
            approvedLeave($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error Approved Leave",
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
            processRejectedLeave($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ), JSON_FORCE_OBJECT);
        }
    }
);

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

?>


