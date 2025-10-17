<?php 
class LeaveRestriction{
    public $updateLeaveRestrictionData;
    public $getAllRestrictedLeavesData;
    public $createRestrictedLeaveData;
    public $publishRestrictedLeaveData;
    public $unPublishRestrictedLeaveData;

    public function createRestrictedLeave(){
        include('config.inc');
        header('Content-Type: application/json');
        error_reporting( E_ALL );
        ini_set('display_errors', 1);
        
        try {
            //Decode Token Start
            $secratekey = "CreateRestrictedLeaveFromWeb";
            $decodeVal = decryptDataFunc($this->createRestrictedLeaveData['CreateRestrictedLeaveToken'], $secratekey);
            // DECODE Token End
            $queryCreate = "INSERT INTO tblRestrictLeave (restrictLeaveDate, Reason, createdBy, isPublish) 
                           VALUES (?, ?, ?, ?)";
            
            $stmt = mysqli_prepare($connect_var, $queryCreate);
            
            $isPublish = isset($decodeVal->isPublish) ? $decodeVal->isPublish : 0;
            $createdBy = isset($decodeVal->createdBy) ? $decodeVal->createdBy : 0;
            
            mysqli_stmt_bind_param($stmt, "ssii", $decodeVal->restrictLeaveDate, $decodeVal->Reason, $createdBy, $isPublish);
            
            $result = mysqli_stmt_execute($stmt);
            
            if($result){
                $restrictLeaveID = mysqli_insert_id($connect_var);
                $responseStatus = "success";
                $responseMessage = "Restricted leave created successfully";
                $responseRestrictLeaveID = $restrictLeaveID;
            } else {
                $responseStatus = "failure";
                $responseMessage = "Failed to create restricted leave";
                $responseRestrictLeaveID = 0;
            }
            $payload_info = array(
                "message_text"=> $responseMessage,
                "restrictLeaveID"=> $responseRestrictLeaveID,
                "status"=> $responseStatus
            );
            $encodeToken = encryptDataFunc($payload_info, $secratekey);
            echo json_encode(array("status"=>$responseStatus, "response"=>$encodeToken),JSON_FORCE_OBJECT);
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
            //Decode Token Start
            $secratekey = "GetAllRestrictedLeavesFromWeb";
            $decodeVal = decryptDataFunc($this->getAllRestrictedLeavesData['GetAllRestrictedLeavesToken'], $secratekey);
            // Validate decoded data
            if (!$decodeVal || !isset($decodeVal->organisationID)) {
                throw new Exception("Invalid or missing token data");
            }
            
            $querySelect = "SELECT restrictLeaveID, restrictLeaveDate, Reason, createdOn, createdBy, isPublish 
                           FROM tblRestrictLeave 
                           WHERE organisationID = ?
                           ORDER BY createdOn DESC";
            
            $stmt = mysqli_prepare($connect_var, $querySelect);
            if (!$stmt) {
                throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
            }
            
            mysqli_stmt_bind_param($stmt, "s", $decodeVal->organisationID);
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
                $responseStatus = "success";
                $responseMessage = "";
                $responseCount = $count;
                $responseData = $resultArr;
               
            } else {
                $responseStatus = "success";
                $responseMessage = "No restricted leaves found";
                $responseCount = $count;
                $responseData = array();
              
            }
            $payload_info = array(
                "message_text"=> $responseMessage,
                "record_count"=> $responseCount,
                "result"=> $responseData,
                "status"=> $responseStatus
            );
            //Encode Token End
            $encodeToken = encryptDataFunc($payload_info, $secratekey);
            echo json_encode(array("status"=>$responseStatus, "response"=>$encodeToken),JSON_FORCE_OBJECT);
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
            //Decode Token Start
            $secratekey = "UpdateRestrictedLeaveFromWeb";
            $decodeVal = decryptDataFunc($this->updateLeaveRestrictionData['UpdateRestrictedLeaveToken'], $secratekey);
            // DECODE Token End
            $queryUpdate = "UPDATE tblRestrictLeave SET restrictLeaveDate = ?, Reason = ?, isPublish = ? 
                           WHERE restrictLeaveID = ?";
            
            $stmt = mysqli_prepare($connect_var, $queryUpdate);
            
            $isPublish = isset($decodeVal->isPublish) ? $decodeVal->isPublish : 0;
            
            mysqli_stmt_bind_param($stmt, "ssii", $decodeVal->restrictLeaveDate, $decodeVal->Reason, $isPublish, $decodeVal->restrictLeaveID);
            
            $result = mysqli_stmt_execute($stmt);
            
            if($result && mysqli_affected_rows($connect_var) > 0){
                $responseStatus = "success";
                $responseMessage = "Restricted leave updated successfully";
            } else {
                $responseStatus = "failure";
                $responseMessage = "Failed to update restricted leave or record not found";
            }
            $payload_info = array(
                "message_text"=> $responseMessage,
                "status"=> $responseStatus
            );
            $encodeToken = encryptDataFunc($payload_info, $secratekey);
            echo json_encode(array("status"=>$responseStatus, "response"=>$encodeToken),JSON_FORCE_OBJECT);
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
            //Decode Token Start
            $secratekey = "PublishRestrictedLeaveFromWeb";
            $decodeVal = decryptDataFunc($this->publishRestrictedLeaveData['PublishRestrictedLeaveToken'], $secratekey);
            // DECODE Token End
            $queryUpdate = "UPDATE tblRestrictLeave SET isPublish = 1 
                           WHERE restrictLeaveID = ?";
            
            $stmt = mysqli_prepare($connect_var, $queryUpdate);
            
            mysqli_stmt_bind_param($stmt, "i", $decodeVal->restrictLeaveID);
            
            $result = mysqli_stmt_execute($stmt);
            
            if($result && mysqli_affected_rows($connect_var) > 0){
                $responseStatus = "success";
                $responseMessage = "Restricted leave published successfully";
            } else {
                $responseStatus = "failure";
                $responseMessage = "Failed to publish restricted leave or record not found";
            }
            $payload_info = array(
                "message_text"=> $responseMessage,
                "status"=> $responseStatus
            );
            $encodeToken = encryptDataFunc($payload_info, $secratekey);
            echo json_encode(array("status"=>$responseStatus, "response"=>$encodeToken),JSON_FORCE_OBJECT);
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
            //Decode Token Start
            $secratekey = "UnpublishRestrictedLeaveFromWeb";
            $decodeVal = decryptDataFunc($this->unPublishRestrictedLeaveData['UnpublishRestrictedLeaveToken'], $secratekey);
            // DECODE Token End
            $queryUpdate = "UPDATE tblRestrictLeave SET isPublish = 0 
                           WHERE restrictLeaveID = ?";
            
            $stmt = mysqli_prepare($connect_var, $queryUpdate);
            
            mysqli_stmt_bind_param($stmt, "i", $decodeVal->restrictLeaveID);
            
            $result = mysqli_stmt_execute($stmt);
            
            if($result && mysqli_affected_rows($connect_var) > 0){
                $responseStatus = "success";
                $responseMessage = "Restricted leave unpublished successfully";
             } else {
                $responseStatus = "failure";
                $responseMessage = "Failed to unpublish restricted leave or record not found";
            }
            $payload_info = array(
                "message_text"=> $responseMessage,
                "status"=> $responseStatus
            );
            $encodeToken = encryptDataFunc($payload_info, $secratekey);
            echo json_encode(array("status"=>$responseStatus, "response"=>$encodeToken),JSON_FORCE_OBJECT);
            mysqli_close($connect_var);
        }   
        catch(Exception $e) {
            echo json_encode(array("status"=>"error","message_text"=>$e->getMessage()),JSON_FORCE_OBJECT);
        }
    }
}

