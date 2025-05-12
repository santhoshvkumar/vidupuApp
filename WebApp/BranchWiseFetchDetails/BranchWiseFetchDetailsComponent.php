<?php
class BranchWiseFetchDetailsComponent{
    public $branchID;
    public $branchName;
    public $currentDate;    
    
    public function loadBranchWiseAttendanceForToday(array $data){ 
        if (isset($data['currentDate'])) {  
            $this->currentDate = $data['currentDate'];
            return true;
        } else {
            return false;
        }
    }

    public function BranchWiseAttendanceForToday() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       

            // Debug input values
            error_log("BranchWiseAttendanceForToday - Input values:");
            error_log("currentDate: " . $this->currentDate);

            // 1. Branch Wise Employee Attendance for today
            $queryBranchWiseEmployeeAttendanceForToday = "
                SELECT 
                    b.branchID,
                    b.branchName AS branch_name,
                    (SELECT COUNT(e.employeeID)
                     FROM tblEmployee e
                     JOIN tblmapEmp m ON e.employeeID = m.employeeID
                     WHERE m.branchID = b.branchID) AS total_employees,
                    (SELECT COUNT(att.employeeID) 
                     FROM tblAttendance att
                     JOIN tblEmployee e ON att.employeeID = e.employeeID
                     JOIN tblmapEmp m ON e.employeeID = m.employeeID
                     WHERE m.branchID = b.branchID
                     AND att.checkInTime IS NOT NULL
                     AND att.attendanceDate = ?) AS total_checkins,
                    (SELECT COUNT(att.employeeID) 
                     FROM tblAttendance att
                     JOIN tblEmployee e ON att.employeeID = e.employeeID
                     JOIN tblmapEmp m ON e.employeeID = m.employeeID
                     WHERE m.branchID = b.branchID
                     AND att.checkInTime IS NOT NULL
                     AND att.checkInTime > '10:10:00'
                     AND att.attendanceDate = ?) AS late_checkin,
                    (SELECT COUNT(att.employeeID) 
                     FROM tblAttendance att
                     JOIN tblEmployee e ON att.employeeID = e.employeeID
                     JOIN tblmapEmp m ON e.employeeID = m.employeeID
                     WHERE m.branchID = b.branchID
                     AND att.checkOutTime IS NOT NULL
                     AND att.checkOutTime < '17:00:00'
                     AND att.attendanceDate = ?) AS early_checkout,
                    (SELECT COUNT(e.employeeID)
                     FROM tblApplyLeave l
                     JOIN tblEmployee e ON l.employeeID = e.employeeID
                     JOIN tblmapEmp m ON e.employeeID = m.employeeID
                     WHERE m.branchID = b.branchID
                     AND l.fromDate = ?) AS on_leave
                FROM tblBranch b
                ORDER BY b.branchName ASC;";

            // Debug the query with actual values
            $debug_query = str_replace(
                ['?', '?', '?', '?'],
                [
                    $this->currentDate,
                    $this->currentDate,
                    $this->currentDate,
                    $this->currentDate,
                ],
                $queryBranchWiseEmployeeAttendanceForToday
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryBranchWiseEmployeeAttendanceForToday);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ssss", 
                $this->currentDate,  // for total_checkins
                $this->currentDate, // for late_checkin
                $this->currentDate, // for early_checkout
                $this->currentDate, // for on_leave
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Execute failed: " . mysqli_stmt_error($stmt));
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
            $branches = [];
            $branchName = [];
            $totalEmployees = [];
            $totalCheckIns = [];
            $onLeave = [];
            $lateCheckIn = [];
            $earlyCheckOut = [];
            $absentees = [];
            $countBranch = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $branchName[] = $row['branch_name'];
                $totalEmployees[] = $row['total_employees'] != 0 ? intval($row['total_employees']) : 0;
                $totalCheckIns[] = $row['total_checkins'] != 0 ? intval($row['total_checkins']) : 0;
                $onLeave[] = $row['on_leave'] != 0 ? intval($row['on_leave']) : 0;
                $lateCheckIn[] = $row['late_checkin'] != 0 ? intval($row['late_checkin']) : 0;
                $earlyCheckOut[] = $row['early_checkout'] != 0 ? intval($row['early_checkout']) : 0;
                $absentees[] = $row['total_employees'] != 0 ? intval($row['total_employees']) - ($row['total_checkins'] + $row['on_leave']) : 0;
                $countBranch++;
            }
            
            if ($countBranch > 0) {
                echo json_encode([
                    "status" => "success",
                    "branchName" => $branchName,
                    "totalEmployees" => $totalEmployees,
                    "totalCheckIns" => $totalCheckIns,
                    "onLeave" => $onLeave,
                    "lateCheckIn" => $lateCheckIn,
                    "earlyCheckOut" => $earlyCheckOut,
                    "absentees" => $absentees
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message_text" => "No data found for any branch"
                ], JSON_FORCE_OBJECT);
            }
            
        } catch (Exception $e) {
            error_log("Error in BranchWiseAttendanceForToday: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    } 
}
function BranchWiseAttendanceForToday($decoded_items) {
    $BranchWiseFetchDetailsObject = new BranchWiseFetchDetailsComponent();
    if ($BranchWiseFetchDetailsObject->loadBranchWiseAttendanceForToday($decoded_items)) {
        $BranchWiseFetchDetailsObject->BranchWiseAttendanceForToday();
    } else {        
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
?>