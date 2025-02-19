<?php
namespace LeaveHistory;

class LeaveHistoryRouter {
    private $leaveHistoryComponent;

    public function __construct() {
        $this->leaveHistoryComponent = new LeaveHistoryComponent();
        
        // Define routes
        global $f3;
        $f3->route('GET /leave-history', [$this, 'getAllLeaveHistory']);
        $f3->route('GET /leave-history/@employeeID', [$this, 'getLeaveHistoryByEmployee']);
        $f3->route('PUT /cancel-leave', [$this, 'cancelLeave']);
    }

    // Get all leave history
    public function getAllLeaveHistory($f3) {
        header('Content-Type: application/json');
        $this->leaveHistoryComponent->getAllLeaveHistory($f3);
    }

    // Get leave history for specific employee
    public function getLeaveHistoryByEmployee($f3, $params) {
        header('Content-Type: application/json');
        $employeeID = $params['employeeID'];
        $this->leaveHistoryComponent->getLeaveHistoryByEmployee($f3, $employeeID);
    }

    // Cancel leave
    public function cancelLeave($f3) {
        header('Content-Type: application/json');
        $this->leaveHistoryComponent->cancelLeave($f3);
    }
}
?>