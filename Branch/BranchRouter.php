<?php
$f3->route('POST /CreateBranch',
    function($f3) {
        header('Content-Type: application/json');
        if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
            CreateBranch($_POST);
        } else {
            $decoded_items = json_decode($f3->get('BODY'), true);
            if(!$decoded_items == NULL)
                CreateBranch($decoded_items);
            else
                echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
        }
    }
);

$f3->route('POST /UpdateBranch',
    function($f3) {
        header('Content-Type: application/json');
        if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
            UpdateBranch($_POST);
        } else {
            $decoded_items = json_decode($f3->get('BODY'), true);
            if(!$decoded_items == NULL)
                UpdateBranch($decoded_items);
            else
                echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
        }
    }
);

$f3->route('POST /GetBranchDetailsByBranchID',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL)
            GetBranchDetailsByBranchID($decoded_items);
        else
            echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
);

$f3->route('POST /GetBranchesByOrganisation',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if(!$decoded_items == NULL)
            GetBranchesByOrganisation($decoded_items);
        else
            echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
);
?>
