<?php
namespace EmployeePaySlip;

use Base;

class EmployeePaySlipRouter {
    public static function register(Base $f3) {
        $f3->route('POST /EmployeePaySlipDetails',
            function($f3) {
                header('Content-Type: application/json');
                $decoded_items = json_decode($f3->get('BODY'), true);
                if ($decoded_items !== null) {
                    EmployeePaySlip($decoded_items);
                } else {
                    echo json_encode([
                        "status" => "error",
                        "message_text" => "Invalid input parameters"
                    ], JSON_FORCE_OBJECT);
                }
            }
        );
    }
}