<?php

$f3->route('POST /CreateOrganisation',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL)
            CreateOrganisation($decoded_items);
        else
            echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
);

$f3->route('POST /UpdateOrganisation',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL)
            UpdateOrganisation($decoded_items);
        else
            echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
);

$f3->route('POST /UpdateOrganisationStatus',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL)
            UpdateOrganisationStatus($decoded_items);
        else
            echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
);

$f3->route('POST /GetOrganisation',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL)
            GetOrganisation($decoded_items);
        else
            echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
);

$f3->route('GET /GetAllOrganisations',
    function($f3) {
        header('Content-Type: application/json');
        GetAllOrganisations();
    }
);
?>
