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

    // public function checkInOnGivenDate(){
    //     include('config.inc');  
    //     // header('Content-Type: application/json');
    //     // try{
    //     //     $currentDate = date('Y-m-d');
    //     //     $currentTime = date('H:i:s');
    //     //     $stmt = mysqli_prepare($connect_var, "INSERT INTO tblAttendance (employeeID, attendanceDate, checkInTime, status) VALUES (?, ?, ?, ?)");
    //     //     mysqli_stmt_bind_param($stmt, "ssss", $this->empID, $currentDate, $currentTime, 'Present');
    //     //     $rsd = mysqli_stmt_execute($stmt);
    //     //     mysqli_stmt_close($stmt);
    //     //     mysqli_close($connect_var);
    //     //     echo json_encode(array("status"=>"success","message_text"=>"CheckIn Successfully"),JSON_FORCE_OBJECT);
    //     // }
    //     // catch(Exception $e){
    //     //     echo json_encode(array("status"=>"error","message_text"=>"Error Checking In"),JSON_FORCE_OBJECT);
    //     }
    // }

    public function checkInOnGivenDate(){
        include('config.inc');  
        header('Content-Type: application/json');
        try{
        $currentDate = date('Y-m-d');
        $currentTime = date('H:i:s');
        $queryCheckIn = "INSERT INTO tblAttendance (employeeID, attendanceDate, checkInTime) VALUES ('$this->empID', '$currentDate', '$currentTime')";
        echo $queryCheckIn;
        $rsd = mysqli_query($connect_var,$queryCheckIn);
        
        // $rsd = mysqli_stmt_execute($stmt);
        // mysqli_stmt_close($stmt);
        mysqli_close($connect_var);
        echo json_encode(array("status"=>"success","message_text"=>"CheckIn Successfully"),JSON_FORCE_OBJECT);
        }
        catch(Exception $e){
            echo json_encode(array("status"=>"error","message_text"=>"Error Checking In"),JSON_FORCE_OBJECT);
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
?>