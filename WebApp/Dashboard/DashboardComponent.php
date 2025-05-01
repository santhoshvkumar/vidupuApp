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
            $data['totalEmployees'] = intval($row['total']);
    
            // 2. Today's check-ins
            $queryCheckInDetails = "SELECT COUNT(*) as checked_in FROM tblAttendance WHERE attendanceDate = CURDATE()";
            $result = mysqli_query($connect_var, $queryCheckInDetails);
            $row = mysqli_fetch_assoc($result);
            $data['checkedInToday'] = intval($row['checked_in']);
    
            // 3. Late check-ins
            $queryLateCheckInDetails = "SELECT COUNT(*) as late_checkin FROM tblAttendance WHERE checkInTime > '10:10:00' AND attendanceDate = CURDATE()";
            $result = mysqli_query($connect_var, $queryLateCheckInDetails);
            $row = mysqli_fetch_assoc($result);
            $data['lateCheckIns'] = intval($row['late_checkin']);
    
            // 4. Early check-outs
            $queryEarlyCheckOutDetails = "SELECT COUNT(*) as early_checkout FROM tblAttendance WHERE checkOutTime < '17:00:00' AND attendanceDate = CURDATE()";
            $result = mysqli_query($connect_var, $queryEarlyCheckOutDetails);
            $row = mysqli_fetch_assoc($result);
            $data['earlyCheckOuts'] = intval($row['early_checkout']);
    
            // 5. Employees on leave
            $queryLeaveDetails = "SELECT COUNT(*) as on_leave FROM tblApplyLeave WHERE CURDATE() BETWEEN fromDate AND toDate";
            $result = mysqli_query($connect_var, $queryLeaveDetails);
            $row = mysqli_fetch_assoc($result);
            $data['onLeave'] = intval($row['on_leave']);
    
            // 6. Calculate absentees
            $data['absentees'] = intval($data['totalEmployees']) - (intval($data['checkedInToday']) + intval($data['onLeave']));
            
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
    public function DashboardAttendanceForHeadOffice() {
        include('config.inc');
        header('Content-Type: application/json');    
        try {
            $data = [];   
                      
            // 1. Total active employees in Head Office
            $queryActiveEmployeeinHO = "SELECT s.sectionName, COUNT(e.employeeID) AS totalEmployeesinHO FROM tblEmployee e JOIN tblAssignedSection a ON e.employeeID = a.employeeID JOIN tblSection s ON a.sectionID = s.sectionID WHERE a.isActive = 1
            GROUP BY s.sectionName ORDER BY s.sectionName";
            $result = mysqli_query($connect_var, $queryActiveEmployeeinHO);
            $row = mysqli_fetch_assoc($result);
            $data['totalEmployeesinHO'] = isset($row['totalEmployeesinHO']) ? intval($row['totalEmployeesinHO']) : 0;

            // 2. Today's check-ins in Head Office
            $queryCheckInDetailsinHO = "SELECT s.sectionName, COUNT(DISTINCT att.employeeID) AS checked_in FROM tblSection s JOIN tblAssignedSection a ON s.sectionID = a.sectionID JOIN tblEmployee e ON a.employeeID = e.employeeID LEFT JOIN tblAttendance att ON e.employeeID = att.employeeID AND att.attendanceDate = CURDATE() WHERE a.isActive = 1 GROUP BY s.sectionName ORDER BY s.sectionName";
            $result = mysqli_query($connect_var, $queryCheckInDetailsinHO);
            $row = mysqli_fetch_assoc($result);
            $data['checkedInTodayinHO'] = isset($row['checked_in']) ? intval($row['checked_in']) : 0;

            // 3. Late check-ins in Head Office
            $queryLateCheckInDetailsinHO = "SELECT s.sectionName, COUNT(DISTINCT att.employeeID) AS late_checkin FROM tblSection s JOIN tblAssignedSection a ON s.sectionID = a.sectionID JOIN tblEmployee e ON a.employeeID = e.employeeID LEFT JOIN tblAttendance att ON e.employeeID = att.employeeID AND att.attendanceDate = CURDATE() AND att.checkInTime > '10:10:00' 
            WHERE a.isActive = 1 GROUP BY s.sectionName ORDER BY s.sectionName";
            $result = mysqli_query($connect_var, $queryLateCheckInDetailsinHO);
            $row = mysqli_fetch_assoc($result);
            $data['lateCheckInsinHO'] = isset($row['late_checkin']) ? intval($row['late_checkin']) : 0;

            // 4. Early check-outs in Head Office
            $queryEarlyCheckOutDetailsinHO = "SELECT s.sectionName, COUNT(DISTINCT att.employeeID) AS early_checkout FROM tblSection s JOIN tblAssignedSection a ON s.sectionID = a.sectionID 
            JOIN tblEmployee e ON a.employeeID = e.employeeID LEFT JOIN tblAttendance att ON e.employeeID = att.employeeID AND att.attendanceDate = CURDATE() AND att.checkOutTime < '17:00:00' AND att.checkOutTime IS NOT NULL WHERE a.isActive = 1 GROUP BY s.sectionName ORDER BY s.sectionName";
            $result = mysqli_query($connect_var, $queryEarlyCheckOutDetailsinHO);
            $row = mysqli_fetch_assoc($result);
            $data['earlyCheckOutsinHO'] = isset($row['early_checkout']) ? intval($row['early_checkout']) : 0;

            // 5. Employees on leave in Head Office
            $queryLeaveDetailsinHO = "SELECT s.sectionName, COUNT(DISTINCT l.employeeID) AS on_leave FROM tblSection s JOIN tblAssignedSection a ON s.sectionID = a.sectionID 
            JOIN tblEmployee e ON a.employeeID = e.employeeID LEFT JOIN tblApplyLeave l ON e.employeeID = l.employeeID AND CURDATE() BETWEEN l.fromDate AND l.toDate WHERE a.isActive = 1 GROUP BY s.sectionName ORDER BY s.sectionName";
            $result = mysqli_query($connect_var, $queryLeaveDetailsinHO);
            $row = mysqli_fetch_assoc($result);
            $data['onLeaveinHO'] = isset($row['on_leave']) ? intval($row['on_leave']) : 0;

            // 6. Calculate absentees in Head Office
            $data['absenteesinHO'] = intval($data['totalEmployeesinHO']) - (intval($data['checkedInTodayinHO']) + intval($data['onLeaveinHO']));

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

    public function DashboardGetAllSectionForGraph() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            
            $queryGetAllSection = "SELECT * FROM tblSection";

            $result = mysqli_query($connect_var, $queryGetAllSection);
            $sections[] = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $sections[] = $row;
            }

            echo json_encode([
                "status" => "success",
                "data" => $sections
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
function DashboardDetailsForHO() {
    $dashboardfordepartmentComponent = new DashboardComponent();
    $dashboardfordepartmentComponent->DashboardAttendanceForHeadOffice();
}

function DashboardGetAllSection() {
    $dashboardfordepartmentComponent = new DashboardComponent();
    $dashboardfordepartmentComponent->DashboardGetAllSectionForGraph();
}