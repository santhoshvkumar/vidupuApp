<?php

class DashboardComponent{
    public $employeeID;
    public $employeeRole;

    public function loadDashboardDetails(array $data){
        $this->employeeID = $data['employeeID'];
        $this->employeeRole = $data['employeeRole'];
        return true;
    }

    public function DashboardDetails() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            // Initialize an array to hold the results
            $data = [];
    
            // 1. Fetch all dashboard details
            $queryDashboardDetails = "SELECT * FROM tblDashboardDetails";
            $rsd = mysqli_query($connect_var, $queryDashboardDetails);
            $dashboardDetails = [];
            while ($row = mysqli_fetch_assoc($rsd)) {
                $dashboardDetails[] = $row;
            }
            $data['dashboardDetails'] = $dashboardDetails;    
            
    
            echo json_encode([
                "status" => "success",
                "data" => $data
            ]);
    
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
    

    public function DashboardAttendanceDetails() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
    
            // 1. Total active employees
            $queryActiveEmployeeDetails = "SELECT COUNT(*) as total FROM tblEmployee";
            $result = mysqli_query($connect_var, $queryActiveEmployeeDetails);
            $row = mysqli_fetch_assoc($result);
            $data['totalEmployees'] = $row['total'];
    
            // 2. Today's check-ins
            $queryCheckInDetails = "SELECT COUNT(*) as checked_in FROM tblAttendance WHERE attendanceDate = CURDATE()";
            $result = mysqli_query($connect_var, $queryCheckInDetails);
            $row = mysqli_fetch_assoc($result);
            $data['checkedInToday'] = $row['checked_in'];
    
            // 3. Late check-ins
            $queryLateCheckInDetails = "SELECT COUNT(*) as late_checkin FROM tblAttendance WHERE checkInTime > '10:10:00' AND attendanceDate = CURDATE()";
            $result = mysqli_query($connect_var, $queryLateCheckInDetails);
            $row = mysqli_fetch_assoc($result);
            $data['lateCheckIns'] = $row['late_checkin'];
    
            // 4. Early check-outs
            $queryEarlyCheckOutDetails = "SELECT COUNT(*) as early_checkout FROM tblAttendance WHERE checkOutTime < '17:00:00' AND attendanceDate = CURDATE()";
            $result = mysqli_query($connect_var, $queryEarlyCheckOutDetails);
            $row = mysqli_fetch_assoc($result);
            $data['earlyCheckOuts'] = $row['early_checkout'];
    
            // 5. Employees on leave
            $queryLeaveDetails = "SELECT COUNT(*) as on_leave FROM tblApplyLeave WHERE CURDATE() BETWEEN fromDate AND toDate";
            $result = mysqli_query($connect_var, $queryLeaveDetails);
            $row = mysqli_fetch_assoc($result);
            $data['onLeave'] = $row['on_leave'];
    
            // 6. Calculate absentees
            $data['absentees'] = $data['totalEmployees'] - ($data['checkedInToday'] + $data['onLeave']);
    
            echo json_encode([
                "status" => "success",
                "data" => $data
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
    
} // Close the DashboardComponent class

function DashboardDetails() {
    $dashboardComponent = new DashboardComponent();
    $dashboardComponent->DashboardAttendanceDetails();
}
