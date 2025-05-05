<?php

class DashboardComponent{
    public $employeeID;
    public $employeeRole;
    public $currentmonth;
    public $previousmonth;
    public $sectionName;

    public function loadDashboardAttendanceForHeadOffice(array $data){
        $this->currentmonth = $data['currentmonth'];
        //$this->previousmonth = $data['previousmonth'];
        $this->sectionName = $data['sectionName'];
        return true;
    }

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
    public function DashboardAttendanceForHeadOffice(array $data) {
        include('config.inc');
        header('Content-Type: application/json');    
        try {       
            $data = []; 
            $currentmonth = date('mm');
            $previousmonth = date('mm', strtotime('-1 month'));
                      
            // 1. Total active employees in Head Office
            $queryHOEmployeeAttendanceSectionWise = "SELECT 
                s.sectionName,
                COUNT(DISTINCT e.employeeID) AS totalEmployeesinHO,
                COUNT(DISTINCT CASE 
                    WHEN MONTH(att.attendanceDate) = $this->currentmonth THEN e.employeeID 
                END) AS checked_in,
                COUNT(DISTINCT CASE 
                    WHEN MONTH(att.attendanceDate) = $this->currentmonth AND att.checkInTime > '10:10:00' THEN e.employeeID 
                END) AS late_checkin,
                COUNT(DISTINCT CASE 
                    WHEN MONTH(att.attendanceDate) = $this->currentmonth AND att.checkOutTime IS NOT NULL AND att.checkOutTime < '17:00:00' THEN e.employeeID 
                END) AS early_checkout,
                COUNT(DISTINCT CASE 
                    WHEN CURDATE() BETWEEN l.fromDate AND l.toDate THEN e.employeeID 
                END) AS on_leave
            FROM 
                tblSection s
                JOIN tblAssignedSection a ON s.sectionID = a.sectionID
                JOIN tblEmployee e ON a.employeeID = e.employeeID
                LEFT JOIN tblAttendance att ON e.employeeID = att.employeeID
                LEFT JOIN tblApplyLeave l ON e.employeeID = l.employeeID
            WHERE 
                a.isActive = 1
                AND s.sectionName = '$this->sectionName'
            GROUP BY 
                s.sectionName
            ORDER BY 
                s.sectionName;";

            $result = mysqli_query($connect_var, $queryHOEmployeeAttendanceSectionWise);
            $row = mysqli_fetch_assoc($result);
            
            if ($row) {
                //$data['employees'] = $row;
                $data['sectionName'] = $row['sectionName'];
                $data['currentmonth'] = $this->currentmonth;
                $data['totalEmployeesinHO'] = isset($row['totalEmployeesinHO']) ? intval($row['totalEmployeesinHO']) : 0;
                $data['checkedInTodayinHO'] = isset($row['checked_in']) ? intval($row['checked_in']) : 0;
                $data['onLeaveinHO'] = isset($row['on_leave']) ? intval($row['on_leave']) : 0;
                $data['absenteesinHO'] = $data['totalEmployeesinHO'] - ($data['checkedInTodayinHO'] + $data['onLeaveinHO']);
                $data['lateCheckIns'] = isset($row['late_checkin']) ? intval($row['late_checkin']) : 0;
                $data['earlyCheckOuts'] = isset($row['early_checkout']) ? intval($row['early_checkout']) : 0;
                echo json_encode([
                    "status" => "success",
                    "data" => $data
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message_text" => "No data found for the specified section"
                ], JSON_FORCE_OBJECT);
            }
            
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
            $sections = [];
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

function DashboardGetAllSection() {
    $dashboardfordepartmentComponent = new DashboardComponent();
    $dashboardfordepartmentComponent->DashboardGetAllSectionForGraph();
}
function DashboardDetailsForHO($decoded_items) {
    $dashboardComponent = new DashboardComponent();
    if ($dashboardComponent->loadDashboardAttendanceForHeadOffice($decoded_items)) {
        $dashboardComponent->DashboardAttendanceForHeadOffice($decoded_items);
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}