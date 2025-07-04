<?php
class ReportsComponent {    
    public $startDate;
    public $endDate;
    public $organisationID;
    public $selectedMonth;

    public function loadOrganisationID(array $data) {
        if (isset($data['organisationID'])) {
            $this->organisationID = $data['organisationID'];
            return true;
        }
        return false;
    }

    public function loadReportsforGivenDate(array $data) { 
        $this->startDate = $data['startDate'];
        $this->endDate = $data['endDate'];
        return true;
    }
    public function loadReportsforGivenDateforAll(array $data) { 
        $this->startDate = $data['startDate'];
        $this->endDate = $data['endDate'];
        return true;
    }
   
    public function GetAttendanceReport() {    
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $data = [];
            $queryforGetAttendanceReport = "SELECT DISTINCT
    e.empID AS Employee_ID,
    e.employeeName AS Employee_Name,
    e.employeePhone AS Employee_Phone,
    e.Designation,
    DATE_ADD(?, INTERVAL n.num DAY) AS attendanceDate,
    a.checkInTime AS CheckIn_Time,
    a.checkOutTime AS CheckOut_Time,
    CASE
        WHEN a.checkInTime IS NOT NULL THEN 'Present'
        WHEN EXISTS (
            SELECT 1
            FROM tblApplyLeave al
            WHERE al.employeeID = e.employeeID
              AND DATE_ADD(?, INTERVAL n.num DAY) BETWEEN al.fromDate AND al.toDate
              AND al.Status = 'Approved'
        ) THEN 'Leave'
        ELSE 'Absent'
    END AS Status,
    b.branchName
FROM 
    (
        SELECT empID, employeeName, employeePhone, Designation, employeeID
        FROM tblEmployee 
        WHERE isTemporary = 0 AND isActive = 1
    ) e
CROSS JOIN (
    SELECT a.N + b.N * 10 + c.N * 100 AS num
    FROM 
        (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
         UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a
    CROSS JOIN 
        (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
         UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) b
    CROSS JOIN 
        (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
         UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) c
    WHERE a.N + b.N * 10 + c.N * 100 <= DATEDIFF(?, ?)
) n
LEFT JOIN tblAttendance a 
    ON e.employeeID = a.employeeID  
    AND a.attendanceDate = DATE_ADD(?, INTERVAL n.num DAY)
INNER JOIN tblmapEmp m ON e.employeeID = m.employeeID
INNER JOIN tblBranch b ON m.branchID = b.branchID
WHERE 
    DATE_ADD(?, INTERVAL n.num DAY) BETWEEN ? AND ?
ORDER BY 
    e.empID, attendanceDate;
";

            $debug_query = str_replace(
                array_fill(0, 8, '?'),
                [
                    "'" . $this->startDate . "'",
                    "'" . $this->startDate . "'",
                    "'" . $this->endDate . "'",
                    "'" . $this->startDate . "'",
                    "'" . $this->startDate . "'",
                    "'" . $this->startDate . "'",
                    "'" . $this->startDate . "'",
                    "'" . $this->endDate . "'"
                ],
                $queryforGetAttendanceReport
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryforGetAttendanceReport);
            if (!$stmt) {
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ssssssss", 
                $this->startDate,  // For attendanceDate
                $this->startDate,  // For leave check
                $this->endDate,    // For DATEDIFF
                $this->startDate,  // For DATEDIFF
                $this->startDate,  // For attendance join
                $this->startDate,  // For WHERE clause
                $this->startDate,  // For WHERE clause start
                $this->endDate     // For WHERE clause end
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
            $countEmployee = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $countEmployee++;
                $data[] = $row;
            }

            if ($countEmployee > 0) {
                echo json_encode([
                    "status" => "success",  
                    "data" => $data
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message_text" => "No data found for any employee"
                ], JSON_FORCE_OBJECT);
            }

            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);
        } catch (Exception $e) {
            error_log("Error in GetValueDashboardforCheckin: " . $e->getMessage());     
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
    public function GetSectionWiseAttendanceReport() {    
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $data = [];
            $queryforGetAttendanceReport = "SELECT DISTINCT
    e.empID AS Employee_ID,
    e.employeeName AS Employee_Name,
    e.employeePhone AS Employee_Phone,
    e.Designation,
    s.sectionName AS Section_Name,
    DATE_ADD(?, INTERVAL n.num DAY) AS attendanceDate,
    a.checkInTime AS CheckIn_Time,
    a.checkOutTime AS CheckOut_Time,
    CASE
        WHEN a.checkInTime IS NOT NULL THEN 'Present'
        WHEN EXISTS (
            SELECT 1
            FROM tblApplyLeave al
            WHERE al.employeeID = e.employeeID
              AND DATE_ADD(?, INTERVAL n.num DAY) BETWEEN al.fromDate AND al.toDate
              AND al.Status = 'Approved'
        ) THEN 'Leave'
        ELSE 'Absent'
    END AS Status,
    b.branchName
FROM 
    (
        SELECT empID, employeeName, employeePhone, Designation, employeeID
        FROM tblEmployee 
        WHERE isTemporary = 0 AND isActive = 1
    ) e
CROSS JOIN (
    SELECT a.N + b.N * 10 + c.N * 100 AS num
    FROM 
        (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
         UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a
    CROSS JOIN 
        (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
         UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) b
    CROSS JOIN 
        (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
         UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) c
    WHERE a.N + b.N * 10 + c.N * 100 <= DATEDIFF(?, ?)
) n
LEFT JOIN tblAttendance a 
    ON e.employeeID = a.employeeID  
    AND a.attendanceDate = DATE_ADD(?, INTERVAL n.num DAY)
INNER JOIN tblmapEmp m ON e.employeeID = m.employeeID
INNER JOIN tblBranch b ON m.branchID = b.branchID
INNER JOIN tblAssignedSection sa ON sa.employeeID = e.employeeID
INNER JOIN tblSection s ON sa.sectionID = s.sectionID
WHERE 
    b.branchName = 'Head Office'
    AND DATE_ADD(?, INTERVAL n.num DAY) BETWEEN ? AND ?
ORDER BY 
    e.empID, attendanceDate;
";

            $debug_query = str_replace(
                array_fill(0, 7, '?'),
                [
                    "'" . $this->startDate . "'",
                    "'" . $this->startDate . "'",
                    "'" . $this->endDate . "'",
                    "'" . $this->startDate . "'",
                    "'" . $this->startDate . "'",
                    "'" . $this->startDate . "'",
                    "'" . $this->startDate . "'",
                    "'" . $this->endDate . "'"
                ],
                $queryforGetAttendanceReport
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryforGetAttendanceReport);
            if (!$stmt) {
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ssssssss", 
                $this->startDate,  // For attendanceDate
                $this->startDate,  // For leave check
                $this->endDate,    // For DATEDIFF
                $this->startDate,  // For DATEDIFF
                $this->startDate,  // For attendance join
                $this->startDate,  // For WHERE clause
                $this->startDate,  // For WHERE clause start
                $this->endDate     // For WHERE clause end
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
            $countEmployee = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $countEmployee++;
                $data[] = $row;
            }

            if ($countEmployee > 0) {
                echo json_encode([
                    "status" => "success",  
                    "data" => $data
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message_text" => "No data found for any employee"
                ], JSON_FORCE_OBJECT);
            }

            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);
        } catch (Exception $e) {
            error_log("Error in GetValueDashboardforCheckin: " . $e->getMessage());     
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
    public function GetLeaveReport() {    
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $data = [];
            $queryforGetLeaveReport = "SELECT 
    e.empID AS Employee_ID, 
    e.employeeName AS Employee_Name, 
    e.Designation, 
    e.employeePhone AS Employee_Phone, 
    b.branchName AS Branch_Name,
    l.reason AS ReasonForLeave,
    l.typeOfLeave AS Type_Of_Leave, 
    l.leaveDuration AS Leave_Duration,
    l.createdOn AS Applied_On, 
    l.status AS Status, 
    l.fromDate AS From_Date, 
    l.toDate AS To_Date
FROM tblEmployee AS e
JOIN tblApplyLeave AS l ON e.employeeID = l.employeeID
JOIN tblmapEmp AS m ON e.employeeID = m.employeeID
JOIN tblBranch AS b ON m.branchID = b.branchID
WHERE l.createdOn BETWEEN ? AND ?
ORDER BY l.createdOn DESC;";
            error_log("Start Date: " . $this->startDate);
            error_log("End Date: " . $this->endDate);

            $debug_query = str_replace(
                array_fill(0, 2, '?'),
                [
                    "'" . $this->startDate . "'",
                    "'" . $this->endDate . "'"
                ],
                $queryforGetLeaveReport
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryforGetLeaveReport);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ss", 
                $this->startDate,
                $this->endDate
            );

            if (!mysqli_stmt_execute($stmt)) {
                error_log("Execute failed: " . mysqli_stmt_error($stmt));
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
            $countEmployee = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $countEmployee++;
                $data[] = $row;
            }

            error_log("Number of records found: " . $countEmployee);

            if ($countEmployee > 0) {
                echo json_encode([
                    "status" => "success",  
                    "data" => $data
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message_text" => "No data found for any employee"
                ], JSON_FORCE_OBJECT);
            }

            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);
        } catch (Exception $e) {
            error_log("Error in GetLeaveReport: " . $e->getMessage());     
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
    public function GetDesignationWiseAttendanceReport() {    
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $data = [];
            $queryforGetAttendanceReport = "SELECT
    e.designation,
    DATE_ADD(?, INTERVAL n.n DAY) AS AttendanceDate,
    SUM(CASE WHEN b.BranchName = 'Head Office' THEN 1 ELSE 0 END) AS HeadOffice_Total,
    SUM(CASE WHEN b.BranchName <> 'Head Office' THEN 1 ELSE 0 END) AS Branch_Total,
    SUM(CASE 
        WHEN b.BranchName = 'Head Office' AND a.CheckInTime IS NOT NULL THEN 1 ELSE 0 
    END) AS HeadOffice_Present,
    SUM(CASE 
        WHEN b.BranchName <> 'Head Office' AND a.CheckInTime IS NOT NULL THEN 1 ELSE 0 
    END) AS Branch_Present,
    SUM(CASE 
        WHEN b.BranchName = 'Head Office' 
             AND l.Status = 'Approved'
             AND l.FromDate <= DATE_ADD(?, INTERVAL n.n DAY)
             AND l.ToDate >= DATE_ADD(?, INTERVAL n.n DAY)
        THEN 1 ELSE 0 
    END) AS HeadOffice_Leave,
    SUM(CASE 
        WHEN b.BranchName <> 'Head Office' 
             AND l.Status = 'Approved'
             AND l.FromDate <= DATE_ADD(?, INTERVAL n.n DAY)
             AND l.ToDate >= DATE_ADD(?, INTERVAL n.n DAY)
        THEN 1 ELSE 0 
    END) AS Branch_Leave,

    -- Absent = Total - Present - Leave
    SUM(CASE WHEN b.BranchName = 'Head Office' THEN 1 ELSE 0 END)
    - SUM(CASE 
        WHEN b.BranchName = 'Head Office' AND a.CheckInTime IS NOT NULL THEN 1 ELSE 0 
    END)
    - SUM(CASE 
        WHEN b.BranchName = 'Head Office' 
             AND l.Status = 'Approved'
             AND l.FromDate <= DATE_ADD(?, INTERVAL n.n DAY)
             AND l.ToDate >= DATE_ADD(?, INTERVAL n.n DAY)
        THEN 1 ELSE 0 
    END) AS HeadOffice_Absent,

    SUM(CASE WHEN b.BranchName <> 'Head Office' THEN 1 ELSE 0 END)
    - SUM(CASE 
        WHEN b.BranchName <> 'Head Office' AND a.CheckInTime IS NOT NULL THEN 1 ELSE 0 
    END)
    - SUM(CASE 
        WHEN b.BranchName <> 'Head Office' 
             AND l.Status = 'Approved'
             AND l.FromDate <= DATE_ADD(?, INTERVAL n.n DAY)
             AND l.ToDate >= DATE_ADD(?, INTERVAL n.n DAY)
        THEN 1 ELSE 0 
    END) AS Branch_Absent

FROM (
    SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 
    UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 
    UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
) n

JOIN tblEmployee e ON 1 = 1
JOIN tblmapEmp m ON e.EmployeeID = m.EmployeeID
JOIN tblBranch b ON m.BranchID = b.BranchID

LEFT JOIN tblAttendance a 
    ON e.EmployeeID = a.EmployeeID 
   AND a.AttendanceDate = DATE_ADD(?, INTERVAL n.n DAY)

LEFT JOIN tblApplyLeave l 
    ON e.EmployeeID = l.EmployeeID 
   AND l.Status = 'Approved'
   AND DATE_ADD(?, INTERVAL n.n DAY) BETWEEN l.FromDate AND l.ToDate

WHERE e.isActive = 1 AND e.isTemporary = 0
  AND DATE_ADD(?, INTERVAL n.n DAY) <= ?

GROUP BY e.designation, n.n
ORDER BY 
    CASE e.designation
        WHEN 'Deputy General Manager' THEN 1
        WHEN 'Assistant General Manager' THEN 2
        WHEN 'IT Specialist' THEN 3
        WHEN 'PA TO EXECUTIVE' THEN 4
        WHEN 'Chief Manager' THEN 5
        WHEN 'Manager' THEN 6
        WHEN 'Assistant Manager' THEN 7
        WHEN 'Assistant' THEN 8
        WHEN 'System Admin' THEN 9
        WHEN 'Teller' THEN 10
        WHEN 'Sub Staff' THEN 11
        WHEN 'Sweeper' THEN 12
        WHEN 'Intern' THEN 13
        ELSE 14 END,
    e.designation,
    AttendanceDate;
";

            $debug_query = str_replace(
                array_fill(0, 12, '?'),
                [
                    "'" . $this->startDate . "'",
                    "'" . $this->startDate . "'",
                    "'" . $this->startDate . "'",
                    "'" . $this->startDate . "'",
                    "'" . $this->startDate . "'",
                    "'" . $this->startDate . "'",
                    "'" . $this->startDate . "'",
                    "'" . $this->startDate . "'",
                    "'" . $this->startDate . "'",
                    "'" . $this->startDate . "'",
                    "'" . $this->startDate . "'",
                    "'" . $this->startDate . "'",
                    "'" . $this->endDate . "'",
                    
                ],
                $queryforGetAttendanceReport
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryforGetAttendanceReport);
            if (!$stmt) {
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "sssssssssssss", 
                $this->startDate,  // For attendanceDate
                $this->startDate,  // For HeadOffice_Leave
                $this->startDate,  // For HeadOffice_Leave
                $this->startDate,  // For Branch_Leave
                $this->startDate,  // For Branch_Leave
                $this->startDate,  // For HeadOffice_Absent
                $this->startDate,  // For HeadOffice_Absent
                $this->startDate,  // For Branch_Absent
                $this->startDate,  // For Branch_Absent
                $this->startDate,  // For attendance join
                $this->startDate,  // For leave join
                $this->startDate,  // For WHERE clause
                $this->endDate     // For WHERE clause end
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
            $designation = [];  
            $attendanceDate = [];
            $headOfficePresent = [];
            $branchPresent = [];
            $headOfficeLeave = [];
            $branchLeave = [];
            $headOfficeTotal = [];
            $branchTotal = [];
            $headOfficeAbsent = [];
            $branchAbsent = [];
            $countEmployee = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $countEmployee++;
                $designation[] = $row['designation'];
                $attendanceDate[] = $row['AttendanceDate'];
                $headOfficePresent[] = $row['HeadOffice_Present'];
                $branchPresent[] = $row['Branch_Present'];
                $headOfficeLeave[] = $row['HeadOffice_Leave'];
                $branchLeave[] = $row['Branch_Leave'];
                $headOfficeTotal[] = $row['HeadOffice_Total'];
                $branchTotal[] = $row['Branch_Total'];
                $headOfficeAbsent[] = $row['HeadOffice_Absent'];
                $branchAbsent[] = $row['Branch_Absent'];
            }

            if ($countEmployee > 0) {
                echo json_encode([
                    "status" => "success",  
                    "designation" => $designation ?? [],
                    "attendanceDate" => $attendanceDate ?? [],
                    "headOfficePresent" => $headOfficePresent ?? [],
                    "branchPresent" => $branchPresent ?? [],
                    "headOfficeLeave" => $headOfficeLeave ?? [],
                    "branchLeave" => $branchLeave ?? [],
                    "headOfficeTotal" => $headOfficeTotal ?? [],
                    "branchTotal" => $branchTotal ?? [],
                    "headOfficeAbsent" => $headOfficeAbsent ?? [],
                    "branchAbsent" => $branchAbsent ?? []
                ]);
            } else {
                echo json_encode([
                    "status" => "success",
                    "designation" => [],
                    "attendanceDate" => [],
                    "headOfficePresent" => [],
                    "branchPresent" => [],
                    "headOfficeLeave" => [],
                    "branchLeave" => [],
                    "headOfficeTotal" => [],
                    "branchTotal" => [],
                    "headOfficeAbsent" => [],
                    "branchAbsent" => []
                ], JSON_FORCE_OBJECT);
            }

            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);
        } catch (Exception $e) {
            error_log("Error in GetValueDashboardforCheckin: " . $e->getMessage());     
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }

