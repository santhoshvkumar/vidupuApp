<?php
class LoginComponent{
    public $userID;
    public $userPassword;
    public $userName;
    public $sectionName;
    public $userPhone;   
    public $LoginDetailsData;

   
    public function LoginDetails() {
        include('config.inc');
        header('Content-Type: application/json');
        $responseStatus = "Failure";
        $responseMessage = 'Login Details Failed';

        //Decode Token Start
        $secratekey = "UserLoginWebToken";
        $decodeVal = decryptDataFunc($this->LoginDetailsData['LoginDetailsToken'], $secratekey);
        // DECODE Token End

        try {       
            $data = []; 
            $queryLoginDetails = "
                SELECT 
                    tblU.userID, 
                    tblU.userName, 
                    tblU.sectionID,
                    tblU.role,
                    tblU.organisationID,
                    tblO.organisationName,
                    tblO.organisationLogo
                FROM tblUser tblU 
                INNER JOIN tblOrganisation tblO on tblO.organisationID = tblU.organisationID
                WHERE userPhone = ? 
                AND userPassword = MD5(?);";

           
            $stmt = mysqli_prepare($connect_var, $queryLoginDetails);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "ss", 
                $decodeVal->userPhone,
                $decodeVal->userPassword
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Execute failed: " . mysqli_stmt_error($stmt));
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            
            
            if ($row) {
                $data['userID'] = $row['userID'];
                $data['userName'] = $row['userName'];
                $data['sectionID'] = $row['sectionID'];
                $data['role'] = $row['role'];
                $data['organisationID'] = $row['organisationID'];
                $data['organisationName'] = $row['organisationName'];
                $data['organisationLogo'] = $row['organisationLogo'];
                // Debug final data
                $responseStatus = "success";
                $responseMessage = "Login Details Success";
                // echo json_encode([
                //     "status" => "success",
                //     "data" => $data
                // ]);
            } else {    
                $responseStatus = "error";
              
            }
            //Encode Token Start
            $payload_info = array(
                "data"=>$data,
                "message"=> $responseMessage,
                "status" => $responseStatus
            );
            $encodeToken = encryptDataFunc($payload_info, $secratekey);
            //Encode Token End
            echo json_encode(array("status"=>$responseStatus, "response"=>$encodeToken),JSON_FORCE_OBJECT);
            
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
        if($decoded_items){
            $LoginDetailsObject->LoginDetailsData = $decoded_items;
            $LoginDetailsObject->LoginDetails();
        } else {        
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
}
?>