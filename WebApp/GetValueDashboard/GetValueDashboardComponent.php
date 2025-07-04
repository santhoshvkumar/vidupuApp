<?php
class GetValueDashboardComponent {    
    public $currentDate;    
    public $branchID;
    public $employeeID;
    public $organisationID;
    public function loadGetValueDashboard(array $data) { 
        if (!isset($data['currentDate']) || !isset($data['branchID']) || !isset($data['organisationID'])) {
            return false;
        }
        $this->currentDate = $data['currentDate'];
        $this->branchID = $data['branchID'];
        $this->organisationID = $data['organisationID'];
        return true;
    }
    public function loadResetMiskenlyEarlyCheckout(array $data) {    
        $this->currentDate = $data['currentDate'];
        $this->employeeID = $data['employeeID'];
        $this->organisationID = $data['organisationID'];
        return true;
    }
    public function loadGetAllActiveEmployees(array $data) {    
        $this->branchID = $data['branchID'];
        $this->organisationID = $data['organisationID'];
        return true;
    }
    public function loadGetAllActiveEmployeesforAll(array $data) {    
        $this->currentDate = $data['currentDate'];
        $this->organisationID = $data['organisationID'];
        return true;
    }
    public function loadGetAllAbesentEmployees(array $data) {    
        if (!isset($data['currentDate']) || !isset($data['branchID']) || !isset($data['organisationID'])) {
            return false;
        }
        $this->currentDate = $data['currentDate'];
        $this->branchID = $data['branchID'];
        $this->organisationID = $data['organisationID'];
        return true;
    }
    public function loadGetAllCheckInMembersforAll(array $data) {    
        $this->currentDate = $data['currentDate'];
        $this->organisationID = $data['organisationID'];
        return true;
    }
    public function loadGetAllEarlyCheckOutMembersforAll(array $data) {    
        $this->currentDate = $data['currentDate'];
        $this->organisationID = $data['organisationID'];
        return true;
    }
    public function loadGetAllOnLeaveMembersforAll(array $data) {    
        $this->currentDate = $data['currentDate'];
        $this->organisationID = $data['organisationID'];
        return true;
    }
    public function loadGetAllLateCheckInMembersforAll(array $data) {    
        $this->currentDate = $data['currentDate'];
        $this->organisationID = $data['organisationID'];
        return true;
    }
    public function loadGetAllAbesentEmployeesforAll(array $data) {    
        $this->currentDate = $data['currentDate'];
        $this->organisationID = $data['organisationID'];
        return true;
    }
    public function GetAllActiveEmployeesDetails() {    
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $data = [];
            $queryIndividualNoOfCheckinsInHeadOffice = "SELECT DISTINCT
    emp.employeeName, 
    COALESCE(b.branchName, sec.sectionName) AS locationName, 
    emp.employeePhone 
FROM tblEmployee AS emp 
LEFT JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
LEFT JOIN tblBranch AS b ON m.branchID = b.branchID AND b.branchID <> 1
LEFT JOIN tblAssignedSection AS assign ON emp.employeeID = assign.employeeID
LEFT JOIN tblSection AS sec ON assign.sectionID = sec.sectionID 
WHERE emp.isActive = 1
  AND m.branchID = ? AND m.organisationID = ?
  AND emp.employeeID <> 888
;";

            $debug_query = str_replace(
                ['?', '?'],
                [
                    "'" . $this->branchID . "'",
                    "'" . $this->organisationID . "'",
                ],
                $queryIndividualNoOfCheckinsInHeadOffice
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfCheckinsInHeadOffice);
            if (!$stmt) {
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ss", $this->branchID, $this->organisationID);

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
    public function GetAllActiveEmployeesDetailsforAll() {   
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $data = [];
            $queryIndividualNoOfCheckinsInHeadOffice = "SELECT 
    emp.employeeName,
    COALESCE(b.branchName, sec.sectionName) AS locationName,
    emp.employeePhone
FROM tblEmployee AS emp
LEFT JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
LEFT JOIN tblBranch AS b ON m.branchID = b.branchID
LEFT JOIN tblAssignedSection AS assign ON emp.employeeID = assign.employeeID
LEFT JOIN tblSection AS sec ON assign.sectionID = sec.sectionID
LEFT JOIN tblAttendance AS att 
    ON emp.employeeID = att.employeeID 
    AND DATE(att.attendanceDate) = ?
WHERE emp.isActive = 1 AND m.organisationID = ?
  AND emp.employeeID <> 888
  AND att.checkInTime IS NULL  GROUP BY 
                emp.employeeName, 
                locationName, 
                emp.employeePhone;";

            $debug_query = str_replace(
                ['?', '?'],
                [
                    "'" . $this->currentDate . "'",
                    "'" . $this->organisationID . "'",
                ],
                $queryIndividualNoOfCheckinsInHeadOffice
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfCheckinsInHeadOffice);
            if (!$stmt) {           
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ss", $this->currentDate, $this->organisationID);

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
    public function GetAllAbesentEmployeesDetails() {   
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $data = [];
            $queryIndividualNoOfCheckinsInHeadOffice = "SELECT 
    emp.employeeName,
    COALESCE(b.branchName, sec.sectionName) AS locationName,
    emp.employeePhone
FROM tblEmployee AS emp
LEFT JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
LEFT JOIN tblBranch AS b ON m.branchID = b.branchID
LEFT JOIN tblAssignedSection AS assign ON emp.employeeID = assign.employeeID
LEFT JOIN tblSection AS sec ON assign.sectionID = sec.sectionID
LEFT JOIN tblAttendance AS att 
    ON emp.employeeID = att.employeeID 
    AND DATE(att.attendanceDate) = ?
WHERE emp.isActive = 1
  AND m.branchID = ? AND m.organisationID = ?
  AND att.checkInTime IS NULL
  AND emp.employeeID NOT IN (
      SELECT employeeID
      FROM tblApplyLeave
      WHERE status = 'Approved' AND employeeID <> 888
        AND ? BETWEEN fromDate AND toDate
  );

";

            $debug_query = str_replace(
                ['?', '?', '?', '?'],
                [
                    "'" . $this->currentDate . "'",
                    "'" . $this->branchID . "'",
                    "'" . $this->organisationID . "'",
                    "'" . $this->currentDate . "'",
                ],
                $queryIndividualNoOfCheckinsInHeadOffice
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfCheckinsInHeadOffice);
            if (!$stmt) {           
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ssss", $this->currentDate, $this->branchID, $this->organisationID, $this->currentDate);

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
    public function GetAllAbesentEmployeesDetailsforAll() {   
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $data = [];

            $currentDate = mysqli_real_escape_string($connect_var, $this->currentDate);
            $organisationID = mysqli_real_escape_string($connect_var, $this->organisationID);
            $queryIndividualNoOfCheckinsInHeadOffice = "SELECT 
                emp.employeeName,
                COALESCE(b.branchName, sec.sectionName) AS locationName,
                emp.employeePhone
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
            );
            ";
            $rsd = mysqli_query($connect_var, $queryIndividualNoOfCheckinsInHeadOffice);
            if (!$rsd) {
                error_log("Query failed: " . mysqli_error($connect_var));
                throw new Exception("Database query failed");
            }
            $countEmployee = 0;
            $resultData = [];
            while ($row = mysqli_fetch_assoc($rsd)) {
                $resultData[] = $row;
                $countEmployee++;
            }

            if ($countEmployee > 0) {
                echo json_encode([
                    "status" => "success",          
                    "data" => $resultData
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message_text" => "No data found for any employee"
                ], JSON_FORCE_OBJECT);  
            }

            mysqli_close($connect_var);
        } catch (Exception $e) {
            error_log("Error in GetValueDashboardforCheckin: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
    public function GetAllCheckInMembersDetails() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       
            $queryIndividualNoOfCheckinsInHeadOffice = "SELECT tblE.employeeName, tblB.branchName as locationName, tblE.employeePhone, tblA.checkInTime FROM `tblAttendance` tblA INNER JOIN tblEmployee tblE on tblE.employeeID = tblA.employeeID INNER JOIN tblBranch tblB on tblB.branchID = tblA.checkInBranchID WHERE tblA.attendanceDate=? and tblA.checkInBranchID=?";

            $debug_query = str_replace(['?', '?'], ["'" . $this->currentDate . "'", "'" . $this->branchID . "'"], $queryIndividualNoOfCheckinsInHeadOffice);
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfCheckinsInHeadOffice);
            if (!$stmt) {
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ss", $this->currentDate, $this->branchID);
            
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
    public function GetAllCheckInMembersDetailsforAll() {
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
                    AND m.organisationID = ?
                    AND emp.employeeID <> 888
            GROUP BY 
                emp.employeeName, 
                locationName, 
                emp.employeePhone;";

            $debug_query = str_replace(['?', '?'], ["'" . $this->currentDate . "'", "'" . $this->organisationID . "'"], $queryIndividualNoOfCheckinsInHeadOffice);
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfCheckinsInHeadOffice);
            if (!$stmt) {
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ss", $this->currentDate, $this->organisationID);
            
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
            $queryIndividualNoOfCheckinsInHeadOffice = "SELECT 
            emp.employeeName, emp.employeeID,
            COALESCE(sec.sectionName, b.branchName) AS locationName,
            emp.employeePhone,
            CAST(MIN(att.checkOutTime) AS CHAR) AS checkOutTime,
            COUNT(
                CASE
                    -- Early checkout rule using branch-based logic
                    WHEN b.checkOutTime IS NOT NULL 
                    AND att.checkOutTime IS NOT NULL 
                    AND att.checkOutTime < b.checkOutTime THEN 1
                    ELSE NULL
                END
            ) AS early_checkout
        FROM tblEmployee AS emp
        JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
        LEFT JOIN tblAssignedSection AS assign ON emp.employeeID = assign.employeeID
        LEFT JOIN tblSection AS sec ON assign.sectionID = sec.sectionID AND m.branchID = 1
        LEFT JOIN tblBranch AS b ON m.branchID = b.branchID AND m.branchID <> 1
        JOIN tblAttendance AS att ON emp.employeeID = att.employeeID
        WHERE DATE(att.attendanceDate) = ?
          AND m.branchID IN (?) 
          AND m.organisationID = ?
          AND emp.employeeID <> 888
          AND att.checkOutTime IS NOT NULL
          AND (att.checkInBranchID = ? OR att.checkOutBranchID = ?)
        GROUP BY emp.employeeName, locationName, emp.employeePhone, emp.employeeID
        HAVING early_checkout > 0;";
        
    
            $debug_query = str_replace(
                ['?', '?', '?', '?', '?'],  
                [
                    "'" . $this->currentDate . "'",
                    "'" . $this->branchID . "'",   
                    "'" . $this->organisationID . "'",
                    "'" . $this->branchID . "'",
                    "'" . $this->branchID . "'",
                ],
                $queryIndividualNoOfCheckinsInHeadOffice
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfCheckinsInHeadOffice);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "sssss", $this->currentDate, $this->branchID, $this->organisationID, $this->branchID, $this->branchID);
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Execute failed: " . mysqli_stmt_error($stmt));
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
            $countEmployee = 0;
            error_log("Debug Early Checkout List Employees:");
            while ($row = mysqli_fetch_assoc($result)) {
                $countEmployee++;
                error_log("List Employee: " . $row['employeeName'] . " (ID: " . $row['employeeID'] . ") - CheckOut: " . $row['checkOutTime']);
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
    public function GetAllEarlyCheckOutMembersDetailsforAll() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       
            $queryIndividualNoOfCheckinsInHeadOffice = "SELECT 
            emp.employeeName, emp.employeeID,
            COALESCE(sec.sectionName, b.branchName) AS locationName,
            emp.employeePhone,
            CAST(MIN(att.checkOutTime) AS CHAR) AS checkOutTime,
            COUNT(
                CASE
                    -- Early checkout rule using branch-based logic
                    WHEN b.checkOutTime IS NOT NULL 
                    AND att.checkOutTime IS NOT NULL 
                    AND att.checkOutTime < b.checkOutTime THEN 1
                    ELSE NULL
                END
            ) AS early_checkout
        FROM tblEmployee AS emp
        JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
        LEFT JOIN tblAssignedSection AS assign ON emp.employeeID = assign.employeeID
        LEFT JOIN tblSection AS sec ON assign.sectionID = sec.sectionID AND m.branchID = 1
        LEFT JOIN tblBranch AS b ON m.branchID = b.branchID AND m.branchID <> 1
        JOIN tblAttendance AS att ON emp.employeeID = att.employeeID
        WHERE DATE(att.attendanceDate) = ? AND m.organisationID = ? AND emp.employeeID <> 888
        GROUP BY emp.employeeName, locationName, emp.employeePhone, emp.employeeID
        HAVING early_checkout > 0;";
        
    
            $debug_query = str_replace(
                ['?', '?'],  
                [
                    "'" . $this->currentDate . "'",
                    "'" . $this->organisationID . "'",
                ],
                $queryIndividualNoOfCheckinsInHeadOffice
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfCheckinsInHeadOffice);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ss", $this->currentDate, $this->organisationID);
            
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
            $queryIndividualNoOfCheckinsInHeadOffice = "SELECT 
    emp.employeeName,
    COALESCE(sec.sectionName, b.branchName) AS locationName,
    emp.employeePhone,
    COUNT(
        CASE 
            WHEN DATE(?) BETWEEN lv.fromDate AND lv.toDate THEN 1 
            ELSE NULL
        END
    ) AS on_leave
FROM tblEmployee AS emp
JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
LEFT JOIN tblAssignedSection AS assign ON emp.employeeID = assign.employeeID
LEFT JOIN tblSection AS sec ON assign.sectionID = sec.sectionID AND m.branchID = 1
LEFT JOIN tblBranch AS b ON m.branchID = b.branchID AND m.branchID <> 1
JOIN tblApplyLeave AS lv ON emp.employeeID = lv.employeeID
WHERE lv.status = 'Approved'
  AND m.branchID IN (?) AND m.organisationID = ? AND emp.employeeID <> 888
GROUP BY emp.employeeName, locationName, emp.employeePhone
HAVING on_leave > 0;
";

            $debug_query = str_replace(
                ['?', '?', '?'],
                [
                    "'" . $this->currentDate . "'",
                    "'" . $this->branchID . "'",
                    "'" . $this->organisationID . "'",
                ],
                $queryIndividualNoOfCheckinsInHeadOffice
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfCheckinsInHeadOffice);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "sss", $this->currentDate, $this->branchID, $this->organisationID);

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
    public function GetAllOnLeaveMembersforAll() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       
            $queryIndividualNoOfCheckinsInHeadOffice = "SELECT 
                    emp.employeeName,
                    COALESCE(sec.sectionName, b.branchName) AS locationName,
                    emp.employeePhone,
                    COUNT(
                        CASE 
                            WHEN DATE(?) BETWEEN lv.fromDate AND lv.toDate THEN 1 
                            ELSE NULL
                        END
                    ) AS on_leave
                FROM tblEmployee AS emp
                JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
                LEFT JOIN tblAssignedSection AS assign ON emp.employeeID = assign.employeeID
                LEFT JOIN tblSection AS sec ON assign.sectionID = sec.sectionID AND m.branchID = 1
                LEFT JOIN tblBranch AS b ON m.branchID = b.branchID AND m.branchID <> 1
                JOIN tblApplyLeave AS lv ON emp.employeeID = lv.employeeID
                WHERE lv.status = 'Approved' AND m.organisationID = ? AND emp.employeeID <> 888
                GROUP BY emp.employeeName, locationName, emp.employeePhone
                HAVING on_leave > 0;
            ";

            $debug_query = str_replace(
                ['?', '?'],
                [
                    "'" . $this->currentDate . "'",
                    "'" . $this->organisationID . "'",
                ],
                $queryIndividualNoOfCheckinsInHeadOffice
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfCheckinsInHeadOffice);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ss", $this->currentDate, $this->organisationID);

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
            $queryIndividualNoOfCheckinsInHeadOffice = "SELECT 
            emp.employeeName,
            COALESCE(sec.sectionName, b.branchName) AS locationName,
            emp.employeePhone,
            CAST(MIN(att.checkInTime) AS CHAR) AS checkInTime,
            COUNT(
                CASE
                    -- Custom late rule for employeeIDs 72, 73, 75
                    WHEN emp.employeeID IN (72, 73, 75) AND att.checkInTime > '08:10:00' THEN 1

                    -- Custom late rule for employeeIDs 24, 27
                    WHEN emp.employeeID IN (24, 27) AND att.checkInTime > '11:10:00' THEN 1
        
                    -- Late rule for branches 1
                    WHEN m.branchID IN (1) AND att.checkInTime > '10:10:00' THEN 1
        
                    -- Late rule for branches 2 to 52
                    WHEN m.branchID BETWEEN 2 AND 52 AND att.checkInTime > '09:25:00' THEN 1
        
                    ELSE NULL
                END
            ) AS late_checkin
        FROM tblEmployee AS emp
        JOIN tblmapEmp AS m ON emp.employeeID = m.employeeID
        LEFT JOIN tblAssignedSection AS assign ON emp.employeeID = assign.employeeID
        LEFT JOIN tblSection AS sec ON assign.sectionID = sec.sectionID AND m.branchID = 1
        LEFT JOIN tblBranch AS b ON m.branchID = b.branchID AND m.branchID <> 1
        JOIN tblAttendance AS att ON emp.employeeID = att.employeeID
        WHERE DATE(att.attendanceDate) = ?
          AND m.branchID IN (?) AND m.organisationID = ? AND emp.employeeID <> 888
        GROUP BY emp.employeeName, locationName, emp.employeePhone
        HAVING late_checkin > 0;";
        

            $debug_query = str_replace(
                ['?', '?', '?'],
                [
                    "'" . $this->currentDate . "'", 
                    "'" . $this->branchID . "'",           
                    "'" . $this->organisationID . "'",
                ],
                $queryIndividualNoOfCheckinsInHeadOffice
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfCheckinsInHeadOffice);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "sss", $this->currentDate, $this->branchID, $this->organisationID);
            
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
    public function GetAllLateCheckInMembersDetailsforAll() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       
            $queryIndividualNoOfCheckinsInHeadOffice = "SELECT tblA.employeeID, COALESCE(tblS.SectionName, tblB.branchName) AS locationName, tblA.checkInTime, tblE.employeeName, tblE.employeePhone FROM tblAttendance tblA INNER JOIN tblEmployee tblE on tblE.employeeID = tblA.employeeID LEFT JOIN tblAssignedSection tblAS on tblAS.employeeID = tblA.employeeID LEFT JOIN tblSection tblS on tblS.SectionID = tblAS.sectionID INNER JOIN tblBranch tblB on tblB.branchID = tblA.checkInBranchID WHERE tblA.attendanceDate=? and tblA.organisationID=? and tblA.isLateCheckIN=1;";
        

            $debug_query = str_replace(
                ['?', '?'],
                [
                    "'" . $this->currentDate . "'",
                    "'" . $this->organisationID . "'",
                ],
                $queryIndividualNoOfCheckinsInHeadOffice
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfCheckinsInHeadOffice);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ss", $this->currentDate, $this->organisationID);
            
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
    public function ResetMiskenlyEarlyCheckout() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
            $queryResetMiskenlyEarlyCheckout = "UPDATE tblAttendance AS a
JOIN tblEmployee AS e ON a.employeeID = e.employeeID
SET a.checkOutTime = NULL,
    a.TotalWorkingHour = NULL
WHERE a.attendanceDate = ?
  AND a.employeeID = ?
  AND e.organisationID = ?;
";
            
            $stmt = mysqli_prepare($connect_var, $queryResetMiskenlyEarlyCheckout);
            mysqli_stmt_bind_param($stmt, "sss",
                $this->currentDate,
                $this->employeeID,
                $this->organisationID
            );  

            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(array(
                    "status" => "success",
                    "message" => "Employee Checkout Time Reset successfully"
                ));
            } else {    
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error Resetting employee Checkout Time"
                ));
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
function GetAllActiveEmployees($decoded_items) {
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadGetAllActiveEmployees($decoded_items)) {
        $GetValueDashboardObject->GetAllActiveEmployeesDetails();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function GetAllActiveEmployeesDetailsforAll($decoded_items) {
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadGetAllActiveEmployeesforAll($decoded_items)) {
        $GetValueDashboardObject->GetAllActiveEmployeesDetailsforAll();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function GetAllActiveEmployeesforAll($decoded_items) {
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadGetAllActiveEmployeesforAll($decoded_items)) {
        $GetValueDashboardObject->GetAllActiveEmployeesDetailsforAll();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function GetAllAbesentEmployees($decoded_items) {
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadGetAllAbesentEmployees($decoded_items)) {
        $GetValueDashboardObject->GetAllAbesentEmployeesDetails();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}   
function ResetMiskenlyEarlyCheckout($decoded_items) {
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadResetMiskenlyEarlyCheckout($decoded_items)) {
        $GetValueDashboardObject->ResetMiskenlyEarlyCheckout();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }       
}
function GetAllCheckInMembersforAll($decoded_items) {
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadGetAllCheckInMembersforAll($decoded_items)) {
        $GetValueDashboardObject->GetAllCheckInMembersDetailsforAll();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function GetAllEarlyCheckOutMembersforAll($decoded_items) {
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadGetAllEarlyCheckOutMembersforAll($decoded_items)) {
        $GetValueDashboardObject->GetAllEarlyCheckOutMembersDetailsforAll();
    } else {    
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function GetAllOnLeaveMembersforAll($decoded_items) {
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadGetAllOnLeaveMembersforAll($decoded_items)) {
        $GetValueDashboardObject->GetAllOnLeaveMembersforAll();
    } else {        
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function GetAllLateCheckInMembersforAll($decoded_items) {
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadGetAllLateCheckInMembersforAll($decoded_items)) {
        $GetValueDashboardObject->GetAllLateCheckInMembersDetailsforAll();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function GetAllAbesentEmployeesforAll($decoded_items) {
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadGetAllAbesentEmployeesforAll($decoded_items)) {
        $GetValueDashboardObject->GetAllAbesentEmployeesDetailsforAll();
    } else {        
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
?>