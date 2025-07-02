<?php
class DashboardComponent{
    public $employeeID;
    public $employeeRole;
    public $currentmonth;
    public $previousmonth;
    public $sectionName;
    public $year;
    public $totalactiveemployeesinsection;
	public $totalcheckins;
	public $on_leave;
	public $late_checkin;
	public $early_checkout;
    public $branchID;
    public $branchName;
    public $currentDate;
    public $organisationID;

    public function loadDashboardAttendanceForHeadOffice(array $data) {
        error_log("Loading dashboard data with input: " . print_r($data, true));        
        // Ensure we're getting numeric values for month and year
        $this->currentmonth = isset($data['currentmonth']) ? intval($data['currentmonth']) : intval(date('m'));
        $this->sectionName = isset($data['sectionName']) ? trim($data['sectionName']) : '';
        $this->year = isset($data['year']) ? intval($data['year']) : intval(date('Y'));
        
        error_log("Processed values:");
        error_log("currentmonth: " . $this->currentmonth);
        error_log("sectionName: " . $this->sectionName);
        error_log("year: " . $this->year);
        
        // Validate the data
        if (empty($this->sectionName)) {
            error_log("Error: sectionName is empty");
            return false;
        }
        
        if ($this->currentmonth < 1 || $this->currentmonth > 12) {
            error_log("Error: Invalid month value: " . $this->currentmonth);
            return false;
        }
        
        if ($this->year < 2000 || $this->year > 2100) {
            error_log("Error: Invalid year value: " . $this->year);
            return false;
        }
        
        return true;
    }
    public function loadDashboardAttendanceDetails(array $data) {
        if (isset($data['branchID']) && isset($data['currentDate']) && isset($data['organisationID'])) {
            $this->branchID = $data['branchID'];
            $this->currentDate = $data['currentDate'];
            $this->organisationID = $data['organisationID'];
            return true;
        } else {
            error_log("Missing required parameters in loadDashboardAttendanceDetails: " . print_r($data, true));
            return false;
        }
    }
    public function loadDashboardAttendanceDetailsforAll(array $data) {
        $this->currentDate = $data['currentDate'];
        $this->organisationID = $data['organisationID'];
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
            $queryActiveEmployeeDetails =   "SELECT

    -- Total employees
    (select count(*) from tblEmployee tblE INNER JOIN tblmapEmp tblMap on tblMap.employeeID = tblE.employeeID WHERE tblMap.branchID=? and tblE.organisationID=? and tblE.isActive=1) AS totalEmployees,

   
    (SELECT count(*) FROM `tblAttendance` WHERE attendanceDate=?  and checkInBranchID=?) As checkedInToday,

    -- Late check-in (using branch-based logic like AttendanceOperationComponent)
    (SELECT count(*) FROM `tblAttendance` WHERE checkInBranchID=? and attendanceDate=? and isLateCheckIN='1') AS lateCheckin;

    -- Early check-out (using branch-based logic like AttendanceOperationComponent)
    (SELECT count(*) FROM `tblAttendance` WHERE checkInBranchID=? and attendanceDate=? and isEarlyCheckOut='1') AS earlyCheckout,

    -- On Leave
    (SELECT COUNT(*)
            FROM tblApplyLeave AS l
            JOIN tblmapEmp AS map ON l.employeeID = map.employeeID
            WHERE ? BETWEEN l.fromDate AND l.toDate 
            AND map.organisationID = ?
            AND map.branchID IN (?)
            AND l.status = 'Approved') AS onLeave,

    -- Logged-in devices
    (SELECT COUNT(*)
     FROM tblEmployee AS emp
     JOIN tblmapEmp AS map ON emp.employeeID = map.employeeID
     WHERE emp.deviceFingerprint IS NOT NULL 
       AND emp.deviceFingerprint <> ''
       AND emp.organisationID = ?
       AND map.branchID IN (?)
       AND emp.isActive = 1) AS loginnedDevices

FROM (SELECT 1) AS dummy;
";
            $debug_query = str_replace(
                ['?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?'],
                [   
                    "'" . $this->branchID . "'",
                    "'" . $this->organisationID . "'",
                    "'" . $this->currentDate . "'",
                    "'" . $this->branchID . "'",
                    "'" . $this->branchID . "'",
                    "'" . $this->currentDate . "'",
                    "'" . $this->branchID . "'",
                    "'" . $this->currentDate . "'",
                    "'" . $this->currentDate . "'", // for onLeave
                    "'" . $this->organisationID . "'", // for onLeave
                    "'" . $this->branchID . "'", // for onLeave
                    "'" . $this->organisationID . "'", // for loginnedDevices
                    "'" . $this->branchID . "'", // for loginnedDevices
                ],
                $queryActiveEmployeeDetails
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryActiveEmployeeDetails);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ssssssssssssss", 
                    $this->branchID, // for totalEmployees
                    $this->organisationID, // for totalEmployees
                    $this->currentDate, // for checkedInToday
                    $this->branchID, // for checkedInToday
                    $this->branchID,
                    $this->currentDate,
                    $this->branchID,
                    $this->currentDate, // for lateCheckin
                    $this->currentDate, // for earlyCheckout
                    $this->organisationID, // for earlyCheckout
                    $this->branchID,
                    $this->organisationID, // for onLeave
                    $this->branchID, // for onLeave
            );            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Execute failed: " . mysqli_stmt_error($stmt));
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            
            // Debug the result
            error_log("Query Result: " . print_r($row, true));
            
