<?php
class AttendanceOperationMaster{
    public $applyLeaveID;
    public $status; 
    public $empID;
    public $branchID;
    public $organisationID;

    public function loadCancelLeave($decoded_items){
        $this->applyLeaveID = $decoded_items['applyLeaveID'];
        return true;
    }
    public function loadCheckIn($decoded_items){
        if (!isset($decoded_items['employeeID'])) {
            error_log("loadCheckIn: employeeID not provided in request");
            return false;
        }
        $this->empID = $decoded_items['employeeID'];
        error_log("loadCheckIn: Looking up branch info for employeeID: " . $this->empID);
        
        // Get branchID and organisationID from database using employeeID
        include('config.inc');
        $query = "SELECT branchID, organisationID FROM tblmapEmp WHERE employeeID = ? LIMIT 1";
        error_log("loadCheckIn: Executing query: " . $query . " with employeeID: " . $this->empID);
        
        $stmt = mysqli_prepare($connect_var, $query);
        if (!$stmt) {
            error_log("loadCheckIn: Failed to prepare statement: " . mysqli_error($connect_var));
            return false;
        }
        
        mysqli_stmt_bind_param($stmt, "s", $this->empID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $this->branchID = $row['branchID'];
            $this->organisationID = $row['organisationID'];
            error_log("loadCheckIn: Found branch info - BranchID: " . $this->branchID . ", OrganisationID: " . $this->organisationID);
            mysqli_close($connect_var);
            return true;
        } else {
            error_log("loadCheckIn: No branch mapping found for employeeID: " . $this->empID);
            mysqli_close($connect_var);
            return false;
        }
    }
    public function loadCheckOut($decoded_items){
        if (!isset($decoded_items['employeeID'])) {
            error_log("loadCheckOut: employeeID not provided in request");
            return false;
        }
        $this->empID = $decoded_items['employeeID'];
        error_log("loadCheckOut: Looking up branch info for employeeID: " . $this->empID);
        
        // Get branchID and organisationID from database using employeeID
        include('config.inc');
        $query = "SELECT branchID, organisationID FROM tblmapEmp WHERE employeeID = ? LIMIT 1";
        error_log("loadCheckOut: Executing query: " . $query . " with employeeID: " . $this->empID);
        
        $stmt = mysqli_prepare($connect_var, $query);
        if (!$stmt) {
            error_log("loadCheckOut: Failed to prepare statement: " . mysqli_error($connect_var));
            return false;
        }
        
        mysqli_stmt_bind_param($stmt, "s", $this->empID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $this->branchID = $row['branchID'];
            $this->organisationID = $row['organisationID'];
            error_log("loadCheckOut: Found branch info - BranchID: " . $this->branchID . ", OrganisationID: " . $this->organisationID);
            mysqli_close($connect_var);
            return true;
        } else {
            error_log("loadCheckOut: No branch mapping found for employeeID: " . $this->empID);
            mysqli_close($connect_var);
            return false;
        }
    }
    public function loadAutoCheckout($decoded_items){
        $this->dateOfCheckout = $decoded_items['dateOfCheckout'];
        return true;
    }

