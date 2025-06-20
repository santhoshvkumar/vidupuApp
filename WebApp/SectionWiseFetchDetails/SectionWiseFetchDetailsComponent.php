<?php
class SectionWiseFetchDetailsComponent{
    public $sectionID;
    public $sectionName;
    public $currentMonth;
    public $currentYear;
    public $currentDate;
    public $branchID;
    public $branchName;

    public function loadSectionWiseFetchDetails(array $data){
        if (isset($data['sectionID']) && isset($data['currentMonth']) && isset($data['currentYear'])) {
            $this->sectionID = $data['sectionID'];
            $this->currentMonth = $data['currentMonth'];
            $this->currentYear = $data['currentYear'];
            return true;
        } else {
            return false;
        }
    }
    public function loadSectionWiseAttendanceDetails(array $data){
        if (isset($data['currentMonth']) && isset($data['currentYear'])) {
            $this->currentMonth = $data['currentMonth'];
            $this->currentYear = $data['currentYear'];
            return true;
        } else {
            return false;
        }
    }
    public function loadSectionWiseAttendanceForToday(array $data){ 
        if (isset($data['currentDate'])) {  
            $this->currentDate = $data['currentDate'];
            $this->organisationID = $data['OrganisationID'];
            return true;
        } else {
            return false;
        }
    }
    public function loadBranchWiseAttendanceForToday(array $data){ 
        if (isset($data['currentDate'])) {  
            $this->currentDate = $data['currentDate'];
            return true;
        } else {
            return false;
        }
    }
    public function SectionWiseFetchDetails() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       

            // Debug input values
            error_log("DashboardAttendanceForHeadOffice - Input values:");
            error_log("sectionID: " . $this->sectionID);
            error_log("currentMonth: " . $this->currentMonth);
            error_log("currentYear: " . $this->currentYear);

            // 1. Total active employees in Head Office
            $queryHOEmployeeAttendanceSectionWise = "
                SELECT 
                    (SELECT COUNT(e.employeeID)
                     FROM tblEmployee e
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     JOIN tblSection s ON a.sectionID = s.sectionID
                     WHERE a.isActive = 1
                     AND s.sectionID = ?) AS totalactiveemployeesinsection,

                    (SELECT COUNT(att.employeeID) 
                     FROM tblAttendance att
                     JOIN tblEmployee e ON att.employeeID = e.employeeID
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     JOIN tblSection s ON a.sectionID = s.sectionID
                     WHERE a.isActive = 1
                     AND s.sectionID = ?
                     AND MONTH(att.attendanceDate) = ?
                     AND YEAR(att.attendanceDate) = ?) AS totalcheckins,

                    (SELECT COUNT(att.employeeID) 
                     FROM tblAttendance att
                     JOIN tblEmployee e ON att.employeeID = e.employeeID
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     JOIN tblSection s ON a.sectionID = s.sectionID
                     WHERE att.checkInTime > '10:10:00'
                     AND MONTH(att.attendanceDate) = ?
                     AND YEAR(att.attendanceDate) = ?
                     AND a.isActive = 1
                     AND s.sectionID = ?) AS late_checkin,

                    (SELECT COUNT(att.employeeID) 
                     FROM tblAttendance att
                     JOIN tblEmployee e ON att.employeeID = e.employeeID
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     JOIN tblSection s ON a.sectionID = s.sectionID
                     WHERE att.checkOutTime < '17:00:00'
                     AND MONTH(att.attendanceDate) = ?
                     AND YEAR(att.attendanceDate) = ?
                     AND a.isActive = 1
                     AND s.sectionID = ?) AS early_checkout,

                    (SELECT COUNT(e.employeeID)
                     FROM tblApplyLeave l
                     JOIN tblEmployee e ON l.employeeID = e.employeeID
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     JOIN tblSection s ON a.sectionID = s.sectionID
                     WHERE MONTH(l.fromDate) = ?
                     AND YEAR(l.fromDate) = ?
                     AND a.isActive = 1
                     AND s.sectionID = ?) AS on_leave;";

