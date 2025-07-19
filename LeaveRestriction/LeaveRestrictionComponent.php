<?php 
class LeaveRestriction{
    public $restrictLeaveID;
    public $restrictLeaveDate;
    public $Reason;
    public $createdOn;
    public $createdBy;
    public $isPublish;

    public function loadLeaveRestriction(array $data){
        if(isset($data['restrictLeaveID'])){
            $this->restrictLeaveID = $data['restrictLeaveID'];
        }
        if(isset($data['restrictLeaveDate'])){
            $this->restrictLeaveDate = $data['restrictLeaveDate'];
        }
        if(isset($data['Reason'])){
            $this->Reason = $data['Reason'];
        }
        if(isset($data['createdBy'])){
            $this->createdBy = $data['createdBy'];
        }
        if(isset($data['isPublish'])){
            $this->isPublish = $data['isPublish'];
        }
        return true;
    }

    public function createRestrictedLeave(){
        include('config.inc');
        header('Content-Type: application/json');
        error_reporting( E_ALL );
        ini_set('display_errors', 1);
        
        try {
            $queryCreate = "INSERT INTO tblRestrictLeave (restrictLeaveDate, Reason, createdBy, isPublish) 
                           VALUES (?, ?, ?, ?)";
            
            $stmt = mysqli_prepare($connect_var, $queryCreate);
            
            $isPublish = isset($this->isPublish) ? $this->isPublish : 0;
            $createdBy = isset($this->createdBy) ? $this->createdBy : 0;
            
            mysqli_stmt_bind_param($stmt, "ssii", $this->restrictLeaveDate, $this->Reason, $createdBy, $isPublish);
            
            $result = mysqli_stmt_execute($stmt);
            
            if($result){
                $restrictLeaveID = mysqli_insert_id($connect_var);
                echo json_encode(array("status"=>"success","message_text"=>"Restricted leave created successfully","restrictLeaveID"=>$restrictLeaveID));
            } else {
                echo json_encode(array("status"=>"failure","message_text"=>"Failed to create restricted leave"));
            }
            
            mysqli_close($connect_var);
        }   
        catch(Exception $e) {
            echo json_encode(array("status"=>"error","message_text"=>$e->getMessage()),JSON_FORCE_OBJECT);
        }
    }

    public function getAllRestrictedLeaves(){
        include('config.inc');
        header('Content-Type: application/json');
        error_reporting( E_ALL );
        ini_set('display_errors', 1);
        
        try {
            $querySelect = "SELECT restrictLeaveID, restrictLeaveDate, Reason, createdOn, createdBy, isPublish 
                           FROM tblRestrictLeave 
                           ORDER BY createdOn DESC";
            
            $stmt = mysqli_prepare($connect_var, $querySelect);
            
            mysqli_stmt_execute($stmt);
            
            $result = mysqli_stmt_get_result($stmt);
            
            $resultArr = array();
            $count = 0;
            
            while($rs = mysqli_fetch_assoc($result)){
                $resultArr[] = array(
                    'restrictLeaveID' => $rs['restrictLeaveID'],
                    'restrictLeaveDate' => $rs['restrictLeaveDate'],
                    'Reason' => $rs['Reason'],
                    'createdOn' => $rs['createdOn'],
                    'isPublish' => $rs['isPublish']
                );
                $count++;
            }
            
            mysqli_close($connect_var);
            
            if($count > 0){
                echo json_encode(array("status"=>"success","record_count"=>$count,"result"=>$resultArr));
            } else {
                echo json_encode(array("status"=>"success","record_count"=>$count,"result"=>array(),"message_text"=>"No restricted leaves found"));
            }
        }   
        catch(Exception $e) {
            echo json_encode(array("status"=>"error","message_text"=>$e->getMessage()),JSON_FORCE_OBJECT);
        }
    }

