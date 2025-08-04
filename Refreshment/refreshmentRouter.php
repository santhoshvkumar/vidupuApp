<?php

/*****************   Get Refreshment Allowances By Organisation ID  *******************/
$f3->route('POST /GetRefreshmentAllowancesByOrganisationID',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!is_null($decoded_items)) {
            $refreshmentObject = new RefreshmentMaster();
            $refreshmentObject->getRefreshmentAllowancesByOrganisationID($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ));
        }
    }
);
/*****************  End Get Refreshment Allowances By Organisation ID *****************/

/*****************   Calculate Refreshment Allowance  *******************/
$f3->route('POST /CalculateRefreshmentAllowance',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!is_null($decoded_items)) {
            $refreshmentObject = new RefreshmentMaster();
            $refreshmentObject->calculateRefreshmentAllowance($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ));
        }
    }   
);
/*****************  End Calculate Refreshment Allowance *****************/

/*****************   Approve Refreshment Allowance  *******************/
$f3->route('POST /ApproveRefreshmentAllowance',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!is_null($decoded_items)) {
            $refreshmentObject = new RefreshmentMaster();
            $refreshmentObject->approveRefreshmentAllowance($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ));
        }
    }   
);
/*****************  End Approve Refreshment Allowance *****************/

/*****************   Bulk Approve Refreshment Allowances  *******************/
$f3->route('POST /BulkApproveRefreshmentAllowances',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!is_null($decoded_items)) {
            $refreshmentObject = new RefreshmentMaster();
            $refreshmentObject->bulkApproveRefreshmentAllowances($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ));
        }
    }   
);
/*****************  End Bulk Approve Refreshment Allowances *****************/

/*****************   Reject Refreshment Allowance  *******************/
$f3->route('POST /RejectRefreshmentAllowance',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!is_null($decoded_items)) {
            $refreshmentObject = new RefreshmentMaster();
            $refreshmentObject->rejectRefreshmentAllowance($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ));
        }
    }   
);
/*****************  End Reject Refreshment Allowance *****************/
?> 