            // Debug the query with actual values
            $debug_query = str_replace(
                ['?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?'],
                [
                    "'" . $this->sectionID . "'",
                    "'" . $this->sectionID . "'",
                    $this->currentMonth,
                    $this->currentYear,
                    $this->currentMonth,
                    $this->currentYear,
                    "'" . $this->sectionID . "'",
                    $this->currentMonth,
                    $this->currentYear,
                    "'" . $this->sectionID . "'",
                    $this->currentMonth,
                    $this->currentYear,
                    "'" . $this->sectionID . "'"
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
                $this->sectionID,  // for first subquery
                $this->sectionID,  // for second subquery
                $this->currentMonth, // for second subquery
                $this->currentYear,         // for second subquery
                $this->currentMonth, // for third subquery
                $this->currentYear,         // for third subquery
                $this->sectionID,  // for third subquery
                $this->currentMonth, // for fourth subquery
                $this->currentYear,         // for fourth subquery
                $this->sectionID,  // for fourth subquery
                $this->currentMonth, // for fifth subquery
                $this->currentYear,         // for fifth subquery
                $this->sectionID   // for fifth subquery
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
                $data['sectionID'] = $this->sectionID;
                $data['currentMonth'] = $this->currentMonth;
                $data['currentYear'] = $this->currentYear;
                $data['totalActiveEmployeesInSection'] = isset($row['totalactiveemployeesinsection']) ? intval($row['totalactiveemployeesinsection']) : 0;
                $data['totalCheckIns'] = isset($row['totalcheckins']) ? intval($row['totalcheckins']) : 0;
                $data['onLeave'] = isset($row['on_leave']) ? intval($row['on_leave']) : 0;
                $data['lateCheckIn'] = isset($row['late_checkin']) ? intval($row['late_checkin']) : 0;
                $data['earlyCheckOut'] = isset($row['early_checkout']) ? intval($row['early_checkout']) : 0;
                $data['absenteesinHO'] = $data['totalActiveEmployeesInSection'] - ($data['totalCheckIns'] + $data['onLeave']);
                
                // Debug final data
                error_log("Final Data: " . print_r($data, true));
                
                echo json_encode([
                    "status" => "success",
                    "data" => $data
                ]);
            } else {
                error_log("No data found for section: " . $this->sectionID);
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
    
    public function SectionWiseAttendanceDetails() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       

            // Debug input values
            error_log("DashboardAttendanceForHeadOffice - Input values:");
            error_log("currentMonth: " . $this->currentMonth);
            error_log("currentYear: " . $this->currentYear);

            // 1. Total active employees in Head Office
            $queryHOEmployeeAttendanceSectionWise = "
                SELECT 
                    s.sectionID,
                    s.sectionName AS section_name,
                    (SELECT COUNT(e.employeeID)
                     FROM tblEmployee e
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     WHERE a.isActive = 1
                     AND a.sectionID = s.sectionID) AS total_active_employees,
                    (SELECT COUNT(att.employeeID) 
                     FROM tblAttendance att
                     JOIN tblEmployee e ON att.employeeID = e.employeeID
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     WHERE a.isActive = 1
                     AND a.sectionID = s.sectionID
                     AND att.checkInTime IS NOT NULL
                     AND MONTH(att.attendanceDate) = ?
                     AND YEAR(att.attendanceDate) = ?) AS total_checkins,
                    (SELECT COUNT(att.employeeID) 
                     FROM tblAttendance att
                     JOIN tblEmployee e ON att.employeeID = e.employeeID
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     WHERE a.isActive = 1
                     AND a.sectionID = s.sectionID
                     AND att.checkInTime IS NOT NULL
                     AND att.checkInTime > '10:10:00'
                     AND MONTH(att.attendanceDate) = ?
                     AND YEAR(att.attendanceDate) = ?) AS late_checkin,
                    (SELECT COUNT(att.employeeID) 
                     FROM tblAttendance att
                     JOIN tblEmployee e ON att.employeeID = e.employeeID
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     WHERE a.isActive = 1
                     AND a.sectionID = s.sectionID
                     AND att.checkOutTime IS NOT NULL
                     AND att.checkOutTime < '17:00:00'
                     AND MONTH(att.attendanceDate) = ?
                     AND YEAR(att.attendanceDate) = ?) AS early_checkout,
                    (SELECT COUNT(e.employeeID)
                     FROM tblApplyLeave l
                     JOIN tblEmployee e ON l.employeeID = e.employeeID
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     WHERE a.isActive = 1
                     AND a.sectionID = s.sectionID
                     AND MONTH(l.fromDate) = ?
                     AND YEAR(l.fromDate) = ?) AS on_leave
                FROM tblSection s
                ORDER BY s.sectionName ASC;";

            // Debug the query with actual values
            $debug_query = str_replace(
                ['?', '?', '?', '?', '?', '?', '?', '?'],
                [
                    $this->currentMonth,
                    $this->currentYear,
                    $this->currentMonth,
                    $this->currentYear,
                    $this->currentMonth,
                    $this->currentYear,
                    $this->currentMonth,
                    $this->currentYear,                   
                ],
                $queryHOEmployeeAttendanceSectionWise
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryHOEmployeeAttendanceSectionWise);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "iiiiiiii", 
                $this->currentMonth, // for total_checkins
                $this->currentYear,  // for total_checkins
                $this->currentMonth, // for late_checkin
                $this->currentYear,  // for late_checkin
                $this->currentMonth, // for early_checkout
                $this->currentYear,  // for early_checkout
                $this->currentMonth, // for on_leave
                $this->currentYear   // for on_leave
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Execute failed: " . mysqli_stmt_error($stmt));
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
            $sections = [];
            
            while ($row = mysqli_fetch_assoc($result)) {
                $sectionData = [
                    'sectionID' => $row['sectionID'],
                    'sectionName' => $row['section_name'],
                    'currentMonth' => $this->currentMonth,
                    'currentYear' => $this->currentYear,
                    'totalActiveEmployeesInSection' => isset($row['total_active_employees']) ? intval($row['total_active_employees']) : 0,
                    'totalCheckIns' => isset($row['total_checkins']) ? intval($row['total_checkins']) : 0,
                    'onLeave' => isset($row['on_leave']) ? intval($row['on_leave']) : 0,
                    'lateCheckIn' => isset($row['late_checkin']) ? intval($row['late_checkin']) : 0,
                    'earlyCheckOut' => isset($row['early_checkout']) ? intval($row['early_checkout']) : 0
                ];
                $sectionData['absenteesinHO'] = $sectionData['totalActiveEmployeesInSection'] - ($sectionData['totalCheckIns'] + $sectionData['onLeave']);
                $sections[] = $sectionData;
            }
            
            if (!empty($sections)) {
                echo json_encode([
                    "status" => "success",
                    "data" => $sections
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message_text" => "No data found for any section"
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
    public function SectionWiseAttendanceForToday() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       

            // Debug input values
            error_log("SectionWiseAttendanceForToday - Input values:");
            error_log("currentDate: " . $this->currentDate);

            // 1. Total active employees in Head Office for today            
                $queryHOEmployeeAttendanceSectionWiseForToday = "SELECT 
                    s.sectionID,
                    s.sectionName AS section_name,
                    (SELECT COUNT(e.employeeID)
                     FROM tblEmployee e
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     WHERE a.isActive = 1
                     AND a.sectionID = s.sectionID) AS total_active_employees,
                    (SELECT COUNT(att.employeeID) 
                     FROM tblAttendance att
                     JOIN tblEmployee e ON att.employeeID = e.employeeID
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     WHERE a.isActive = 1
                     AND a.sectionID = s.sectionID
                     AND att.checkInTime IS NOT NULL
                     AND att.attendanceDate = ?) AS total_checkins,
                    (SELECT COUNT(att.employeeID) 
                     FROM tblAttendance att
                     JOIN tblEmployee e ON att.employeeID = e.employeeID
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     WHERE a.isActive = 1
                     AND a.sectionID = s.sectionID
                     AND att.checkInTime IS NOT NULL
                     AND att.checkInTime > '10:10:00'
                     AND att.attendanceDate = ?) AS late_checkin,
                    (SELECT COUNT(att.employeeID) 
                     FROM tblAttendance att
                     JOIN tblEmployee e ON att.employeeID = e.employeeID
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     WHERE a.isActive = 1
                     AND a.sectionID = s.sectionID
                     AND att.checkOutTime IS NOT NULL
                     AND att.checkOutTime < '17:00:00'
                     AND att.attendanceDate = ?) AS early_checkout,
                    (SELECT COUNT(e.employeeID)
                     FROM tblApplyLeave l
                     JOIN tblEmployee e ON l.employeeID = e.employeeID
                     JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                     WHERE a.isActive = 1
                     AND a.sectionID = s.sectionID
                     AND l.fromDate = ? AND l.status = 'Approved') AS on_leave
                FROM tblSection s
                WHERE s.organisationID = ?
                ORDER BY s.sectionName ASC;";


            // Debug the query with actual values
            $debug_query = str_replace(
                ['?', '?', '?', '?', '?'],
                [
                    $this->currentDate,
                    $this->currentDate,
                    $this->currentDate,
                    $this->currentDate,
                    $this->organisationID,
                ],
                $queryHOEmployeeAttendanceSectionWiseForToday
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryHOEmployeeAttendanceSectionWiseForToday);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "sssss", 
                $this->currentDate,  // for total_checkins
                $this->currentDate, // for late_checkin
                $this->currentDate, // for early_checkout
                $this->currentDate, // for on_leave
                $this->organisationID
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Execute failed: " . mysqli_stmt_error($stmt));
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
            $sections = [];
            $sectionName  = [];
            $totalEmployees = [];
            $totalCheckIns = [];
            $onLeave = [];
            $lateCheckIn = [];
            $earlyCheckOut = [];
            $absentees = [];
            $countSection = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $sectionName[] = $row['section_name'];
                $totalEmployees[] = $row['total_active_employees'] != 0 ? intval($row['total_active_employees']) : 0;
                $totalCheckIns[] = $row['total_checkins'] != 0 ? intval($row['total_checkins']) : 0;
                $onLeave[] = $row['on_leave'] != 0 ? intval($row['on_leave']) : 0;
                $lateCheckIn[] = $row['late_checkin'] != 0 ? intval($row['late_checkin']) : 0;
                $earlyCheckOut[] = $row['early_checkout'] != 0 ? intval($row['early_checkout']) : 0;
                $absentees[] = $row['total_active_employees'] != 0 ? intval($row['total_active_employees']) - ($row['total_checkins'] + $row['on_leave']) : 0;
                
                $countSection++;
            }
            
            if ($countSection > 0) {
                echo json_encode([
                    "status" => "success",
                    "sectionName" => $sectionName ?? [],
                    "totalEmployees" => $totalEmployees ?? [],
                    "totalCheckIns" => $totalCheckIns ?? [],
                    "onLeave" => $onLeave ?? [],
                    "lateCheckIn" => $lateCheckIn ?? [],
                    "earlyCheckOut" => $earlyCheckOut ?? [],
                    "absentees" => $absentees ?? []
                ]);         
            } else {
                echo json_encode([
                    "status" => "success",
                    "sectionName" => [],
                    "totalEmployees" => [],
                    "totalCheckIns" => [],
                    "onLeave" => [],
                    "lateCheckIn" => [],
                    "earlyCheckOut" => [],
                    "absentees" => []
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Error in SectionWiseAttendanceForToday: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    } 
    public function BranchWiseAttendanceForToday() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       

            // Debug input values
            error_log("SectionWiseAttendanceForToday - Input values:");
            error_log("currentDate: " . $this->currentDate);

            // 1. Total active employees in Head Office for today
            $queryBranchWiseAttendanceForToday = "SELECT 
                    b.branchID, 
                    b.branchName AS branch_name,
                    (SELECT COUNT(e.employeeID) 
                     FROM tblEmployee e 
                     JOIN tblmapEmp m ON e.employeeID = m.employeeID 
                     WHERE e.isActive = 1 
                     AND m.branchID = b.branchID) AS total_active_employees,
                    (SELECT COUNT(e.employeeID) 
                     FROM tblAttendance att
                     JOIN tblEmployee e ON att.employeeID = e.employeeID
                     JOIN tblmapEmp m ON e.employeeID = m.employeeID
                     WHERE e.isActive = 1
                     AND m.branchID = b.branchID
                     AND att.checkInTime IS NOT NULL
                     AND att.attendanceDate = ?) AS total_checkins,
                    (SELECT COUNT(att.employeeID) 
                     FROM tblAttendance att
                     JOIN tblEmployee e ON att.employeeID = e.employeeID
                     JOIN tblmapEmp m ON e.employeeID = m.employeeID
                     WHERE e.isActive = 1
                     AND m.branchID = b.branchID
                     AND att.checkInTime IS NOT NULL
                     AND att.checkInTime > '09:25:00'
                     AND att.attendanceDate = ?) AS late_checkin,
                    (SELECT COUNT(att.employeeID) 
                     FROM tblAttendance att
                     JOIN tblEmployee e ON att.employeeID = e.employeeID
                     JOIN tblmapEmp m ON e.employeeID = m.employeeID
                     WHERE e.isActive = 1
                     AND m.branchID = b.branchID
                     AND att.checkOutTime IS NOT NULL
                     AND att.checkOutTime < '16:30:00'
                     AND att.attendanceDate = ?) AS early_checkout,
                    (SELECT COUNT(e.employeeID)
                     FROM tblApplyLeave l
                     JOIN tblEmployee e ON l.employeeID = e.employeeID
                     JOIN tblmapEmp m ON e.employeeID = m.employeeID
                     WHERE e.isActive = 1 AND l.status = 'Approved'
                     AND m.branchID = b.branchID
                     AND l.fromDate = ?) AS on_leave
                FROM tblBranch b
                ORDER BY b.branchName ASC;";
    
            // Debug the query with actual values
            $debug_query = str_replace(
                ['?', '?', '?', '?'],
                [
                    $this->currentDate,
                    $this->currentDate,
                    $this->currentDate,
                    $this->currentDate,
                ],
                $queryBranchWiseAttendanceForToday
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryBranchWiseAttendanceForToday);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ssss", 
                $this->currentDate,  // for total_checkins
                $this->currentDate, // for late_checkin
                $this->currentDate, // for early_checkout
                $this->currentDate, // for on_leave
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Execute failed: " . mysqli_stmt_error($stmt));
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
            $branches = [];
            $branchName = [];
            $totalEmployees = [];
            $totalCheckIns = [];
            $onLeave = [];
            $lateCheckIn = [];
            $earlyCheckOut = [];
            $absentees = [];
            $countBranch = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $branchName[] = $row['branch_name'];                
                $totalEmployees[] = $row['total_active_employees'] != 0 ? intval($row['total_active_employees']) : 0;
                $totalCheckIns[] = $row['total_checkins'] != 0 ? intval($row['total_checkins']) : 0;
                $onLeave[] = $row['on_leave'] != 0 ? intval($row['on_leave']) : 0;
                $lateCheckIn[] = $row['late_checkin'] != 0 ? intval($row['late_checkin']) : 0;
                $earlyCheckOut[] = $row['early_checkout'] != 0 ? intval($row['early_checkout']) : 0;
                $absentees[] = $row['total_active_employees'] != 0 ? intval($row['total_active_employees']) - ($row['total_checkins'] + $row['on_leave']) : 0;

                $countBranch++;
            }

            if ($countBranch > 0) {
                echo json_encode([
                    "status" => "success",
                    "branchName" => $branchName ?? [],
                    "totalEmployees" => $totalEmployees ?? [],
                    "totalCheckIns" => $totalCheckIns ?? [],
                    "onLeave" => $onLeave ?? [],
                    "lateCheckIn" => $lateCheckIn ?? [],
                    "earlyCheckOut" => $earlyCheckOut ?? [],
                    "absentees" => $absentees ?? []
                ]);         
            } else {
                echo json_encode([
                    "status" => "success",
                    "branchName" => [],
                    "totalEmployees" => [],
                    "totalCheckIns" => [],
                    "onLeave" => [],
                    "lateCheckIn" => [],
                    "earlyCheckOut" => [],
                    "absentees" => []
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Error in BranchWiseAttendanceForToday: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
    public function SectionEmployees() {
        include('config.inc');
        header('Content-Type: application/json');    
        try {       
            $data = [];                       

            $query = "
                SELECT DISTINCT
                    e.employeeName,
                    e.Designation,
                    s.sectionName,
                    l.fromDate,
                    l.toDate,
                    l.typeOfLeave,
                    l.status,
                    l.reason
                FROM tblEmployee e
                JOIN tblAssignedSection a ON e.employeeID = a.employeeID
                JOIN tblSection s ON a.sectionID = s.sectionID
                LEFT JOIN tblApplyLeave l ON e.employeeID = l.employeeID 
                    AND MONTH(l.fromDate) = ? 
                    AND YEAR(l.fromDate) = ?
                WHERE s.sectionID = ?
                AND a.isActive = 1
                ORDER BY e.employeeName ASC, l.fromDate ASC";

            $stmt = mysqli_prepare($connect_var, $query);
            if (!$stmt) {
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "iis", 
                $this->currentMonth,
                $this->currentYear,
                $this->sectionID
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
            $employees = [];
            $sectionName = "";
            $currentEmployee = null;
            
            while ($row = mysqli_fetch_assoc($result)) {
                if ($currentEmployee === null || $currentEmployee['employeeName'] !== $row['employeeName']) {
                    if ($currentEmployee !== null) {
                        $employees[] = $currentEmployee;
                    }
                    
                    $currentEmployee = [
                        'employeeName' => $row['employeeName'],
                        'designation' => $row['Designation'],
                        'leaves' => []
                    ];
                    $sectionName = $row['sectionName'];
                }
                
                if ($row['fromDate'] !== null) {
                    $currentEmployee['leaves'][] = [
                        'fromDate' => $row['fromDate'],
                        'toDate' => $row['toDate'],
                        'typeOfLeave' => $row['typeOfLeave'],
                        'status' => $row['status'], 
                        'reason' => $row['reason']
                    ];
                }
            }
            
            if ($currentEmployee !== null) {
                $employees[] = $currentEmployee;
            }
            
            $data['sectionName'] = $sectionName;
            $data['employees'] = $employees;
            
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
} 

function SectionWiseFetchDetails($decoded_items) {
    $SectionWiseFetchDetailsObject = new SectionWiseFetchDetailsComponent();
    if ($SectionWiseFetchDetailsObject->loadSectionWiseFetchDetails($decoded_items)) {
        $SectionWiseFetchDetailsObject->SectionWiseFetchDetails($decoded_items);
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}

function SectionEmployees($decoded_items) {
    $SectionWiseFetchDetailsObject = new SectionWiseFetchDetailsComponent();
    if ($SectionWiseFetchDetailsObject->loadSectionEmployees($decoded_items)) {
        $SectionWiseFetchDetailsObject->SectionEmployees();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function SectionWiseAttendanceDetails($decoded_items) {
    $SectionWiseFetchDetailsObject = new SectionWiseFetchDetailsComponent();
    if ($SectionWiseFetchDetailsObject->loadSectionWiseAttendanceDetails($decoded_items)) {
        $SectionWiseFetchDetailsObject->SectionWiseAttendanceDetails();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function SectionWiseAttendanceForToday($decoded_items) {
    $SectionWiseFetchDetailsObject = new SectionWiseFetchDetailsComponent();
    if ($SectionWiseFetchDetailsObject->loadSectionWiseAttendanceForToday($decoded_items)) {
        $SectionWiseFetchDetailsObject->SectionWiseAttendanceForToday();
    } else {        
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
?>