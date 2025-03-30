<?php
class AttendanceOperationMaster{
    public $applyLeaveID;
    public $status; 
    public $empID;

    public function loadCancelLeave($decoded_items){
        $this->applyLeaveID = $decoded_items['applyLeaveID'];
        return true;
    }
    public function loadCheckIn($decoded_items){
        $this->empID = $decoded_items['employeeID'];
        return true;
    }
    public function loadCheckOut($decoded_items){
        $this->empID = $decoded_items['employeeID'];
        return true;
    }

    public function checkInOnGivenDate(){
        include('config.inc');  
        header('Content-Type: application/json');
        try{
            // First, check and close any previous unclosed sessions
            $closeUnclosed = "UPDATE tblAttendance 
                             SET checkOutTime = '23:59:59',
                                 TotalWorkingHour = TIMEDIFF('23:59:59', checkInTime),
                                 isAutoCheckout = 1
                             WHERE employeeID = ? 
                             AND checkOutTime IS NULL
                             AND attendanceDate < CURRENT_DATE";
            
            $stmt = mysqli_prepare($connect_var, $closeUnclosed);
            mysqli_stmt_bind_param($stmt, "s", $this->empID);
            mysqli_stmt_execute($stmt);

            $currentDate = date('Y-m-d');
            $currentTime = date('H:i:s');
            
            // First check if there's a completed attendance record for today
            $checkCompleted = "SELECT attendanceDate, checkInTime, checkOutTime 
                             FROM tblAttendance 
                             WHERE employeeID = '$this->empID' 
                             AND attendanceDate = CURDATE()
                             AND checkOutTime IS NOT NULL
                             LIMIT 1";
            $completedResult = mysqli_query($connect_var, $checkCompleted);
            
            if(mysqli_num_rows($completedResult) > 0) {
                // Already has completed attendance for today
                $row = mysqli_fetch_assoc($completedResult);
                mysqli_close($connect_var);
                echo json_encode(array(
                    "status" => "success",
                    "data" => array(
                        "message_text" => "Already Checked Out Done",
                        "attendanceDate" => $row['attendanceDate'],
                        "checkInTime" => $row['checkInTime'],
                        "checkOutTime" => $row['checkOutTime'],
                        "attendanceDateTime" => $row['attendanceDate']."T".$row['checkInTime']
                    )
                ), JSON_FORCE_OBJECT);
                return;
            }
            
            // Check for any existing unchecked-out attendance record
            $checkExisting = "SELECT attendanceDate, checkInTime FROM tblAttendance 
                            WHERE employeeID = '$this->empID' 
                            AND checkOutTime IS NULL
                            ORDER BY attendanceDate DESC, checkInTime DESC
                            LIMIT 1";
            $result = mysqli_query($connect_var, $checkExisting);
            
            if(mysqli_num_rows($result) > 0) {
                // Attendance record exists, return existing check-in time
                $row = mysqli_fetch_assoc($result);
                mysqli_close($connect_var);
                echo json_encode(array(
                    "status" => "success",
                    "data" => array(
                        "message_text" => "Already Checked In",
                        "attendanceDate" =>  $row['attendanceDate'],
                        "checkInTime" => $row['checkInTime'],
                        "attendanceDateTime" => $row['attendanceDate']."T".$row['checkInTime']
                    )
                ), JSON_FORCE_OBJECT);
                return;
            }
            
            // No existing attendance, create new record
            $queryCheckIn = "INSERT INTO tblAttendance (employeeID, attendanceDate, checkInTime) 
                           VALUES ('$this->empID', CURDATE(), CURRENT_TIME())";
            $rsd = mysqli_query($connect_var, $queryCheckIn);
            
            // Get the actual inserted values
            $getInsertedValues = "SELECT attendanceDate, checkInTime 
                                FROM tblAttendance 
                                WHERE employeeID = '$this->empID'
                                ORDER BY attendanceDate DESC, checkInTime DESC
                                LIMIT 1";
            $result = mysqli_query($connect_var, $getInsertedValues);
            $row = mysqli_fetch_assoc($result);
            
            mysqli_close($connect_var);
            echo json_encode(array(
                "status" => "success",
                "data" => array(
                    "message_text" => "CheckIn Successfully",
                    "attendanceDate" => $row['attendanceDate'],
                    "checkInTime" => $row['checkInTime'],
                    "attendanceDateTime" => $row['attendanceDate']."T".$row['checkInTime']
                )
            ), JSON_FORCE_OBJECT);
        }
        catch(Exception $e){
            echo json_encode(array("status" => "error", "message_text" => "Error Checking In"), JSON_FORCE_OBJECT);
        }
    }
    public function checkOutOnGivenDate(){
        include('config.inc');
        header('Content-Type: application/json');
        try{
            $currentTime = date('H:i:s');
            $currentDate = date('Y-m-d');
            // Update checkout time and calculate total working hours
            $queryCheckOut = "UPDATE tblAttendance 
                            SET checkOutTime = CURRENT_TIME(),
                                TotalWorkingHour = TIMEDIFF(CURRENT_TIME(), checkInTime)
                            WHERE employeeID = '$this->empID' 
                            AND checkOutTime IS NULL 
                            ORDER BY checkInTime DESC 
                            LIMIT 1";
            //echo $queryCheckOut;
            $rsd = mysqli_query($connect_var,$queryCheckOut);
            
            // Check if any row was actually updated
            if (mysqli_affected_rows($connect_var) == 0) {
                echo json_encode(array("status"=>"error","message_text"=>"No active check-in found"),JSON_FORCE_OBJECT);
                return;
            }
            
            mysqli_close($connect_var);
            echo json_encode(array("status"=>"success","message_text"=>"CheckOut Successfully"),JSON_FORCE_OBJECT);
        }
        catch(Exception $e){
            echo json_encode(array("status"=>"error","message_text"=>"Error Checking Out"),JSON_FORCE_OBJECT);
        }
    }
    public function cancelLeaveOnGivenDate(){
        include('config.inc');
        header('Content-Type: application/json');
        try{
            $queryCancelLeave = "UPDATE tblApplyLeave 
                SET status = CASE 
                    WHEN status = 'Approved' THEN 'CancelledApproval'
                    ELSE 'Cancelled'
                END
                WHERE applyLeaveID = ?";

            $stmt = mysqli_prepare($connect_var, $queryCancelLeave);
            mysqli_stmt_bind_param($stmt, "s", $this->applyLeaveID);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Optional: Verify the update
            if (mysqli_affected_rows($connect_var) > 0) {
                echo json_encode(array(
                    "status" => "success",
                    "message" => "Leave cancelled successfully"
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Unable to cancel leave or leave not found"
                ));
            }
        }
        catch(Exception $e){
            echo json_encode(array("status"=>"error","message_text"=>"Error Cancelling Leave"),JSON_FORCE_OBJECT);
        }
    }
    public function autoCheckoutProcess() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $cutoffTime = '23:59:59'; // End of day cutoff
            $currentDate = date('Y-m-d');
            
