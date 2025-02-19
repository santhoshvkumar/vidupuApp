<?php
namespace Attendance;

class AttendanceComponent {
    
    public function checkIn($f3) {
        global $connect_var;
        
        try {
            $data = json_decode($f3->get('BODY'), true);
            
            if (!isset($data['employeeID']) || !isset($data['attendanceDate'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Missing required fields'
                ]);
                return;
            }

            $employeeID = mysqli_real_escape_string($connect_var, $data['employeeID']);
            $attendanceDate = mysqli_real_escape_string($connect_var, $data['attendanceDate']);
            date_default_timezone_set('Asia/Kolkata'); // For Indian Standard Time
            $currentTime = date('H:i:s');

            // Check if already checked in for the day
            $checkQuery = "SELECT * FROM tblAttendance 
                         WHERE EmployeeID = '$employeeID' 
                         AND AttendanceDate = '$attendanceDate'";
            
            $checkResult = mysqli_query($connect_var, $checkQuery);
            
            if (mysqli_num_rows($checkResult) > 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Already checked in for today'
                ]);
                return;
            }

            // Insert check-in record
            $query = "INSERT INTO tblAttendance 
                     (EmployeeID, AttendanceDate, CheckIn) 
                     VALUES ('$employeeID', '$attendanceDate', '$currentTime')";
            
            if (mysqli_query($connect_var, $query)) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Check-in recorded successfully',
                    'checkInTime' => $currentTime
                ]);
            } else {
                throw new Exception(mysqli_error($connect_var));
            }

        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to record check-in: ' . $e->getMessage()
            ]);
        }
    }

    public function checkOut($f3) {
        global $connect_var;
        
        try {
            $data = json_decode($f3->get('BODY'), true);
            
            if (!isset($data['employeeID']) || !isset($data['attendanceDate'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Missing required fields'
                ]);
                return;
            }

            $employeeID = mysqli_real_escape_string($connect_var, $data['employeeID']);
            $attendanceDate = mysqli_real_escape_string($connect_var, $data['attendanceDate']);
            date_default_timezone_set('Asia/Kolkata'); // For Indian Standard Time
            $currentTime = date('H:i:s');

            // Check if check-in exists and check-out doesn't
            $checkQuery = "SELECT CheckIn FROM tblAttendance 
                         WHERE EmployeeID = '$employeeID' 
                         AND AttendanceDate = '$attendanceDate' 
                         AND CheckOut IS NULL";
            
            $checkResult = mysqli_query($connect_var, $checkQuery);
            
            if (mysqli_num_rows($checkResult) === 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No active check-in found for today or already checked out'
                ]);
                return;
            }

            $row = mysqli_fetch_assoc($checkResult);
            $checkInTime = $row['CheckIn'];

            // Calculate total hours using fully qualified DateTime
            $checkInDateTime = new \DateTime($checkInTime);
            $checkOutDateTime = new \DateTime($currentTime);
            $interval = $checkInDateTime->diff($checkOutDateTime);
            $totalHours = $interval->h + ($interval->i / 60) + ($interval->s / 3600);

            // Update record with check-out time and total hours
            $query = "UPDATE tblAttendance 
                     SET CheckOut = '$currentTime', 
                         TotalHours = $totalHours 
                     WHERE EmployeeID = '$employeeID' 
                     AND AttendanceDate = '$attendanceDate'";
            
            if (mysqli_query($connect_var, $query)) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Check-out recorded successfully',
                    'checkOutTime' => $currentTime,
                    'totalHours' => round($totalHours, 2)
                ]);
            } else {
                throw new \Exception(mysqli_error($connect_var));
            }

        } catch (\Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to record check-out: ' . $e->getMessage()
            ]);
        }
    }
}

echo date_default_timezone_get();
?>