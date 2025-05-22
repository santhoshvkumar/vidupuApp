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
    e.empID,
    e.employeeName,
    e.employeePhone,
    e.Designation,
    DATE_ADD(?, INTERVAL n.num DAY) AS attendanceDate,
    a.checkInTime,
    a.checkOutTime,
    CASE
        WHEN a.checkInTime IS NOT NULL THEN 'Present'
        WHEN EXISTS (
            SELECT 1
            FROM tblApplyLeave al
            WHERE al.employeeID = e.employeeID
              AND DATE_ADD(?, INTERVAL n.num DAY) BETWEEN al.fromDate AND al.toDate
        ) THEN 'Leave'
        ELSE 'Absent'
    END AS STATUS,
    b.branchName
FROM 
    (SELECT DISTINCT empID, employeeName, employeePhone, Designation, employeeID 
     FROM tblEmployee 
     WHERE isTemporary = 0 AND isActive = 1) e
CROSS JOIN (
    SELECT DISTINCT a.N + b.N * 10 + c.N * 100 AS num
    FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
    CROSS JOIN (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
    CROSS JOIN (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) c
    WHERE a.N + b.N * 10 + c.N * 100 <= DATEDIFF(?, ?)
) n
LEFT JOIN tblAttendance a 
    ON e.employeeID = a.employeeID  
    AND a.attendanceDate = DATE_ADD(?, INTERVAL n.num DAY)
INNER JOIN tblmapEmp m
    ON e.employeeID = m.employeeID
INNER JOIN tblBranch b
    ON m.branchID = b.branchID
WHERE 
    DATE_ADD(?, INTERVAL n.num DAY) BETWEEN ? AND ?
ORDER BY 
    e.empID, attendanceDate;";

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
    e.empID, 
    e.employeeName, 
    e.Designation, 
    e.employeePhone, 
    l.typeOfLeave, 
    l.leaveDuration,
    l.createdOn, 
    l.status, 
    l.fromDate, 
    l.toDate
FROM tblEmployee AS e
JOIN tblApplyLeave AS l ON e.employeeID = l.employeeID
WHERE l.createdOn BETWEEN ? AND ?;";

            $debug_query = str_replace(
                array_fill(0, 8, '?'),
                [
                    "'" . $this->startDate . "'",
                    "'" . $this->endDate . "'"
                ],
                $queryforGetLeaveReport
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryforGetLeaveReport);
            if (!$stmt) {
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ss", 
                $this->startDate,  // For attendanceDate
                $this->endDate     // For DATEDIFF
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

?>