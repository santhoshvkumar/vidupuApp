<?php
class GetValueDashboardComponent{    
    public $currentDate;    
    
    public function loadGetValueDashboard(array $data){ 
        if (isset($data['currentDate'])) {  
            // Convert the date to YYYY-MM-DD format
            $date = DateTime::createFromFormat('Y-m-d', $data['currentDate']);
            if ($date && $date->format('Y-m-d') === $data['currentDate']) {
                $this->currentDate = $data['currentDate'];
                return true;
            } else {
                error_log("Invalid date format. Expected YYYY-MM-DD, got: " . $data['currentDate']);
                return false;
            }
        } else {
            error_log("currentDate parameter is missing");
            return false;
        }
    }

    public function GetValueDashboardforCheckin() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       

            // Debug input values
            error_log("GetValueDashboardforCheckin - Input values:");
            error_log("currentDate: " . $this->currentDate);

            // 1. No of Checkins in Head Office
            $queryIndividualNoOfCheckinsInHeadOffice = "
                SELECT 
                    emp.employeeName,
                    sec.sectionName, emp.employeePhone,att.checkInTime,
                    COUNT(att.employeeID) AS checked_in
                FROM tblEmployee AS emp
                JOIN tblAssignedSection AS assign 
                    ON emp.employeeID = assign.employeeID
                JOIN tblSection AS sec 
                    ON assign.sectionID = sec.sectionID
                INNER JOIN tblAttendance AS att 
                    ON emp.employeeID = att.employeeID 
                    AND DATE(att.attendanceDate) = ?
                GROUP BY 
                    emp.employeeName,
                    sec.sectionName, emp.employeePhone,att.checkInTime;";

