<?php
        
/*****************   Get Leave for Approval  *******************/
$f3->route('POST /GetLeaveforApproval',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            getLeaveforApproval($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error Approve Leave",
                "message_text" => "Invalid input parameters"
            ), JSON_FORCE_OBJECT);
        }
    }
);
/*****************  End Get Leave for Approval *****************/

?>