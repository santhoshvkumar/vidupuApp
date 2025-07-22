<?php
class ReportsComponent {    
    public $startDate;
    public $endDate;
    public $organisationID;
    public $selectedMonth;
    public $employeeID;
    public $selectedYear;
    public $selectedDate;

    public function loadOrganisationID(array $data) {
        if (isset($data['organisationID'])) {
            $this->organisationID = $data['organisationID'];
            return true;
        }
        return false;
    }

    public function loadEmployeeID(array $data) {
        if (isset($data['employeeID'])) {
            $this->employeeID = $data['employeeID'];
            return true;
        }
        return false;
    }

    public function loadSelectedYear(array $data) {
        if (isset($data['selectedYear'])) {
            $this->selectedYear = $data['selectedYear'];
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
            $stmt = mysqli_prepare($connect_var, $queryforGetLeaveReport);
            if (!$stmt) {
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ss", 
                $this->startDate,
                $this->endDate
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
            
            // Check if this is a yearly request (format: 2025-01 means year 2025)
            $isYearlyRequest = false;
            $year = '';
            if (preg_match('/^(\d{4})-01$/', $this->selectedMonth, $matches)) {
                $isYearlyRequest = true;
                $year = $matches[1];
            }
            
            if ($isYearlyRequest) {
                // Handle yearly report
                $this->getYearlyManagementLeaveReport($year, $connect_var);
            } else {
                // Handle monthly report (existing logic)
                $this->getMonthlyManagementLeaveReport($connect_var);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
    
    private function getYearlyManagementLeaveReport($year, $connect_var) {
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
                    AND YEAR(al.fromDate) = ?
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

        $stmt = mysqli_prepare($connect_var, $query);
        if (!$stmt) {
            throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
        }

        mysqli_stmt_bind_param($stmt, "si", 
            $year,
            $this->organisationID
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Database execute failed: " . mysqli_error($connect_var));
        }

        $result = mysqli_stmt_get_result($stmt);
        $leaveReport = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $leaveReport[] = $row;
        }

        mysqli_stmt_close($stmt);

        $response = [
            "status" => "success",
            "data" => $leaveReport
        ];
        echo json_encode($response);
    }
    
    private function getMonthlyManagementLeaveReport($connect_var) {
        // Convert single month number to YYYY-MM format
        $formattedMonth = '2025-' . str_pad($this->selectedMonth, 2, '0', STR_PAD_LEFT);
        
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

        $stmt = mysqli_prepare($connect_var, $query);
        if (!$stmt) {
            throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
        }

        mysqli_stmt_bind_param($stmt, "si", 
            $formattedMonth,
            $this->organisationID
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Database execute failed: " . mysqli_error($connect_var));
        }

        $result = mysqli_stmt_get_result($stmt);
        $leaveReport = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $leaveReport[] = $row;
        }

        mysqli_stmt_close($stmt);

        $response = [
            "status" => "success",
            "data" => $leaveReport
        ];
        echo json_encode($response);
    }

    public function GetDesignationWiseLeaveReport() {
        include(dirname(__FILE__) . '/../../config.inc');
        header('Content-Type: application/json');
        try {
            // Check if this is a yearly request (format: 2025-01 means year 2025)
            $isYearlyRequest = false;
            $year = '';
            if (preg_match('/^(\d{4})-01$/', $this->selectedMonth, $matches)) {
                $isYearlyRequest = true;
                $year = $matches[1];
            }
            
            if ($isYearlyRequest) {
                // Handle yearly report
                $this->getYearlyDesignationWiseLeaveReport($year, $connect_var);
            } else {
                // Handle monthly report (existing logic)
                $this->getMonthlyDesignationWiseLeaveReport($connect_var);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
    
    private function getYearlyDesignationWiseLeaveReport($year, $connect_var) {
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

        // Get leave data for the entire year
        $query = "
            SELECT 
                e.Designation,
                MONTH(al.fromDate) as leaveMonth,
                al.fromDate,
                al.toDate,
                e.employeeID,
                e.employeeName,
                e.empID,
                al.typeOfLeave,
                al.leaveDuration
            FROM 
                tblEmployee e
            JOIN 
                tblApplyLeave al ON e.employeeID = al.employeeID
            WHERE 
                e.organisationID = ? AND
                e.isActive = 1 AND
                al.status = 'Approved' AND
                YEAR(al.fromDate) = ?
            ORDER BY 
                al.fromDate";

        $stmt = mysqli_prepare($connect_var, $query);
        if (!$stmt) {
            throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
        }

        mysqli_stmt_bind_param($stmt, "is", 
            $this->organisationID,
            $year
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Database execute failed: " . mysqli_error($connect_var));
        }

        $result = mysqli_stmt_get_result($stmt);
        $leaveData = [];
        while ($row = mysqli_fetch_assoc($result)) {
            if (!isset($leaveData[$row['Designation']])) {
                $leaveData[$row['Designation']] = [
                    'counts' => array_fill(0, 12, 0), // 12 months
                    'details' => array_fill(0, 12, '')
                ];
            }
            
            // Process leave duration for each month
            $fromDate = new DateTime($row['fromDate']);
            $toDate = new DateTime($row['toDate']);
            $yearStart = new DateTime("$year-01-01");
            $yearEnd = new DateTime("$year-12-31");
            
            // Adjust dates to be within the year
            $startDate = max($fromDate, $yearStart);
            $endDate = min($toDate, $yearEnd);
            
            $typeOfLeave = $row['typeOfLeave'] == 'Privilege Leave(Medical Grounds)' ? 'PL(Medical)' : $row['typeOfLeave'];
            $employeeDetail = $row['employeeName'] . ' (' . $row['empID'] . ' - ' . $typeOfLeave . ')';
            
            // Calculate leave days per month
            $currentDate = clone $startDate;
            while ($currentDate <= $endDate) {
                $monthIndex = (int)$currentDate->format('n') - 1; // 0-based month index
                $leaveData[$row['Designation']]['counts'][$monthIndex]++;
                
                if ($leaveData[$row['Designation']]['details'][$monthIndex] != '') {
                    $leaveData[$row['Designation']]['details'][$monthIndex] .= ', ';
                }
                $leaveData[$row['Designation']]['details'][$monthIndex] .= $employeeDetail;
                
                $currentDate->add(new DateInterval('P1D'));
            }
        }

        mysqli_stmt_close($stmt);

        // Format the response with all designations in order
        $formattedReport = [];
        foreach ($designationOrder as $designation) {
            $row = [
                'designation' => $designation
            ];
            
            // Initialize all months with 0 count
            for ($i = 0; $i < 12; $i++) {
                $row['month_' . ($i + 1)] = 0;
            }
            
            // If this designation has leave data, use it
            if (isset($leaveData[$designation])) {
                foreach ($leaveData[$designation]['counts'] as $index => $count) {
                    $row['month_' . ($index + 1)] = $count;
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
                "dates" => [], // Empty for yearly report
                "report" => $formattedReport
            ]
        ];
        
        echo json_encode($response);
    }
    
    private function getMonthlyDesignationWiseLeaveReport($connect_var) {
        // Original monthly logic
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

        // Get leave data for the month - using dashboard logic (count ALL approved leaves)
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
    }

    public function GetMonthlyAttendanceSummaryReport() {
        include(dirname(__FILE__) . '/../../config.inc');
        header('Content-Type: application/json');
        try {
            // Get year and month
            $currentYear = date('Y');
            $selectedMonth = str_pad($this->selectedMonth, 2, '0', STR_PAD_LEFT);
            $monthStart = "$currentYear-$selectedMonth-01";
            $monthEnd = date('Y-m-t', strtotime($monthStart));
            
            // Get current date to limit data "as of today"
            $currentDate = date('Y-m-d');
            $currentDay = (int)date('d');
            $isCurrentMonth = (date('Y-m') === "$currentYear-$selectedMonth");
            
            // If we're in the selected month, limit to today's date
            if ($isCurrentMonth) {
                $monthEnd = min($monthEnd, $currentDate);
            }

            // Get month name for display
            $monthNames = [
                1 => 'JANUARY', 2 => 'FEBRUARY', 3 => 'MARCH', 4 => 'APRIL',
                5 => 'MAY', 6 => 'JUNE', 7 => 'JULY', 8 => 'AUGUST',
                9 => 'SEPTEMBER', 10 => 'OCTOBER', 11 => 'NOVEMBER', 12 => 'DECEMBER'
            ];
            $monthName = $monthNames[$this->selectedMonth] ?? '';

            // 1. Get working days for the month - use numeric month format
            $workingDaysQuery = "SELECT noOfWorkingDays FROM tblworkingdays WHERE monthName = ? AND year = ?";
            $stmt = mysqli_prepare($connect_var, $workingDaysQuery);
            mysqli_stmt_bind_param($stmt, "ss", $selectedMonth, $currentYear);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $workingDaysData = mysqli_fetch_assoc($result);
            $totalWorkingDays = $workingDaysData['noOfWorkingDays'] ?? 0;
            
            // Working days will be calculated later based on current month or full month
            $workingDays = $totalWorkingDays;
            mysqli_stmt_close($stmt);

            // 2. Get all active, non-temporary employees for the org
            $employeeQuery = "SELECT employeeID FROM tblEmployee WHERE organisationID = ? AND isActive = 1 AND isTemporary = 0";
            $stmt = mysqli_prepare($connect_var, $employeeQuery);
            mysqli_stmt_bind_param($stmt, "i", $this->organisationID);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $employeeIDs = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $employeeIDs[] = $row['employeeID'];
            }
            mysqli_stmt_close($stmt);
            $totalEmployees = count($employeeIDs);

            // 3. Fetch all attendance records for these employees up to today
            $attendanceQuery = "SELECT employeeID, attendanceDate, checkInTime FROM tblAttendance WHERE employeeID IN (" . implode(",", array_fill(0, count($employeeIDs), '?')) . ") AND attendanceDate BETWEEN ? AND ?";
            $types = str_repeat('i', count($employeeIDs)) . 'ss';
            $params = array_merge($employeeIDs, [$monthStart, $monthEnd]);
            $stmt = mysqli_prepare($connect_var, $attendanceQuery);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $attendanceMap = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $attendanceMap[$row['employeeID'] . '_' . $row['attendanceDate']] = $row['checkInTime'];
            }
            mysqli_stmt_close($stmt);

            // 4. Fetch all approved leaves for these employees up to today
            $leaveQuery = "SELECT employeeID, fromDate, toDate FROM tblApplyLeave WHERE employeeID IN (" . implode(",", array_fill(0, count($employeeIDs), '?')) . ") AND status = 'Approved' AND ((fromDate <= ? AND toDate >= ?) OR (fromDate >= ? AND fromDate <= ?))";
            $types = str_repeat('i', count($employeeIDs)) . 'ssss';
            $params = array_merge($employeeIDs, [$monthEnd, $monthStart, $monthStart, $monthEnd]);
            $stmt = mysqli_prepare($connect_var, $leaveQuery);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $leaveMap = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $from = $row['fromDate'];
                $to = $row['toDate'];
                $emp = $row['employeeID'];
                $period = new DatePeriod(new DateTime($from), new DateInterval('P1D'), (new DateTime($to))->modify('+1 day'));
                foreach ($period as $date) {
                    $d = $date->format('Y-m-d');
                    // Only count leaves up to today for current month
                    if (date('Y-m') === "$currentYear-$selectedMonth" && $d > $currentDate) {
                        continue;
                    }
                    $leaveMap[$emp . '_' . $d] = true;
                }
            }
            mysqli_stmt_close($stmt);

            // 5. Calculate counts up to today
            $presentCount = 0;
            $leaveCount = 0;
            $absentCount = 0;
            
            // For current month, only count up to today
            if ($isCurrentMonth) {
                $workingDaysUpToToday = 0;
                $saturdayCount = 0;
                for ($day = 1; $day <= $currentDay; $day++) {
                    $date = date('Y-m-d', strtotime("$currentYear-$selectedMonth-$day"));
                    $dayOfWeek = date('N', strtotime($date)); // 6 = Saturday, 7 = Sunday
                    $isWorkingDay = false;
                    if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                        $isWorkingDay = true;
                        error_log("Working day: $date (Weekday)");
                    } elseif ($dayOfWeek == 6) {
                        $saturdayCount++;
                        if ($saturdayCount == 1 || $saturdayCount == 3) {
                            $isWorkingDay = true;
                            error_log("Working day: $date (Saturday, Nth $saturdayCount)");
                        }
                    }
                    if ($isWorkingDay && !$this->isHoliday($date, $connect_var)) {
                        $workingDaysUpToToday++;
                    } else if ($isWorkingDay) {
                        error_log("Holiday: $date");
                    }
                }
                $workingDays = $workingDaysUpToToday;
                $totalManDays = $totalEmployees * $workingDays;
                // Only count attendance and leave for working days up to today
                $presentCount = 0;
                $leaveCount = 0;
                foreach ($employeeIDs as $emp) {
                    $saturdayCount = 0;
                for ($day = 1; $day <= $currentDay; $day++) {
                    $date = date('Y-m-d', strtotime("$currentYear-$selectedMonth-$day"));
                        $dayOfWeek = date('N', strtotime($date));
                        $isWorkingDay = false;
                        if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                            $isWorkingDay = true;
                        } elseif ($dayOfWeek == 6) {
                            $saturdayCount++;
                            if ($saturdayCount == 1 || $saturdayCount == 3) {
                                $isWorkingDay = true;
                            }
                        }
                        if ($isWorkingDay && !$this->isHoliday($date, $connect_var)) {
                        $key = $emp . '_' . $date;
                        if (isset($attendanceMap[$key]) && $attendanceMap[$key]) {
                            $presentCount++;
                        } elseif (isset($leaveMap[$key])) {
                            $leaveCount++;
                        }
                    }
                }
                }
                $absentCount = $totalManDays - $presentCount - $leaveCount;
                if ($absentCount < 0) $absentCount = 0;
            } else {
                $daysInMonth = date('t', strtotime($monthStart));
                $workingDaysFullMonth = 0;
                $saturdayCount = 0;
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $date = date('Y-m-d', strtotime("$currentYear-$selectedMonth-$day"));
                    $dayOfWeek = date('N', strtotime($date));
                    $isWorkingDay = false;
                    if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                        $isWorkingDay = true;
                        error_log("Working day: $date (Weekday)");
                    } elseif ($dayOfWeek == 6) {
                        $saturdayCount++;
                        if ($saturdayCount == 1 || $saturdayCount == 3) {
                            $isWorkingDay = true;
                            error_log("Working day: $date (Saturday, Nth $saturdayCount)");
                        }
                    }
                    if ($isWorkingDay && !$this->isHoliday($date, $connect_var)) {
                        $workingDaysFullMonth++;
                    } else if ($isWorkingDay) {
                        error_log("Holiday: $date");
                    }
                }
                $workingDays = $workingDaysFullMonth;
                $totalManDays = $totalEmployees * $workingDays;
                $presentCount = 0;
                $leaveCount = 0;
                    foreach ($employeeIDs as $emp) {
                    $saturdayCount = 0;
                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        $date = date('Y-m-d', strtotime("$currentYear-$selectedMonth-$day"));
                        $dayOfWeek = date('N', strtotime($date));
                        $isWorkingDay = false;
                        if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                            $isWorkingDay = true;
                        } elseif ($dayOfWeek == 6) {
                            $saturdayCount++;
                            if ($saturdayCount == 1 || $saturdayCount == 3) {
                                $isWorkingDay = true;
                            }
                        }
                        if ($isWorkingDay && !$this->isHoliday($date, $connect_var)) {
                        $key = $emp . '_' . $date;
                        if (isset($attendanceMap[$key]) && $attendanceMap[$key]) {
                            $presentCount++;
                        } elseif (isset($leaveMap[$key])) {
                            $leaveCount++;
                        }
                    }
                }
                }
                $absentCount = $totalManDays - $presentCount - $leaveCount;
                if ($absentCount < 0) $absentCount = 0;
            }

            // 6. Calculate percentages
            $presentPercentage = $totalManDays > 0 ? number_format(($presentCount / $totalManDays) * 100, 2, '.', '') : "0.00";
            $absentPercentage = $totalManDays > 0 ? number_format(($absentCount / $totalManDays) * 100, 2, '.', '') : "0.00";
            $leavePercentage = $totalManDays > 0 ? number_format(($leaveCount / $totalManDays) * 100, 2, '.', '') : "0.00";

            // Add "as of" indicator for current month
            $asOfText = $isCurrentMonth ? " (as of " . date('d M Y') . ")" : "";

            // Debug logging
            error_log("Monthly Attendance Summary Debug:");
            error_log("Current Date: " . $currentDate);
            error_log("Current Day: " . $currentDay);
            error_log("Selected Month: " . $selectedMonth);
            error_log("Is Current Month: " . ($isCurrentMonth ? 'Yes' : 'No'));
            error_log("Working Days (Total): " . $totalWorkingDays);
            error_log("Working Days (Final): " . $workingDays);
            error_log("Total Man Days: " . $totalManDays);
            error_log("Present Count: " . $presentCount);
            error_log("Absent Count: " . $absentCount);
            error_log("Leave Count: " . $leaveCount);
            error_log("Present %: " . $presentPercentage);
            error_log("Absent %: " . $absentPercentage);
            error_log("Leave %: " . $leavePercentage);

            $response = [
                "status" => "success",
                "data" => [
                    "month" => $monthName . $asOfText,
                    "totalEmployees" => $totalEmployees,
                    "workingDays" => $workingDays,
                    "totalManDays" => $totalManDays,
                    "presentCount" => $presentCount,
                    "absentCount" => $absentCount,
                    "leaveCount" => $leaveCount,
                    "presentPercentage" => $presentPercentage,
                    "absentPercentage" => $absentPercentage,
                    "leavePercentage" => $leavePercentage,
                    "isCurrentMonth" => $isCurrentMonth,
                    // For pie chart
                    "pieChart" => [
                        ["label" => "Present", "value" => (float)$presentPercentage],
                        ["label" => "Leave", "value" => (float)$leavePercentage],
                        ["label" => "Absent", "value" => (float)$absentPercentage],
                    ]
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

    private function isHoliday($date, $connect_var) {
        $formattedDate = date('Y-m-d', strtotime($date));
        $sql = "SELECT COUNT(*) as count FROM tblHoliday WHERE date = ?";
        $stmt = mysqli_prepare($connect_var, $sql);
        mysqli_stmt_bind_param($stmt, "s", $formattedDate);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $row['count'] > 0;
    }

    public function GetEmployees() {
        include(dirname(__FILE__) . '/../../config.inc');
        header('Content-Type: application/json');
        try {
            $query = "SELECT 
                employeeID,
                empID,
                employeeName,
                Designation
            FROM 
                tblEmployee 
            WHERE 
                organisationID = ? AND
                isActive = 1
            ORDER BY 
                employeeName ASC";

            $stmt = mysqli_prepare($connect_var, $query);
            if (!$stmt) {
                throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
            }

            mysqli_stmt_bind_param($stmt, "i", $this->organisationID);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Database execute failed: " . mysqli_error($connect_var));
            }

            $result = mysqli_stmt_get_result($stmt);
            $employees = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $employees[] = $row;
            }

            mysqli_stmt_close($stmt);

            $response = [
                "status" => "success",
                "data" => $employees
            ];
            echo json_encode($response);

        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }

    public function GetEmployeeLeaveReport() {
        include(dirname(__FILE__) . '/../../config.inc');
        header('Content-Type: application/json');
        try {
            // First get employee details
            $employeeQuery = "SELECT 
                employeeID,
                empID,
                employeeName,
                employeePhone,
                Designation
            FROM 
                tblEmployee 
            WHERE 
                employeeID = ? AND
                organisationID = ? AND
                isActive = 1";

            $stmt = mysqli_prepare($connect_var, $employeeQuery);
            if (!$stmt) {
                throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
            }

            mysqli_stmt_bind_param($stmt, "ii", $this->employeeID, $this->organisationID);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Database execute failed: " . mysqli_error($connect_var));
            }

            $result = mysqli_stmt_get_result($stmt);
            $employee = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$employee) {
                throw new Exception("Employee not found");
            }

            // Get leave data for the year
            $leaveQuery = "SELECT 
                MONTH(fromDate) as month,
                fromDate,
                toDate,
                typeOfLeave,
                leaveDuration,
                reason
            FROM 
                tblApplyLeave 
            WHERE 
                employeeID = ? AND
                status = 'Approved' AND
                YEAR(fromDate) = ?
            ORDER BY 
                fromDate";

            $stmt = mysqli_prepare($connect_var, $leaveQuery);
            if (!$stmt) {
                throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
            }

            mysqli_stmt_bind_param($stmt, "is", $this->employeeID, $this->selectedYear);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Database execute failed: " . mysqli_error($connect_var));
            }

            $result = mysqli_stmt_get_result($stmt);
            $leaveData = [];
            $monthlyData = array_fill(1, 12, [
                'leaveCount' => 0,
                'leaveDetails' => [],
                'dates' => []
            ]);

            while ($row = mysqli_fetch_assoc($result)) {
                $month = (int)$row['month'];
                $fromDate = new DateTime($row['fromDate']);
                $toDate = new DateTime($row['toDate']);
                $yearStart = new DateTime("{$this->selectedYear}-01-01");
                $yearEnd = new DateTime("{$this->selectedYear}-12-31");
                
                // Adjust dates to be within the year
                $startDate = max($fromDate, $yearStart);
                $endDate = min($toDate, $yearEnd);
                
                $typeOfLeave = $row['typeOfLeave'] == 'Privilege Leave(Medical Grounds)' ? 'PL(Medical)' : $row['typeOfLeave'];
                $leaveDetail = $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y') . ' (' . $typeOfLeave . ')';
                
                // Calculate leave days per month
                $currentDate = clone $startDate;
                $daysInMonth = 0;
                $datesInMonth = [];
                
                while ($currentDate <= $endDate) {
                    $currentMonth = (int)$currentDate->format('n');
                    if ($currentMonth === $month) {
                        $daysInMonth++;
                        $datesInMonth[] = $currentDate->format('Y-m-d');
                    }
                    $currentDate->add(new DateInterval('P1D'));
                }
                
                if ($daysInMonth > 0) {
                    $monthlyData[$month]['leaveCount'] += $daysInMonth;
                    $monthlyData[$month]['leaveDetails'][] = $leaveDetail;
                    $monthlyData[$month]['dates'] = array_merge($monthlyData[$month]['dates'], $datesInMonth);
                }
            }

            mysqli_stmt_close($stmt);

            // Get attendance data for the year
            $attendanceQuery = "SELECT 
                MONTH(attendanceDate) as month,
                attendanceDate,
                checkInTime,
                checkOutTime,
                isLateCheckIN,
                isEarlyCheckOut
            FROM 
                tblAttendance 
            WHERE 
                employeeID = ? AND
                YEAR(attendanceDate) = ?
            ORDER BY 
                attendanceDate";

            $stmt = mysqli_prepare($connect_var, $attendanceQuery);
            if (!$stmt) {
                throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
            }

            mysqli_stmt_bind_param($stmt, "is", $this->employeeID, $this->selectedYear);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Database execute failed: " . mysqli_error($connect_var));
            }

            $result = mysqli_stmt_get_result($stmt);
            $attendanceData = array_fill(1, 12, [
                'presentCount' => 0,
                'lateCheckIns' => [],
                'earlyCheckOuts' => []
            ]);

            while ($row = mysqli_fetch_assoc($result)) {
                $month = (int)$row['month'];
                $attendanceData[$month]['presentCount']++;
                
                if ($row['isLateCheckIN'] == 1) {
                    $attendanceData[$month]['lateCheckIns'][] = $row['attendanceDate'];
                }
                
                if ($row['isEarlyCheckOut'] == 1) {
                    $attendanceData[$month]['earlyCheckOuts'][] = $row['attendanceDate'];
                }
            }

            mysqli_stmt_close($stmt);

            // Format the response
            $formattedLeaveData = [];
            foreach ($monthlyData as $month => $data) {
                $formattedLeaveData[] = [
                    'month' => str_pad($month, 2, '0', STR_PAD_LEFT),
                    'leaveCount' => $data['leaveCount'],
                    'leaveDetails' => $data['leaveDetails'],
                    'dates' => array_unique($data['dates']),
                    'presentCount' => $attendanceData[$month]['presentCount'],
                    'lateCheckIns' => $attendanceData[$month]['lateCheckIns'],
                    'earlyCheckOuts' => $attendanceData[$month]['earlyCheckOuts']
                ];
            }

            $totalLeaves = array_sum(array_column($formattedLeaveData, 'leaveCount'));

            $response = [
                "status" => "success",
                "data" => [
                    "employee" => $employee,
                    "leaveData" => $formattedLeaveData,
                    "totalLeaves" => $totalLeaves
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

    public function GetDailyCheckoutReport() {
        include(dirname(__FILE__) . '/../../config.inc');
        header('Content-Type: application/json');
        try {
            $query = "SELECT 
                e.employeeID,
                e.empID,
                e.employeeName,
                e.Designation,
                e.employeePhone,
                b.branchName,
                a.checkInTime,
                a.checkOutTime,
                a.isAutoCheckout,
                a.TotalWorkingHour,
                a.reasonForCheckOut
            FROM 
                tblEmployee e
            LEFT JOIN 
                tblAttendance a ON e.employeeID = a.employeeID AND a.attendanceDate = ?
            LEFT JOIN 
                tblmapEmp m ON e.employeeID = m.employeeID
            LEFT JOIN 
                tblBranch b ON m.branchID = b.branchID
            WHERE 
                e.organisationID = ? AND
                e.isActive = 1 AND
                e.isTemporary = 0 AND
                a.attendanceDate IS NOT NULL
            ORDER BY 
                a.isAutoCheckout DESC,
                e.employeeName ASC";

            $stmt = mysqli_prepare($connect_var, $query);
            if (!$stmt) {
                throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
            }

            mysqli_stmt_bind_param($stmt, "si", $this->selectedDate, $this->organisationID);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Database execute failed: " . mysqli_error($connect_var));
            }

            $result = mysqli_stmt_get_result($stmt);
            $checkoutData = [];
            $autoCheckoutCount = 0;
            $manualCheckoutCount = 0;
            $noCheckoutCount = 0;

            while ($row = mysqli_fetch_assoc($result)) {
                $checkoutStatus = 'No Checkout';
                $checkoutType = 'None';
                
                if ($row['checkOutTime']) {
                    if ($row['isAutoCheckout'] == 1) {
                        $checkoutStatus = 'Auto Checkout';
                        $checkoutType = 'Auto';
                        $autoCheckoutCount++;
                    } else {
                        $checkoutStatus = 'Manual Checkout';
                        $checkoutType = 'Manual';
                        $manualCheckoutCount++;
                    }
                } else {
                    $noCheckoutCount++;
                }

                $checkoutData[] = [
                    'employeeID' => $row['employeeID'],
                    'empID' => $row['empID'],
                    'employeeName' => $row['employeeName'],
                    'designation' => $row['Designation'],
                    'employeePhone' => $row['employeePhone'],
                    'branchName' => $row['branchName'],
                    'checkInTime' => $row['checkInTime'],
                    'checkOutTime' => $row['checkOutTime'],
                    'totalWorkingHour' => $row['TotalWorkingHour'],
                    'checkoutStatus' => $checkoutStatus,
                    'checkoutType' => $checkoutType,
                    'reasonForCheckOut' => $row['reasonForCheckOut']
                ];
            }

            mysqli_stmt_close($stmt);

            $response = [
                "status" => "success",
                "data" => [
                    "checkoutData" => $checkoutData,
                    "summary" => [
                        "autoCheckoutCount" => $autoCheckoutCount,
                        "manualCheckoutCount" => $manualCheckoutCount,
                        "noCheckoutCount" => $noCheckoutCount,
                        "totalEmployees" => count($checkoutData)
                    ],
                    "selectedDate" => $this->selectedDate
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

    public function loadSelectedDate(array $data) {
        if (isset($data['selectedDate'])) {
            $this->selectedDate = $data['selectedDate'];
            return true;
        }
        return false;
    }

    public function GetMonthlyCheckoutReport() {
        include(dirname(__FILE__) . '/../../config.inc');
        header('Content-Type: application/json');
        try {
            // Get year and month
            $currentYear = date('Y');
            $selectedMonth = str_pad($this->selectedMonth, 2, '0', STR_PAD_LEFT);
            $monthStart = "$currentYear-$selectedMonth-01";
            $monthEnd = date('Y-m-t', strtotime($monthStart));
            
            // Get current date to limit data "as of today"
            $currentDate = date('Y-m-d');
            $isCurrentMonth = (date('Y-m') === "$currentYear-$selectedMonth");
            
            // If we're in the selected month, limit to today's date
            if ($isCurrentMonth) {
                $monthEnd = min($monthEnd, $currentDate);
            }

            $query = "
                SELECT 
                    (@row_number := @row_number + 1) as sNo,
                    e.employeeID,
                    e.empID,
                    e.employeeName,
                    e.Designation,
                    COALESCE(attendance_stats.present_days, 0) as present_days,
                    COALESCE(attendance_stats.absent_days, 0) as absent_days,
                    COALESCE(leave_stats.leave_days, 0) as leave_days,
                    COALESCE(attendance_stats.late_checkins, 0) as late_checkins,
                    COALESCE(attendance_stats.early_checkouts, 0) as early_checkouts,
                    COALESCE(attendance_stats.auto_checkouts, 0) as auto_checkouts,
                    attendance_stats.late_checkin_dates,
                    attendance_stats.early_checkout_dates,
                    attendance_stats.auto_checkout_dates
                FROM 
                    tblEmployee e
                LEFT JOIN (
                    SELECT 
                        a.employeeID,
                        COUNT(CASE WHEN a.checkInTime IS NOT NULL THEN 1 END) as present_days,
                        COUNT(CASE WHEN a.checkInTime IS NULL THEN 1 END) as absent_days,
                        COUNT(CASE WHEN a.isLateCheckIN = 1 THEN 1 END) as late_checkins,
                        COUNT(CASE WHEN a.isEarlyCheckOut = 1 THEN 1 END) as early_checkouts,
                        COUNT(CASE WHEN a.isAutoCheckout = 1 THEN 1 END) as auto_checkouts,
                        GROUP_CONCAT(
                            CASE WHEN a.isLateCheckIN = 1 
                                THEN DATE_FORMAT(a.attendanceDate, '%d/%m/%Y') 
                                ELSE NULL 
                            END
                            ORDER BY a.attendanceDate
                            SEPARATOR ', '
                        ) as late_checkin_dates,
                        GROUP_CONCAT(
                            CASE WHEN a.isEarlyCheckOut = 1 
                                THEN DATE_FORMAT(a.attendanceDate, '%d/%m/%Y') 
                                ELSE NULL 
                            END
                            ORDER BY a.attendanceDate
                            SEPARATOR ', '
                        ) as early_checkout_dates,
                        GROUP_CONCAT(
                            CASE WHEN a.isAutoCheckout = 1 
                                THEN DATE_FORMAT(a.attendanceDate, '%d/%m/%Y') 
                                ELSE NULL 
                            END
                            ORDER BY a.attendanceDate
                            SEPARATOR ', '
                        ) as auto_checkout_dates
                    FROM 
                        tblAttendance a
                    WHERE 
                        a.attendanceDate BETWEEN ? AND ?
                        AND a.organisationID = ?
                    GROUP BY 
                        a.employeeID
                ) attendance_stats ON e.employeeID = attendance_stats.employeeID
                LEFT JOIN (
                    SELECT 
                        al.employeeID,
                        SUM(al.leaveDuration) as leave_days
                    FROM 
                        tblApplyLeave al
                    WHERE 
                        al.status = 'Approved'
                        AND (
                            (al.fromDate >= ? AND al.fromDate <= ?) OR
                            (al.toDate >= ? AND al.toDate <= ?) OR
                            (al.fromDate <= ? AND al.toDate >= ?)
                        )
                    GROUP BY 
                        al.employeeID
                ) leave_stats ON e.employeeID = leave_stats.employeeID,
                (SELECT @row_number := 0) r
                WHERE 
                    e.organisationID = ? AND
                    e.isActive = 1 AND
                    e.isTemporary = 0
                ORDER BY 
                    e.employeeName ASC";

            $stmt = mysqli_prepare($connect_var, $query);
            if (!$stmt) {
                throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
            }

            mysqli_stmt_bind_param($stmt, "ssissssssi", 
                $monthStart,
                $monthEnd,
                $this->organisationID,
                $monthStart,
                $monthEnd,
                $monthStart,
                $monthEnd,
                $monthStart,
                $monthEnd,
                $this->organisationID
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Database execute failed: " . mysqli_error($connect_var));
            }

            $result = mysqli_stmt_get_result($stmt);
            $checkoutReport = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $checkoutReport[] = $row;
            }

            mysqli_stmt_close($stmt);

            // Debug: Log the first few records to see the data structure
            error_log("DEBUG - Monthly Checkout Report Data:");
            error_log("Total records: " . count($checkoutReport));
            if (count($checkoutReport) > 0) {
                error_log("First record: " . print_r($checkoutReport[0], true));
                // Check if auto_checkouts field exists in the first record
                if (isset($checkoutReport[0]['auto_checkouts'])) {
                    error_log("auto_checkouts field exists with value: " . $checkoutReport[0]['auto_checkouts']);
                } else {
                    error_log("auto_checkouts field is MISSING from the response");
                }
            }

            $response = [
                "status" => "success",
                "data" => $checkoutReport
            ];
            echo json_encode($response);

        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }

    public function DebugAutoCheckoutRecords() {
        include(dirname(__FILE__) . '/../../config.inc');
        header('Content-Type: application/json');
        try {
            $currentYear = date('Y');
            $selectedMonth = str_pad($this->selectedMonth, 2, '0', STR_PAD_LEFT);
            $monthStart = "$currentYear-$selectedMonth-01";
            $monthEnd = date('Y-m-t', strtotime($monthStart));
            
            // Debug query to check auto checkout records
            $debugQuery = "
                SELECT 
                    COUNT(*) as total_auto_checkouts,
                    COUNT(DISTINCT employeeID) as unique_employees_with_auto_checkout,
                    GROUP_CONCAT(DISTINCT employeeID) as employee_ids_with_auto_checkout
                FROM tblAttendance 
                WHERE isAutoCheckout = 1 
                AND attendanceDate BETWEEN ? AND ?
                AND organisationID = ?";
            
            $stmt = mysqli_prepare($connect_var, $debugQuery);
            if (!$stmt) {
                throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
            }

            mysqli_stmt_bind_param($stmt, "ssi", 
                $monthStart,
                $monthEnd,
                $this->organisationID
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Database execute failed: " . mysqli_error($connect_var));
            }

            $result = mysqli_stmt_get_result($stmt);
            $debugData = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            // Also check the table structure
            $structureQuery = "DESCRIBE tblAttendance";
            $structureResult = mysqli_query($connect_var, $structureQuery);
            $tableStructure = [];
            while ($row = mysqli_fetch_assoc($structureResult)) {
                $tableStructure[] = $row;
            }

            // Check for any auto checkout records in the entire table
            $totalQuery = "SELECT COUNT(*) as total_records FROM tblAttendance WHERE isAutoCheckout = 1";
            $totalResult = mysqli_query($connect_var, $totalQuery);
            $totalData = mysqli_fetch_assoc($totalResult);

            $response = [
                "status" => "success",
                "data" => [
                    "debug_info" => [
                        "monthStart" => $monthStart,
                        "monthEnd" => $monthEnd,
                        "organisationID" => $this->organisationID,
                        "selectedMonth" => $this->selectedMonth
                    ],
                    "auto_checkout_records" => $debugData,
                    "total_auto_checkout_records" => $totalData,
                    "table_structure" => $tableStructure
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
}
?>