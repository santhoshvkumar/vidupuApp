<?php
class ReportsComponent {    
    public $startDate;
    public $endDate;
    public $organisationID;
    public $selectedMonth;

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
    e.Designation,
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

GROUP BY e.Designation, n.n
ORDER BY AttendanceDate, e.Designation;
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
                $designation[] = $row['Designation'];
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
            $query = "
                SELECT 
                    ROW_NUMBER() OVER (ORDER BY e.employeeName) as sNo,
                    e.employeeName,
                    e.empID as employeeCode,
                    e.Designation,
                    COALESCE(lb.CasualLeave, 0) as cl,
                    COALESCE(lb.PrivilegeLeave, 0) as pl,
                    COALESCE(
                        (SELECT SUM(leaveDuration)
                        FROM tblApplyLeave al 
                        WHERE al.employeeID = e.employeeID 
                        AND al.typeOfLeave = 'PrivilegeLeave(Medical Grounds)'
                        AND al.status = 'Approved'
                        AND YEAR(al.fromDate) = YEAR(CURRENT_DATE())), 0) as plMedical,
                    COALESCE(lb.MedicalLeave, 0) as sl,
                    (COALESCE(lb.CasualLeave, 0) + 
                     COALESCE(lb.PrivilegeLeave, 0) + 
                     COALESCE(lb.MedicalLeave, 0)) as total
                FROM 
                    tblEmployeeStructure e
                LEFT JOIN 
                    tblLeaveBalance lb ON e.employeeID = lb.EmployeeID
                WHERE 
                    e.organisationID = ? AND
                    e.isActive = 1 AND
                    lb.Year = YEAR(CURRENT_DATE())
                ORDER BY 
                    total DESC";

            $stmt = mysqli_prepare($connect_var, $query);
            if (!$stmt) {
                throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
            }

            mysqli_stmt_bind_param($stmt, "i", $this->organisationID);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Database execute failed: " . mysqli_error($connect_var));
            }

            $result = mysqli_stmt_get_result($stmt);
            $leaveReport = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $leaveReport[] = $row;
            }

            mysqli_stmt_close($stmt);

            echo json_encode([
                "status" => "success",
                "data" => $leaveReport
            ]);

        } catch (Exception $e) {
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
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $dates[] = date('Y-m-d', strtotime($this->selectedMonth . '-' . $i));
            }

            // Get leave data for the month
            $query = "
                SELECT 
                    e.Designation,
                    al.fromDate as leaveDate,
                    CASE 
                        WHEN al.typeOfLeave = 'PrivilegeLeave(Medical Grounds)'
                        THEN 'PL(Medical)'
                        ELSE al.typeOfLeave
                    END as typeOfLeave,
                    GROUP_CONCAT(DISTINCT e.employeeName ORDER BY e.employeeName ASC SEPARATOR ', ') as employeeNames
                FROM 
                    tblEmployeeStructure e
                JOIN 
                    tblApplyLeave al ON e.employeeID = al.employeeID
                WHERE 
                    e.organisationID = ? AND
                    e.isActive = 1 AND
                    al.status = 'Approved' AND
                    al.typeOfLeave IN ('Casual Leave', 'Privilege Leave', 'PrivilegeLeave(Medical Grounds)', 'Medical Leave') AND
                    (DATE_FORMAT(al.fromDate, '%Y-%m') = DATE_FORMAT(?, '%Y-%m') OR
                     DATE_FORMAT(al.toDate, '%Y-%m') = DATE_FORMAT(?, '%Y-%m'))
                GROUP BY 
                    e.Designation, al.fromDate, 
                    CASE 
                        WHEN al.typeOfLeave = 'PrivilegeLeave(Medical Grounds)'
                        THEN 'PL(Medical)'
                        ELSE al.typeOfLeave
                    END
                ORDER BY 
                    e.Designation, al.fromDate";

            $stmt = mysqli_prepare($connect_var, $query);
            if (!$stmt) {
                throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
            }

            mysqli_stmt_bind_param($stmt, "iss", $this->organisationID, $this->selectedMonth, $this->selectedMonth);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Database execute failed: " . mysqli_error($connect_var));
            }

            $result = mysqli_stmt_get_result($stmt);
            $leaveData = [];
            while ($row = mysqli_fetch_assoc($result)) {
                if (!isset($leaveData[$row['Designation']])) {
                    $leaveData[$row['Designation']] = array_fill(0, $daysInMonth, '');
                }
                $day = (int)date('d', strtotime($row['leaveDate'])) - 1;
                $currentValue = $leaveData[$row['Designation']][$day];
                $newEntry = $row['employeeNames'] . ' (' . $row['typeOfLeave'] . ')';
                $leaveData[$row['Designation']][$day] = $currentValue ? $currentValue . '; ' . $newEntry : $newEntry;
            }

            mysqli_stmt_close($stmt);

            // Format the response
            $formattedReport = [];
            foreach ($leaveData as $designation => $daysData) {
                $row = [
                    'designation' => $designation
                ];
                foreach ($daysData as $index => $employees) {
                    $row['day_' . ($index + 1)] = $employees;
                }
                $formattedReport[] = $row;
            }

            echo json_encode([
                "status" => "success",
                "data" => [
                    "dates" => $dates,
                    "report" => $formattedReport
                ]
            ]);

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