// Create Restricted Leave
function createRestrictedLeaveTemp($decoded_items){
    $leaveRestrictionObject = new LeaveRestriction;
    if($decoded_items) {
        $leaveRestrictionObject->createRestrictedLeaveData = $decoded_items;
        $leaveRestrictionObject->createRestrictedLeave();
    }
    else {
         echo json_encode(array("status"=>"error On Create Restricted Leave","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }
}

// Get All Restricted Leaves
function getAllRestrictedLeavesTemp($decoded_items){
    $leaveRestrictionObject = new LeaveRestriction;
    if($decoded_items) {
        $leaveRestrictionObject->getAllRestrictedLeavesData = $decoded_items;
        $leaveRestrictionObject->getAllRestrictedLeaves();
    }
    else {
         echo json_encode(array("status"=>"error On Get All Restricted Leaves","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }
    // For fetching all records, we don't need to validate parameters
   
}

// Update Restricted Leave
function updateLeaveRestrictionTemp($decoded_items){
    $leaveRestrictionObject = new LeaveRestriction;
    if($decoded_items) {
        $leaveRestrictionObject->updateLeaveRestrictionData = $decoded_items;
        $leaveRestrictionObject->updateLeaveRestriction();
    }
    else {
         echo json_encode(array("status"=>"error On Update Restricted Leave","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }
}


// Publish Restricted Leave
function publishRestrictedLeaveTemp($decoded_items){
    $leaveRestrictionObject = new LeaveRestriction;
    if($decoded_items) {
        $leaveRestrictionObject->publishRestrictedLeaveData = $decoded_items;
        $leaveRestrictionObject->publishRestrictedLeave();
    }
    else {
         echo json_encode(array("status"=>"error On Publish Restricted Leave","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }
}

// Unpublish Restricted Leave
function unPublishRestrictedLeaveTemp($decoded_items){
    $leaveRestrictionObject = new LeaveRestriction;
    if($decoded_items) {
        $leaveRestrictionObject->unPublishRestrictedLeaveData = $decoded_items;
        $leaveRestrictionObject->unPublishRestrictedLeave();
    } else {
         echo json_encode(array("status"=>"error On Unpublish Restricted Leave","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }
}

?>