            if ($row) {                
                $data['totalEmployees'] = isset($row['totalEmployees']) ? intval($row['totalEmployees']) : 0;
                $data['checkedInToday'] = isset($row['checkedInToday']) ? intval($row['checkedInToday']) : 0;
                $data['lateCheckin'] = isset($row['lateCheckin']) ? intval($row['lateCheckin']) : 0;
                $data['earlyCheckout'] = isset($row['earlyCheckout']) ? intval($row['earlyCheckout']) : 0;
                $data['onLeave'] = isset($row['onLeave']) ? intval($row['onLeave']) : 0;
                $data['loginnedDevices'] = isset($row['loginnedDevices']) ? intval($row['loginnedDevices']) : 0;
                $data['absenteesinHO'] = $data['totalEmployees'] - ($data['checkedInToday'] + $data['onLeave']);
                // Debug final data
                error_log("Final Data: " . print_r($data, true));                
                echo json_encode([
                    "status" => "success",
                    "data" => $data
                ]);
            } else {
                error_log("No data found for section: " . $this->branchID);
                echo json_encode([
                    "status" => "error",
                    "message_text" => "No data found for the specified branch"
                ], JSON_FORCE_OBJECT);
            }
            
        } catch (Exception $e) {
            error_log("Error in DashboardAttendanceForHeadOffice: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
    public function DashboardAttendanceDetailsforAll() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
    
            // 1. Total active employees
            $queryActiveEmployeeDetails =   "SELECT
            -- Total employees
            (SELECT COUNT(DISTINCT emp.employeeID)
            FROM tblEmployee AS emp
            WHERE emp.organisationID = ?
            AND emp.isActive = 1) AS totalEmployees,

            -- Checked-in today
            (SELECT COUNT(*)
            FROM tblAttendance AS a
            JOIN tblmapEmp AS map ON a.employeeID = map.employeeID
            WHERE a.attendanceDate = ? 
            AND map.organisationID = ?) AS checkedInToday,

            -- Late check-in (using branch-based logic like AttendanceOperationComponent)
            (SELECT COUNT(*)
            FROM tblAttendance AS a
            JOIN tblBranch b ON a.checkInBranchID = b.branchID
            WHERE a.attendanceDate = ? 
            AND a.organisationID = ?
            AND a.checkInTime IS NOT NULL
            AND b.checkInTime IS NOT NULL
            AND a.checkInTime > b.checkInTime
            AND a.checkInBranchID NOT IN (55, 56)) AS lateCheckin,

            -- Early check-out (using branch-based logic like AttendanceOperationComponent)
            (SELECT COUNT(*)
            FROM tblAttendance AS a
            JOIN tblBranch b ON a.checkInBranchID = b.branchID
            WHERE a.attendanceDate = ? 
            AND a.organisationID = ?
            AND a.checkOutTime IS NOT NULL
            AND b.checkOutTime IS NOT NULL
            AND a.checkOutTime < b.checkOutTime
            AND a.checkInBranchID NOT IN (55, 56)) AS earlyCheckout,

            -- On leave
           (SELECT COUNT(*)
            FROM tblApplyLeave AS l
            JOIN tblmapEmp AS map ON l.employeeID = map.employeeID
            WHERE ? BETWEEN l.fromDate AND l.toDate 
            AND map.organisationID = ?
            AND l.status = 'Approved') AS onLeave,
 
            -- Logged-in devices
            (SELECT COUNT(*)
            FROM tblEmployee AS emp
            WHERE emp.deviceFingerprint IS NOT NULL 
            AND emp.deviceFingerprint <> '' 
            AND emp.organisationID = ?
            AND emp.isActive = 1) AS loginnedDevices
        FROM (SELECT 1) AS dummy;";
            $debug_query = str_replace(
                ['?', '?', '?', '?', '?', '?', '?', '?', '?', '?'],
                [   
                    "'" . $this->organisationID . "'",
                    "'" . $this->currentDate . "'",
                    "'" . $this->organisationID . "'",
                    "'" . $this->currentDate . "'",
                    "'" . $this->organisationID . "'",
                    "'" . $this->currentDate . "'",
                    "'" . $this->organisationID . "'",
                    "'" . $this->currentDate . "'",
                    "'" . $this->organisationID . "'",
                    "'" . $this->organisationID . "'",
                ],
                $queryActiveEmployeeDetails
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryActiveEmployeeDetails);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ssssssssss", 
                $this->organisationID,  // for checkedInToday
                $this->currentDate, // for checkedInToday
                $this->organisationID, // for lateCheckin
                $this->currentDate, // for lateCheckin
                $this->organisationID, // for earlyCheckout
                $this->currentDate, // for earlyCheckout
                $this->organisationID, // for onLeave
                $this->currentDate, // for onLeave
                $this->organisationID, // for loginnedDevices
                $this->organisationID, // for loginnedDevices
            );
                
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Execute failed: " . mysqli_stmt_error($stmt));
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            
            // Debug the result
            error_log("Query Result: " . print_r($row, true));
            
            if ($row) {                
                $data['totalEmployees'] = isset($row['totalEmployees']) ? intval($row['totalEmployees']) : 0;
                $data['checkedInToday'] = isset($row['checkedInToday']) ? intval($row['checkedInToday']) : 0;
                $data['lateCheckin'] = isset($row['lateCheckin']) ? intval($row['lateCheckin']) : 0;
                $data['earlyCheckout'] = isset($row['earlyCheckout']) ? intval($row['earlyCheckout']) : 0;
                $data['onLeave'] = isset($row['onLeave']) ? intval($row['onLeave']) : 0;
                $data['loginnedDevices'] = isset($row['loginnedDevices']) ? intval($row['loginnedDevices']) : 0;
                $data['absenteesinHO'] = $data['totalEmployees'] - ($data['checkedInToday'] + $data['onLeave']);
                // Debug final data
                error_log("Final Data: " . print_r($data, true));                
                echo json_encode([
                    "status" => "success",
                    "data" => $data
                ]);
            } else {
                error_log("No data found for section: " . $this->currentDate);
                echo json_encode([
                    "status" => "error",
                    "message_text" => "No data found for the specified date"
                ], JSON_FORCE_OBJECT);
            }
            
        } catch (Exception $e) {
            error_log("Error in DashboardAttendanceForHeadOffice: " . $e->getMessage());
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

            // Debug input values
            error_log("DashboardAttendanceForHeadOffice - Input values:");
            error_log("sectionName: " . $this->sectionName);
            error_log("currentmonth: " . $this->currentmonth);
            error_log("year: " . $this->year);

            // 1. Total active employees in Head Office
            $queryHOEmployeeAttendanceSectionWise = "
                SELECT 
                    (SELECT COUNT(DISTINCT e.employeeID)
                     FROM tblEmployee e
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     JOIN tblSection s ON a.sectionID = s.sectionID
                     WHERE a.isActive = 1
                     AND s.sectionName = ?) AS totalactiveemployeesinsection,

                    (SELECT COUNT(DISTINCT att.employeeID) 
                     FROM tblAttendance att
                     JOIN tblEmployee e ON att.employeeID = e.employeeID
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     JOIN tblSection s ON a.sectionID = s.sectionID
                     WHERE a.isActive = 1
                     AND s.sectionName = ?
                     AND MONTH(att.attendanceDate) = ?
                     AND YEAR(att.attendanceDate) = ?) AS totalcheckins,

                    (SELECT COUNT(DISTINCT att.employeeID) 
                     FROM tblAttendance att
                     JOIN tblEmployee e ON att.employeeID = e.employeeID
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     JOIN tblSection s ON a.sectionID = s.sectionID
                     WHERE att.checkInTime > '10:10:00'
                     AND MONTH(att.attendanceDate) = ?
                     AND YEAR(att.attendanceDate) = ?
                     AND a.isActive = 1
                     AND s.sectionName = ?) AS late_checkin,

                    (SELECT COUNT(DISTINCT att.employeeID) 
                     FROM tblAttendance att
                     JOIN tblEmployee e ON att.employeeID = e.employeeID
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     JOIN tblSection s ON a.sectionID = s.sectionID
                     WHERE att.checkOutTime < '17:00:00'
                     AND MONTH(att.attendanceDate) = ?
                     AND YEAR(att.attendanceDate) = ?
                     AND a.isActive = 1
                     AND s.sectionName = ?) AS early_checkout,

                    (SELECT COUNT(DISTINCT e.employeeID)
                     FROM tblApplyLeave l
                     JOIN tblEmployee e ON l.employeeID = e.employeeID
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     JOIN tblSection s ON a.sectionID = s.sectionID
                     WHERE MONTH(l.fromDate) = ?
                     AND YEAR(l.fromDate) = ?
                     AND a.isActive = 1
                     AND s.sectionName = ?) AS on_leave";

            // Debug the query with actual values
            $debug_query = str_replace(
                ['?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?'],
                [
                    "'" . $this->sectionName . "'",
                    "'" . $this->sectionName . "'",
                    $this->currentmonth,
                    $this->year,
                    $this->currentmonth,
                    $this->year,
                    "'" . $this->sectionName . "'",
                    $this->currentmonth,
                    $this->year,
                    "'" . $this->sectionName . "'",
                    $this->currentmonth,
                    $this->year,
                    "'" . $this->sectionName . "'"
                ],
                $queryHOEmployeeAttendanceSectionWise
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryHOEmployeeAttendanceSectionWise);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ssiisiiisiiis", 
                $this->sectionName,  // for first subquery
                $this->sectionName,  // for second subquery
                $this->currentmonth, // for second subquery
                $this->year,         // for second subquery
                $this->currentmonth, // for third subquery
                $this->year,         // for third subquery
                $this->sectionName,  // for third subquery
                $this->currentmonth, // for fourth subquery
                $this->year,         // for fourth subquery
                $this->sectionName,  // for fourth subquery
                $this->currentmonth, // for fifth subquery
                $this->year,         // for fifth subquery
                $this->sectionName   // for fifth subquery
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Execute failed: " . mysqli_stmt_error($stmt));
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            
            // Debug the result
            error_log("Query Result: " . print_r($row, true));
            
            if ($row) {
                $data['sectionName'] = $this->sectionName;
                $data['currentmonth'] = $this->currentmonth;
                $data['year'] = $this->year;
                $data['totalactiveemployeesinsection'] = isset($row['totalactiveemployeesinsection']) ? intval($row['totalactiveemployeesinsection']) : 0;
                $data['totalcheckins'] = isset($row['totalcheckins']) ? intval($row['totalcheckins']) : 0;
                $data['on_leave'] = isset($row['on_leave']) ? intval($row['on_leave']) : 0;
                $data['late_checkin'] = isset($row['late_checkin']) ? intval($row['late_checkin']) : 0;
                $data['early_checkout'] = isset($row['early_checkout']) ? intval($row['early_checkout']) : 0;
                $data['absenteesinHO'] = $data['totalactiveemployeesinsection'] - ($data['totalcheckins'] + $data['on_leave']);
                
                // Debug final data
                error_log("Final Data: " . print_r($data, true));
                
                echo json_encode([
                    "status" => "success",
                    "data" => $data
                ]);
            } else {
                error_log("No data found for section: " . $this->sectionName);
                echo json_encode([
                    "status" => "error",
                    "message_text" => "No data found for the specified section"
                ], JSON_FORCE_OBJECT);
            }
            
        } catch (Exception $e) {
            error_log("Error in DashboardAttendanceForHeadOffice: " . $e->getMessage());
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
} 
function DashboardAttendanceDetails($decoded_items) {
    $dashboardComponent = new DashboardComponent();
    if ($dashboardComponent->loadDashboardAttendanceDetails($decoded_items)) {
        $dashboardComponent->DashboardAttendanceDetails();  
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
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
function DashboardAttendanceDetailsforAll() {
    $dashboardComponent = new DashboardComponent();
    if ($dashboardComponent->loadDashboardAttendanceDetailsforAll()) {
        $dashboardComponent->DashboardAttendanceDetailsforAll();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}