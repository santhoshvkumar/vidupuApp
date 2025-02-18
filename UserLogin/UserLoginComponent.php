<?php
 
class UserMaster{
    public $User_ID;
    public $UserName;
    public $UserPhoneNumber;
    public $CompanyID;
    public $DatabaseName;
    public $GeneralCompanyID;
    public $GeneralAdminID;
    
    public function loadLoginUser(array $data){
        $this->UserName = $data['EmployeePhone'];
        $this->UserPassword = $data['EmployeePassword'];
        return true;
    }

    public function LoginUserTempInfo(){
        include('config.inc');
        header('Content-Type: application/json');
        try
        {
        
            $queryUserLogin = "SELECT tblE.employeeID, tblE.empID, tblE.employeeName, tblLB.CasualLeave, tblLB.MedicalLeave, PrivilegeLeave, tblLB.MaternityLeave, tblLB.SpecialCasualLeave, tblLB.CompensatoryOff, tblLB.SpecialLeaveBloodDonation, tblLB.LeaveOnPrivateAffairs FROM tblEmployee tblE inner join tblLeaveBalance tblLB on tblLB.employeeID = tblE.employeeID WHERE tblE.employeePhone='$this->UserName' and tblE.employeePassword='$this->UserPassword'";
            $rsd = mysqli_query($connect_var,$queryUserLogin);
            $resultArr=Array();
            $count=0;
            $userExist=0;
            while($rs = mysqli_fetch_assoc($rsd)){
               if(isset($rs['empID'])){
                    $getEmployeeName = $rs['employeeName'];
                    $resultArr['employeeName'] = $getEmployeeName;
                    $resultArr['employeeID'] = $rs['employeeID'];
                    $resultArr['causalLeave'] = $rs['CasualLeave'];
                    $resultArr['MedicalLeave'] = $rs['MedicalLeave'];
                    $resultArr['PrivilageLeave'] = $rs['PrivilegeLeave'];
                    $resultArr['MaternityLeave'] = $rs['MaternityLeave'];
                    $resultArr['SpecialCasualLeave'] = $rs['SpecialCasualLeave'];   
                    $resultArr['CompensatoryOff'] = $rs['CompensatoryOff'];
                    $resultArr['SpecialLeaveBloodDonation'] = $rs['SpecialLeaveBloodDonation'];
                    $resultArr['LeaveOnPrivateAffairs'] = $rs['LeaveOnPrivateAffairs'];
                    $resultArr['TotalLeave'] = $rs['CasualLeave'] + $rs['MedicalLeave'] + $rs['PrivilegeLeave'] + $rs['SpecialCasualLeave'] + $rs['CompensatoryOff'] + $rs['SpecialLeaveBloodDonation'] + $rs['LeaveOnPrivateAffairs'];
                    $count++;
               }  
            }
 
        
        mysqli_close($connect_var);

        if($count>0)
            echo json_encode(array("status"=>"success","record_count"=>$count,"result"=>$resultArr));
        else
            echo json_encode(array("status"=>"failure","record_count"=>$count,"message_text"=>"No user with userPhoneNumber='$this->UserName'"),JSON_FORCE_OBJECT);
        }   
        catch(PDOException $e) {
            echo json_encode(array("status"=>"error","message_text"=>$e->getMessage()),JSON_FORCE_OBJECT);
        }
    }

}


function loginUserTemp(array $data){
    $userObject = new UserMaster;
    if($userObject->loadLoginUser($data)){
        $userObject->LoginUserTempInfo();
    }
    else {
         echo json_encode(array("status"=>"error On Login User temp Info","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }
}


?>