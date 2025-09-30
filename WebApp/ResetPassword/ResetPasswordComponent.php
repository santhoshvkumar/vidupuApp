<?php
class ResetPasswordComponent{
   public $empID;
   public $employeePassword;    
    
    public function loadResetPassword(array $data){ 
        if (isset($data['empID'])) {  
            $this->empID = $data['empID'];
            return true;
        } else {
            return false;
        }
    }
    

    public function ResetPassword() {
        include('config.inc');
        header('Content-Type: application/json');    
        try {       
            $data = [];                       

            $ResetPasswordQuery = "
               UPDATE tblEmployee 
               SET employeePassword = MD5('Password#1') 
               WHERE employeeID = ?;";


            $stmt = mysqli_prepare($connect_var, $ResetPasswordQuery);
            if (!$stmt) {
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "s", 
                $this->empID
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);    
            $ResetPasswordResult = [];    
            $ResetPasswordResult['status'] = "success";
            $ResetPasswordResult['message_text'] = "Password reset successfully";
            
            echo json_encode($ResetPasswordResult, JSON_FORCE_OBJECT);
            
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
} 



function ResetPassword($decoded_items) {
    $ResetPasswordObject = new ResetPasswordComponent();
    if ($ResetPasswordObject->loadResetPassword($decoded_items)) {
        $ResetPasswordObject->ResetPassword();
    } else {    
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}

?>