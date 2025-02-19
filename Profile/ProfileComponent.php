<?php
class ProfileMaster{
    public $EmployeeID;
    public $EmployeePassword;
    public $NewPassword;
    public function loadChangePassword($decoded_items){
        $this->EmployeeID = $decoded_items['EmployeeID'];
        $this->EmployeePassword = $decoded_items['EmployeePassword'];
        $this->NewPassword = $decoded_items['NewPassword'];
        return true;
    }
    public function changePassword() {
        include('config.inc');
        header('Content-Type: application/json');
        try
        {
        
            $queryUserLogin = "Select * from tblEmployee where employeeID = '$this->EmployeeID' and employeePassword = '$this->EmployeePassword'";
            $rsd = mysqli_query($connect_var,$queryUserLogin);
            $resultArr=Array();
            $userExist=0;
            while($rs = mysqli_fetch_assoc($rsd)){
               if(isset($rs['empID'])){
                   $userExist=1;
                   $queryUpdatePassword = "Update tblEmployee set employeePassword = '$this->NewPassword' where employeeID = '$this->EmployeeID'";
                   $rsdUpdatePassword = mysqli_query($connect_var,$queryUpdatePassword);
                }
            }
            mysqli_close($connect_var);
            if($userExist==1){
                echo json_encode(array("status"=>"success","message_text"=>"Password Changed Successfully"),JSON_FORCE_OBJECT);
            }
            else{
                echo json_encode(array("status"=>"error","message_text"=>"Invalid Password"),JSON_FORCE_OBJECT);
            }
        }
        catch(Exception $e){
            echo json_encode(array("status"=>"error","message_text"=>"Error in changing password"),JSON_FORCE_OBJECT);
        }
    }
}

function changePassword($decoded_items){
    $profileObject = new ProfileMaster;
    if($profileObject->loadChangePassword($decoded_items)){
        $profileObject->changePassword();
    }
    else{
        echo json_encode(array("status"=>"error","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
    }

}

?>