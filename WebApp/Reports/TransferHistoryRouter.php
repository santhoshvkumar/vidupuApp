<?php
require_once 'TransferHistoryComponent.php';

$f3->route('POST /GetTransferHistory',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            $transferHistoryComponent = new TransferHistoryComponent();
            if ($transferHistoryComponent->loadTransferHistoryDetails($decoded_items)) {
                $transferHistoryComponent->getTransferHistory();
            } else {
                echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
            }
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
        }
    }
);
?> 