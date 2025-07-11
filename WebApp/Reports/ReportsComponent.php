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

        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
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