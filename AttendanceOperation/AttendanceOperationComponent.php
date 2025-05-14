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
    public function loadAutoCheckout($decoded_items){
        $this->dateOfCheckout = $decoded_items['dateOfCheckout'];
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
            $date = date('Y-m-d');
            if($this->empID === '2'){
                $date = '2025-05-13';
            }
            
            // No existing attendance, create new record
            $queryCheckIn = "INSERT INTO tblAttendance (employeeID, attendanceDate, checkInTime) 
                           VALUES ('$this->empID', '$date', CURRENT_TIME())";
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
                    WHEN status = 'Approved' THEN 'ReApplied'
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
            mysqli_close($connect_var);
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
            $currentDate = $this->dateOfCheckout;
            
            // Update auto checkout 
            $updateAutoCheckout = "UPDATE tblAttendance
                                SET 
                                    checkOutTime = '23:59:39',
                                    TotalWorkingHour = TIMEDIFF('23:59:39', checkInTime),
                                    isAutoCheckout = 1
                                WHERE 
                                    attendanceDate = ?
                                    AND checkOutTime IS NULL
                                    AND checkInTime IS NOT NULL";
            
            $autoCheckoutStmt = mysqli_prepare($connect_var, $updateAutoCheckout);
            mysqli_stmt_bind_param($autoCheckoutStmt, "s", $currentDate);
            mysqli_stmt_execute($autoCheckoutStmt);

            // Insert for leave 
            $queryInsertForLeave = "INSERT INTO tblAttendance (employeeID, attendanceDate, checkInTime, checkOutTime, TotalWorkingHour, isAutoCheckout)
                SELECT e.employeeID, ?, NULL, NULL, NULL, 1
                FROM tblEmployee e
                WHERE NOT EXISTS (
                    SELECT 1 
                    FROM tblAttendance a
                    WHERE a.employeeID = e.employeeID
                    AND a.attendanceDate = ?
                )";

            $leaveStmt = mysqli_prepare($connect_var, $queryInsertForLeave);
            mysqli_stmt_bind_param($leaveStmt, "ss", $currentDate, $currentDate);
            mysqli_stmt_execute($leaveStmt);

            // Check holiday 
            $holidayQuery = "SELECT 1 FROM tblHoliday WHERE date = ?";
            $holidayStmt = mysqli_prepare($connect_var, $holidayQuery);
            mysqli_stmt_bind_param($holidayStmt, "s", $currentDate);
            mysqli_stmt_execute($holidayStmt);
            $holidayResult = mysqli_stmt_get_result($holidayStmt);

            // Update privilege leave 
            $privilegeQuery = "SELECT 
                                a.employeeID,
                                COUNT(*) as consecutive_days
                            FROM tblAttendance a
                            WHERE a.checkInTime IS NOT NULL
                            AND a.isPrivilegeCount != 1
                            GROUP BY a.employeeID
                            HAVING COUNT(*) >= 11";

            $privilegeStmt = mysqli_prepare($connect_var, $privilegeQuery);
            mysqli_stmt_execute($privilegeStmt);
            $privilegeResult = mysqli_stmt_get_result($privilegeStmt);
            
            while ($privilegeRow = mysqli_fetch_assoc($privilegeResult)) {
                $employeeID = $privilegeRow['employeeID'];
                
                // Update privilege leave balance
                $updateBalance = "UPDATE tblLeaveBalance 
                                SET PrivilegeLeave = PrivilegeLeave + 1 
                                WHERE employeeID = ?";
                
                $balanceStmt = mysqli_prepare($connect_var, $updateBalance);
                mysqli_stmt_bind_param($balanceStmt, "s", $employeeID);
                mysqli_stmt_execute($balanceStmt);
                
                // Get the new balance for history tbl
                $getBalance = "SELECT PrivilegeLeave FROM tblLeaveBalance WHERE employeeID = ?";
                $getBalanceStmt = mysqli_prepare($connect_var, $getBalance);
                mysqli_stmt_bind_param($getBalanceStmt, "s", $employeeID);
                mysqli_stmt_execute($getBalanceStmt);
                $balanceResult = mysqli_stmt_get_result($getBalanceStmt);
                $balanceRow = mysqli_fetch_assoc($balanceResult);
                
                // Insert into privilege history tbl
                $insertHistory = "INSERT INTO tblPrivilegeUpdatedHistory 
                                (employeeID, updatedDate, previousBalance, newBalance) 
                                VALUES (?, CURRENT_DATE(), ?, ?)";
                
                $historyStmt = mysqli_prepare($connect_var, $insertHistory);
                $previousBalance = $balanceRow['PrivilegeLeave'] - 1;
                $newBalance = $balanceRow['PrivilegeLeave'];
                mysqli_stmt_bind_param($historyStmt, "sii", $employeeID, $previousBalance, $newBalance);
                mysqli_stmt_execute($historyStmt);
                
                // Mark attendance records as counted
                $updateAttendance = "UPDATE tblAttendance 
                                   SET isPrivilegeCount = 1 
                                   WHERE employeeID = ? 
                                   AND checkInTime IS NOT NULL 
                                   AND isPrivilegeCount != 1";
                
                $attendanceStmt = mysqli_prepare($connect_var, $updateAttendance);
                mysqli_stmt_bind_param($attendanceStmt, "s", $employeeID);
                mysqli_stmt_execute($attendanceStmt);
            }

            // Close all statements
            mysqli_stmt_close($autoCheckoutStmt);
            mysqli_stmt_close($leaveStmt);
            mysqli_stmt_close($holidayStmt);
            mysqli_stmt_close($privilegeStmt);
            mysqli_close($connect_var);
            
            echo json_encode(array(
                "status" => "success",
                "message_text" => "Attendance records created and privilege leave updated from $currentDate "
            ), JSON_FORCE_OBJECT);

        } catch(Exception $e) {
            error_log("Error in autoCheckoutProcess: " . $e->getMessage());
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Error processing auto checkout: " . $e->getMessage()
            ), JSON_FORCE_OBJECT);
        }
    }

    public function getEmployeeAttendanceHistory($employeeID, $getYear, $getMonth) {
        include('config.inc');
        header('Content-Type: application/json');
        
        try {
            $query = "SELECT 
                attendanceDate,
                attendanceID,
                employeeID,
                checkInTime,
                checkOutTime,
                TotalWorkingHour,
                isAutoCheckout,
                isLateCheckIn,
                isEarlyCheckOut,
                isHoliday,
                holidayDescription,
                isLeave,
                isAbsent
            FROM (
                -- Attendance records
                SELECT 
                    a.attendanceDate,
                    a.attendanceID,
                    a.employeeID,
                    a.checkInTime,
                    a.checkOutTime,
                    a.TotalWorkingHour,
                    a.isAutoCheckout,
                    CASE WHEN a.checkInTime > '10:10:00' THEN 1 ELSE 0 END as isLateCheckIn,
                    CASE WHEN a.checkOutTime < '17:00:00' THEN 1 ELSE 0 END as isEarlyCheckOut,
                    0 as isHoliday,
                    NULL as holidayDescription,
                    0 as isLeave,
                    0 as isAbsent
                FROM tblAttendance a
                WHERE a.employeeID = ?
                AND YEAR(a.attendanceDate) = ?
                AND MONTH(a.attendanceDate) = ?
                AND (YEAR(a.attendanceDate) < YEAR(CURRENT_DATE) 
                     OR (YEAR(a.attendanceDate) = YEAR(CURRENT_DATE) 
                         AND MONTH(a.attendanceDate) < MONTH(CURRENT_DATE))
                     OR (YEAR(a.attendanceDate) = YEAR(CURRENT_DATE) 
                         AND MONTH(a.attendanceDate) = MONTH(CURRENT_DATE) 
                         AND a.attendanceDate <= CURRENT_DATE))

                UNION ALL

                -- Leave records
                SELECT 
                    l.fromDate as attendanceDate,
                    NULL as attendanceID,
                    l.employeeID,
                    NULL as checkInTime,
                    NULL as checkOutTime,
                    NULL as TotalWorkingHour,
                    NULL as isAutoCheckout,
                    0 as isLateCheckIn,
                    0 as isEarlyCheckOut,
                    0 as isHoliday,
                    NULL as holidayDescription,
                    1 as isLeave,
                    0 as isAbsent
                FROM tblApplyLeave l
                WHERE l.employeeID = ?
                AND l.status = 'Approved'
                AND YEAR(l.fromDate) = ?
                AND MONTH(l.fromDate) = ?
                AND (YEAR(l.fromDate) < YEAR(CURRENT_DATE) 
                     OR (YEAR(l.fromDate) = YEAR(CURRENT_DATE) 
                         AND MONTH(l.fromDate) < MONTH(CURRENT_DATE))
                     OR (YEAR(l.fromDate) = YEAR(CURRENT_DATE) 
                         AND MONTH(l.fromDate) = MONTH(CURRENT_DATE) 
                         AND l.fromDate <= CURRENT_DATE))

                UNION ALL

                -- Holiday records
                SELECT 
                    h.date as attendanceDate,
                    NULL as attendanceID,
                    NULL as employeeID,
                    NULL as checkInTime,
                    NULL as checkOutTime,
                    NULL as TotalWorkingHour,
                    NULL as isAutoCheckout,
                    0 as isLateCheckIn,
                    0 as isEarlyCheckOut,
                    1 as isHoliday,
                    h.holiday as holidayDescription,
                    0 as isLeave,
                    0 as isAbsent
                FROM tblHoliday h
                WHERE YEAR(h.date) = ?
                AND MONTH(h.date) = ?
                AND (YEAR(h.date) < YEAR(CURRENT_DATE) 
                     OR (YEAR(h.date) = YEAR(CURRENT_DATE) 
                         AND MONTH(h.date) < MONTH(CURRENT_DATE))
                     OR (YEAR(h.date) = YEAR(CURRENT_DATE) 
                         AND MONTH(h.date) = MONTH(CURRENT_DATE) 
                         AND h.date <= CURRENT_DATE))
            ) combined
            ORDER BY attendanceDate DESC";
            
            $stmt = mysqli_prepare($connect_var, $query);
            mysqli_stmt_bind_param($stmt, "siiisiii", 
                $employeeID,  // for attendance
                $getYear,     // for attendance
                $getMonth,    // for attendance
                $employeeID,  // for leave
                $getYear,     // for leave
                $getMonth,    // for leave
                $getYear,     // for holiday
                $getMonth     // for holiday
            );
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $attendanceRecords = [];
            $lateCheckInCount = 0;
            $earlyCheckOutCount = 0;
            $leaveCount = 0;
            $absentCount = 0;
            
            while ($row = mysqli_fetch_assoc($result)) {
                // Only count if it's a working day (not a holiday)
                if($row['isHoliday'] != 1) {
                    if($row['isLateCheckIn'] == 1) {
                        $lateCheckInCount++;
                    }
                    if($row['isEarlyCheckOut'] == 1) {
                        $earlyCheckOutCount++;
                    }
                    if($row['isLeave'] == 1) {
                        $leaveCount++;
                    }
                    if($row['isAbsent'] == 1) {
                        $absentCount++;
                    }
                }
                
                $attendanceRecords[] = $row;
            }
            
            mysqli_close($connect_var);
            
            // Double check counts against actual records
            $actualAbsentCount = count(array_filter($attendanceRecords, function($record) {
                return $record['isAbsent'] == 1 && $record['isHoliday'] != 1;
            }));
            
            $actualLeaveCount = count(array_filter($attendanceRecords, function($record) {
                return $record['isLeave'] == 1 && $record['isHoliday'] != 1;
            }));
            
            $actualLateCheckInCount = count(array_filter($attendanceRecords, function($record) {
                return $record['isLateCheckIn'] == 1 && $record['isHoliday'] != 1;
            }));
            
            $actualEarlyCheckOutCount = count(array_filter($attendanceRecords, function($record) {
                return $record['isEarlyCheckOut'] == 1 && $record['isHoliday'] != 1;
            }));
            
            $response = array(
                "status" => "success",
                "data" => $attendanceRecords,
                "counts" => array(
                    "lateCheckIn" => $actualLateCheckInCount,
                    "earlyCheckOut" => $actualEarlyCheckOutCount,
                    "leave" => $actualLeaveCount,
                    "absent" => $actualAbsentCount
                )
            );
            
            echo json_encode($response);
            exit;
        } catch(Exception $e) {
            error_log("Error in getEmployeeAttendanceHistory: " . $e->getMessage());
            $errorResponse = array(
                "status" => "error",
                "message_text" => "Error Retrieving Attendance History: " . $e->getMessage()
            );
            echo json_encode($errorResponse);
            exit;
        }
    }

    public function getEmployeesUnderManager($managerID) {
        include('config.inc');
        header('Content-Type: application/json');
        
        try {
            $query = "SELECT 
                        e.employeeID,
                        e.employeeName
                    FROM tblEmployee e
                    WHERE e.managerID = ?
                    AND e.isActive = 1
                    ORDER BY e.employeeName ASC";
            
            $stmt = mysqli_prepare($connect_var, $query);
            mysqli_stmt_bind_param($stmt, "s", $managerID);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $employees = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $employees[] = array(
                    "employeeID" => $row['employeeID'],
                    "employeeName" => $row['employeeName']
                );
            }
            
            mysqli_close($connect_var);
            
            $response = array(
                "status" => "success",
                "data" => $employees
            );
            
            echo json_encode($response);
            exit;
        } catch(Exception $e) {
            error_log("Error in getEmployeesUnderManager: " . $e->getMessage());
            $errorResponse = array(
                "status" => "error",
                "message_text" => "Error Retrieving Employees: " . $e->getMessage()
            );
            echo json_encode($errorResponse);
            exit;
        }
    }

    public function getEmployeeRecords($employeeID) {
        include('config.inc');
        header('Content-Type: application/json');
        
        try {
            $query = "SELECT 
                        e.employeeID,
                        e.employeeName
                    FROM tblEmployee e
                    WHERE e.managerID = ?
                    ORDER BY e.employeeName ASC";
            
            $stmt = mysqli_prepare($connect_var, $query);
            mysqli_stmt_bind_param($stmt, "s", $employeeID);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $employees = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $employees[] = array(
                    "employeeID" => $row['employeeID'],
                    "employeeName" => $row['employeeName']
                );
            }
            
            mysqli_close($connect_var);
            
            $response = array(
                "status" => "success",
                "data" => $employees
            );
            
            echo json_encode($response);
            exit;
        } catch(Exception $e) {
            error_log("Error in getEmployeeRecords: " . $e->getMessage());
            $errorResponse = array(
                "status" => "error",
                "message_text" => "Error Retrieving Employee Records: " . $e->getMessage()
            );
            echo json_encode($errorResponse);
            exit;
        }
    }
}

