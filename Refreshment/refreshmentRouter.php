<?php

/*****************   Get Newspaper Details  *******************/
$f3->route('GET /NewspaperDetails',
    function($f3) {
        header('Content-Type: application/json');
        getNewspaperDetails();
    }
);
/*****************  End Get Newspaper Details *****************/

/*****************   Submit Newspaper Subscription  *******************/
$f3->route('POST /SubmitNewspaperAllowance',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!is_null($decoded_items)) {
            submitNewspaperSubscription($decoded_items);
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
            calculateRefreshmentAllowance($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ));
        }
    }
);
/*****************  End Calculate Refreshment Allowance *****************/

?>
