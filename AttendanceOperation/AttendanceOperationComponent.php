<?php
class AttendanceOperationMaster{
    public $applyLeaveID;
    public $status; 

    public function loadCancelLeave($decoded_items){
        $this->applyLeaveID = $decoded_items['applyLeaveID'];
        return true;
    }
    public function cancelLeaveOnGivenDate(){
        include('config.inc');
        header('Content-Type: application/json');
        try{
            $queryCancelLeave = "Update tblApplyLeave set status = 'Cancelled' where applyLeaveID = '$this->applyLeaveID'";
            $rsd = mysqli_query($connect_var,$queryCancelLeave);
            echo $queryCancelLeave;
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
?>