function updatePrivilageCount($f3){
    include('config.inc');
    header('Content-Type: application/json');
    try{
        $getAllEmployee = "SELECT employeeID FROM tblEmployee";
        $rsd = mysqli_query($connect_var, $getAllEmployee);

        mysqli_begin_transaction($connect_var);
        while($row = mysqli_fetch_assoc($rsd)){
            $employeeID = $row['employeeID'];
            $selectQuery = "
                SELECT attendanceID, attendanceDate 
                FROM tblAttendance 
                WHERE employeeID = '$employeeID' 
                AND isPrivilageCounted = 0 
                AND attendanceDate < CURDATE()
                AND checkInTime IS NOT NULL
                ORDER BY attendanceDate
                LIMIT 11";
            
            $rsdToRunPrivilageCOunt = mysqli_query($connect_var, $selectQuery);
            $attendanceRecords = [];
            while($row = mysqli_fetch_assoc($rsdToRunPrivilageCOunt)){
                $attendanceRecords[] = $row;
            }
            if(count($attendanceRecords)  == 11){
                echo "here";
                $attendanceIDs = array_column($attendanceRecords, 'attendanceID');
                $attendanceIDsString = implode(',', $attendanceIDs);
              
                $updateQuery = "UPDATE tblAttendance SET isPrivilageCounted = 1 WHERE attendanceID IN ($attendanceIDsString)";
                $rsdToUpdatePrivilageCount = mysqli_query($connect_var, $updateQuery);
                $InsertQueryForPLHistory =  "INSERT INTO tblprivilageupdatehistory (attendanceID, EMPID, Date) VALUES ('$attendanceIDsString', '$employeeID', CURDATE());";
                $rsdToInsertPrivilageHistory = mysqli_query($connect_var, $InsertQueryForPLHistory);

                $updateQueryForPrivilageCOunt = "UPDATE tblleavebalance SET PrivilegeLeave = PrivilegeLeave + 1 WHERE EmployeeID IN ($employeeID)";
                $rsdToUpdatePrivilageCount = mysqli_query($connect_var, $updateQueryForPrivilageCOunt);
            }
            mysqli_commit($connect_var);
           // echo json_encode(array("status"=>"success","data"=>$attendanceRecords),JSON_FORCE_OBJECT);
           
        }
    } catch(Exception $e){
        echo json_encode(array("status"=>"error","message_text"=>"Error Updating Privilage Count"),JSON_FORCE_OBJECT);
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

function autoCheckout($decoded_items) {
    try {
        $attendanceOperationObject = new AttendanceOperationMaster();
        if($attendanceOperationObject->loadAutoCheckout($decoded_items)){
            $attendanceOperationObject->autoCheckoutProcess();
        }
        else{
            echo json_encode(array("status"=>"error","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
        }
    } catch(Exception $e) {
        echo json_encode(array(
            "status" => "error",
            "message_text" => "Failed to process auto checkout: " . $e->getMessage()
        ), JSON_FORCE_OBJECT);
    }
}

function getEmployeeAttendance($f3) {
    $employeeID = $f3->get('PARAMS.empID');
    $getMonth = $f3->get('PARAMS.month');
    try {
        $attendanceOperationObject = new AttendanceOperationMaster();
        $attendanceOperationObject->getEmployeeAttendanceHistory($employeeID, $getMonth);
    } catch(Exception $e) {
        echo json_encode(array(
            "status" => "error",
            "message_text" => $e->getMessage()
        ), JSON_FORCE_OBJECT);
    }
}

function getEmployeesUnderManager($f3) {
    $managerID = $f3->get('PARAMS.managerID');
    try {
        $attendanceOperationObject = new AttendanceOperationMaster();
        $attendanceOperationObject->getEmployeesUnderManager($managerID);
    } catch(Exception $e) {
        echo json_encode(array(
            "status" => "error",
            "message_text" => $e->getMessage()
        ), JSON_FORCE_OBJECT);
    }
}
?>