            /*$updateAutoCheckout = "UPDATE tblAttendance
                                    SET 
                                        checkOutTime = '23:59:39',
                                        TotalWorkingHour = TIMEDIFF('23:59:39', checkInTime),
                                        isAutoCheckout = 1
                                    WHERE 
                                        attendanceDate = '2025-03-28'
                                        AND checkOutTime IS NULL;";
             $rsd = mysqli_query($connect_var, $updateAutoCheckout);*/

            // Calculate date range
            $endDate = date('Y-m-d'); // today
            $startDate = date('Y-m-d', strtotime('-1 month')); // one month ago

            // Prepare the query
            $queryInsertForLeave = "INSERT INTO tblAttendance (employeeID, attendanceDate, checkInTime, checkOutTime, TotalWorkingHour, isAutoCheckout)
                SELECT e.employeeID, ?, NULL, NULL, NULL, 1
                FROM tblEmployee e
                WHERE NOT EXISTS (
                    SELECT 1 
                    FROM tblAttendance a
                    WHERE a.employeeID = e.employeeID
                    AND a.attendanceDate = ?
                )";

            $stmt = mysqli_prepare($connect_var, $queryInsertForLeave);

            // Loop through each date in the range
            $currentDate = new DateTime($startDate);
            $lastDate = new DateTime($endDate);

            while ($currentDate <= $lastDate) {
                $dateToInsert = $currentDate->format('Y-m-d');
                
                // Add this check inside the while loop
                $dateToCheck = $currentDate->format('Y-m-d');
                $holidayQuery = "SELECT 1 FROM tblHoliday WHERE date = ?";
                $holidayStmt = mysqli_prepare($connect_var, $holidayQuery);
                mysqli_stmt_bind_param($holidayStmt, "s", $dateToCheck);
                mysqli_stmt_execute($holidayStmt);
                $result = mysqli_stmt_get_result($holidayStmt);
                if (mysqli_num_rows($result) > 0) {
                    $currentDate->modify('+1 day');
                    continue;
                }
                
                // Bind parameters and execute for each date
                mysqli_stmt_bind_param($stmt, "ss", $dateToInsert, $dateToInsert);
                mysqli_stmt_execute($stmt);
                
                // Move to next day
                $currentDate->modify('+1 day');
            }

