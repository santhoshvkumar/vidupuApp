<?php
/*****************   Apply For Advance  *******************/
$f3->route('POST /applyAdvance',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            applyForAdvance($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error Getting Leave for Approval",
                "message_text" => "Invalid input parameters"
            ), JSON_FORCE_OBJECT);
        }
    }
);

/*****************  End Apply For Advance *****************/
?>