    public function checkInOnGivenDate(){
        include('config.inc');  
        header('Content-Type: application/json');
        try{
            
            error_log("Starting checkInOnGivenDate - EmployeeID: $this->empID, BranchID: $this->branchID, OrganisationID: $this->organisationID");
            
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
                            AND attendanceDate=CURRENT_DATE()
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
            
            // Get employee's branch and determine if they're late
            $branchQuery = "SELECT b.checkInTime FROM tblBranch b 
                           WHERE b.branchID = '$this->branchID' LIMIT 1";
            $branchResult = mysqli_query($connect_var, $branchQuery);
            $isLateCheckIn = 0; // Default to not late
            
            if ($branchResult && mysqli_num_rows($branchResult) > 0) {
                // Get branch details and check-in time
                $branchRow = mysqli_fetch_assoc($branchResult);
                $branchCheckInTime = $branchRow['checkInTime']; // Get branch's specific check-in time
                $currentTime = date('H:i:s'); // Get current system time
                
                // Debug logging
                error_log("Employee $this->empID - BranchID: $this->branchID, BranchCheckInTime: $branchCheckInTime, CurrentTime: $currentTime");
                
                // Check if employee is late based on their branch's check-in time
                if ($currentTime > $branchCheckInTime) {
                    $isLateCheckIn = 1; // Mark 1 if late
                }
            } else {
                error_log("No branch found for branchID $this->branchID");
                echo json_encode(array(
                    "status" => "error", 
                    "message_text" => "No branch found for branchID $this->branchID",
                    "debug" => "Employee ID: $this->empID, BranchID: $this->branchID, OrganisationID: $this->organisationID"
                ), JSON_FORCE_OBJECT);
                return;
            }
            
            $date = date('Y-m-d');
            // No existing attendance, create new record with late check-in status
            $branchIDValue = $this->branchID ? "'$this->branchID'" : 'NULL';
            $queryCheckIn = "INSERT INTO tblAttendance (employeeID, attendanceDate, checkInTime, isLateCheckIn, checkInBranchID) 
                           VALUES ('$this->empID', '$date', CURRENT_TIME(), '$isLateCheckIn', $branchIDValue)";
            $rsd = mysqli_query($connect_var, $queryCheckIn);
            
            if(!$rsd){
                error_log("Check-in failed for employee $this->empID: " . mysqli_error($connect_var));
                error_log("isLateCheckIn: $isLateCheckIn");
                mysqli_close($connect_var);
                echo json_encode(array(
                    "status" => "error", 
                    "message_text" => "Error during check-in: " . mysqli_error($connect_var),
                    "debug" => array(
                        "employeeID" => $this->empID,
                        "isLateCheckIn" => $isLateCheckIn,
                        "date" => $date,
                        "query" => $queryCheckIn
                    )
                ), JSON_FORCE_OBJECT);
                return;
            }
            
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
            error_log("Error in checkInOnGivenDate: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode(array(
                "status" => "error", 
                "message_text" => "Error Checking In: " . $e->getMessage(),
                "debug" => array(
                    "employeeID" => $this->empID,
                    "branchID" => $this->branchID,
                    "organisationID" => $this->organisationID
                )
            ), JSON_FORCE_OBJECT);
        }
    }
    public function checkOutOnGivenDate(){
        include('config.inc');
        header('Content-Type: application/json');
        try{
            $currentTime = date('H:i:s');
            $currentDate = date('Y-m-d');
            
            // Get branch check-out time to determine early checkout
            $branchQuery = "SELECT b.checkOutTime FROM tblBranch b 
                           WHERE b.branchID = '$this->branchID' LIMIT 1";
            $branchResult = mysqli_query($connect_var, $branchQuery);
            $isEarlyCheckOut = 0; // Default to not early
            
            if ($branchResult && mysqli_num_rows($branchResult) > 0) {
                // Get branch details and check-out time
                $branchRow = mysqli_fetch_assoc($branchResult);
                $branchCheckOutTime = $branchRow['checkOutTime']; // Get branch's specific check-out time
                
                // Debug logging
                error_log("Employee $this->empID - BranchID: $this->branchID, BranchCheckOutTime: $branchCheckOutTime, CurrentTime: $currentTime");
                
                // Check if employee is checking out early based on their branch's check-out time
                if ($currentTime < $branchCheckOutTime) {
                    $isEarlyCheckOut = 1; // Mark 1 if early checkout
                }
            } else {
                error_log("No branch found for branchID $this->branchID");
                echo json_encode(array(
                    "status" => "error", 
                    "message_text" => "No branch found for branchID $this->branchID",
                    "debug" => "Employee ID: $this->empID, BranchID: $this->branchID, OrganisationID: $this->organisationID"
                ), JSON_FORCE_OBJECT);
                return;
            }
            
            // Update checkout time, calculate total working hours, and set early checkout flag
            $branchIDValue = $this->branchID ? "'$this->branchID'" : 'NULL';
            $queryCheckOut = "UPDATE tblAttendance 
                            SET checkOutTime = CURRENT_TIME(),
                                TotalWorkingHour = TIMEDIFF(CURRENT_TIME(), checkInTime),
                                isEarlyCheckOut = '$isEarlyCheckOut',
                                checkOutBranchID = $branchIDValue
                            WHERE employeeID = '$this->empID' 
                            AND checkOutTime IS NULL 
                            ORDER BY checkInTime DESC 
                            LIMIT 1";
            
            $rsd = mysqli_query($connect_var,$queryCheckOut);
            
            // Check if any row was actually updated
            if (mysqli_affected_rows($connect_var) == 0) {
                echo json_encode(array("status"=>"error","message_text"=>"No active check-in found"),JSON_FORCE_OBJECT);
                return;
            }
            
            // Get the updated record details
            $getUpdatedValues = "SELECT attendanceDate, checkInTime, checkOutTime, TotalWorkingHour, checkOutBranchID 
                                FROM tblAttendance 
                                WHERE employeeID = '$this->empID'
                                ORDER BY attendanceDate DESC, checkOutTime DESC
                                LIMIT 1";
            $result = mysqli_query($connect_var, $getUpdatedValues);
            $row = mysqli_fetch_assoc($result);
            
            mysqli_close($connect_var);
            echo json_encode(array(
                "status"=>"success",
                "message_text"=>"CheckOut Successfully",
                "data" => array(
                    "attendanceDate" => $row['attendanceDate'],
                    "checkInTime" => $row['checkInTime'],
                    "checkOutTime" => $row['checkOutTime'],
                    "totalWorkingHour" => $row['TotalWorkingHour'],
                    "isEarlyCheckOut" => $isEarlyCheckOut,
                    "checkOutBranchID" => $row['checkOutBranchID']
                )
            ),JSON_FORCE_OBJECT);
        }
        catch(Exception $e){
            echo json_encode(array("status"=>"error","message_text"=>"Error Checking Out: " . $e->getMessage()),JSON_FORCE_OBJECT);
        }
    }
    public function cancelLeaveOnGivenDate(){
        include('config.inc');
        header('Content-Type: application/json');
        try{
            // First, get the leave details to check the dates and employee
            $queryGetLeave = "SELECT employeeID, fromDate, toDate, status FROM tblApplyLeave WHERE applyLeaveID = ?";
            $stmt = mysqli_prepare($connect_var, $queryGetLeave);
            mysqli_stmt_bind_param($stmt, "s", $this->applyLeaveID);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) == 0) {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Leave not found"
                ));
                mysqli_close($connect_var);
                return;
            }
            
            $leaveData = mysqli_fetch_assoc($result);
            $employeeID = $leaveData['employeeID'];
            $fromDate = $leaveData['fromDate'];
            $toDate = $leaveData['toDate'];
            $status = $leaveData['status'];
            
            // Only check attendance for approved leaves
            if ($status === 'Approved') {
                // Check if there are any attendance records (check-in time) for the leave period
                $queryCheckAttendance = "SELECT COUNT(*) as attendance_count 
                                       FROM tblAttendance 
                                       WHERE employeeID = ? 
                                       AND attendanceDate BETWEEN ? AND ? 
                                       AND checkInTime IS NOT NULL";
                
                $attendanceStmt = mysqli_prepare($connect_var, $queryCheckAttendance);
                mysqli_stmt_bind_param($attendanceStmt, "sss", $employeeID, $fromDate, $toDate);
                mysqli_stmt_execute($attendanceStmt);
                $attendanceResult = mysqli_stmt_get_result($attendanceStmt);
                $attendanceData = mysqli_fetch_assoc($attendanceResult);
                $attendanceCount = $attendanceData['attendance_count'];
                
                if ($attendanceCount > 0) {
                    // There are attendance records, allow cancellation
                    $queryCancelLeave = "UPDATE tblApplyLeave 
                        SET status = 'ReApplied'
                        WHERE applyLeaveID = ?";
                    
                    $cancelStmt = mysqli_prepare($connect_var, $queryCancelLeave);
                    mysqli_stmt_bind_param($cancelStmt, "s", $this->applyLeaveID);
                    mysqli_stmt_execute($cancelStmt);
                    
                    if (mysqli_affected_rows($connect_var) > 0) {
                        echo json_encode(array(
                            "status" => "success",
                            "message" => "Leave cancelled successfully. Attendance records found for the leave period."
                        ));
                    } else {
                        echo json_encode(array(
                            "status" => "error",
                            "message" => "Unable to cancel leave"
                        ));
                    }
                } else {
                    // No attendance records found, do not allow cancellation
                    echo json_encode(array(
                        "status" => "error",
                        "message" => "Cannot cancel leave.No attendance record(s) found for the leave period. You must have checked in on the day of the leave to cancel."
                    ));
                }
            } else {
                // For non-approved leaves, allow cancellation without attendance check
                $queryCancelLeave = "UPDATE tblApplyLeave 
                    SET status = 'Cancelled'
                    WHERE applyLeaveID = ?";
                
                $cancelStmt = mysqli_prepare($connect_var, $queryCancelLeave);
                mysqli_stmt_bind_param($cancelStmt, "s", $this->applyLeaveID);
                mysqli_stmt_execute($cancelStmt);
                
                if (mysqli_affected_rows($connect_var) > 0) {
                    echo json_encode(array(
                        "status" => "success",
                        "message" => "Leave cancelled successfully"
                    ));
                } else {
                    echo json_encode(array(
                        "status" => "error",
                        "message" => "Unable to cancel leave"
                    ));
                }
            }
            
            mysqli_close($connect_var);
        }
        catch(Exception $e){
            echo json_encode(array("status"=>"error","message_text"=>"Error Cancelling Leave: " . $e->getMessage()),JSON_FORCE_OBJECT);
        }
    }
    public function autoCheckoutProcess() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $cutoffTime = '23:59:59'; // End of day cutoff
            $currentDate = $this->dateOfCheckout;
            
            // Update auto checkout Where Check In Happened and not Checkec Out
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

            // First check if it's a holiday
            $holidayQuery = "SELECT 1 FROM tblHoliday WHERE date = ?";
            $holidayStmt = mysqli_prepare($connect_var, $holidayQuery);
            mysqli_stmt_bind_param($holidayStmt, "s", $currentDate);
            mysqli_stmt_execute($holidayStmt);
            $holidayResult = mysqli_stmt_get_result($holidayStmt);
            
            if (mysqli_num_rows($holidayResult) > 0) {
                mysqli_close($connect_var);
                echo json_encode(array(
                    "status" => "success",
                    "message_text" => "Skipped auto checkout for holiday on $currentDate"
                ), JSON_FORCE_OBJECT);
                return;
            }

            // Check for approved leaves
            $leaveQuery = "SELECT 1 FROM tblApplyLeave 
                          WHERE fromDate <= ? AND toDate >= ? 
                          AND status = 'Approved'";
            $leaveStmt = mysqli_prepare($connect_var, $leaveQuery);
            mysqli_stmt_bind_param($leaveStmt, "ss", $currentDate, $currentDate);
            mysqli_stmt_execute($leaveStmt);
            $leaveResult = mysqli_stmt_get_result($leaveStmt);
            
            if (mysqli_num_rows($leaveResult) > 0) {
                mysqli_close($connect_var);
                echo json_encode(array(
                    "status" => "success",
                    "message_text" => "Skipped auto checkout for approved leave on $currentDate"
                ), JSON_FORCE_OBJECT);
                return;
            }

            // Check for absences (no check-in record for the day)
            $absenceQuery = "SELECT e.employeeID 
                           FROM tblEmployee e 
                           LEFT JOIN tblAttendance a ON e.employeeID = a.employeeID 
                           AND a.attendanceDate = ? 
                           WHERE a.attendanceID IS NULL 
                           AND e.isActive = 1";
            $absenceStmt = mysqli_prepare($connect_var, $absenceQuery);
            mysqli_stmt_bind_param($absenceStmt, "s", $currentDate);
            mysqli_stmt_execute($absenceStmt);
            $absenceResult = mysqli_stmt_get_result($absenceStmt);
            
            if (mysqli_num_rows($absenceResult) > 0) {
                // Insert absence records
                $insertAbsenceQuery = "INSERT INTO tblAttendance 
                                     (employeeID, attendanceDate, isAbsent, organisationID) 
                                     SELECT e.employeeID, ?, 1, m.organisationID
                                     FROM tblEmployee e
                                     JOIN tblmapEmp m ON e.employeeID = m.employeeID
                                     WHERE e.isActive = 1 
                                     AND e.employeeID NOT IN (
                                         SELECT employeeID 
                                         FROM tblAttendance 
                                         WHERE attendanceDate = ?
                                     )";
                $insertAbsenceStmt = mysqli_prepare($connect_var, $insertAbsenceQuery);
                mysqli_stmt_bind_param($insertAbsenceStmt, "ss", $currentDate, $currentDate);
                mysqli_stmt_execute($insertAbsenceStmt);
                
                mysqli_close($connect_var);
                echo json_encode(array(
                    "status" => "success",
                    "message_text" => "Marked absences for employees without check-in on $currentDate"
                ), JSON_FORCE_OBJECT);
                return;
            }
            
            // Update auto checkout only for working days with check-ins
            $updateAutoCheckout = "UPDATE tblAttendance
                                SET 
                                    checkOutTime = '23:59:39',
                                    TotalWorkingHour = TIMEDIFF('23:59:39', checkInTime),
                                    isAutoCheckout = 1
                                WHERE 
                                    attendanceDate = ?
                                    AND checkOutTime IS NULL
                                    AND checkInTime IS NOT NULL
                                    AND isAbsent = 0";
            
            $autoCheckoutStmt = mysqli_prepare($connect_var, $updateAutoCheckout);
            mysqli_stmt_bind_param($autoCheckoutStmt, "s", $currentDate);
            mysqli_stmt_execute($autoCheckoutStmt);

            // Update privilege leave 
            $privilegeQuery = "SELECT 
                                a.employeeID,
                                COUNT(*) as consecutive_days
                            FROM tblAttendance a
                            WHERE a.checkInTime IS NOT NULL
                            AND a.isPrivilegeCount != 1
                            AND a.isAbsent = 0
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
                                   AND isPrivilegeCount != 1
                                   AND isAbsent = 0";
                
                $attendanceStmt = mysqli_prepare($connect_var, $updateAttendance);
                mysqli_stmt_bind_param($attendanceStmt, "s", $employeeID);
                mysqli_stmt_execute($attendanceStmt);
            }

            // Check for employees with more than 2 auto checkouts in current month
            $currentMonth = date('Y-m', strtotime($currentDate));
            $autoCheckoutCountQuery = "SELECT 
                                        employeeID,
                                        COUNT(*) as autoCheckoutCount
                                    FROM tblAttendance 
                                    WHERE isAutoCheckout = 1 
                                    AND DATE_FORMAT(attendanceDate, '%Y-%m') = ?
                                    GROUP BY employeeID
                                    HAVING COUNT(*) > 2";

            $autoCheckoutCountStmt = mysqli_prepare($connect_var, $autoCheckoutCountQuery);
            mysqli_stmt_bind_param($autoCheckoutCountStmt, "s", $currentMonth);
            mysqli_stmt_execute($autoCheckoutCountStmt);
            $autoCheckoutCountResult = mysqli_stmt_get_result($autoCheckoutCountStmt);
            
            // Update isCheckInLocked for employees with more than 2 auto checkouts
            while ($autoCheckoutRow = mysqli_fetch_assoc($autoCheckoutCountResult)) {
                $employeeID = $autoCheckoutRow['employeeID'];
                $autoCheckoutCount = $autoCheckoutRow['autoCheckoutCount'];
                
                // Update isCheckInLocked field in tblEmployee
                $updateCheckInLocked = "UPDATE tblEmployee 
                                      SET isCheckInLocked = 1 
                                      WHERE employeeID = ?";
                
                $updateLockedStmt = mysqli_prepare($connect_var, $updateCheckInLocked);
                mysqli_stmt_bind_param($updateLockedStmt, "s", $employeeID);
                mysqli_stmt_execute($updateLockedStmt);
                mysqli_stmt_close($updateLockedStmt);
                
                error_log("Employee $employeeID locked due to $autoCheckoutCount auto checkouts in $currentMonth");
            }
            mysqli_stmt_close($autoCheckoutCountStmt);

            // Close all statements
            mysqli_stmt_close($autoCheckoutStmt);
            mysqli_stmt_close($holidayStmt);
            mysqli_stmt_close($leaveStmt);
            mysqli_stmt_close($absenceStmt);
            mysqli_stmt_close($privilegeStmt);
            mysqli_close($connect_var);
            
            echo json_encode(array(
                "status" => "success",
                "message_text" => "Attendance records processed for working day on $currentDate"
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
            error_log("Starting getEmployeeAttendanceHistory for employee: $employeeID, year: $getYear, month: $getMonth");
            
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
                isAbsent,
                checkInBranchID
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
                    CASE 
                        /* Previous logic:
                        WHEN a.employeeID IN (72, 73) AND a.checkInTime > '08:10:00' THEN 1
                        WHEN a.employeeID IN (27) AND a.checkInTime > '11:10:00' THEN 1
                        WHEN m.branchID IN (1, 52) AND a.checkInTime > '10:10:00' THEN 1
                        WHEN m.branchID BETWEEN 2 AND 51 AND a.checkInTime > '09:25:00' THEN 1
                        WHEN m.branchID in(55,56) AND a.checkInTime > '11:00:00' THEN 1
                        */
                        WHEN a.checkInBranchID IN (55, 56) THEN 0
                        WHEN a.checkInTime IS NOT NULL AND b.checkInTime IS NOT NULL AND a.checkInTime > b.checkInTime THEN 1
                        ELSE 0 
                    END as isLateCheckIn,
                    CASE 
/* Previous logic:
                        WHEN a.employeeID IN (72, 73) AND a.checkOutTime < '15:00:00' THEN 1
                        WHEN a.employeeID IN (27) AND a.checkOutTime < '18:00:00' THEN 1
                        WHEN m.branchID IN (1, 52) AND a.checkOutTime < '17:00:00' THEN 1
                        WHEN m.branchID BETWEEN 2 AND 51 AND a.checkOutTime < '16:30:00' THEN 1
                        WHEN m.branchID in(55,56) AND a.checkOutTime < '17:00:00' THEN 1*/
                        WHEN a.checkInBranchID IN (55, 56) THEN 0
                        WHEN a.checkOutTime IS NOT NULL AND b.checkOutTime IS NOT NULL AND a.checkOutTime < b.checkOutTime THEN 1
                        ELSE 0 
                    END as isEarlyCheckOut,
                    CASE WHEN h.date IS NOT NULL THEN 1 ELSE 0 END as isHoliday,
                    h.holiday as holidayDescription,
                    0 as isLeave,
                    0 as isAbsent,
                    a.checkInBranchID
                FROM tblAttendance a
                LEFT JOIN tblBranch b ON a.checkInBranchID = b.branchID
                LEFT JOIN tblHoliday h ON a.attendanceDate = h.date
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

                -- Holiday records (only if no actual attendance exists for that date)
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
                    0 as isAbsent,
                    NULL as checkInBranchID
                FROM tblHoliday h
                WHERE YEAR(h.date) = ?
                AND MONTH(h.date) = ?
                AND (YEAR(h.date) < YEAR(CURRENT_DATE) 
                     OR (YEAR(h.date) = YEAR(CURRENT_DATE) 
                         AND MONTH(h.date) < MONTH(CURRENT_DATE))
                     OR (YEAR(h.date) = YEAR(CURRENT_DATE) 
                         AND MONTH(h.date) = MONTH(CURRENT_DATE) 
                         AND h.date <= CURRENT_DATE))
                AND NOT EXISTS (
                    SELECT 1 FROM tblAttendance a 
                    WHERE a.employeeID = ? 
                    AND a.attendanceDate = h.date
                    AND a.checkInTime IS NOT NULL
                )
            ) combined
            ORDER BY attendanceDate DESC";
            
            error_log("Executing main attendance query");
            $stmt = mysqli_prepare($connect_var, $query);
            if (!$stmt) {
                throw new Exception("Failed to prepare main query: " . mysqli_error($connect_var));
            }
            
            mysqli_stmt_bind_param($stmt, "siiiii", 
                $employeeID,  // for attendance
                $getYear,     // for attendance
                $getMonth,    // for attendance
                $getYear,     // for holiday
                $getMonth,    // for holiday
                $employeeID   // for NOT EXISTS clause
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to execute main query: " . mysqli_stmt_error($stmt));
            }
            
            $result = mysqli_stmt_get_result($stmt);
            if (!$result) {
                throw new Exception("Failed to get main result: " . mysqli_error($connect_var));
            }
            
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
            
            error_log("Found " . count($attendanceRecords) . " attendance/holiday records");
            
            // Create a date-indexed array to handle duplicates and prioritize leave over holiday
            $dateIndexedRecords = [];
            
            // First, add all existing records (attendance and holiday)
            foreach ($attendanceRecords as $record) {
                $dateKey = $record['attendanceDate'];
                $dateIndexedRecords[$dateKey] = $record;
            }
            
            // Now fetch leave records and generate individual day records
            $leaveQuery = "SELECT fromDate, toDate, employeeID 
                          FROM tblApplyLeave 
                          WHERE employeeID = ? 
                          AND status = 'Approved'
                          AND (
                              (YEAR(fromDate) = ? AND MONTH(fromDate) = ?) OR
                              (YEAR(toDate) = ? AND MONTH(toDate) = ?) OR
                              (fromDate <= ? AND toDate >= ?)
                          )
                          AND (YEAR(fromDate) < YEAR(CURRENT_DATE) 
                               OR (YEAR(fromDate) = YEAR(CURRENT_DATE) 
                                   AND MONTH(fromDate) < MONTH(CURRENT_DATE))
                               OR (YEAR(fromDate) = YEAR(CURRENT_DATE) 
                                   AND MONTH(fromDate) = MONTH(CURRENT_DATE) 
                                   AND fromDate <= CURRENT_DATE))";
            
            // Create date strings for the month boundaries
            $monthStartDate = sprintf('%04d-%02d-01', $getYear, $getMonth);
            $monthEndDate = date('Y-m-t', strtotime($monthStartDate)); // Last day of month
            
            error_log("Executing leave query with month range: $monthStartDate to $monthEndDate");
            
            $leaveStmt = mysqli_prepare($connect_var, $leaveQuery);
            mysqli_stmt_bind_param($leaveStmt, "siiisss", 
                $employeeID, 
                $getYear, $getMonth,  // fromDate conditions
                $getYear, $getMonth,  // toDate conditions  
                $monthEndDate, $monthStartDate  // span conditions
            );
            
            if (!$leaveStmt) {
                throw new Exception("Failed to prepare leave query: " . mysqli_error($connect_var));
            }
            
            if (!mysqli_stmt_execute($leaveStmt)) {
                throw new Exception("Failed to execute leave query: " . mysqli_stmt_error($leaveStmt));
            }
            
            $leaveResult = mysqli_stmt_get_result($leaveStmt);
            
            if (!$leaveResult) {
                throw new Exception("Failed to get leave result: " . mysqli_error($connect_var));
            }
            
            $leaveRecordsFound = 0;
            
            // Generate individual day records for each leave period
            while ($leaveRow = mysqli_fetch_assoc($leaveResult)) {
                try {
                    $leaveRecordsFound++;
                    error_log("Processing leave record $leaveRecordsFound: fromDate=" . $leaveRow['fromDate'] . ", toDate=" . $leaveRow['toDate']);
                    
                    $fromDate = new DateTime($leaveRow['fromDate']);
                    $toDate = new DateTime($leaveRow['toDate']);
                    
                    // Generate records for each day from fromDate to toDate
                    $currentDate = clone $fromDate;
                    $daysAdded = 0;
                    
                    while ($currentDate <= $toDate) {
                        $currentDateStr = $currentDate->format('Y-m-d');
                        
                        // Only add if it's in the requested month/year
                        if ($currentDate->format('Y') == $getYear && $currentDate->format('n') == $getMonth) {
                            $leaveRecord = [
                                'attendanceDate' => $currentDateStr,
                                'attendanceID' => NULL,
                                'employeeID' => $leaveRow['employeeID'],
                                'checkInTime' => NULL,
                                'checkOutTime' => NULL,
                                'TotalWorkingHour' => NULL,
                                'isAutoCheckout' => NULL,
                                'isLateCheckIn' => 0,
                                'isEarlyCheckOut' => 0,
                                'isHoliday' => 0,
                                'holidayDescription' => NULL,
                                'isLeave' => 1,
                                'isAbsent' => 0,
                                'checkInBranchID' => NULL
                            ];
                            
                            // Always override existing records with leave records (leave takes priority)
                            $dateIndexedRecords[$currentDateStr] = $leaveRecord;
                            $daysAdded++;
                        }
                        
                        $currentDate->add(new DateInterval('P1D')); // Add 1 day
                    }
                    
                    error_log("Added $daysAdded leave days for this leave period");
                    
                } catch (Exception $dateException) {
                    error_log("Error processing leave dates: " . $dateException->getMessage());
                    // Continue with other records
                }
            }
            
            error_log("Found $leaveRecordsFound leave records");
            
            // Convert back to indexed array
            $attendanceRecords = array_values($dateIndexedRecords);
            
            mysqli_close($connect_var);
            
            // Sort records by date (newest first)
            usort($attendanceRecords, function($a, $b) {
                return strcmp($b['attendanceDate'], $a['attendanceDate']);
            });
            
            error_log("Total records after processing: " . count($attendanceRecords));
            
            // Recalculate counts after deduplication
            $lateCheckInCount = 0;
            $earlyCheckOutCount = 0;
            $leaveCount = 0;
            $absentCount = 0;
            
            error_log("Starting count calculation for " . count($attendanceRecords) . " records");
            
            foreach ($attendanceRecords as $record) {
                // Only count if it's a working day (not a holiday)
                if($record['isHoliday'] != 1) {
                    error_log("Processing record for date: " . $record['attendanceDate'] . 
                             " - isLateCheckIn: " . $record['isLateCheckIn'] . 
                             " - isEarlyCheckOut: " . $record['isEarlyCheckOut'] . 
                             " - isLeave: " . $record['isLeave'] . 
                             " - isAbsent: " . $record['isAbsent']);
                    
                    if($record['isLateCheckIn'] == 1) {
                        $lateCheckInCount++;
                        error_log("Incremented lateCheckInCount to: " . $lateCheckInCount);
                    }
                    if($record['isEarlyCheckOut'] == 1) {
                        $earlyCheckOutCount++;
                        error_log("Incremented earlyCheckOutCount to: " . $earlyCheckOutCount);
                    }
                    if($record['isLeave'] == 1) {
                        $leaveCount++;
                        error_log("Incremented leaveCount to: " . $leaveCount);
                    }
                    if($record['isAbsent'] == 1) {
                        $absentCount++;
                        error_log("Incremented absentCount to: " . $absentCount);
                    }
                } else {
                    error_log("Skipping holiday record for date: " . $record['attendanceDate']);
                }
            }
            
            error_log("Final counts - lateCheckIn: $lateCheckInCount, earlyCheckOut: $earlyCheckOutCount, leave: $leaveCount, absent: $absentCount");
            
            $response = array(
                "status" => "success",
                "data" => $attendanceRecords,
                "counts" => array(
                    "lateCheckIn" => $lateCheckInCount,
                    "earlyCheckOut" => $earlyCheckOutCount,
                    "leave" => $leaveCount,
                    "absent" => $absentCount
                )
            );
            
            error_log("Successfully returning response with " . count($attendanceRecords) . " records");
            echo json_encode($response);
            exit;
        } catch(Exception $e) {
            error_log("Error in getEmployeeAttendanceHistory: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
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

    public function getTodayCheckIn($employeeID, $organisationID) {
        include('config.inc');
        header('Content-Type: application/json');
        
        try {
            $query = "SELECT 
                        m.branchID as branchID,
                        b.checkInTime as branchCheckInTime,
                        b.branchLatitude as branchLatitude,
                        b.branchLongitude as branchLongitude,
                        b.branchName as branchName,
                        b.branchAddress as branchAddress,
                        b.checkInTime as checkInTime,
                        b.checkOutTime as checkOutTime,
                        tblE.isCheckInLocked as checkINLocked
                    FROM tblmapEmp m
                    INNER JOIN tblEmployee tblE on tblE.employeeID = m.employeeID
                    INNER JOIN tblBranch b ON m.branchID = b.branchID
                    WHERE m.employeeID = ?
                    AND m.organisationID = ?
                    LIMIT 1";
            
            $stmt = mysqli_prepare($connect_var, $query);
            if (!$stmt) {
                throw new Exception("Failed to prepare query: " . mysqli_error($connect_var));
            }
            
            mysqli_stmt_bind_param($stmt, "ss", $employeeID, $organisationID);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to execute query: " . mysqli_stmt_error($stmt));
            }
            
            $result = mysqli_stmt_get_result($stmt);
            if (!$result) {
                throw new Exception("Failed to get result: " . mysqli_error($connect_var));
            }
            
            if (mysqli_num_rows($result) == 0) {
                echo json_encode(array(
                    "status" => "error",
                    "message_text" => "No branch information found for this employee and organisation"
                ), JSON_FORCE_OBJECT);
                return;
            }
            
            $row = mysqli_fetch_assoc($result);
            $row['checkinBeyondTime'] = false;

            $queryForTodayAttendance = "SELECT COUNT(*) as todayAttendanceCount
                                        FROM tblAttendance 
                                        WHERE employeeID = ? 
                                        AND attendanceDate = CURDATE();";
            $stmtForTodayAttendance = mysqli_prepare($connect_var, $queryForTodayAttendance);
            mysqli_stmt_bind_param($stmtForTodayAttendance, "s", $employeeID);
            mysqli_stmt_execute($stmtForTodayAttendance);
            $resultForTodayAttendance = mysqli_stmt_get_result($stmtForTodayAttendance);
            $rowForTodayAttendance = mysqli_fetch_assoc($resultForTodayAttendance);
            $todayAttendanceCount = $rowForTodayAttendance['todayAttendanceCount'];
            echo $todayAttendanceCount;
            if($todayAttendanceCount > 0) {
                // Compare current system time with branch check-in time
                $currentTime = date('H:i:s');
                if($currentTime > $row['branchCheckInTime']) {
                    $row['checkinBeyondTime'] = true;
                } else {
                    $row['checkinBeyondTime'] = false;
                }
            }
            
            $response = array(
                "status" => "success",
                "data" => $row
            );
            
            mysqli_close($connect_var);
            echo json_encode($response, JSON_FORCE_OBJECT);
            
        } catch(Exception $e) {
            error_log("Error in getTodayCheckIn: " . $e->getMessage());
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Error retrieving check-in information: " . $e->getMessage()
            ), JSON_FORCE_OBJECT);
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
    $loadResult = $attendanceOperationObject->loadCheckIn($decoded_items);
    
    if($loadResult){
        $attendanceOperationObject->checkInOnGivenDate();
    }
    else{
        echo json_encode(array(
            "status" => "error",
            "message_text" => "Employee not found or not mapped to any branch. Please provide valid employeeID."
        ), JSON_FORCE_OBJECT);
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

function getTodayCheckIn($decoded_items) {
    try {
        if (isset($decoded_items['employeeID'])) {
            $attendanceOperationObject = new AttendanceOperationMaster();
            $organisationID = 1;
            if(isset($decoded_items['organisationID'])) {
                $organisationID = $decoded_items['organisationID'];
            }
            $attendanceOperationObject->getTodayCheckIn($decoded_items['employeeID'], $organisationID);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Missing required parameters (employeeID, organisationID)"
            ), JSON_FORCE_OBJECT);
        }
    } catch(Exception $e) {
        echo json_encode(array(
            "status" => "error",
            "message_text" => $e->getMessage()
        ), JSON_FORCE_OBJECT);
    }
}
?>
