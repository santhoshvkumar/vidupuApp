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
        $this->UserToken = $data['UserToken'];
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
                  WHERE tblE.employeePhone=? AND tblE.employeePassword=?";
            
            $stmt = mysqli_prepare($connect_var, $queryUserLogin);
            
            mysqli_stmt_bind_param($stmt, "ss", $this->UserName, $this->UserPassword);
            
            mysqli_stmt_execute($stmt);
            
            $result = mysqli_stmt_get_result($stmt);
            
            // $rsd = mysqli_query($connect_var,$queryUserLogin);
            $resultArr=Array();
            $count=0;
            $userExist=0;
            while($rs = mysqli_fetch_assoc($result)){
               if(isset($rs['empID'])){
                    $getEmployeeName = $rs['employeeName'];
                    $resultArr['employeeName'] = $getEmployeeName;
                    $resultArr['employeeID'] = $rs['employeeID'];
                    $resultArr['employeePhoto'] = $rs['employeePhoto'];
                    $resultArr['managerID'] = $rs['managerID'];
                    $resultArr['causalLeave'] = $rs['CasualLeave'];
                    $resultArr['MedicalLeave'] = $rs['MedicalLeave'];
                    $resultArr['PrivilageLeave'] = $rs['PrivilegeLeave'];
                    $resultArr['NoOfMaternityLeave'] = $rs['NoOfMaternityLeave'];
                    $resultArr['SpecialCasualLeave'] = $rs['SpecialCasualLeave'];   
                    $resultArr['CompensatoryOff'] = $rs['CompensatoryOff'];
                    $resultArr['SpecialLeaveBloodDonation'] = $rs['SpecialLeaveBloodDonation'];
                    $resultArr['LeaveOnPrivateAffairs'] = $rs['LeaveOnPrivateAffairs'];
                    $resultArr['TotalLeave'] = $rs['CasualLeave'] + $rs['MedicalLeave'] + $rs['PrivilegeLeave'] + $rs['SpecialCasualLeave'] + $rs['CompensatoryOff'] + $rs['SpecialLeaveBloodDonation'] + $rs['LeaveOnPrivateAffairs'];
                    $resultArr['branchUniqueID'] = $rs['branchUniqueID'];
                    $resultArr['branchName'] = $rs['branchName'];
                    $resultArr['branchAddress'] = $rs['branchAddress'];
                    $resultArr['branchLatitude'] = $rs['branchLatitude'];
                    $resultArr['branchLongitude'] = $rs['branchLongitude'];
                    $resultArr['branchRadius'] = $rs['branchRadius'];
                    $resultArr['IsManager'] = $rs['isManager'];
                    
                    // Update UserToken in database
                    $updateToken = "UPDATE tblEmployee SET userToken = '$this->UserToken' 
                                  WHERE employeeID = '" . $rs['employeeID'] . "'";
                    mysqli_query($connect_var, $updateToken);
                    
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