    public function GetManagementLeaveReport() {
        include(dirname(__FILE__) . '/../../config.inc');
        header('Content-Type: application/json');
        try {
            error_log("GetManagementLeaveReport called with month: " . $this->selectedMonth . " and orgID: " . $this->organisationID);
            
            // Convert single month number to YYYY-MM format
            $formattedMonth = '2025-' . str_pad($this->selectedMonth, 2, '0', STR_PAD_LEFT);
            error_log("Formatted month: " . $formattedMonth);
            
            $query = "
                SELECT 
                    (@row_number := @row_number + 1) as sNo,
                    subquery.*
                FROM (
                    SELECT 
                        e.employeeName,
                        e.empID as employeeCode,
                        e.Designation,
                        COALESCE(SUM(CASE WHEN al.typeOfLeave = 'Casual Leave' THEN al.leaveDuration ELSE 0 END), 0) as cl,
                        COALESCE(SUM(CASE WHEN al.typeOfLeave = 'Privilege Leave' THEN al.leaveDuration ELSE 0 END), 0) as pl,
                        COALESCE(SUM(CASE WHEN al.typeOfLeave = 'Privilege Leave(Medical Grounds)' THEN al.leaveDuration ELSE 0 END), 0) as plMedical,
                        COALESCE(SUM(CASE WHEN al.typeOfLeave = 'Medical Leave' THEN al.leaveDuration ELSE 0 END), 0) as sl,
                        COALESCE(SUM(CASE 
                            WHEN al.typeOfLeave NOT IN ('Maternity Leave') 
                            THEN al.leaveDuration 
                            ELSE 0 
                        END), 0) as total
                    FROM 
                        tblEmployee e
                    LEFT JOIN 
                        tblApplyLeave al ON e.employeeID = al.employeeID 
                        AND al.status = 'Approved'
                        AND DATE_FORMAT(al.fromDate, '%Y-%m') = ?
                        AND al.typeOfLeave NOT IN ('Maternity Leave')
                    WHERE 
                        e.organisationID = ? AND
                        e.isActive = 1
                    GROUP BY
                        e.employeeID,
                        e.employeeName,
                        e.empID,
                        e.Designation
                    HAVING total > 0
                    ORDER BY 
                        total DESC,
                        e.employeeName ASC
                ) subquery,
                (SELECT @row_number := 0) r";

            error_log("Preparing query: " . $query);
            $stmt = mysqli_prepare($connect_var, $query);
            if (!$stmt) {
                error_log("Database prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
            }

            error_log("Binding parameters: " . $formattedMonth . ", " . $this->organisationID);
            mysqli_stmt_bind_param($stmt, "si", 
                $formattedMonth,
                $this->organisationID
            );
            
            error_log("Executing query");
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Database execute failed: " . mysqli_error($connect_var));
                throw new Exception("Database execute failed: " . mysqli_error($connect_var));
            }

            $result = mysqli_stmt_get_result($stmt);
            $leaveReport = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $leaveReport[] = $row;
            }
            error_log("Found " . count($leaveReport) . " records");

            mysqli_stmt_close($stmt);

            $response = [
                "status" => "success",
                "data" => $leaveReport
            ];
            error_log("Sending response: " . json_encode($response));
            echo json_encode($response);

        } catch (Exception $e) {
            error_log("Error in GetManagementLeaveReport: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }

    public function GetDesignationWiseLeaveReport() {
        include(dirname(__FILE__) . '/../../config.inc');
        header('Content-Type: application/json');
        try {
            // First get all dates in the selected month
            $dates = [];
            $daysInMonth = date('t', strtotime($this->selectedMonth));
            $monthStart = date('Y-m-01', strtotime($this->selectedMonth));
            $monthEnd = date('Y-m-t', strtotime($this->selectedMonth));
            
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $dates[] = date('Y-m-d', strtotime($this->selectedMonth . '-' . $i));
            }

            // Define the order of designations
            $designationOrder = [
                'Deputy General Manager',
                'Assistant General Manager', 
                'IT Specialist',
                'PA to Executive',
                'Chief Manager',
                'Manager',
                'Assistant Manager',
                'System Admin',
                'Assistant',
                'Teller',
                'Sub Staff',
                'Intern',
                'Sweeper'
            ];

            // Get leave data for the month - using a simpler approach
            $query = "
                SELECT 
                    e.Designation,
                    al.fromDate,
                    al.toDate,
                    e.employeeID,
                    e.employeeName,
                    e.empID,
                    al.typeOfLeave
                FROM 
                    tblEmployee e
                JOIN 
                    tblApplyLeave al ON e.employeeID = al.employeeID
                WHERE 
                    e.organisationID = ? AND
                    e.isActive = 1 AND
                    al.status = 'Approved' AND
                    al.typeOfLeave IN ('Casual Leave', 'Privilege Leave', 'PrivilegeLeave(Medical Grounds)', 'Medical Leave') AND
                    (
                        (al.fromDate >= ? AND al.fromDate <= ?) OR
                        (al.toDate >= ? AND al.toDate <= ?) OR
                        (al.fromDate <= ? AND al.toDate >= ?)
                    )
                ORDER BY 
                    al.fromDate";

            $stmt = mysqli_prepare($connect_var, $query);
            if (!$stmt) {
                throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
            }

            mysqli_stmt_bind_param($stmt, "sssssss", 
                $this->organisationID,
                $monthStart,
                $monthEnd,
                $monthStart,
                $monthEnd,
                $monthStart,
                $monthEnd
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Database execute failed: " . mysqli_error($connect_var));
                throw new Exception("Database execute failed: " . mysqli_error($connect_var));
            }

            $result = mysqli_stmt_get_result($stmt);
            $leaveData = [];
            while ($row = mysqli_fetch_assoc($result)) {
                if (!isset($leaveData[$row['Designation']])) {
                    $leaveData[$row['Designation']] = [
                        'counts' => array_fill(0, $daysInMonth, 0),
                        'details' => array_fill(0, $daysInMonth, '')
                    ];
                }
                
                // Process each day in the leave period
                $fromDate = new DateTime($row['fromDate']);
                $toDate = new DateTime($row['toDate']);
                $monthStartDate = new DateTime($monthStart);
                $monthEndDate = new DateTime($monthEnd);
                
                // Adjust dates to be within the month
                $startDate = max($fromDate, $monthStartDate);
                $endDate = min($toDate, $monthEndDate);
                
                $typeOfLeave = $row['typeOfLeave'] == 'PrivilegeLeave(Medical Grounds)' ? 'PL(Medical)' : $row['typeOfLeave'];
                $employeeDetail = $row['employeeName'] . ' (' . $row['empID'] . ' - ' . $typeOfLeave . ')';
                
                while ($startDate <= $endDate) {
                    $day = (int)$startDate->format('d') - 1;
                    $leaveData[$row['Designation']]['counts'][$day]++;
                    if ($leaveData[$row['Designation']]['details'][$day] != '') {
                        $leaveData[$row['Designation']]['details'][$day] .= ', ';
                    }
                    $leaveData[$row['Designation']]['details'][$day] .= $employeeDetail;
                    $startDate->add(new DateInterval('P1D'));
                }
            }

            mysqli_stmt_close($stmt);

            // Format the response with all designations in order
            $formattedReport = [];
            foreach ($designationOrder as $designation) {
                $row = [
                    'designation' => $designation
                ];
                
                // Initialize all days with 0 count
                for ($i = 0; $i < $daysInMonth; $i++) {
                    $row['day_' . ($i + 1)] = 0;
                }
                
                // If this designation has leave data, use it
                if (isset($leaveData[$designation])) {
                    foreach ($leaveData[$designation]['counts'] as $index => $count) {
                        $row['day_' . ($index + 1)] = $count;
                        if ($count > 0) {
                            $row['details_' . ($index + 1)] = $leaveData[$designation]['details'][$index];
                        }
                    }
                }
                
                $formattedReport[] = $row;
            }

            $response = [
                "status" => "success",
                "data" => [
                    "dates" => $dates,
                    "report" => $formattedReport
                ]
            ];
            
            echo json_encode($response);

        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }

    public function loadSelectedMonth(array $data) {
        if (isset($data['selectedMonth'])) {
            $this->selectedMonth = $data['selectedMonth'];
            return true;
        }
        return false;
    }
}
function GetAttendanceReport($decoded_items) {
    $ReportsComponentObject = new ReportsComponent();
    if ($ReportsComponentObject->loadReportsforGivenDate($decoded_items)) {
        $ReportsComponentObject->GetAttendanceReport();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }   
}
function GetSectionWiseAttendanceReport($decoded_items) {
    $ReportsComponentObject = new ReportsComponent();
    if ($ReportsComponentObject->loadReportsforGivenDate($decoded_items)) {
        $ReportsComponentObject->GetSectionWiseAttendanceReport();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function GetLeaveReport($decoded_items) {
    $ReportsComponentObject = new ReportsComponent();
    if ($ReportsComponentObject->loadReportsforGivenDate($decoded_items)) {
        $ReportsComponentObject->GetLeaveReport();
    } else {    
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function GetDesignationWiseAttendanceReport($decoded_items) {
    $ReportsComponentObject = new ReportsComponent();
    if ($ReportsComponentObject->loadReportsforGivenDate($decoded_items)) {
        $ReportsComponentObject->GetDesignationWiseAttendanceReport();
    } else {    
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
?>