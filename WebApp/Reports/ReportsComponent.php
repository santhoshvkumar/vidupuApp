<?php
class ReportsComponent {    
    public $startDate;
    public $endDate;

    public function loadReportsforGivenDate(array $data) { 
        $this->startDate = $data['startDate'];
        $this->endDate = $data['endDate'];
        return true;
    }
   
    public function GetAttendanceReport() {    
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $data = [];
            $queryforGetAttendanceReport = "SELECT 
    e.empID,
    e.employeeName,
    e.employeePhone,
    e.Designation,
    d.attendanceDate,
    a.checkInTime,
    a.checkOutTime,
    CASE
        WHEN a.checkInTime IS NOT NULL THEN 'Present'
        WHEN EXISTS (
            SELECT 1
            FROM tblApplyLeave al
            WHERE al.employeeID = e.employeeID
              AND d.attendanceDate BETWEEN al.fromDate AND al.toDate
        ) THEN 'Leave'
        ELSE 'Absent'
    END AS STATUS,
    b.branchName
FROM 
    (
        SELECT ? AS attendanceDate
        UNION ALL
        SELECT ?
    ) d
JOIN tblEmployee e ON 1=1
LEFT JOIN tblAttendance a 
    ON e.employeeID = a.employeeID  
    AND a.attendanceDate = d.attendanceDate
INNER JOIN tblmapEmp m
    ON e.employeeID = m.employeeID
INNER JOIN tblBranch b
    ON m.branchID = b.branchID
WHERE 
    e.isTemporary = 0 
    AND e.isActive = 1
ORDER BY 
    e.empID, d.attendanceDate;";

            $debug_query = str_replace(
                ['?'],
                [
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

            mysqli_stmt_bind_param($stmt, "ss", $this->startDate, $this->endDate);

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
?>