    public function getAllPublishedRestrictedLeave(){
        include('config.inc');
        header('Content-Type: application/json');
        error_reporting( E_ALL );
        ini_set('display_errors', 1);
        
        try {
            $querySelect = "SELECT restrictLeaveID, restrictLeaveDate, Reason, createdOn, createdBy, isPublish 
                           FROM tblRestrictLeave 
                           WHERE isPublish = 1 
                           ORDER BY createdOn DESC";
            
            $stmt = mysqli_prepare($connect_var, $querySelect);
            
            mysqli_stmt_execute($stmt);
            
            $result = mysqli_stmt_get_result($stmt);
            
            $resultArr = array();
            $count = 0;
            
            while($rs = mysqli_fetch_assoc($result)){
                $resultArr[] = array(
                    'restrictLeaveID' => $rs['restrictLeaveID'],
                    'restrictLeaveDate' => $rs['restrictLeaveDate'],
                    'Reason' => $rs['Reason'],
                    'createdOn' => $rs['createdOn'],
                    'createdBy' => $rs['createdBy'],
                    'isPublish' => $rs['isPublish']
                );
                $count++;
            }
            
            mysqli_close($connect_var);
            
            if($count > 0){
                echo json_encode(array("status"=>"success","record_count"=>$count,"result"=>$resultArr));
            } else {
                echo json_encode(array("status"=>"success","record_count"=>$count,"result"=>array(),"message_text"=>"No published restricted leaves found"));
            }
        }   
        catch(Exception $e) {
            echo json_encode(array("status"=>"error","message_text"=>$e->getMessage()),JSON_FORCE_OBJECT);
        }
    }

    public function updateLeaveRestriction(){
        include('config.inc');
        header('Content-Type: application/json');
        error_reporting( E_ALL );
        ini_set('display_errors', 1);
        
        try {
            $queryUpdate = "UPDATE tblRestrictLeave SET restrictLeaveDate = ?, Reason = ?, isPublish = ? 
                           WHERE restrictLeaveID = ?";
            
            $stmt = mysqli_prepare($connect_var, $queryUpdate);
            
            $isPublish = isset($this->isPublish) ? $this->isPublish : 0;
            
            mysqli_stmt_bind_param($stmt, "ssii", $this->restrictLeaveDate, $this->Reason, $isPublish, $this->restrictLeaveID);
            
            $result = mysqli_stmt_execute($stmt);
            
            if($result && mysqli_affected_rows($connect_var) > 0){
                echo json_encode(array("status"=>"success","message_text"=>"Restricted leave updated successfully"));
            } else {
                echo json_encode(array("status"=>"failure","message_text"=>"Failed to update restricted leave or record not found"));
            }
            
            mysqli_close($connect_var);
        }   
        catch(Exception $e) {
            echo json_encode(array("status"=>"error","message_text"=>$e->getMessage()),JSON_FORCE_OBJECT);
        }
    }

    public function deleteLeaveRestriction(){
        include('config.inc');
        header('Content-Type: application/json');
        error_reporting( E_ALL );
        ini_set('display_errors', 1);
        
        try {
            $queryDelete = "DELETE FROM tblRestrictLeave WHERE restrictLeaveID = ?";
            
            $stmt = mysqli_prepare($connect_var, $queryDelete);
            
            mysqli_stmt_bind_param($stmt, "i", $this->restrictLeaveID);
            
            $result = mysqli_stmt_execute($stmt);
            
            if($result && mysqli_affected_rows($connect_var) > 0){
                echo json_encode(array("status"=>"success","message_text"=>"Restricted leave deleted successfully"));
            } else {
                echo json_encode(array("status"=>"failure","message_text"=>"Failed to delete restricted leave or record not found"));
            }
            
            mysqli_close($connect_var);
        }   
        catch(Exception $e) {
            echo json_encode(array("status"=>"error","message_text"=>$e->getMessage()),JSON_FORCE_OBJECT);
        }
    }

