<?php
// ApplyLeaveRouter.php
namespace Router;

use Component\ApplyLeaveComponent;

class ApplyLeaveRouter {
    private $applyLeaveComponent;

    public function __construct() {
        $this->applyLeaveComponent = new ApplyLeaveComponent();
    }

    // Fetch all leave applications
    public function getAllLeaveApplications($f3) {
        $this->applyLeaveComponent->getAllLeaveApplications($f3);
    }

    // Fetch leave applications for a specific employee
    public function getLeaveApplicationsByEmployee($f3, $params) {
        $employeeID = $params['employeeID'];
        $this->applyLeaveComponent->getLeaveApplicationsByEmployee($f3, $employeeID);
    }

    // Check leave status
    public function checkLeaveStatus($f3) {
        $this->applyLeaveComponent->checkLeaveStatus($f3);
    }

    // Apply for leave
    public function applyLeave($f3) {
        $this->applyLeaveComponent->applyLeave($f3);
    }
}
?>