            // Debug the query with actual values
            $debug_query = str_replace(
                ['?'],
                [
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

            mysqli_stmt_bind_param($stmt, "s", $this->currentDate);
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Execute failed: " . mysqli_stmt_error($stmt));
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
            $employeeData = [];
            
            while ($row = mysqli_fetch_assoc($result)) {
                error_log("Row data: " . print_r($row, true));
                $employeeData[] = [
                    'employeeName' => $row['employeeName'],
                    'sectionName' => $row['sectionName'],
                    'checked_in' => intval($row['checked_in']),
                    'employeePhone' => $row['employeePhone'],
                    'checkInTime' => $row['checkInTime']
                ];
            }
            
            error_log("Final employeeData: " . print_r($employeeData, true));
            
            echo json_encode([
                "status" => "success",
                "data" => $employeeData
            ]);
            
        } catch (Exception $e) {
            error_log("Error in GetValueDashboardforCheckin: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }

    public function GetValueDashboardforLateCheckin() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       

            // Debug input values
            error_log("GetValueDashboardforLateCheckin - Input values:");
            error_log("currentDate: " . $this->currentDate);

            // 1. No of Late Checkins in Head Office
            $queryIndividualNoOfLateCheckinsInHeadOffice = "
                SELECT 
                    emp.employeeName,
                    sec.sectionName,
                    COUNT(CASE WHEN att.checkInTime > '10:10:00' THEN 1 END) AS late_checkin
                FROM tblEmployee AS emp
                JOIN tblAssignedSection AS assign 
                    ON emp.employeeID = assign.employeeID
                JOIN tblSection AS sec 
                    ON assign.sectionID = sec.sectionID
                INNER JOIN tblAttendance AS att 
                    ON emp.employeeID = att.employeeID 
                    AND DATE(att.attendanceDate) = ?
                GROUP BY 
                    emp.employeeName,
                    sec.sectionName;";

            // Debug the query with actual values
            $debug_query = str_replace(
                ['?'],
                [
                    "'" . $this->currentDate . "'",                    
                ],
                $queryIndividualNoOfLateCheckinsInHeadOffice
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfLateCheckinsInHeadOffice);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "s", 
                $this->currentDate
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Execute failed: " . mysqli_stmt_error($stmt));
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
            $employeeData = [];           
            
            // Debug number of rows
            $num_rows = mysqli_num_rows($result);
            error_log("Number of rows returned: " . $num_rows);
            
            while ($row = mysqli_fetch_assoc($result)) {
                error_log("Row data: " . print_r($row, true));
                $employeeData[] = [
                    'employeeName' => $row['employeeName'],
                    'sectionName' => $row['sectionName'],
                    'late_checkin' => $row['late_checkin']
                ];
            }
            
            error_log("Final employeeData: " . print_r($employeeData, true));
            
            echo json_encode([
                "status" => "success",
                "data" => $employeeData
            ]);
            
        } catch (Exception $e) {
            error_log("Error in GetValueDashboardforLateCheckin: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
    public function GetValueDashboardforEarlyCheckout() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       

            // Debug input values
            error_log("GetValueDashboardforEarlyCheckout - Input values:");
            error_log("currentDate: " . $this->currentDate);

            // 1. No of Early Checkouts in Head Office
            $queryIndividualNoOfEarlyCheckoutInHeadOffice = "
                SELECT 
                    emp.employeeName,
                    sec.sectionName,
                    COUNT(CASE WHEN att.checkOutTime < '17:00:00' THEN 1 END) AS early_checkout
                FROM tblEmployee AS emp
                JOIN tblAssignedSection AS assign 
                    ON emp.employeeID = assign.employeeID
                JOIN tblSection AS sec 
                    ON assign.sectionID = sec.sectionID
                INNER JOIN tblAttendance AS att 
                    ON emp.employeeID = att.employeeID 
                    AND DATE(att.attendanceDate) = ?
                GROUP BY 
                    emp.employeeName,
                    sec.sectionName;";

            // Debug the query with actual values
            $debug_query = str_replace(
                ['?'],
                [
                    "'" . $this->currentDate . "'",                    
                ],
                $queryIndividualNoOfEarlyCheckoutInHeadOffice
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfEarlyCheckoutInHeadOffice);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "s", 
                $this->currentDate
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Execute failed: " . mysqli_stmt_error($stmt));
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
            $employeeData = [];           
            
            // Debug number of rows
            $num_rows = mysqli_num_rows($result);
            error_log("Number of rows returned: " . $num_rows);
            
            while ($row = mysqli_fetch_assoc($result)) {
                error_log("Row data: " . print_r($row, true));
                $employeeData[] = [
                    'employeeName' => $row['employeeName'],
                    'sectionName' => $row['sectionName'],
                    'early_checkout' => $row['early_checkout']
                ];
            }
            
            error_log("Final employeeData: " . print_r($employeeData, true));
            
            echo json_encode([
                "status" => "success",
                "data" => $employeeData
            ]);
            
        } catch (Exception $e) {
            error_log("Error in GetValueDashboardforEarlyCheckout: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    } 

    public function GetValueDashboardforOnLeave() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       

            // Debug input values
            error_log("GetValueDashboardforOnLeave - Input values:");
            error_log("currentDate: " . $this->currentDate);

            // 1. No of On Leave in Head Office
            $queryIndividualNoOfOnLeaveInHeadOffice = "
                SELECT 
                    emp.employeeName,
                    sec.sectionName,
                    COUNT(CASE WHEN ? BETWEEN lv.fromDate AND lv.toDate THEN 1 END) AS on_leave
                FROM tblEmployee AS emp
                JOIN tblAssignedSection AS assign 
                    ON emp.employeeID = assign.employeeID
                JOIN tblSection AS sec 
                    ON assign.sectionID = sec.sectionID
                INNER JOIN tblApplyLeave AS lv 
                    ON emp.employeeID = lv.employeeID 
                WHERE ? BETWEEN lv.fromDate AND lv.toDate
                GROUP BY 
                    emp.employeeName,
                    sec.sectionName;";

            // Debug the query with actual values
            $debug_query = str_replace(
                ['?'],
                [
                    "'" . $this->currentDate . "'",                    
                ],
                $queryIndividualNoOfOnLeaveInHeadOffice
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfOnLeaveInHeadOffice);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "s", 
                $this->currentDate
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Execute failed: " . mysqli_stmt_error($stmt));
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
            $employeeData = [];           
            
            // Debug number of rows
            $num_rows = mysqli_num_rows($result);
            error_log("Number of rows returned: " . $num_rows);
            
            while ($row = mysqli_fetch_assoc($result)) {
                error_log("Row data: " . print_r($row, true));
                $employeeData[] = [
                    'employeeName' => $row['employeeName'],
                    'sectionName' => $row['sectionName'],
                    'on_leave' => $row['on_leave']
                ];
            }
            
            error_log("Final employeeData: " . print_r($employeeData, true));
            
            echo json_encode([
                "status" => "success",
                "data" => $employeeData
            ]);
            
        } catch (Exception $e) {
            error_log("Error in GetValueDashboardforOnLeave: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    } 
}

function GetValueDashboardforCheckin($decoded_items) {
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadGetValueDashboard($decoded_items)) {
        $GetValueDashboardObject->GetValueDashboardforCheckin();
    } else {        
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function GetValueDashboardforLateCheckin($decoded_items) {
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadGetValueDashboard($decoded_items)) {
        $GetValueDashboardObject->GetValueDashboardforLateCheckin();
    } else {        
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function GetValueDashboardforEarlyCheckout($decoded_items) {
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadGetValueDashboard($decoded_items)) {
        $GetValueDashboardObject->GetValueDashboardforEarlyCheckout();
    } else {        
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function GetValueDashboardforOnLeave($decoded_items) {
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadGetValueDashboard($decoded_items)) {
        $GetValueDashboardObject->GetValueDashboardforOnLeave();
    } else {    
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
?>