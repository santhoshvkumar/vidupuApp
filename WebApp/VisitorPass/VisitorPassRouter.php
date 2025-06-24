<?php

$f3->route('POST /CreateVisitorPass',
    function($f3) {
        header('Content-Type: application/json');
        if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
            CreateVisitorPass($_POST);
        } else {
            $decoded_items = json_decode($f3->get('BODY'), true);
            if(!$decoded_items == NULL)
                CreateVisitorPass($decoded_items);
            else
                echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
        }
    }
);

$f3->route('POST /UpdateVisitorPass',
    function($f3) {
        header('Content-Type: application/json');
        if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
            UpdateVisitorPass($_POST);
        } else {
            $decoded_items = json_decode($f3->get('BODY'), true);
            if(!$decoded_items == NULL)
                UpdateVisitorPass($decoded_items);
            else
                echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
        }
    }
);

$f3->route('POST /GetVisitorPass',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL)
            GetVisitorPass($decoded_items);
        else
            echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
);

$f3->route('POST /GetAllVisitorPasses',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL)
            GetAllVisitorPasses($decoded_items);
        else
            echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
);

$f3->route('GET /GetAllVisitorPasses',
    function($f3) {
        header('Content-Type: application/json');
        $organisationId = $f3->get('GET.organisationId') ?: '1';
        GetAllVisitorPasses(array('organisationId' => $organisationId));
    }
);

$f3->route('POST /DeleteVisitorPass',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL)
            DeleteVisitorPass($decoded_items);
        else
            echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
);

?> 