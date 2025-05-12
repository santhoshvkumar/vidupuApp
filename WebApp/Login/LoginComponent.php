<?php
class LoginComponent{
    public $userID;
    public $userPassword;
    public $userName;
    public $sectionName;
    public $userPhone;   
    
    public function loadLoginDetails(array $data){
        if (isset($data['userPhone']) && isset($data['userPassword'])) {
            $this->userPhone = $data['userPhone'];
            $this->userPassword = $data['userPassword'];
            return true;
        } else {
            return false;
        }
    }
   
    public function LoginDetails() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       

            // Debug input values
            error_log("LoginDetails - Input values:");
            error_log("userPhone: " . $this->userPhone);
            error_log("userPassword: " . $this->userPassword);

            $queryLoginDetails = "
                SELECT userID, userName, sectionName 
                FROM tbluser 
                WHERE userPhone = ? AND userPassword = ?;";

            // Debug the query with actual values
            $debug_query = str_replace(
                ['?', '?'],
                [
                    "'" . $this->userPhone . "'",
                    "'" . $this->userPassword . "'"
                ],
                $queryLoginDetails
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryLoginDetails);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ss", 
                $this->userPhone,
                $this->userPassword
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Execute failed: " . mysqli_stmt_error($stmt));
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            
            // Debug the result
            error_log("Query Result: " . print_r($row, true));
            
            if ($row) {
                $data['userID'] = $row['userID'];
                $data['userName'] = $row['userName'];
                $data['sectionName'] = $row['sectionName'];
                
                // Debug final data
                error_log("Final Data: " . print_r($data, true));
                
                echo json_encode([
                    "status" => "success",
                    "data" => $data
                ]);
            } else {    
                error_log("No data found for user phone: " . $this->userPhone);
                echo json_encode([
                    "status" => "error",
                    "message_text" => "Invalid phone number or password"
                ], JSON_FORCE_OBJECT);
            }
            
        } catch (Exception $e) {
            error_log("Error in LoginDetails: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
    
}

function LoginDetails($decoded_items) {
        $LoginDetailsObject = new LoginComponent();
        if ($LoginDetailsObject->loadLoginDetails($decoded_items)) {
            $LoginDetailsObject->LoginDetails();
        } else {        
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
}
?>