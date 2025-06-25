<?php

/*****************   Get Newspaper Details  *******************/
$f3->route('GET /NewspaperDetails',
    function($f3) {
        header('Content-Type: application/json');
        getNewspaperDetails();
    }
);
/*****************  End Get Newspaper Details *****************/

/*****************   Get Newspaper History  *******************/
$f3->route('GET /NewspaperHistory',
    function($f3) {
        header('Content-Type: application/json');
        $employeeID = $f3->get('GET.employeeID');
        if (!empty($employeeID)) {
            $refreshmentObject = new RefreshmentMaster();
            $refreshmentObject->getNewspaperHistory($employeeID);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Employee ID is required"
            ));
        }
    }
);
/*****************  End Get Newspaper History *****************/

/*****************   Submit Newspaper Subscription  *******************/
$f3->route('POST /SubmitNewspaperAllowance',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!is_null($decoded_items)) {
            $refreshmentObject = new RefreshmentMaster();
            $refreshmentObject->submitNewspaperAllowance($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ));
        }
    }
);
/*****************  End Submit Newspaper Subscription *****************/

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

/*****************  Get Applied Newspaper Allowance Details *****************/
$f3->route('POST /GetAppliedNewspaperAllowanceDetails', function($f3) {
    $data = json_decode($f3->get('BODY'), true);
    getAppliedNewspaperAllowanceDetails($data);
});
/*****************  End Get Applied Newspaper Allowance Details *****************/
?>
