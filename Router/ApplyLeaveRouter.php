<?php
// ApplyLeaveRouter.php
class ApplyLeaveRouter {

    // Fetch all leave applications
    public function getAllLeaveApplications($f3) {
        $applyLeaveComponent = new ApplyLeaveComponent();
        $applyLeaveComponent->getAllLeaveApplications($f3); // Call the component method
    }

    // Fetch leave applications for a specific employee
    public function getLeaveApplicationsByEmployee($f3, $params) {
        $applyLeaveComponent = new ApplyLeaveComponent();
        $employeeID = $params['employeeID']; // Capture employee ID from URL parameter
        $applyLeaveComponent->getLeaveApplicationsByEmployee($f3, $employeeID); // Call the component method
    }
}
?>