    public function publishRestrictedLeave(){
        include('config.inc');
        header('Content-Type: application/json');
        error_reporting( E_ALL );
        ini_set('display_errors', 1);
        
        try {
            $queryUpdate = "UPDATE tblRestrictLeave SET isPublish = 1 
                           WHERE restrictLeaveID = ?";
            
            $stmt = mysqli_prepare($connect_var, $queryUpdate);
            
            mysqli_stmt_bind_param($stmt, "i", $this->restrictLeaveID);
            
            $result = mysqli_stmt_execute($stmt);
            
            if($result && mysqli_affected_rows($connect_var) > 0){
                echo json_encode(array("status"=>"success","message_text"=>"Restricted leave published successfully"));
            } else {
                echo json_encode(array("status"=>"failure","message_text"=>"Failed to publish restricted leave or record not found"));
            }
            
            mysqli_close($connect_var);
        }   
        catch(Exception $e) {
            echo json_encode(array("status"=>"error","message_text"=>$e->getMessage()),JSON_FORCE_OBJECT);
        }
    }

    public function unPublishRestrictedLeave(){
        include('config.inc');
        header('Content-Type: application/json');
        error_reporting( E_ALL );
        ini_set('display_errors', 1);
        
        try {
            $queryUpdate = "UPDATE tblRestrictLeave SET isPublish = 0 
                           WHERE restrictLeaveID = ?";
            
            $stmt = mysqli_prepare($connect_var, $queryUpdate);
            
            mysqli_stmt_bind_param($stmt, "i", $this->restrictLeaveID);
            
            $result = mysqli_stmt_execute($stmt);
            
            if($result && mysqli_affected_rows($connect_var) > 0){
                echo json_encode(array("status"=>"success","message_text"=>"Restricted leave unpublished successfully"));
            } else {
                echo json_encode(array("status"=>"failure","message_text"=>"Failed to unpublish restricted leave or record not found"));
            }
            
            mysqli_close($connect_var);
        }   
        catch(Exception $e) {
            echo json_encode(array("status"=>"error","message_text"=>$e->getMessage()),JSON_FORCE_OBJECT);
        }
    }
}

// Create Restricted Leave
function createRestrictedLeaveTemp(array $data){
    $leaveRestrictionObject = new LeaveRestriction;
    if($leaveRestrictionObject->loadLeaveRestriction($data)){
        $leaveRestrictionObject->createRestrictedLeave();
    }
    else {
         echo json_encode(array("status"=>"error On Create Restricted Leave","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }
}

// Get All Restricted Leaves
function getAllRestrictedLeavesTemp(array $data){
    $leaveRestrictionObject = new LeaveRestriction;
    // For fetching all records, we don't need to validate parameters
    $leaveRestrictionObject->getAllRestrictedLeaves();
}

// Get All Published Restricted Leave
function getAllPublishedRestrictedLeaveTemp(array $data){
    $leaveRestrictionObject = new LeaveRestriction;
    // For fetching all published records, we don't need to validate parameters
    $leaveRestrictionObject->getAllPublishedRestrictedLeave();
}

// Update Restricted Leave
function updateLeaveRestrictionTemp(array $data){
    $leaveRestrictionObject = new LeaveRestriction;
    if($leaveRestrictionObject->loadLeaveRestriction($data)){
        $leaveRestrictionObject->updateLeaveRestriction();
    }
    else {
         echo json_encode(array("status"=>"error On Update Restricted Leave","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }
}

// Delete Restricted Leave
function deleteLeaveRestrictionTemp(array $data){
    $leaveRestrictionObject = new LeaveRestriction;
    if($leaveRestrictionObject->loadLeaveRestriction($data)){
        $leaveRestrictionObject->deleteLeaveRestriction();
    }
    else {
         echo json_encode(array("status"=>"error On Delete Restricted Leave","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }
}

// Publish Restricted Leave
function publishRestrictedLeaveTemp(array $data){
    $leaveRestrictionObject = new LeaveRestriction;
    if($leaveRestrictionObject->loadLeaveRestriction($data)){
        $leaveRestrictionObject->publishRestrictedLeave();
    }
    else {
         echo json_encode(array("status"=>"error On Publish Restricted Leave","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }
}

// Unpublish Restricted Leave
function unPublishRestrictedLeaveTemp(array $data){
    $leaveRestrictionObject = new LeaveRestriction;
    if($leaveRestrictionObject->loadLeaveRestriction($data)){
        $leaveRestrictionObject->unPublishRestrictedLeave();
    }
    else {
         echo json_encode(array("status"=>"error On Unpublish Restricted Leave","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }
}

?>
