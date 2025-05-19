<?php
class GetValueDashboardComponent {    
    public $currentDate;    

    public function loadGetValueDashboard(array $data) { 
        $this->currentDate = $data['currentDate'];
        return true;
    }

    public function GetAllCheckInMembersDetails() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       
            $queryIndividualNoOfCheckinsInHeadOffice = "SELECT 
                emp.employeeName, 
                COALESCE(b.branchName, sec.sectionName) AS locationName, 
                emp.employeePhone, 
                CAST(MIN(att.checkInTime) AS CHAR) AS checkInTime,
                COUNT(att.employeeID) AS checked_in
            FROM tblEmployee AS emp
                LEFT JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
                LEFT JOIN tblBranch AS b ON m.branchID = b.branchID AND b.branchID <> 1
                LEFT JOIN tblAssignedSection AS assign ON emp.employeeID = assign.employeeID
                LEFT JOIN tblSection AS sec ON assign.sectionID = sec.sectionID
                INNER JOIN tblAttendance AS att ON emp.employeeID = att.employeeID 
                    AND DATE(att.attendanceDate) = ?
            GROUP BY 
                emp.employeeName, 
                locationName, 
                emp.employeePhone;";

            $debug_query = str_replace(['?'], ["'" . $this->currentDate . "'"], $queryIndividualNoOfCheckinsInHeadOffice);
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfCheckinsInHeadOffice);
            if (!$stmt) {
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "s", $this->currentDate);
            
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

    public function GetAllEarlyCheckOutMembersDetails() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       
            $queryIndividualNoOfCheckinsInHeadOffice = "SELECT emp.employeeName, sec.sectionName AS locationName, emp.employeePhone, 
       CAST(MIN(att.checkOutTime) AS CHAR) AS checkOutTime, 
       COUNT(CASE WHEN att.checkOutTime < '17:00:00' THEN 1 END) AS early_checkout
FROM tblEmployee AS emp
JOIN tblAssignedSection AS assign ON emp.employeeID = assign.employeeID
JOIN tblSection AS sec ON assign.sectionID = sec.sectionID
INNER JOIN tblAttendance AS att ON emp.employeeID = att.employeeID
WHERE DATE(att.attendanceDate) = ?
GROUP BY emp.employeeName, sec.sectionName, emp.employeePhone
HAVING early_checkout > 0

UNION ALL

SELECT emp.employeeName, b.branchName AS locationName, emp.employeePhone, 
       CAST(MIN(att.checkOutTime) AS CHAR) AS checkOutTime, 
       COUNT(CASE 
           WHEN b.branchID = 52 AND att.checkOutTime < '17:00:00' THEN 1
           WHEN b.branchID <> 52 AND att.checkOutTime < '16:30:00' THEN 1
           ELSE NULL
       END) AS early_checkout
FROM tblEmployee AS emp
JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
JOIN tblBranch AS b ON m.branchID = b.branchID
INNER JOIN tblAttendance AS att ON emp.employeeID = att.employeeID
WHERE DATE(att.attendanceDate) = ? 
  AND b.branchID BETWEEN 2 AND 52
GROUP BY emp.employeeName, b.branchName, emp.employeePhone
HAVING early_checkout > 0;";
    
            $debug_query = str_replace(
                ['?'],  
                [
                    "'" . $this->currentDate . "'",
                    "'" . $this->currentDate . "'",
                ],
                $queryIndividualNoOfCheckinsInHeadOffice
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfCheckinsInHeadOffice);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ss", $this->currentDate, $this->currentDate);
            
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
    public function GetAllOnLeaveMembers() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       
            $queryIndividualNoOfCheckinsInHeadOffice = "SELECT combined_results.employeeName, combined_results.locationName, combined_results.employeePhone, combined_results.on_leave
