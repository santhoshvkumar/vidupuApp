<?php

/*****************   Get Newspaper Allowances By Organisation ID  *******************/
$f3->route('POST /GetNewspaperAllowancesByOrganisationID',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!is_null($decoded_items)) {
            $newspaperObject = new NewspaperMaster();
            $newspaperObject->getNewspaperAllowancesByOrganisationID($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ));
        }
    }
);
/*****************  End Get Newspaper Allowances By Organisation ID *****************/

/*****************   Approve Newspaper Allowance  *******************/
$f3->route('POST /ApproveNewspaperAllowance',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!is_null($decoded_items)) {
            $newspaperObject = new NewspaperMaster();
            $newspaperObject->approveNewspaperAllowance($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ));
        }
    }   
);
/*****************  End Approve Newspaper Allowance *****************/

/*****************   Reject Newspaper Allowance  *******************/
$f3->route('POST /RejectNewspaperAllowance',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!is_null($decoded_items)) {
            $newspaperObject = new NewspaperMaster();
            $newspaperObject->rejectNewspaperAllowance($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ));
        }
    }   
);
/*****************  End Reject Newspaper Allowance *****************/

/*****************   Delete Newspaper Allowance  *******************/
$f3->route('POST /DeleteNewspaperAllowance',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!is_null($decoded_items)) {
            $newspaperObject = new NewspaperMaster();
            $newspaperObject->deleteNewspaperAllowance($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ));
        }
    }   
);
/*****************  End Delete Newspaper Allowance *****************/

/*****************   Bulk Approve Newspaper Allowances  *******************/
$f3->route('POST /BulkApproveNewspaperAllowances',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!is_null($decoded_items)) {
            $newspaperObject = new NewspaperMaster();
            $newspaperObject->bulkApproveNewspaperAllowances($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ));
        }
    }   
);
/*****************  End Bulk Approve Newspaper Allowances *****************/

?> 