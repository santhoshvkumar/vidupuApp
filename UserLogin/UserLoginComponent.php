<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_log("Request received: " . print_r($_POST, true));

class UserMaster{
    public $User_ID;
    public $UserName;
    public $UserPhoneNumber;
    public $CompanyID;
    public $DatabaseName;
    public $GeneralCompanyID;
    public $GeneralAdminID;
    public $UserToken;
    public $DeviceId;
    
    public function loadLoginUser(array $data){
        error_log("loadLoginUser called with: " . print_r($data, true));
        
        if (!isset($data['EmployeePhone']) || !isset($data['EmployeePassword'])) {
            error_log("Missing required fields");
            return false;
        }
        
        $this->UserName = $data['EmployeePhone'];
        $this->UserPassword = $data['EmployeePassword'];
        $this->UserToken = $data['UserToken'] ?? null;
        $this->DeviceId = $data['DeviceId'] ?? null;
        error_log("User credentials set - Phone: {$this->UserName}, Token: {$this->UserToken}, DeviceId: {$this->DeviceId}");
        return true;
    }

    public function LoginUserTempInfo(){
        include('config.inc');
        header('Content-Type: application/json');
        try
        {
            // First, update the user's token and device ID
            if($this->UserToken && $this->DeviceId) {
                $updateTokenQuery = "UPDATE tblEmployee SET 
                    userToken = ?, 
                    deviceId = ?,
                    lastTokenUpdate = NOW() 
                    WHERE employeePhone = ?";
                
                $stmt = $conn->prepare($updateTokenQuery);
                $stmt->bind_param("sss", $this->UserToken, $this->DeviceId, $this->UserName);
                $stmt->execute();
                error_log("Token update result: " . ($stmt->affected_rows > 0 ? "Success" : "Failed"));
            }

            // Modified query to include userToken and deviceId
            $queryUserLogin = "SELECT 
                tblE.employeeID, tblE.empID, tblE.employeeName, tblE.managerID, 
                tblE.employeePhoto, tblE.userToken, tblE.deviceId,
                tblLB.CasualLeave, tblLB.MedicalLeave, PrivilegeLeave, 
                tblLB.NoOfMaternityLeave, tblLB.SpecialCasualLeave, 
                tblLB.CompensatoryOff, tblLB.SpecialLeaveBloodDonation, 
                tblLB.LeaveOnPrivateAffairs, tblB.branchUniqueID, tblB.branchName, 
                tblB.branchAddress, tblB.branchLatitude, tblB.branchLongitude, 
                tblB.branchRadius, tblE.isManager
                FROM tblEmployee tblE 
                INNER JOIN tblLeaveBalance tblLB ON tblLB.employeeID = tblE.employeeID
                INNER JOIN tblmapEmp tblM ON tblM.employeeID = tblE.employeeID
                INNER JOIN tblBranch tblB ON tblB.branchID = tblM.branchID
                WHERE tblE.employeePhone = ? AND tblE.employeePassword = ?";
            
            error_log("Executing login query for user: " . $this->UserName);
            
            $stmt = $conn->prepare($queryUserLogin);
            $stmt->bind_param("ss", $this->UserName, $this->UserPassword);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $resultArr = Array();
            $count = 0;
            
            while($rs = mysqli_fetch_assoc($result)){
               if(isset($rs['empID'])){
                    $resultArr = array(
                        'employeeName' => $rs['employeeName'],
                        'employeeID' => $rs['employeeID'],
                        'employeePhoto' => $rs['employeePhoto'],
                        'managerID' => $rs['managerID'],
                        'userToken' => $rs['userToken'],
                        'deviceId' => $rs['deviceId'],
                        'causalLeave' => $rs['CasualLeave'],
                        'MedicalLeave' => $rs['MedicalLeave'],
                        'PrivilageLeave' => $rs['PrivilegeLeave'],
                        'NoOfMaternityLeave' => $rs['NoOfMaternityLeave'],
                        'SpecialCasualLeave' => $rs['SpecialCasualLeave'],
                        'CompensatoryOff' => $rs['CompensatoryOff'],
                        'SpecialLeaveBloodDonation' => $rs['SpecialLeaveBloodDonation'],
                        'LeaveOnPrivateAffairs' => $rs['LeaveOnPrivateAffairs'],
                        'TotalLeave' => $rs['CasualLeave'] + $rs['MedicalLeave'] + 
                            $rs['PrivilegeLeave'] + $rs['SpecialCasualLeave'] + 
                            $rs['CompensatoryOff'] + $rs['SpecialLeaveBloodDonation'] + 
                            $rs['LeaveOnPrivateAffairs'],
                        'branchUniqueID' => $rs['branchUniqueID'],
                        'branchName' => $rs['branchName'],
                        'branchAddress' => $rs['branchAddress'],
                        'branchLatitude' => $rs['branchLatitude'],
                        'branchLongitude' => $rs['branchLongitude'],
                        'branchRadius' => $rs['branchRadius'],
                        'IsManager' => $rs['isManager']
                    );
                    $count++;
               }  
            }
        
            mysqli_close($connect_var);

            if($count > 0) {
                echo json_encode(array(
                    "status" => "success",
                    "record_count" => $count,
                    "result" => $resultArr,
                    "token" => $resultArr['userToken'],
                    "deviceId" => $resultArr['deviceId']
                ));
            } else {
                echo json_encode(array(
                    "status" => "failure",
                    "record_count" => $count,
                    "message_text" => "No user with userPhoneNumber='$this->UserName'"
                ), JSON_FORCE_OBJECT);
            }
        }   
        catch(PDOException $e) {
            error_log("Exception in LoginUserTempInfo: " . $e->getMessage());
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ), JSON_FORCE_OBJECT);
        }
    }

}


function loginUserTemp(array $data){
    error_log("=== Login Attempt Start ===");
    error_log("Raw input data: " . print_r($data, true));
    error_log("POST data: " . print_r($_POST, true));
    
    $userObject = new UserMaster;
    if($userObject->loadLoginUser($data)){
        error_log("Login validation passed");
        $userObject->LoginUserTempInfo();
    }
    else {
        error_log("Login validation failed");
        echo json_encode(array(
            "status" => "error On Login User temp Info",
            "message_text" => "Invalid Input Parameters",
            "debug_data" => $data  // This will show what data was received
        ), JSON_FORCE_OBJECT);
    }
    error_log("=== Login Attempt End ===");
}


?>