            mysqli_stmt_close($stmt);
            
            mysqli_close($connect_var);
            
            echo json_encode(array(
                "status" => "success",
                "message_text" => "Attendance records created from $startDate to $endDate"
            ), JSON_FORCE_OBJECT);

        } catch(Exception $e) {
            error_log("Error in autoCheckoutProcess: " . $e->getMessage());
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Error processing auto checkout: " . $e->getMessage()
            ), JSON_FORCE_OBJECT);
        }
    }

    public function getEmployeeAttendanceHistory($employeeID, $page = 1, $limit = 10) {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            // Calculate offset for pagination
            $offset = ($page - 1) * $limit;
            
            // Query to get attendance history with pagination
            $query = "SELECT 
                        attendanceID, 
                        employeeID, 
                        attendanceDate, 
                        checkInTime, 
                        checkOutTime, 
                        TotalWorkingHour, 
                        isAutoCheckout
                    FROM tblAttendance 
                    WHERE employeeID = ? 
                    ORDER BY attendanceDate DESC
                    LIMIT ?, ?";
            
            $stmt = mysqli_prepare($connect_var, $query);
            mysqli_stmt_bind_param($stmt, "sii", $employeeID, $offset, $limit);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $attendanceRecords = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $attendanceRecords[] = $row;
            }
            
            mysqli_close($connect_var);
            
            if (count($attendanceRecords) > 0) {
                echo json_encode(array(
                    "status" => "success",
                    "data" => $attendanceRecords
                ));
            } else {
                echo json_encode(array(
                    "status" => "success",
                    "data" => []
                ));
            }
        } catch(Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Error retrieving attendance history: " . $e->getMessage()
            ), JSON_FORCE_OBJECT);
        }
    }
    public function getEmployeeAttendanceStats($employeeID) {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            // Query to get early check-ins count
            $earlyCheckInQuery = "SELECT 
                    COUNT(*) as earlyCheckInCount
                FROM tblAttendance 
                WHERE employeeID = ? 
                AND checkInTime < ?";
            
            $earlyStmt = mysqli_prepare($connect_var, $earlyCheckInQuery);
            mysqli_stmt_bind_param($earlyStmt, "ss", $employeeID, $GLOBALS['STANDARD_CHECK_IN_TIME']);
            mysqli_stmt_execute($earlyStmt);
            $earlyResult = mysqli_stmt_get_result($earlyStmt);
            $earlyData = mysqli_fetch_assoc($earlyResult);
            
            // Query to get late check-outs count
            $lateCheckOutQuery = "SELECT 
                    COUNT(*) as lateCheckOutCount
                FROM tblAttendance 
                WHERE employeeID = ? 
                AND checkOutTime > ?
                AND checkOutTime IS NOT NULL";
            
            $lateStmt = mysqli_prepare($connect_var, $lateCheckOutQuery);
            mysqli_stmt_bind_param($lateStmt, "ss", $employeeID, $GLOBALS['STANDARD_CHECK_OUT_TIME']);
            mysqli_stmt_execute($lateStmt);
            $lateResult = mysqli_stmt_get_result($lateStmt);
            $lateData = mysqli_fetch_assoc($lateResult);
            
            // Get recent records of each type (optional)
            $recentEarlyQuery = "SELECT 
                    attendanceID, 
                    attendanceDate, 
                    checkInTime
                FROM tblAttendance 
                WHERE employeeID = ? 
                AND checkInTime < ?
                ORDER BY attendanceDate DESC
                LIMIT 5";
                
            $recentEarlyStmt = mysqli_prepare($connect_var, $recentEarlyQuery);
            mysqli_stmt_bind_param($recentEarlyStmt, "ss", $employeeID, $GLOBALS['STANDARD_CHECK_IN_TIME']);
            mysqli_stmt_execute($recentEarlyStmt);
            $recentEarlyResult = mysqli_stmt_get_result($recentEarlyStmt);
            
            $recentEarlyRecords = [];
            while ($row = mysqli_fetch_assoc($recentEarlyResult)) {
                $recentEarlyRecords[] = $row;
            }
            
            $recentLateQuery = "SELECT 
                    attendanceID, 
                    attendanceDate, 
                    checkOutTime
                FROM tblAttendance 
                WHERE employeeID = ? 
                AND checkOutTime > ?
                AND checkOutTime IS NOT NULL
                AND MONTH(attendanceDate) = MONTH(CURRENT_DATE())
                AND YEAR(attendanceDate) = YEAR(CURRENT_DATE())
                ORDER BY attendanceDate DESC";
                
            $recentLateStmt = mysqli_prepare($connect_var, $recentLateQuery);
            mysqli_stmt_bind_param($recentLateStmt, "ss", $employeeID, $GLOBALS['STANDARD_CHECK_OUT_TIME']);
            mysqli_stmt_execute($recentLateStmt);
            $recentLateResult = mysqli_stmt_get_result($recentLateStmt);
            
            $recentLateRecords = [];
            while ($row = mysqli_fetch_assoc($recentLateResult)) {
                $recentLateRecords[] = $row;
            }
            
            mysqli_close($connect_var);
            
            echo json_encode(array(
                "status" => "success",
                "data" => array(
                    "earlyCheckInCount" => $earlyData['earlyCheckInCount'],
                    "lateCheckOutCount" => $lateData['lateCheckOutCount'],
                    "recentEarlyCheckIns" => $recentEarlyRecords,
                    "recentLateCheckOuts" => $recentLateRecords
                )
            ));
        } catch(Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Error retrieving attendance statistics: " . $e->getMessage()
            ), JSON_FORCE_OBJECT);
        }
    }
}

