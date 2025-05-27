<?php
class ReportsComponent {    
    public $startDate;
    public $endDate;

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
    a.AttendanceDate,
    SUM(
        CASE 
            WHEN b.BranchName = 'Head Office' 
                 AND a.CheckInTime IS NOT NULL 
            THEN 1 ELSE 0 
        END
    ) AS HeadOffice_Present,
    SUM(
        CASE 
            WHEN b.BranchName <> 'Head Office' 
                 AND a.CheckInTime IS NOT NULL 
            THEN 1 ELSE 0 
        END
    ) AS Branch_Present,    
    SUM(
        CASE 
            WHEN b.BranchName = 'Head Office' 
                 AND l.Status = 'Approved' 
                 AND (
                      (l.FromDate <= a.AttendanceDate AND l.ToDate >= a.AttendanceDate)
                 )
            THEN 1 ELSE 0 
        END
    ) AS HeadOffice_Leave,
    SUM(
        CASE 
            WHEN b.BranchName <> 'Head Office' 
                 AND l.Status = 'Approved' 
                 AND (
                      (l.FromDate <= a.AttendanceDate AND l.ToDate >= a.AttendanceDate)
                 )
            THEN 1 ELSE 0 
        END
    ) AS Branch_Leave,
    SUM(CASE WHEN b.BranchName = 'Head Office' THEN 1 ELSE 0 END) AS HeadOffice_Total,
    
    SUM(CASE WHEN b.BranchName <> 'Head Office' THEN 1 ELSE 0 END) AS Branch_Total,    
    SUM(CASE WHEN b.BranchName = 'Head Office' THEN 1 ELSE 0 END)
    - SUM(CASE WHEN b.BranchName = 'Head Office' AND a.CheckInTime IS NOT NULL THEN 1 ELSE 0 END)
    - SUM(CASE WHEN b.BranchName = 'Head Office' AND l.Status = 'Approved' 
                AND l.FromDate <= a.AttendanceDate AND l.ToDate >= a.AttendanceDate THEN 1 ELSE 0 END)
    AS HeadOffice_Absent,
    SUM(CASE WHEN b.BranchName <> 'Head Office' THEN 1 ELSE 0 END)
    - SUM(CASE WHEN b.BranchName <> 'Head Office' AND a.CheckInTime IS NOT NULL THEN 1 ELSE 0 END)
    - SUM(CASE WHEN b.BranchName <> 'Head Office' AND l.Status = 'Approved' 
                AND l.FromDate <= a.AttendanceDate AND l.ToDate >= a.AttendanceDate THEN 1 ELSE 0 END)
    AS Branch_Absent

FROM tblEmployee e
JOIN tblmapEmp m ON e.EmployeeID = m.EmployeeID
JOIN tblBranch b ON m.BranchID = b.BranchID
LEFT JOIN tblAttendance a ON e.EmployeeID = a.EmployeeID
LEFT JOIN tblApplyLeave l ON e.EmployeeID = l.EmployeeID

WHERE e.isActive = 1
  AND a.AttendanceDate BETWEEN ? AND ?

GROUP BY e.Designation, a.AttendanceDate
ORDER BY a.AttendanceDate, e.Designation;
";

            $debug_query = str_replace(
                array_fill(0, 1, '?'),
                [
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

            mysqli_stmt_bind_param($stmt, "ss", 
                $this->startDate,  // For attendanceDate
                $this->endDate     // For leave check
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
}
function GetAttendanceReport($decoded_items) {
    $ReportsComponentObject = new ReportsComponent();
    if ($ReportsComponentObject->loadReportsforGivenDate($decoded_items)) {
        $ReportsComponentObject->GetAttendanceReport();
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