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

            // Escape variables for security
            $branchID = mysqli_real_escape_string($connect_var, $this->branchID);
            $currentDate = mysqli_real_escape_string($connect_var, $this->currentDate);
            $organisationID = mysqli_real_escape_string($connect_var, $this->organisationID);
    
            // 1. Total active employees
            $queryActiveEmployeeDetails =   "SELECT

                -- Total employees (filtered by branch)
                (select count(*) from tblEmployee tblE INNER JOIN tblmapEmp tblMap on tblMap.employeeID = tblE.employeeID WHERE tblE.organisationID='$organisationID' and tblE.isActive=1 AND tblMap.branchID='$branchID') AS totalEmployees,

                (SELECT count(*) FROM tblAttendance WHERE attendanceDate='$currentDate'  and checkInBranchID='$branchID') As checkedInToday,

                -- Late check-in (using branch-based logic like AttendanceOperationComponent)
                (SELECT count(*) FROM tblAttendance WHERE checkInBranchID='$branchID' and attendanceDate='$currentDate' and isLateCheckIN='1') AS lateCheckin,

                -- Early check-out (using branch-based logic like AttendanceOperationComponent)
                (SELECT COUNT(*)
                FROM tblAttendance AS a
                JOIN tblEmployee AS emp ON a.employeeID = emp.employeeID
                JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
                LEFT JOIN tblBranch AS b ON m.branchID = b.branchID AND m.branchID <> 1
                WHERE a.attendanceDate = '$currentDate'
                AND m.branchID = '$branchID'
                AND a.checkOutTime IS NOT NULL
                AND b.checkOutTime IS NOT NULL
                AND a.checkOutTime < b.checkOutTime
                AND m.branchID NOT IN (55, 56)
                AND (a.checkInBranchID = '$branchID' OR a.checkOutBranchID = '$branchID')) AS earlyCheckout,

                -- On Leave (filtered by branch)
                (SELECT COUNT(*)
                        FROM tblApplyLeave AS l
                        JOIN tblmapEmp AS map ON l.employeeID = map.employeeID
                        WHERE '$currentDate' BETWEEN l.fromDate AND l.toDate 
                        AND map.organisationID = '$organisationID'
                        AND map.branchID = '$branchID'
                        AND l.status = 'Approved') AS onLeave,
                -- Logged-in devices (filtered by branch)
                (SELECT COUNT(*)
                FROM tblEmployee AS emp
                JOIN tblmapEmp AS map ON emp.employeeID = map.employeeID
                WHERE emp.deviceFingerprint IS NOT NULL 
                AND emp.deviceFingerprint <> ''
                AND emp.organisationID = '$organisationID'
                AND map.branchID = '$branchID'
                AND map.branchID = '$branchID'
                AND emp.isActive = 1) AS loginnedDevices,

                -- Absentees (filtered by branch)
                (SELECT COUNT(DISTINCT e.employeeID)
                FROM tblEmployee e
                JOIN tblmapEmp m ON e.employeeID = m.employeeID
                WHERE e.isActive = 1
                AND m.organisationID = '$organisationID'
                AND m.branchID = '$branchID'
                AND m.branchID = '$branchID'
                AND e.employeeID NOT IN (
                    -- Not checked in (branch-specific)
                    SELECT DISTINCT a.employeeID 
                    FROM tblAttendance a 
                    JOIN tblmapEmp map ON a.employeeID = map.employeeID
                    WHERE a.attendanceDate = '$currentDate'
                    AND map.branchID = '$branchID'
                    UNION
                    -- Not on approved leave (branch-specific)
                    SELECT DISTINCT l.employeeID
                    FROM tblApplyLeave l
                    JOIN tblmapEmp map ON l.employeeID = map.employeeID
                    WHERE '$currentDate' BETWEEN l.fromDate AND l.toDate 
                    AND map.branchID = '$branchID'
                    AND l.status = 'Approved'
                    AND map.branchID = '$branchID'
                )) AS absentees,

                -- Pending leave requests (Yet To Be Approved)
                (SELECT COUNT(*)
                FROM tblApplyLeave l
                JOIN tblEmployee e ON l.employeeID = e.employeeID
                JOIN tblmapEmp m ON e.employeeID = m.employeeID
                WHERE l.status = 'Yet To Be Approved'
                AND e.organisationID = '$organisationID'
                AND m.branchID = '$branchID') AS pendingLeaveRequests

            FROM (SELECT 1) AS dummy;";

            error_log("Debug Query: " . $queryActiveEmployeeDetails);

            $rsd = mysqli_query($connect_var, $queryActiveEmployeeDetails);
            if (!$rsd) {
                error_log("Query failed: " . mysqli_error($connect_var));
                throw new Exception("Database query failed");
            }

            $row = mysqli_fetch_assoc($rsd);
            // Debug the result
            error_log("Query Result: " . print_r($row, true));
            
            // Debug: Show exactly which employees are being counted as early check-out
            $debugEarlyCheckoutQuery = "SELECT 
                emp.employeeName, emp.employeeID,
                a.checkInBranchID, a.checkOutBranchID, a.checkOutTime,
                b.branchName, b.checkOutTime as branchCheckOutTime
            FROM tblAttendance AS a
            JOIN tblEmployee AS emp ON a.employeeID = emp.employeeID
            JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
            LEFT JOIN tblBranch AS b ON m.branchID = b.branchID AND m.branchID <> 1
            WHERE a.attendanceDate = '$currentDate'
            AND m.branchID = '$branchID'
            AND a.checkOutTime IS NOT NULL
            AND b.checkOutTime IS NOT NULL
            AND a.checkOutTime < b.checkOutTime
            AND m.branchID NOT IN (55, 56)
            AND (a.checkInBranchID = '$branchID' OR a.checkOutBranchID = '$branchID')";
            
            $debugResult = mysqli_query($connect_var, $debugEarlyCheckoutQuery);
            if ($debugResult) {
                error_log("Debug Early Checkout Employees:");
                while ($debugRow = mysqli_fetch_assoc($debugResult)) {
                    error_log("Employee: " . $debugRow['employeeName'] . " (ID: " . $debugRow['employeeID'] . ") - CheckOut: " . $debugRow['checkOutTime'] . " vs Branch: " . $debugRow['branchCheckOutTime']);
                }
            }
            
            if ($row) {                
                $data['totalEmployees'] = isset($row['totalEmployees']) ? intval($row['totalEmployees']) : 0;
                $data['checkedInToday'] = isset($row['checkedInToday']) ? intval($row['checkedInToday']) : 0;
                $data['lateCheckin'] = isset($row['lateCheckin']) ? intval($row['lateCheckin']) : 0;
                $data['earlyCheckout'] = isset($row['earlyCheckout']) ? intval($row['earlyCheckout']) : 0;
                $data['onLeave'] = isset($row['onLeave']) ? intval($row['onLeave']) : 0;
                $data['loginnedDevices'] = isset($row['loginnedDevices']) ? intval($row['loginnedDevices']) : 0;
                $data['absenteesinHO'] = isset($row['absentees']) ? intval($row['absentees']) : 0;
                $data['pendingLeaveRequests'] = isset($row['pendingLeaveRequests']) ? intval($row['pendingLeaveRequests']) : 0;
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

            $organisationID = mysqli_real_escape_string($connect_var, $this->organisationID);
            $currentDate = mysqli_real_escape_string($connect_var, $this->currentDate);
    
            // 1. Total active employees
            $queryActiveEmployeeDetails =   "SELECT
            -- Total employees
            (SELECT COUNT(DISTINCT emp.employeeID)
            FROM tblEmployee AS emp
            WHERE emp.organisationID = '$organisationID'
            AND emp.isActive = 1) AS totalEmployees,

            -- Checked-in today
            (SELECT COUNT(*)
            FROM tblAttendance AS a
            JOIN tblmapEmp AS map ON a.employeeID = map.employeeID
            WHERE a.attendanceDate = '$currentDate' 
            AND map.organisationID = '$organisationID') AS checkedInToday,

            -- Late check-in (using branch-based logic like AttendanceOperationComponent)
            (SELECT COUNT(*) FROM tblAttendance tblA INNER JOIN tblEmployee tblE on tblE.employeeID = tblA.employeeID INNER JOIN tblBranch tblB on tblB.branchID = tblA.checkInBranchID WHERE  tblA.attendanceDate='$currentDate' and tblA.organisationID='$organisationID' and tblA.isLateCheckIN=1) AS lateCheckin,

            -- Early check-out (using branch-based logic like AttendanceOperationComponent)
            (SELECT COUNT(*)
            FROM tblAttendance AS a
            JOIN tblEmployee AS emp ON a.employeeID = emp.employeeID
            JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
            LEFT JOIN tblBranch AS b ON m.branchID = b.branchID AND m.branchID <> 1
            WHERE a.attendanceDate = '$currentDate' 
            AND m.organisationID = '$organisationID'
            AND a.checkOutTime IS NOT NULL
            AND b.checkOutTime IS NOT NULL
            AND a.checkOutTime < b.checkOutTime
            AND m.branchID NOT IN (55, 56)
            AND (a.checkInBranchID = '$organisationID' OR a.checkOutBranchID = '$organisationID')) AS earlyCheckout,

            -- On leave
           (SELECT COUNT(*)
            FROM tblApplyLeave AS l
            JOIN tblmapEmp AS map ON l.employeeID = map.employeeID
            WHERE '$currentDate' BETWEEN l.fromDate AND l.toDate 
            AND map.organisationID = '$organisationID'
            AND l.status = 'Approved') AS onLeave,
 
            -- Logged-in devices
            (SELECT COUNT(*)
            FROM tblEmployee AS emp
            WHERE emp.deviceFingerprint IS NOT NULL 
            AND emp.deviceFingerprint <> '' 
            AND emp.organisationID = '$organisationID'
            AND emp.isActive = 1) AS loginnedDevices,

            -- Pending leave requests (Yet To Be Approved)
            (SELECT COUNT(*)
            FROM tblApplyLeave l
            JOIN tblEmployee e ON l.employeeID = e.employeeID
            JOIN tblmapEmp m ON e.employeeID = m.employeeID
            WHERE l.status = 'Yet To Be Approved'
            AND e.organisationID = '$organisationID') AS pendingLeaveRequests
        FROM (SELECT 1) AS dummy;";
            error_log("Debug Query: " . $queryActiveEmployeeDetails);

            $result = mysqli_query($connect_var, $queryActiveEmployeeDetails);
            if (!$result) {
                error_log("Query failed: " . mysqli_error($connect_var));
                throw new Exception("Database query failed");
            }

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
                $data['pendingLeaveRequests'] = isset($row['pendingLeaveRequests']) ? intval($row['pendingLeaveRequests']) : 0;
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

            $sectionName = mysqli_real_escape_string($connect_var, $this->sectionName);
            $currentmonth = intval($this->currentmonth);
            $year = intval($this->year);
            // 1. Total active employees in Head Office
            $queryHOEmployeeAttendanceSectionWise = "
                SELECT 
                    (SELECT COUNT(DISTINCT e.employeeID)
                     FROM tblEmployee e
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     JOIN tblSection s ON a.sectionID = s.sectionID
                     WHERE a.isActive = 1
                     AND s.sectionName = '$sectionName') AS totalactiveemployeesinsection,

                    (SELECT COUNT(DISTINCT att.employeeID) 
                     FROM tblAttendance att
                     JOIN tblEmployee e ON att.employeeID = e.employeeID
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     JOIN tblSection s ON a.sectionID = s.sectionID
                     WHERE a.isActive = 1
                     AND s.sectionName = '$sectionName'
                     AND MONTH(att.attendanceDate) = $currentmonth
                     AND YEAR(att.attendanceDate) = $year) AS totalcheckins,

                    (SELECT COUNT(DISTINCT att.employeeID) 
                     FROM tblAttendance att
                     JOIN tblEmployee e ON att.employeeID = e.employeeID
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     JOIN tblSection s ON a.sectionID = s.sectionID
                     WHERE att.checkInTime > '10:10:00'
                     AND MONTH(att.attendanceDate) = $currentmonth
                     AND YEAR(att.attendanceDate) = $year
                     AND a.isActive = 1
                     AND s.sectionName = '$sectionName') AS late_checkin,

                    (SELECT COUNT(DISTINCT att.employeeID) 
                     FROM tblAttendance att
                     JOIN tblEmployee e ON att.employeeID = e.employeeID
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     JOIN tblSection s ON a.sectionID = s.sectionID
                     WHERE att.checkOutTime < '17:00:00'
                     AND MONTH(att.attendanceDate) = $currentmonth
                     AND YEAR(att.attendanceDate) = $year
                     AND a.isActive = 1
                     AND s.sectionName = '$sectionName') AS early_checkout,

                    (SELECT COUNT(DISTINCT e.employeeID)
                     FROM tblApplyLeave l
                     JOIN tblEmployee e ON l.employeeID = e.employeeID
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     JOIN tblSection s ON a.sectionID = s.sectionID
                     WHERE MONTH(l.fromDate) = $currentmonth
                     AND YEAR(l.fromDate) = $year
                     AND a.isActive = 1
                     AND s.sectionName = '$sectionName') AS on_leave";

            error_log("Debug Query: " . $queryHOEmployeeAttendanceSectionWise);

            $result = mysqli_query($connect_var, $queryHOEmployeeAttendanceSectionWise);
            if (!$result) {
                error_log("Query failed: " . mysqli_error($connect_var));
                throw new Exception("Database query failed");
            }

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

    public function GetPendingLeaveRequestsList() {
        include('config.inc');
        header('Content-Type: application/json');
        
        try {
            $branchID = mysqli_real_escape_string($connect_var, $this->branchID);
            $organisationID = mysqli_real_escape_string($connect_var, $this->organisationID);
            
            // Get detailed list of pending leave requests
            $queryPendingLeaves = "
                SELECT 
                    e.employeeName,
                    e.empID,
                    e.employeePhone,
                    e.Designation,
                    l.typeOfLeave,
                    b.branchName as locationName,
                    mng.employeeName AS managerName,
                    mng.empID AS managerID
                FROM tblApplyLeave l
                JOIN tblEmployee e ON l.employeeID = e.employeeID
                JOIN tblmapEmp m ON e.employeeID = m.employeeID
                JOIN tblBranch b ON m.branchID = b.branchID
                LEFT JOIN tblEmployee mng ON e.managerID = mng.employeeID
                WHERE l.status = 'Yet To Be Approved'
                AND e.organisationID = '$organisationID'
                AND m.branchID = '$branchID'
                ORDER BY l.createdOn DESC";
            
            $result = mysqli_query($connect_var, $queryPendingLeaves);
            if (!$result) {
                throw new Exception("Database query failed: " . mysqli_error($connect_var));
            }
            
            $pendingLeaves = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $pendingLeaves[] = $row;
            }
            
            echo json_encode([
                "status" => "success",
                "data" => $pendingLeaves
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }

    public function GetPendingLeaveRequestsListForAll() {
        include('config.inc');
        header('Content-Type: application/json');
        
        try {
            $organisationID = mysqli_real_escape_string($connect_var, $this->organisationID);
            
            // Get detailed list of pending leave requests for all branches
            $queryPendingLeaves = "
                SELECT 
                    e.employeeName,
                    e.empID,
                    e.employeePhone,
                    e.Designation,
                    l.typeOfLeave,
                    b.branchName as locationName,
                    mng.employeeName AS managerName,
                    mng.empID AS managerID
                FROM tblApplyLeave l
                JOIN tblEmployee e ON l.employeeID = e.employeeID
                JOIN tblmapEmp m ON e.employeeID = m.employeeID
                JOIN tblBranch b ON m.branchID = b.branchID
                LEFT JOIN tblEmployee mng ON e.managerID = mng.employeeID
                WHERE l.status = 'Yet To Be Approved'
                AND e.organisationID = '$organisationID'
                ORDER BY l.createdOn DESC";
            
            $result = mysqli_query($connect_var, $queryPendingLeaves);
            if (!$result) {
                throw new Exception("Database query failed: " . mysqli_error($connect_var));
            }
            
            $pendingLeaves = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $pendingLeaves[] = $row;
            }
            
            echo json_encode([
                "status" => "success",
                "data" => $pendingLeaves
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

function GetPendingLeaveRequestsList($decoded_items) {
    $dashboardComponent = new DashboardComponent();
    if ($dashboardComponent->loadDashboardAttendanceDetails($decoded_items)) {
        $dashboardComponent->GetPendingLeaveRequestsList();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}

function GetPendingLeaveRequestsListForAll($decoded_items) {
    $dashboardComponent = new DashboardComponent();
    if ($dashboardComponent->loadDashboardAttendanceDetailsforAll($decoded_items)) {
        $dashboardComponent->GetPendingLeaveRequestsListForAll();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}