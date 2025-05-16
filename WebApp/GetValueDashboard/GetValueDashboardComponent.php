<?php
class GetValueDashboardComponent{    
    public $currentDate;    
    public $getMethodForCalling;

    public function loadGetValueDashboard(array $data){ 
        $this->currentDate = $data['currentDate'];
        $this->getMethodForCalling = $data['getMethod'];
        return true;
    }

    public function GetAllCheckInMembersDetails() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       
            // 1. No of Checkins in Head Office
            if($this->getMethodForCalling == "CheckIn")  {
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
            }
            else if($this->getMethodForCalling == "LateCheckin") {
                $queryIndividualNoOfCheckinsInHeadOffice = "
                SELECT emp.employeeName,sec.sectionName,emp.employeePhone,att.checkInTime,
                COUNT(CASE WHEN att.checkInTime > '10:10:00' THEN 1 END) AS late_checkin
                FROM tblEmployee AS emp JOIN tblAssignedSection AS assign ON emp.employeeID = assign.employeeID JOIN tblSection AS sec ON assign.sectionID = sec.sectionID INNER JOIN tblAttendance AS att ON emp.employeeID = att.employeeID AND DATE(att.attendanceDate) = ? GROUP BY emp.employeeName,sec.sectionName,emp.employeePhone,att.checkInTime HAVING late_checkin > 0;";   
            }
            else if($this->getMethodForCalling == "EarlyCheckout") {
                $queryIndividualNoOfCheckinsInHeadOffice = "
                SELECT emp.employeeName,sec.sectionName,emp.employeePhone,att.checkOutTime,COUNT(CASE WHEN att.checkOutTime < '17:00:00' THEN 1 END) AS early_checkout
                FROM tblEmployee AS emp JOIN tblAssignedSection AS assign ON emp.employeeID = assign.employeeID JOIN tblSection AS sec ON assign.sectionID = sec.sectionID INNER JOIN tblAttendance AS att ON emp.employeeID = att.employeeID AND DATE(att.attendanceDate) = ? GROUP BY emp.employeeName,sec.sectionName,emp.employeePhone,att.checkOutTime HAVING early_checkout > 0;";   
                
            }
            else if($this->getMethodForCalling == "OnLeave") {
                $queryIndividualNoOfCheckinsInHeadOffice = "
                SELECT emp.employeeName,sec.sectionName, emp.employeePhone, COUNT(CASE WHEN ? BETWEEN lv.fromDate AND lv.toDate THEN 1 END) AS on_leave
                FROM tblEmployee AS emp JOIN tblAssignedSection AS assign ON emp.employeeID = assign.employeeID JOIN tblSection AS sec ON assign.sectionID = sec.sectionID
                INNER JOIN tblApplyLeave AS lv ON emp.employeeID = lv.employeeID WHERE lv.status = 'Approved' GROUP BY emp.employeeName,sec.sectionName HAVING on_leave > 0;";     
            }
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
            $countEmployee = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $countEmployee++;
                $data[] = $row;
            }
            if ($countEmployee > 0) {
                echo json_encode([
                    "status" => "success",
                    "data" => $data,
                    //"checked_in" => $checked_in
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message_text" => "No data found for any employee"
                ], JSON_FORCE_OBJECT);
            };
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
?>