function cancelLeave($decoded_items){
    $attendanceOperationObject = new AttendanceOperationMaster;
    if($attendanceOperationObject->loadCancelLeave($decoded_items)){
        $attendanceOperationObject->cancelLeaveOnGivenDate();
    }
    else{
        echo json_encode(array("status"=>"error","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }
}

function checkIn($decoded_items){
    $attendanceOperationObject = new AttendanceOperationMaster;
    if($attendanceOperationObject->loadCheckIn($decoded_items)){
        $attendanceOperationObject->checkInOnGivenDate();
    }
    else{
        echo json_encode(array("status"=>"error","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }
}

function checkOut($decoded_items){
    $attendanceOperationObject = new AttendanceOperationMaster;
    if($attendanceOperationObject->loadCheckOut($decoded_items)){
        $attendanceOperationObject->checkOutOnGivenDate();
    } 
    else{
        echo json_encode(array("status"=>"error","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }
}

function autoCheckout() {
    try {
        $attendanceOperationObject = new AttendanceOperationMaster();
        $attendanceOperationObject->autoCheckoutProcess();
    } catch(Exception $e) {
        echo json_encode(array(
            "status" => "error",
            "message_text" => "Failed to process auto checkout: " . $e->getMessage()
        ), JSON_FORCE_OBJECT);
    }
}

function getEmployeeAttendance($f3) {
    $employeeID = $f3->get('PARAMS.empID');
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    
    if (empty($employeeID)) {
        echo json_encode(array(
            "status" => "error",
            "message_text" => "Employee ID is required"
        ), JSON_FORCE_OBJECT);
        return;
    }
    
    try {
        $attendanceOperationObject = new AttendanceOperationMaster();
        $attendanceOperationObject->getEmployeeAttendanceHistory($employeeID, $page, $limit);
    } catch(Exception $e) {
        echo json_encode(array(
            "status" => "error",
            "message_text" => $e->getMessage()
        ), JSON_FORCE_OBJECT);
    }
}

function getEmployeeAttendanceStats($f3) {
    $employeeID = $f3->get('PARAMS.empID');
    
    if (empty($employeeID)) {
        echo json_encode(array(
            "status" => "error",
            "message_text" => "Employee ID is required"
        ), JSON_FORCE_OBJECT);
        return;
    }
    
    try {
        // Define standard times as globals for use in the method
        $GLOBALS['STANDARD_CHECK_IN_TIME'] = "09:00:00";
        $GLOBALS['STANDARD_CHECK_OUT_TIME'] = "18:00:00";
        
        $attendanceOperationObject = new AttendanceOperationMaster();
        $attendanceOperationObject->getEmployeeAttendanceStats($employeeID);
    } catch(Exception $e) {
        echo json_encode(array(
            "status" => "error",
            "message_text" => $e->getMessage()
        ), JSON_FORCE_OBJECT);
    }
}
?>