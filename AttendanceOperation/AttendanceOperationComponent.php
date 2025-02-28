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
            $queryCancelLeave = "Update tblApplyLeave set status = 'Cancelled' where applyLeaveID = '$this->applyLeaveID'";
            $rsd = mysqli_query($connect_var,$queryCancelLeave);
            mysqli_close($connect_var);
            echo json_encode(array("status"=>"success","message_text"=>"Leave Cancelled Successfully"),JSON_FORCE_OBJECT);

        }
        catch(Exception $e){
            echo json_encode(array("status"=>"error","message_text"=>"Error Cancelling Leave"),JSON_FORCE_OBJECT);
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
?>