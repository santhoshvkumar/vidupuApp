<?php
$f3->route('POST /CreateEmployeeTemporaryAttendance',
function($f3) {
    header('Content-Type: application/json');
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
        CreateEmployeeTemporaryAttendance($_POST);
    } else {
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL)
            CreateEmployeeTemporaryAttendance($decoded_items);
        else
            echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
}
);