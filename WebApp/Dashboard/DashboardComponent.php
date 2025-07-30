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
    public $listType;
    public $locationFilter;
    public $employeeType;

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
                (select count(*) from tblEmployee tblE INNER JOIN tblmapEmp tblMap on tblMap.employeeID = tblE.employeeID WHERE tblE.organisationID='$organisationID' and tblE.isActive=1 AND tblE.isTemporary=0 AND tblMap.branchID='$branchID') AS totalEmployees,

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

                -- Absentees (filtered by branch) - excluding pending leave requests
                (SELECT COUNT(*)
                FROM tblEmployee AS emp
                LEFT JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
                LEFT JOIN tblBranch AS b ON m.branchID = b.branchID
                LEFT JOIN tblAssignedSection AS assign ON emp.employeeID = assign.employeeID
                LEFT JOIN tblSection AS sec ON assign.sectionID = sec.sectionID
                LEFT JOIN tblAttendance AS att 
                    ON emp.employeeID = att.employeeID 
                    AND DATE(att.attendanceDate) = '$currentDate'
                WHERE emp.isActive = 1
                AND m.organisationID = '$organisationID'
                AND m.branchID = '$branchID'
                AND att.checkInTime IS NULL
                AND emp.employeeID NOT IN (
                    SELECT employeeID
                    FROM tblApplyLeave
                    WHERE status = 'Approved' AND employeeID <> 888
                        AND '$currentDate' BETWEEN fromDate AND toDate
                )
                AND emp.employeeID NOT IN (
                    SELECT employeeID
                    FROM tblApplyLeave
                    WHERE status = 'Yet To Be Approved' AND employeeID <> 888
                        AND '$currentDate' BETWEEN fromDate AND toDate
                )) AS absentees,

                -- Pending leave requests (Yet To Be Approved)
                (SELECT COUNT(*)
                FROM tblApplyLeave l
                JOIN tblEmployee e ON l.employeeID = e.employeeID
                JOIN tblmapEmp m ON e.employeeID = m.employeeID
                WHERE l.status = 'Yet To Be Approved'
                AND e.organisationID = '$organisationID'
                AND m.branchID = '$branchID'
                AND '$currentDate' BETWEEN l.fromDate AND l.toDate
                ) AS pendingLeaveRequests,

                -- Temporary staff count (filtered by branch)
                (SELECT COUNT(*)
                FROM tblEmployee tblE 
                INNER JOIN tblmapEmp tblMap ON tblMap.employeeID = tblE.employeeID 
                WHERE tblE.organisationID = '$organisationID' 
                AND tblE.isActive = 1 
                AND tblE.isTemporary = 1 
                AND tblMap.branchID = '$branchID') AS totalTemporaryStaff,

                -- Temporary staff checked in today
                (SELECT COUNT(*)
                FROM tblAttendance a
                JOIN tblEmployee e ON a.employeeID = e.employeeID
                JOIN tblmapEmp m ON e.employeeID = m.employeeID
                WHERE a.attendanceDate = '$currentDate'
                AND a.checkInBranchID = '$branchID'
                AND e.isTemporary = 1
                AND e.isActive = 1) AS temporaryStaffCheckedIn

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
                $data['totalTemporaryStaff'] = isset($row['totalTemporaryStaff']) ? intval($row['totalTemporaryStaff']) : 0;
                $data['temporaryStaffCheckedIn'] = isset($row['temporaryStaffCheckedIn']) ? intval($row['temporaryStaffCheckedIn']) : 0;
                
                // Calculate actual absences (excluding pending leave requests)
                $data['actualAbsent'] = $data['absenteesinHO'];
                
                // Calculate total absent (including pending leave requests) for display
                $data['totalAbsent'] = $data['actualAbsent'] + $data['pendingLeaveRequests'];
                
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
            AND emp.isActive = 1
            AND emp.isTemporary = 0) AS totalEmployees,

            -- Temporary staff count (organization-wide)
            (SELECT COUNT(DISTINCT emp.employeeID)
            FROM tblEmployee AS emp
            WHERE emp.organisationID = '$organisationID'
            AND emp.isActive = 1
            AND emp.isTemporary = 1) AS totalTemporaryStaff,

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

            -- Pending leave requests (Yet To Be Approved) for all branches
            (SELECT COUNT(*)
            FROM tblApplyLeave l
            JOIN tblEmployee e ON l.employeeID = e.employeeID
            JOIN tblmapEmp m ON e.employeeID = m.employeeID
            WHERE l.status = 'Yet To Be Approved'
            AND e.organisationID = '$organisationID'
            AND '$currentDate' BETWEEN l.fromDate AND l.toDate
            ) AS pendingLeaveRequests,

            -- Absentees (for all branches) - excluding pending leave requests
            (SELECT COUNT(*)
            FROM tblEmployee AS emp
            LEFT JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
            LEFT JOIN tblBranch AS b ON m.branchID = b.branchID
            LEFT JOIN tblAssignedSection AS assign ON emp.employeeID = assign.employeeID
            LEFT JOIN tblSection AS sec ON assign.sectionID = sec.sectionID
            LEFT JOIN tblAttendance AS att 
                ON emp.employeeID = att.employeeID 
                AND DATE(att.attendanceDate) = '$currentDate'
            WHERE emp.isActive = 1
            AND emp.organisationID = '$organisationID'
            AND att.checkInTime IS NULL
            AND emp.employeeID NOT IN (
                SELECT employeeID
                FROM tblApplyLeave
                WHERE status = 'Approved' AND employeeID <> 888
                    AND '$currentDate' BETWEEN fromDate AND toDate
            )
            AND emp.employeeID NOT IN (
                SELECT employeeID
                FROM tblApplyLeave
                WHERE status = 'Yet To Be Approved' AND employeeID <> 888
                    AND '$currentDate' BETWEEN fromDate AND toDate
            )) AS absentees
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
                $data['totalTemporaryStaff'] = isset($row['totalTemporaryStaff']) ? intval($row['totalTemporaryStaff']) : 0;
                
                // Get pending leave count
                $data['pendingLeaveRequests'] = isset($row['pendingLeaveRequests']) ? intval($row['pendingLeaveRequests']) : 0;
                
                // Calculate absent count using the improved query
                $data['absenteesinHO'] = isset($row['absentees']) ? intval($row['absentees']) : 0;
                
                // Actual absent is now directly from the query
                $data['actualAbsent'] = $data['absenteesinHO'];
                
                // Calculate total absent (including pending leave requests) for display
                $data['totalAbsent'] = $data['actualAbsent'] + $data['pendingLeaveRequests'];
                
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
                     AND e.isTemporary = 0
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

    public function loadDashboardAttendanceDetailsEnhanced(array $data) {
        error_log("🗓️ Enhanced API Date Debug - Received data: " . print_r($data, true));
        
        if (isset($data['currentDate']) && isset($data['organisationID'])) {
            $this->currentDate = $data['currentDate'];
            $this->organisationID = $data['organisationID'];
            $this->branchID = isset($data['branchID']) ? $data['branchID'] : '';
            
            error_log("📅 Enhanced API Date processed: " . $this->currentDate);
            error_log("🏢 Enhanced API Org: " . $this->organisationID);
            error_log("🏢 Enhanced API Branch: " . $this->branchID);
            
            return true;
        } else {
            error_log("Missing required parameters in loadDashboardAttendanceDetailsEnhanced: " . print_r($data, true));
            return false;
        }
    }

    public function DashboardAttendanceDetailsEnhanced() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];

            $organisationID = mysqli_real_escape_string($connect_var, $this->organisationID);
            $currentDate = mysqli_real_escape_string($connect_var, $this->currentDate);
            $branchID = mysqli_real_escape_string($connect_var, $this->branchID);
            
            error_log("Enhanced Dashboard - Branch Filter: " . ($branchID ? $branchID : 'All Branches'));
    
            // Enhanced query that splits HO vs Branch and Permanent vs Temporary
            // If branchID is provided, filter by that specific branch, otherwise show all
            if (!empty($branchID)) {
                error_log("Applying branch filter: branchID = $branchID");
                // For specific branch, show total counts only (no HO/Branch split)
                $queryEnhancedDetails = "SELECT
                    -- ACTIVE EMPLOYEES (total for selected branch)
                    (SELECT COUNT(DISTINCT emp.employeeID)
                    FROM tblEmployee AS emp
                    JOIN tblmapEmp AS map ON emp.employeeID = map.employeeID
                    WHERE emp.organisationID = '$organisationID'
                    AND emp.isActive = 1
                    AND emp.isTemporary = 0
                    AND map.branchID = '$branchID') AS hoEmployeesPermanent,

                    (SELECT COUNT(DISTINCT emp.employeeID)
                    FROM tblEmployee AS emp
                    JOIN tblmapEmp AS map ON emp.employeeID = map.employeeID
                    WHERE emp.organisationID = '$organisationID'
                    AND emp.isActive = 1
                    AND emp.isTemporary = 1
                    AND map.branchID = '$branchID') AS hoEmployeesTemporary,

                    -- Set branch counts to 0 to show only total
                    0 AS branchEmployeesPermanent,
                    0 AS branchEmployeesTemporary,

                    -- CHECKED IN (total for selected branch)
                    (SELECT COUNT(*)
                    FROM tblAttendance AS a
                    JOIN tblmapEmp AS map ON a.employeeID = map.employeeID
                    WHERE a.attendanceDate = '$currentDate' 
                    AND map.organisationID = '$organisationID'
                    AND map.branchID = '$branchID') AS hoCheckedIn,

                    0 AS branchCheckedIn,

                    -- LATE CHECK IN (total for selected branch)
                    (SELECT COUNT(*)
                    FROM tblAttendance AS a
                    JOIN tblmapEmp AS map ON a.employeeID = map.employeeID
                    WHERE a.attendanceDate = '$currentDate'
                    AND map.organisationID = '$organisationID'
                    AND a.isLateCheckIN = 1
                    AND map.branchID = '$branchID') AS hoLateCheckin,

                    0 AS branchLateCheckin,

                    -- EARLY CHECK OUT (total for selected branch)
                    (SELECT COUNT(*)
                    FROM tblAttendance AS a
                    JOIN tblEmployee AS emp ON a.employeeID = emp.employeeID
                    JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
                    LEFT JOIN tblBranch AS b ON m.branchID = b.branchID
                    WHERE a.attendanceDate = '$currentDate' 
                    AND m.organisationID = '$organisationID'
                    AND m.branchID = '$branchID'
                    AND a.checkOutTime IS NOT NULL
                    AND b.checkOutTime IS NOT NULL
                    AND a.checkOutTime < b.checkOutTime) AS hoEarlyCheckout,

                    0 AS branchEarlyCheckout,

                    -- ON LEAVE (total for selected branch)
                    (SELECT COUNT(*)
                    FROM tblApplyLeave AS l
                    JOIN tblmapEmp AS map ON l.employeeID = map.employeeID
                    WHERE '$currentDate' BETWEEN l.fromDate AND l.toDate 
                    AND map.organisationID = '$organisationID'
                    AND map.branchID = '$branchID'
                    AND l.status = 'Approved') AS hoOnLeave,

                    0 AS branchOnLeave,

                    -- ABSENCE (total for selected branch)
                    (SELECT COUNT(*)
                    FROM tblEmployee AS emp
                    LEFT JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
                    LEFT JOIN tblAttendance AS att 
                        ON emp.employeeID = att.employeeID 
                        AND DATE(att.attendanceDate) = '$currentDate'
                    WHERE emp.isActive = 1
                    AND m.organisationID = '$organisationID'
                    AND m.branchID = '$branchID'
                    AND att.checkInTime IS NULL
                    AND emp.employeeID NOT IN (
                        SELECT employeeID
                        FROM tblApplyLeave
                        WHERE status IN ('Approved', 'Yet To Be Approved') AND employeeID <> 888
                            AND '$currentDate' BETWEEN fromDate AND toDate
                    )) AS hoAbsent,

                    0 AS branchAbsent,

                    -- PENDING LEAVE (total for selected branch)
                    (SELECT COUNT(*)
                    FROM tblApplyLeave l
                    JOIN tblEmployee e ON l.employeeID = e.employeeID
                    JOIN tblmapEmp m ON e.employeeID = m.employeeID
                    WHERE l.status = 'Yet To Be Approved'
                    AND e.organisationID = '$organisationID'
                    AND m.branchID = '$branchID'
                    AND '$currentDate' BETWEEN l.fromDate AND l.toDate
                    ) AS hoPendingLeave,

                    0 AS branchPendingLeave

                FROM (SELECT 1) AS dummy;";
            } else {
                error_log("No branch filter applied - showing all branches");
                // Original query for all branches
                $queryEnhancedDetails = "SELECT
                -- ACTIVE EMPLOYEES BREAKDOWN
                -- HO Permanent Employees
                (SELECT COUNT(DISTINCT emp.employeeID)
                FROM tblEmployee AS emp
                JOIN tblmapEmp AS map ON emp.employeeID = map.employeeID
                WHERE emp.organisationID = '$organisationID'
                AND emp.isActive = 1
                AND emp.isTemporary = 0
                AND map.branchID = 1) AS hoEmployeesPermanent,

                -- HO Temporary Employees  
                (SELECT COUNT(DISTINCT emp.employeeID)
                FROM tblEmployee AS emp
                JOIN tblmapEmp AS map ON emp.employeeID = map.employeeID
                WHERE emp.organisationID = '$organisationID'
                AND emp.isActive = 1
                AND emp.isTemporary = 1
                AND map.branchID = 1) AS hoEmployeesTemporary,

                -- Branch Permanent Employees (excluding HO)
                (SELECT COUNT(DISTINCT emp.employeeID)
                FROM tblEmployee AS emp
                JOIN tblmapEmp AS map ON emp.employeeID = map.employeeID
                WHERE emp.organisationID = '$organisationID'
                AND emp.isActive = 1
                AND emp.isTemporary = 0
                AND map.branchID != 1) AS branchEmployeesPermanent,

                -- Branch Temporary Employees (excluding HO)
                (SELECT COUNT(DISTINCT emp.employeeID)
                FROM tblEmployee AS emp
                JOIN tblmapEmp AS map ON emp.employeeID = map.employeeID
                WHERE emp.organisationID = '$organisationID'
                AND emp.isActive = 1
                AND emp.isTemporary = 1
                AND map.branchID != 1) AS branchEmployeesTemporary,

                -- CHECKED IN BREAKDOWN
                -- HO Checked In Today
                (SELECT COUNT(*)
                FROM tblAttendance AS a
                JOIN tblmapEmp AS map ON a.employeeID = map.employeeID
                WHERE a.attendanceDate = '$currentDate' 
                AND map.organisationID = '$organisationID'
                AND (a.checkInBranchID = 1 OR map.branchID = 1)) AS hoCheckedIn,

                -- Branch Checked In Today (excluding HO)
                (SELECT COUNT(*)
                FROM tblAttendance AS a
                JOIN tblmapEmp AS map ON a.employeeID = map.employeeID
                WHERE a.attendanceDate = '$currentDate' 
                AND map.organisationID = '$organisationID'
                AND a.checkInBranchID != 1 
                AND map.branchID != 1) AS branchCheckedIn,

                -- LATE CHECK IN BREAKDOWN
                -- HO Late Check In
                (SELECT COUNT(*)
                FROM tblAttendance AS a
                JOIN tblmapEmp AS map ON a.employeeID = map.employeeID
                WHERE a.attendanceDate = '$currentDate'
                AND map.organisationID = '$organisationID'
                AND a.isLateCheckIN = 1
                AND (a.checkInBranchID = 1 OR map.branchID = 1)) AS hoLateCheckin,

                -- Branch Late Check In (excluding HO)
                (SELECT COUNT(*)
                FROM tblAttendance AS a
                JOIN tblmapEmp AS map ON a.employeeID = map.employeeID
                WHERE a.attendanceDate = '$currentDate'
                AND map.organisationID = '$organisationID'
                AND a.isLateCheckIN = 1
                AND a.checkInBranchID != 1
                AND map.branchID != 1) AS branchLateCheckin,

                -- EARLY CHECK OUT BREAKDOWN
                -- HO Early Check Out
                (SELECT COUNT(*)
                FROM tblAttendance AS a
                JOIN tblEmployee AS emp ON a.employeeID = emp.employeeID
                JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
                LEFT JOIN tblBranch AS b ON m.branchID = b.branchID
                WHERE a.attendanceDate = '$currentDate' 
                AND m.organisationID = '$organisationID'
                AND m.branchID = 1
                AND a.checkOutTime IS NOT NULL
                AND b.checkOutTime IS NOT NULL
                AND a.checkOutTime < b.checkOutTime) AS hoEarlyCheckout,

                -- Branch Early Check Out (excluding HO)
                (SELECT COUNT(*)
                FROM tblAttendance AS a
                JOIN tblEmployee AS emp ON a.employeeID = emp.employeeID
                JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
                LEFT JOIN tblBranch AS b ON m.branchID = b.branchID
                WHERE a.attendanceDate = '$currentDate' 
                AND m.organisationID = '$organisationID'
                AND m.branchID != 1
                AND a.checkOutTime IS NOT NULL
                AND b.checkOutTime IS NOT NULL
                AND a.checkOutTime < b.checkOutTime
                AND m.branchID NOT IN (55, 56)) AS branchEarlyCheckout,

                -- ON LEAVE BREAKDOWN
                -- HO On Leave
                (SELECT COUNT(*)
                FROM tblApplyLeave AS l
                JOIN tblmapEmp AS map ON l.employeeID = map.employeeID
                WHERE '$currentDate' BETWEEN l.fromDate AND l.toDate 
                AND map.organisationID = '$organisationID'
                AND map.branchID = 1
                AND l.status = 'Approved') AS hoOnLeave,

                -- Branch On Leave (excluding HO)
                (SELECT COUNT(*)
                FROM tblApplyLeave AS l
                JOIN tblmapEmp AS map ON l.employeeID = map.employeeID
                WHERE '$currentDate' BETWEEN l.fromDate AND l.toDate 
                AND map.organisationID = '$organisationID'
                AND map.branchID != 1
                AND l.status = 'Approved') AS branchOnLeave,

                -- ABSENCE BREAKDOWN
                -- HO Absentees (excluding approved and pending leaves)
                (SELECT COUNT(*)
                FROM tblEmployee AS emp
                LEFT JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
                LEFT JOIN tblAttendance AS att 
                    ON emp.employeeID = att.employeeID 
                    AND DATE(att.attendanceDate) = '$currentDate'
                WHERE emp.isActive = 1
                AND m.organisationID = '$organisationID'
                AND m.branchID = 1
                AND att.checkInTime IS NULL
                AND emp.employeeID NOT IN (
                    SELECT employeeID
                    FROM tblApplyLeave
                    WHERE status IN ('Approved', 'Yet To Be Approved') AND employeeID <> 888
                        AND '$currentDate' BETWEEN fromDate AND toDate
                )) AS hoAbsent,

                -- Branch Absentees (excluding HO and approved/pending leaves)
                (SELECT COUNT(*)
                FROM tblEmployee AS emp
                LEFT JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
                LEFT JOIN tblAttendance AS att 
                    ON emp.employeeID = att.employeeID 
                    AND DATE(att.attendanceDate) = '$currentDate'
                WHERE emp.isActive = 1
                AND m.organisationID = '$organisationID'
                AND m.branchID != 1
                AND att.checkInTime IS NULL
                AND emp.employeeID NOT IN (
                    SELECT employeeID
                    FROM tblApplyLeave
                    WHERE status IN ('Approved', 'Yet To Be Approved') AND employeeID <> 888
                        AND '$currentDate' BETWEEN fromDate AND toDate
                )) AS branchAbsent,

                -- PENDING LEAVE BREAKDOWN
                -- HO Pending Leave Requests
                (SELECT COUNT(*)
                FROM tblApplyLeave l
                JOIN tblEmployee e ON l.employeeID = e.employeeID
                JOIN tblmapEmp m ON e.employeeID = m.employeeID
                WHERE l.status = 'Yet To Be Approved'
                AND e.organisationID = '$organisationID'
                AND m.branchID = 1
                AND '$currentDate' BETWEEN l.fromDate AND l.toDate
                ) AS hoPendingLeave,

                -- Branch Pending Leave Requests (excluding HO)
                (SELECT COUNT(*)
                FROM tblApplyLeave l
                JOIN tblEmployee e ON l.employeeID = e.employeeID
                JOIN tblmapEmp m ON e.employeeID = m.employeeID
                WHERE l.status = 'Yet To Be Approved'
                AND e.organisationID = '$organisationID'
                AND m.branchID != 1
                AND '$currentDate' BETWEEN l.fromDate AND l.toDate
                ) AS branchPendingLeave

            FROM (SELECT 1) AS dummy;";
            }

            error_log("Enhanced Dashboard Query: " . $queryEnhancedDetails);

            $result = mysqli_query($connect_var, $queryEnhancedDetails);
            if (!$result) {
                error_log("Query failed: " . mysqli_error($connect_var));
                throw new Exception("Database query failed");
            }

            $row = mysqli_fetch_assoc($result);
            error_log("Enhanced Query Result: " . print_r($row, true));
            
            if ($row) {
                // Active Employees Breakdown
                $data['activeEmployees'] = [
                    'ho' => [
                        'permanent' => isset($row['hoEmployeesPermanent']) ? intval($row['hoEmployeesPermanent']) : 0,
                        'temporary' => isset($row['hoEmployeesTemporary']) ? intval($row['hoEmployeesTemporary']) : 0,
                        'total' => 0
                    ],
                    'branch' => [
                        'permanent' => isset($row['branchEmployeesPermanent']) ? intval($row['branchEmployeesPermanent']) : 0,
                        'temporary' => isset($row['branchEmployeesTemporary']) ? intval($row['branchEmployeesTemporary']) : 0,
                        'total' => 0
                    ],
                    'grandTotal' => 0
                ];
                
                // Calculate totals
                $data['activeEmployees']['ho']['total'] = $data['activeEmployees']['ho']['permanent'] + $data['activeEmployees']['ho']['temporary'];
                $data['activeEmployees']['branch']['total'] = $data['activeEmployees']['branch']['permanent'] + $data['activeEmployees']['branch']['temporary'];
                $data['activeEmployees']['grandTotal'] = $data['activeEmployees']['ho']['total'] + $data['activeEmployees']['branch']['total'];

                // Checked In Breakdown
                $data['checkedIn'] = [
                    'ho' => isset($row['hoCheckedIn']) ? intval($row['hoCheckedIn']) : 0,
                    'branch' => isset($row['branchCheckedIn']) ? intval($row['branchCheckedIn']) : 0,
                    'total' => 0
                ];
                $data['checkedIn']['total'] = $data['checkedIn']['ho'] + $data['checkedIn']['branch'];

                // Late Check In Breakdown
                $data['lateCheckIn'] = [
                    'ho' => isset($row['hoLateCheckin']) ? intval($row['hoLateCheckin']) : 0,
                    'branch' => isset($row['branchLateCheckin']) ? intval($row['branchLateCheckin']) : 0,
                    'total' => 0
                ];
                $data['lateCheckIn']['total'] = $data['lateCheckIn']['ho'] + $data['lateCheckIn']['branch'];

                // Early Check Out Breakdown
                $data['earlyCheckOut'] = [
                    'ho' => isset($row['hoEarlyCheckout']) ? intval($row['hoEarlyCheckout']) : 0,
                    'branch' => isset($row['branchEarlyCheckout']) ? intval($row['branchEarlyCheckout']) : 0,
                    'total' => 0
                ];
                $data['earlyCheckOut']['total'] = $data['earlyCheckOut']['ho'] + $data['earlyCheckOut']['branch'];

                // On Leave Breakdown
                $data['onLeave'] = [
                    'ho' => isset($row['hoOnLeave']) ? intval($row['hoOnLeave']) : 0,
                    'branch' => isset($row['branchOnLeave']) ? intval($row['branchOnLeave']) : 0,
                    'total' => 0
                ];
                $data['onLeave']['total'] = $data['onLeave']['ho'] + $data['onLeave']['branch'];

                // Absence Breakdown
                $data['absent'] = [
                    'ho' => [
                        'actualAbsent' => isset($row['hoAbsent']) ? intval($row['hoAbsent']) : 0,
                        'pendingLeave' => isset($row['hoPendingLeave']) ? intval($row['hoPendingLeave']) : 0,
                        'total' => 0
                    ],
                    'branch' => [
                        'actualAbsent' => isset($row['branchAbsent']) ? intval($row['branchAbsent']) : 0,
                        'pendingLeave' => isset($row['branchPendingLeave']) ? intval($row['branchPendingLeave']) : 0,
                        'total' => 0
                    ],
                    'grandTotal' => 0
                ];
                
                // Calculate absence totals
                $data['absent']['ho']['total'] = $data['absent']['ho']['actualAbsent'] + $data['absent']['ho']['pendingLeave'];
                $data['absent']['branch']['total'] = $data['absent']['branch']['actualAbsent'] + $data['absent']['branch']['pendingLeave'];
                $data['absent']['grandTotal'] = $data['absent']['ho']['total'] + $data['absent']['branch']['total'];

                // Summary for backward compatibility
                $data['summary'] = [
                    'totalEmployees' => $data['activeEmployees']['grandTotal'],
                    'checkedInToday' => $data['checkedIn']['total'],
                    'lateCheckin' => $data['lateCheckIn']['total'],
                    'earlyCheckout' => $data['earlyCheckOut']['total'],
                    'onLeave' => $data['onLeave']['total'],
                    'totalAbsent' => $data['absent']['grandTotal']
                ];
                
                error_log("Enhanced Final Data: " . print_r($data, true));
                
                echo json_encode([
                    "status" => "success",
                    "data" => $data
                ]);
            } else {
                error_log("No data found for organisation: " . $this->organisationID);
                echo json_encode([
                    "status" => "error",
                    "message_text" => "No data found for the specified organisation"
                ], JSON_FORCE_OBJECT);
            }
            
        } catch (Exception $e) {
            error_log("Error in DashboardAttendanceDetailsEnhanced: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }

    public function loadDashboardEmployeeListEnhanced(array $data) {
        if (isset($data['currentDate']) && isset($data['organisationID']) && isset($data['listType'])) {
            $this->currentDate = $data['currentDate'];
            $this->organisationID = $data['organisationID'];
            $this->listType = $data['listType']; // 'active', 'checkedIn', 'lateCheckIn', 'onLeave', 'absent', 'pendingLeave'
            $this->locationFilter = isset($data['locationFilter']) ? $data['locationFilter'] : 'all'; // 'ho', 'branch', 'all'
            $this->employeeType = isset($data['employeeType']) ? $data['employeeType'] : 'all'; // 'permanent', 'temporary', 'all'
            $this->branchID = isset($data['branchID']) ? $data['branchID'] : ''; // For specific branch filtering
            return true;
        } else {
            error_log("Missing required parameters in loadDashboardEmployeeListEnhanced: " . print_r($data, true));
            return false;
        }
    }

    public function DashboardEmployeeListEnhanced() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $organisationID = mysqli_real_escape_string($connect_var, $this->organisationID);
            $currentDate = mysqli_real_escape_string($connect_var, $this->currentDate);
            $listType = mysqli_real_escape_string($connect_var, $this->listType);
            $locationFilter = mysqli_real_escape_string($connect_var, $this->locationFilter);
            $employeeType = mysqli_real_escape_string($connect_var, $this->employeeType);
            $branchID = mysqli_real_escape_string($connect_var, $this->branchID);
            
            $employees = [];
            
            switch ($listType) {
                case 'active':
                    $employees = $this->getActiveEmployeesList($connect_var, $organisationID, $locationFilter, $employeeType, $branchID);
                    break;
                case 'checkedIn':
                    $employees = $this->getCheckedInEmployeesList($connect_var, $organisationID, $currentDate, $locationFilter, $branchID);
                    break;
                case 'lateCheckIn':
                    $employees = $this->getLateCheckInEmployeesList($connect_var, $organisationID, $currentDate, $locationFilter, $branchID);
                    break;
                case 'earlyCheckOut':
                    $employees = $this->getEarlyCheckOutEmployeesList($connect_var, $organisationID, $currentDate, $locationFilter, $branchID);
                    break;
                case 'onLeave':
                    $employees = $this->getOnLeaveEmployeesList($connect_var, $organisationID, $currentDate, $locationFilter, $branchID);
                    break;
                case 'absent':
                    $employees = $this->getAbsentEmployeesList($connect_var, $organisationID, $currentDate, $locationFilter, $branchID);
                    break;
                case 'pendingLeave':
                    $employees = $this->getPendingLeaveEmployeesList($connect_var, $organisationID, $currentDate, $locationFilter, $branchID);
                    break;
                default:
                    throw new Exception("Invalid list type: $listType");
            }
            
            echo json_encode([
                "status" => "success",
                "data" => [
                    "listType" => $listType,
                    "locationFilter" => $locationFilter,
                    "employeeType" => $employeeType,
                    "currentDate" => $currentDate,
                    "employees" => $employees,
                    "count" => count($employees)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error in DashboardEmployeeListEnhanced: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }

    private function getActiveEmployeesList($connect_var, $organisationID, $locationFilter, $employeeType, $branchID = '') {
        $locationCondition = "";
        
        // If specific branchID is provided, filter by that branch
        if (!empty($branchID)) {
            $locationCondition = "AND map.branchID = '$branchID'";
        } else {
            // Use locationFilter logic for organization-wide views
            if ($locationFilter === 'ho') {
                $locationCondition = "AND map.branchID = 1";
            } elseif ($locationFilter === 'branch') {
                $locationCondition = "AND map.branchID != 1";
            }
        }
        
        $employeeTypeCondition = "";
        if ($employeeType === 'permanent') {
            $employeeTypeCondition = "AND emp.isTemporary = 0";
        } elseif ($employeeType === 'temporary') {
            $employeeTypeCondition = "AND emp.isTemporary = 1";
        }
        
        $query = "SELECT 
            emp.employeeID,
            emp.employeeName,
            emp.empID,
            emp.employeePhone,
            emp.Designation,
            emp.isTemporary,
            b.branchName as location,
            s.sectionName,
            CASE WHEN emp.isTemporary = 1 THEN 'Temporary' ELSE 'Permanent' END as employeeType,
            CASE WHEN map.branchID = 1 THEN 'Head Office' ELSE 'Branch' END as locationType
        FROM tblEmployee emp
        JOIN tblmapEmp map ON emp.employeeID = map.employeeID
        JOIN tblBranch b ON map.branchID = b.branchID
        LEFT JOIN tblAssignedSection assign ON emp.employeeID = assign.employeeID AND assign.isActive = 1
        LEFT JOIN tblSection s ON assign.sectionID = s.sectionID
        WHERE emp.organisationID = '$organisationID'
        AND emp.isActive = 1
        $locationCondition
        $employeeTypeCondition
        ORDER BY emp.employeeName ASC";
        
        $result = mysqli_query($connect_var, $query);
        if (!$result) {
            throw new Exception("Database query failed: " . mysqli_error($connect_var));
        }
        
        $employees = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $employees[] = $row;
        }
        
        return $employees;
    }

    private function getCheckedInEmployeesList($connect_var, $organisationID, $currentDate, $locationFilter, $branchID = '') {
        $locationCondition = "";
        
        // If specific branchID is provided, filter by that branch
        if (!empty($branchID)) {
            $locationCondition = "AND map.branchID = '$branchID'";
        } else {
            // Use locationFilter logic for organization-wide views
            if ($locationFilter === 'ho') {
                $locationCondition = "AND (a.checkInBranchID = 1 OR map.branchID = 1)";
            } elseif ($locationFilter === 'branch') {
                $locationCondition = "AND a.checkInBranchID != 1 AND map.branchID != 1";
            }
        }
        
        $query = "SELECT 
            emp.employeeID,
            emp.employeeName,
            emp.empID,
            emp.employeePhone,
            emp.Designation,
            emp.isTemporary,
            b.branchName as location,
            s.sectionName,
            a.checkInTime,
            a.checkOutTime,
            CASE WHEN emp.isTemporary = 1 THEN 'Temporary' ELSE 'Permanent' END as employeeType,
            CASE WHEN map.branchID = 1 THEN 'Head Office' ELSE 'Branch' END as locationType
        FROM tblAttendance a
        JOIN tblEmployee emp ON a.employeeID = emp.employeeID
        JOIN tblmapEmp map ON emp.employeeID = map.employeeID
        JOIN tblBranch b ON map.branchID = b.branchID
        LEFT JOIN tblAssignedSection assign ON emp.employeeID = assign.employeeID AND assign.isActive = 1
        LEFT JOIN tblSection s ON assign.sectionID = s.sectionID
        WHERE a.attendanceDate = '$currentDate'
        AND map.organisationID = '$organisationID'
        AND a.checkInTime IS NOT NULL
        $locationCondition
        ORDER BY emp.employeeName ASC";
        
        $result = mysqli_query($connect_var, $query);
        if (!$result) {
            throw new Exception("Database query failed: " . mysqli_error($connect_var));
        }
        
        $employees = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $employees[] = $row;
        }
        
        return $employees;
    }

    private function getLateCheckInEmployeesList($connect_var, $organisationID, $currentDate, $locationFilter, $branchID = '') {
        $locationCondition = "";
        
        // If specific branchID is provided, filter by that branch
        if (!empty($branchID)) {
            $locationCondition = "AND map.branchID = '$branchID'";
        } else {
            // Use locationFilter logic for organization-wide views
            if ($locationFilter === 'ho') {
                $locationCondition = "AND (a.checkInBranchID = 1 OR map.branchID = 1)";
            } elseif ($locationFilter === 'branch') {
                $locationCondition = "AND a.checkInBranchID != 1 AND map.branchID != 1";
            }
        }
        
        $query = "SELECT 
            emp.employeeID,
            emp.employeeName,
            emp.empID,
            emp.employeePhone,
            emp.Designation,
            emp.isTemporary,
            b.branchName as location,
            s.sectionName,
            a.checkInTime,
            a.checkOutTime,
            CASE WHEN emp.isTemporary = 1 THEN 'Temporary' ELSE 'Permanent' END as employeeType,
            CASE WHEN map.branchID = 1 THEN 'Head Office' ELSE 'Branch' END as locationType
        FROM tblAttendance a
        JOIN tblEmployee emp ON a.employeeID = emp.employeeID
        JOIN tblmapEmp map ON emp.employeeID = map.employeeID
        JOIN tblBranch b ON map.branchID = b.branchID
        LEFT JOIN tblAssignedSection assign ON emp.employeeID = assign.employeeID AND assign.isActive = 1
        LEFT JOIN tblSection s ON assign.sectionID = s.sectionID
        WHERE a.attendanceDate = '$currentDate'
        AND map.organisationID = '$organisationID'
        AND a.isLateCheckIN = 1
        $locationCondition
        ORDER BY emp.employeeName ASC";
        
        $result = mysqli_query($connect_var, $query);
        if (!$result) {
            throw new Exception("Database query failed: " . mysqli_error($connect_var));
        }
        
        $employees = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $employees[] = $row;
        }
        
        return $employees;
    }

    private function getEarlyCheckOutEmployeesList($connect_var, $organisationID, $currentDate, $locationFilter, $branchID = '') {
        $locationCondition = "";
        
        // If specific branchID is provided, filter by that branch
        if (!empty($branchID)) {
            $locationCondition = "AND map.branchID = '$branchID'";
        } else {
            // Use locationFilter logic for organization-wide views
            if ($locationFilter === 'ho') {
                $locationCondition = "AND map.branchID = 1";
            } elseif ($locationFilter === 'branch') {
                $locationCondition = "AND map.branchID != 1";
            }
        }
        
        $query = "SELECT 
            emp.employeeID,
            emp.employeeName,
            emp.empID,
            emp.employeePhone,
            emp.Designation,
            emp.isTemporary,
            b.branchName as location,
            s.sectionName,
            att.checkOutTime,
            CASE WHEN emp.isTemporary = 1 THEN 'Temporary' ELSE 'Permanent' END as employeeType,
            CASE WHEN map.branchID = 1 THEN 'Head Office' ELSE 'Branch' END as locationType
        FROM tblEmployee emp
        JOIN tblmapEmp map ON emp.employeeID = map.employeeID
        JOIN tblBranch b ON map.branchID = b.branchID
        LEFT JOIN tblAssignedSection assign ON emp.employeeID = assign.employeeID AND assign.isActive = 1
        LEFT JOIN tblSection s ON assign.sectionID = s.sectionID
        JOIN tblAttendance att ON emp.employeeID = att.employeeID
        WHERE DATE(att.attendanceDate) = '$currentDate'
        AND map.organisationID = '$organisationID'
        AND att.checkOutTime IS NOT NULL
        AND att.checkOutTime < b.checkOutTime
        $locationCondition
        ORDER BY att.checkOutTime ASC";
        
        $result = mysqli_query($connect_var, $query);
        if (!$result) {
            throw new Exception("Database query failed: " . mysqli_error($connect_var));
        }
        
        $employees = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $employees[] = $row;
        }
        
        return $employees;
    }

    private function getOnLeaveEmployeesList($connect_var, $organisationID, $currentDate, $locationFilter, $branchID = '') {
        $locationCondition = "";
        
        // If specific branchID is provided, filter by that branch
        if (!empty($branchID)) {
            $locationCondition = "AND map.branchID = '$branchID'";
        } else {
            // Use locationFilter logic for organization-wide views
            if ($locationFilter === 'ho') {
                $locationCondition = "AND map.branchID = 1";
            } elseif ($locationFilter === 'branch') {
                $locationCondition = "AND map.branchID != 1";
            }
        }
        
        $query = "SELECT 
            emp.employeeID,
            emp.employeeName,
            emp.empID,
            emp.employeePhone,
            emp.Designation,
            emp.isTemporary,
            b.branchName as location,
            s.sectionName,
            l.fromDate,
            l.toDate,
            l.typeOfLeave,
            l.reason,
            CASE WHEN emp.isTemporary = 1 THEN 'Temporary' ELSE 'Permanent' END as employeeType,
            CASE WHEN map.branchID = 1 THEN 'Head Office' ELSE 'Branch' END as locationType
        FROM tblApplyLeave l
        JOIN tblEmployee emp ON l.employeeID = emp.employeeID
        JOIN tblmapEmp map ON emp.employeeID = map.employeeID
        JOIN tblBranch b ON map.branchID = b.branchID
        LEFT JOIN tblAssignedSection assign ON emp.employeeID = assign.employeeID AND assign.isActive = 1
        LEFT JOIN tblSection s ON assign.sectionID = s.sectionID
        WHERE '$currentDate' BETWEEN l.fromDate AND l.toDate
        AND map.organisationID = '$organisationID'
        AND l.status = 'Approved'
        $locationCondition
        ORDER BY emp.employeeName ASC";
        
        $result = mysqli_query($connect_var, $query);
        if (!$result) {
            throw new Exception("Database query failed: " . mysqli_error($connect_var));
        }
        
        $employees = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $employees[] = $row;
        }
        
        return $employees;
    }

    private function getAbsentEmployeesList($connect_var, $organisationID, $currentDate, $locationFilter, $branchID = '') {
        $locationCondition = "";
        
        // If specific branchID is provided, filter by that branch
        if (!empty($branchID)) {
            $locationCondition = "AND m.branchID = '$branchID'";
        } else {
            // Use locationFilter logic for organization-wide views
            if ($locationFilter === 'ho') {
                $locationCondition = "AND m.branchID = 1";
            } elseif ($locationFilter === 'branch') {
                $locationCondition = "AND m.branchID != 1";
            }
        }
        
        $query = "SELECT 
            emp.employeeID,
            emp.employeeName,
            emp.empID,
            emp.employeePhone,
            emp.Designation,
            emp.isTemporary,
            b.branchName as location,
            s.sectionName,
            CASE WHEN emp.isTemporary = 1 THEN 'Temporary' ELSE 'Permanent' END as employeeType,
            CASE WHEN m.branchID = 1 THEN 'Head Office' ELSE 'Branch' END as locationType
        FROM tblEmployee emp
        JOIN tblmapEmp m ON emp.employeeID = m.employeeID
        JOIN tblBranch b ON m.branchID = b.branchID
        LEFT JOIN tblAssignedSection assign ON emp.employeeID = assign.employeeID AND assign.isActive = 1
        LEFT JOIN tblSection s ON assign.sectionID = s.sectionID
        LEFT JOIN tblAttendance att ON emp.employeeID = att.employeeID AND DATE(att.attendanceDate) = '$currentDate'
        WHERE emp.isActive = 1
        AND m.organisationID = '$organisationID'
        AND att.checkInTime IS NULL
        AND emp.employeeID NOT IN (
            SELECT employeeID
            FROM tblApplyLeave
            WHERE status IN ('Approved', 'Yet To Be Approved') AND employeeID <> 888
                AND '$currentDate' BETWEEN fromDate AND toDate
        )
        $locationCondition
        ORDER BY emp.employeeName ASC";
        
        $result = mysqli_query($connect_var, $query);
        if (!$result) {
            throw new Exception("Database query failed: " . mysqli_error($connect_var));
        }
        
        $employees = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $employees[] = $row;
        }
        
        return $employees;
    }

    private function getPendingLeaveEmployeesList($connect_var, $organisationID, $currentDate, $locationFilter, $branchID = '') {
        $locationCondition = "";
        
        // If specific branchID is provided, filter by that branch
        if (!empty($branchID)) {
            $locationCondition = "AND m.branchID = '$branchID'";
        } else {
            // Use locationFilter logic for organization-wide views
            if ($locationFilter === 'ho') {
                $locationCondition = "AND m.branchID = 1";
            } elseif ($locationFilter === 'branch') {
                $locationCondition = "AND m.branchID != 1";
            }
        }
        
        $query = "SELECT 
            emp.employeeID,
            emp.employeeName,
            emp.empID,
            emp.employeePhone,
            emp.Designation,
            emp.isTemporary,
            b.branchName as location,
            s.sectionName,
            l.fromDate,
            l.toDate,
            l.typeOfLeave,
            l.reason,
            l.createdOn,
            mng.employeeName AS managerName,
            mng.empID AS managerID,
            CASE WHEN emp.isTemporary = 1 THEN 'Temporary' ELSE 'Permanent' END as employeeType,
            CASE WHEN m.branchID = 1 THEN 'Head Office' ELSE 'Branch' END as locationType
        FROM tblApplyLeave l
        JOIN tblEmployee emp ON l.employeeID = emp.employeeID
        JOIN tblmapEmp m ON emp.employeeID = m.employeeID
        JOIN tblBranch b ON m.branchID = b.branchID
        LEFT JOIN tblEmployee mng ON emp.managerID = mng.employeeID
        LEFT JOIN tblAssignedSection assign ON emp.employeeID = assign.employeeID AND assign.isActive = 1
        LEFT JOIN tblSection s ON assign.sectionID = s.sectionID
        WHERE l.status = 'Yet To Be Approved'
        AND emp.organisationID = '$organisationID'
        AND '$currentDate' BETWEEN l.fromDate AND l.toDate
        $locationCondition
        ORDER BY l.createdOn DESC, emp.employeeName ASC";
        
        $result = mysqli_query($connect_var, $query);
        if (!$result) {
            throw new Exception("Database query failed: " . mysqli_error($connect_var));
        }
        
        $employees = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $employees[] = $row;
        }
        
        return $employees;
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
function DashboardAttendanceDetailsforAll($decoded_items) {
    $dashboardComponent = new DashboardComponent();
    if ($dashboardComponent->loadDashboardAttendanceDetailsforAll($decoded_items)) {
        $dashboardComponent->DashboardAttendanceDetailsforAll();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function DashboardAttendanceDetailsEnhanced($decoded_items) {
    $dashboardComponent = new DashboardComponent();
    if ($dashboardComponent->loadDashboardAttendanceDetailsEnhanced($decoded_items)) {
        $dashboardComponent->DashboardAttendanceDetailsEnhanced();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function DashboardEmployeeListEnhanced($decoded_items) {
    $dashboardComponent = new DashboardComponent();
    if ($dashboardComponent->loadDashboardEmployeeListEnhanced($decoded_items)) {
        $dashboardComponent->DashboardEmployeeListEnhanced();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}

