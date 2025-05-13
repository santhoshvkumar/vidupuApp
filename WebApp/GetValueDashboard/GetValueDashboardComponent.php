<?php
class GetValueDashboardComponent{    
    public $currentDate;    
    
    public function loadGetValueDashboard(array $data){ 
        if (isset($data['currentDate'])) {  
            $this->currentDate = $data['currentDate'];
            return true;
        } else {
            return false;
        }
    }

    public function GetValueDashboard() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       

            // Debug input values
            error_log("BranchWiseAttendanceForToday - Input values:");
            error_log("currentDate: " . $this->currentDate);

            // 1. No of Checkins in Head Office
            $queryIndividualNoOfCheckinsInHeadOffice = "
                SELECT 
                    emp.employeeName,
                    sec.sectionName,
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
                    sec.sectionName;";

            // Debug the query with actual values
            $debug_query = str_replace(
                ['?'],
                [
                    $this->currentDate,                    
                ],
                $queryIndividualNoOfCheckinsInHeadOffice
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfCheckinsInHeadOffice);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "s", 
                $this->currentDate,  // for total_checkins
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Execute failed: " . mysqli_stmt_error($stmt));
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
            $noOfCheckinsInHeadOffice = [];           
            
            while ($row = mysqli_fetch_assoc($result)) {
                $noOfCheckinsInHeadOffice[] = $row['checked_in'];
            }
            
            echo json_encode([
                "status" => "success",
                "data" => $noOfCheckinsInHeadOffice
            ]);
            
        } catch (Exception $e) {
            error_log("Error in BranchWiseAttendanceForToday: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    } 
}
function GetValueDashboard($decoded_items) {
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadGetValueDashboard($decoded_items)) {
        $GetValueDashboardObject->GetValueDashboard();
    } else {        
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
?>