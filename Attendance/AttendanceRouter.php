<?php
namespace Attendance;

class AttendanceRouter {
    private $attendanceComponent;

    public function __construct() {
        $this->attendanceComponent = new \Attendance\AttendanceComponent();
    }

    // Initialize routes
    public function init($f3) {
        // Define routes
        $f3->route('POST /attendance/checkin', [$this, 'checkIn']);
        $f3->route('POST /attendance/checkout', [$this, 'checkOut']);
    }

    // Handle check-in request
    public function checkIn($f3) {
        header('Content-Type: application/json');
        $this->attendanceComponent->checkIn($f3);
    }

    // Handle check-out request
    public function checkOut($f3) {
        header('Content-Type: application/json');
        $this->attendanceComponent->checkOut($f3);
    }
}
?>