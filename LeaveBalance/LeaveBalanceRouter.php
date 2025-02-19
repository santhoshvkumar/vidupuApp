<?php
namespace LeaveBalance;

require_once(__DIR__ . '/LeaveBalanceComponent.php');

$f3->route('POST /ApplyLeave',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            applyLeave($decoded_items);
        } else {
            echo json_encode(
                array(
                    "status" => "error Leave Balance",
                    "message_text" => "Invalid input parameters"
                ),
                JSON_FORCE_OBJECT
            );
        }
    }
);

$f3->route('GET /GetLeaveBalance',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            getLeaveBalance($decoded_items);
        } else {
            echo json_encode(
                array(
                    "status" => "error Leave Balance",
                    "message_text" => "Invalid input parameters"
                ),
                JSON_FORCE_OBJECT
            );
        }
    }
);

<<<<<<< HEAD
$f3->route('GET /leave-balance',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL && isset($decoded_items['employeeID'])) {
            $leaveBalance = new LeaveBalanceComponent();
            if($leaveBalance->loadEmployeeDetails($decoded_items)) {
                $leaveBalance->getLeaveBalanceInfo();
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message_text" => "Invalid input parameters"
                ), JSON_FORCE_OBJECT);
            }
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ), JSON_FORCE_OBJECT);
        }
    }
);

$f3->route('GET /leave-balance/@employeeID',
    function($f3, $params) {
        header('Content-Type: application/json');
        $data = array(
            'employeeID' => $params['employeeID']
        );
        $leaveBalance = new LeaveBalanceComponent();
        if($leaveBalance->loadEmployeeDetails($data)) {
            $leaveBalance->getLeaveBalanceInfo();
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ), JSON_FORCE_OBJECT);
=======
$f3->route('POST /GetLeaveHistory',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            getLeaveHistory($decoded_items);
        } else {
            echo json_encode(
                array(
                    "status" => "error Leave Balance",
                    "message_text" => "Invalid input parameters"
                ),
                JSON_FORCE_OBJECT
            );
>>>>>>> 782a4156724660228da06b05b7b7a8d371c71960
        }
    }
);
