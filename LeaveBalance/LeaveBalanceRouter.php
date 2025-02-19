<?php
namespace LeaveBalance;

require_once(__DIR__ . '/LeaveBalanceComponent.php');

class LeaveBalanceRouter {
    private $leaveBalanceComponent;

    public function __construct() {
        $this->leaveBalanceComponent = new LeaveBalanceComponent();
        
        // Define routes
        global $f3;
        $f3->route('POST /ApplyLeave', [$this, 'applyLeave']);
        $f3->route('GET /GetLeaveBalance', [$this, 'getLeaveBalance']);
        $f3->route('POST /GetLeaveHistory', [$this, 'getLeaveHistory']);
    }

    public function applyLeave($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            $this->leaveBalanceComponent->applyLeave($decoded_items);
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

    
    public function getLeaveHistory($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!$decoded_items == NULL) {
            $this->leaveBalanceComponent->getLeaveHistory($decoded_items);
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
}
?>
