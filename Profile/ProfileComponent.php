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

class ForgotPasswordMaster {
    public $EmployeePhone;
    public $NewPassword;
    
    public function loadForgotPassword($decoded_items) {
        if (isset($decoded_items['EmployeePhone']) && isset($decoded_items['NewPassword'])) {
            $this->EmployeePhone = $decoded_items['EmployeePhone'];
            $this->NewPassword = md5($decoded_items['NewPassword']);
            return true;
        }
        return false;
    }
    
    public function forgotPassword() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            // First verify if the phone number exists
            $queryCheckPhone = "SELECT employeeID FROM tblEmployee WHERE employeePhone = ?";
            $stmt = mysqli_prepare($connect_var, $queryCheckPhone);
            mysqli_stmt_bind_param($stmt, "s", $this->EmployeePhone);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($rs = mysqli_fetch_assoc($result)) {
                // Phone number exists, update the password
                $queryUpdatePassword = "UPDATE tblEmployee SET employeePassword = ? WHERE employeePhone = ?";
                $updateStmt = mysqli_prepare($connect_var, $queryUpdatePassword);
                mysqli_stmt_bind_param($updateStmt, "ss", $this->NewPassword, $this->EmployeePhone);
                
                if (mysqli_stmt_execute($updateStmt)) {
                    echo json_encode(array(
                        "status" => "success",
                        "message_text" => "Password has been reset successfully"
                    ), JSON_FORCE_OBJECT);
                } else {
                    throw new Exception("Failed to update password");
                }
                
                mysqli_stmt_close($updateStmt);
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message_text" => "Phone number not found"
                ), JSON_FORCE_OBJECT);
            }
            
            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);
            
        } catch(Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Error in resetting password: " . $e->getMessage()
            ), JSON_FORCE_OBJECT);
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

function forgotPassword($decoded_items){
    $forgotPasswordObject = new ForgotPasswordMaster();
    if($forgotPasswordObject->loadForgotPassword($decoded_items)){
        $forgotPasswordObject->forgotPassword();
    } else {
        echo json_encode(array(
            "status" => "error",
            "message_text" => "Invalid input parameters"
        ), JSON_FORCE_OBJECT);
    }
}

?>