FROM (
    -- Section-wise Leave Status
    SELECT emp.employeeName AS employeeName, sec.sectionName AS locationName, emp.employeePhone AS employeePhone,
           COUNT(CASE WHEN ? BETWEEN lv.fromDate AND lv.toDate THEN 1 END) AS on_leave
    FROM tblEmployee AS emp
    JOIN tblAssignedSection AS assign ON emp.employeeID = assign.employeeID
    JOIN tblSection AS sec ON assign.sectionID = sec.sectionID
    INNER JOIN tblApplyLeave AS lv ON emp.employeeID = lv.employeeID
    WHERE lv.status = 'Approved'
    GROUP BY emp.employeeName, sec.sectionName, emp.employeePhone

    UNION ALL

    -- Branch-wise Leave Status (Excluding BranchID = 1)
    SELECT emp.employeeName AS employeeName, b.branchName AS locationName, emp.employeePhone AS employeePhone,
           COUNT(CASE WHEN ? BETWEEN lv.fromDate AND lv.toDate THEN 1 END) AS on_leave
    FROM tblEmployee AS emp
    JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
    JOIN tblBranch AS b ON m.branchID = b.branchID
    INNER JOIN tblApplyLeave AS lv ON emp.employeeID = lv.employeeID
    WHERE lv.status = 'Approved' 
      AND b.branchID <> 1
    GROUP BY emp.employeeName, b.branchName, emp.employeePhone
) AS combined_results
WHERE combined_results.on_leave > 0;";

            $debug_query = str_replace(
                ['?'],
                [
                    "'" . $this->currentDate . "'",
                    "'" . $this->currentDate . "'",
                ],
                $queryIndividualNoOfCheckinsInHeadOffice
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfCheckinsInHeadOffice);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ss", $this->currentDate, $this->currentDate);
            
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
    public function GetAllLateCheckInMembersDetails() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       
            $queryIndividualNoOfCheckinsInHeadOffice = "SELECT * FROM (
    SELECT 
        emp.employeeName,
        sec.sectionName AS locationName,
        emp.employeePhone,
        CAST(MIN(att.checkInTime) AS CHAR) AS checkInTime,
        COUNT(CASE WHEN att.checkInTime > '10:10:00' THEN 1 END) AS late_checkin
    FROM tblEmployee AS emp
    JOIN tblAssignedSection AS assign ON emp.employeeID = assign.employeeID
    JOIN tblSection AS sec ON assign.sectionID = sec.sectionID
    INNER JOIN tblAttendance AS att ON emp.employeeID = att.employeeID
    WHERE DATE(att.attendanceDate) = ?
    GROUP BY emp.employeeName, sec.sectionName, emp.employeePhone
    HAVING late_checkin > 0

    UNION ALL

    SELECT 
        emp.employeeName,
        b.branchName AS locationName,
        emp.employeePhone,
        CAST(MIN(att.checkInTime) AS CHAR) AS checkInTime,
        COUNT(CASE 
            WHEN b.branchID = 52 AND att.checkInTime > '10:10:00' THEN 1
            WHEN b.branchID <> 52 AND att.checkInTime > '09:25:00' THEN 1
            ELSE NULL
        END) AS late_checkin
    FROM tblEmployee AS emp
    JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
    JOIN tblBranch AS b ON m.branchID = b.branchID
    INNER JOIN tblAttendance AS att ON emp.employeeID = att.employeeID
    WHERE DATE(att.attendanceDate) = ? 
      AND b.branchID BETWEEN 2 AND 52
    GROUP BY emp.employeeName, b.branchName, emp.employeePhone
    HAVING late_checkin > 0
) AS combined_results;";

            $debug_query = str_replace(
                ['?'],
                [
                    "'" . $this->currentDate . "'",
                    "'" . $this->currentDate . "'",
                ],
                $queryIndividualNoOfCheckinsInHeadOffice
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfCheckinsInHeadOffice);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ss", $this->currentDate, $this->currentDate);
            
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

function GetAllCheckInMembers($decoded_items) {
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadGetValueDashboard($decoded_items)) {
        $GetValueDashboardObject->GetAllCheckInMembersDetails();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}

function GetAllLateCheckInMembers($decoded_items) {
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadGetValueDashboard($decoded_items)) {
        $GetValueDashboardObject->GetAllLateCheckInMembersDetails();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function GetAllEarlyCheckOutMembers($decoded_items) {
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadGetValueDashboard($decoded_items)) {
        $GetValueDashboardObject->GetAllEarlyCheckOutMembersDetails();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function GetAllOnLeaveMembers($decoded_items) { 
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadGetValueDashboard($decoded_items)) {
        $GetValueDashboardObject->GetAllOnLeaveMembers();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
?>