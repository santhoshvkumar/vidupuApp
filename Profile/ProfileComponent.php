<?php
class ProfileMaster{
    public $EmployeeID;
    public $EmployeePassword;
    public $NewPassword;
    public function loadChangePassword($decoded_items){
        $this->EmployeeID = $decoded_items['EmployeeID'];
        $this->EmployeePassword = md5($decoded_items['EmployeePassword']);
        $this->NewPassword = md5($decoded_items['NewPassword']);
        return true;
    }
    public function changePassword() {
        include('config.inc');
        header('Content-Type: application/json');
        try
        {
            // Check user credentials with prepared statement
            $queryUserLogin = "SELECT empID FROM tblEmployee WHERE employeeID = ? AND employeePassword = ?";
            $stmt = mysqli_prepare($connect_var, $queryUserLogin);
            mysqli_stmt_bind_param($stmt, "ss", $this->EmployeeID, $this->EmployeePassword);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $userExist = 0;
            if ($rs = mysqli_fetch_assoc($result)) {
                if (isset($rs['empID'])) {
                    $userExist = 1;
                    
                    // Update password with prepared statement
                    $queryUpdatePassword = "UPDATE tblEmployee SET employeePassword = ? WHERE employeeID = ?";
                    $updateStmt = mysqli_prepare($connect_var, $queryUpdatePassword);
                    mysqli_stmt_bind_param($updateStmt, "ss", $this->NewPassword, $this->EmployeeID);
                    mysqli_stmt_execute($updateStmt);
                    mysqli_stmt_close($updateStmt);
                }
            }
            
            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);

            if ($userExist == 1) {
                echo json_encode(array("status" => "success", "message_text" => "Password Changed Successfully"), JSON_FORCE_OBJECT);
            } else {
                echo json_encode(array("status" => "error", "message_text" => "Kindly Make sure your current password is correct"), JSON_FORCE_OBJECT);
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