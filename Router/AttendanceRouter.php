<?php

// Include the Attendance component with a relative path
require_once __DIR__ . '/../Component/AttendanceComponent.php';

// Instantiate the Attendance component
$attendanceComponent = new AttendanceComponent();

// Routes for Attendance CRUD operations

// Get all attendance records
$f3->route('GET /attendance', function($f3) use ($attendanceComponent) {
    $attendanceComponent->getAllAttendance($f3);
});

// Get attendance by employee ID
$f3->route('GET /attendance/employee/@empID', function($f3) use ($attendanceComponent) {
    $attendanceComponent->getAttendanceByEmployee($f3);
});

// Add a new attendance record
$f3->route('POST /attendance', function($f3) use ($attendanceComponent) {
    $attendanceComponent->addAttendance($f3);
});

// Update an attendance record by attendanceID
$f3->route('PUT /attendance/@attendanceID', function($f3) use ($attendanceComponent) {
    $attendanceComponent->updateAttendance($f3);
});

// Delete an attendance record by attendanceID
$f3->route('DELETE /attendance/@attendanceID', function($f3) use ($attendanceComponent) {
    $attendanceComponent->deleteAttendance($f3);
});
