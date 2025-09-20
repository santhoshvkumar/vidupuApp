<?php
class ProfileMaster{
    public $EmployeeID;
    public $EmployeePassword;
    public $NewPassword;
    public function loadChangePassword($decoded_items){
        $this->EmployeeID = $decoded_items['EmployeeID'];
        $this->NewPassword = md5($decoded_items['NewPassword']);
        
        // Make EmployeePassword optional
        if (isset($decoded_items['EmployeePassword'])) {
            $this->EmployeePassword = md5($decoded_items['EmployeePassword']);
        } else {
            $this->EmployeePassword = null;
        }
        
        return true;
    }
    public function changePassword() {
        include('config.inc');
        header('Content-Type: application/json');
        try
        {
            // If EmployeePassword is provided, validate it; otherwise skip validation
            if ($this->EmployeePassword !== null) {
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
                    }
                }
                
                mysqli_stmt_close($stmt);
                
                if ($userExist == 0) {
                    echo json_encode(array("status" => "error", "message_text" => "Kindly Make sure your current password is correct"), JSON_FORCE_OBJECT);
                    return;
                }
            }
            
            // Update password with prepared statement
            $queryUpdatePassword = "UPDATE tblEmployee SET employeePassword = ? , userToken = '' WHERE employeeID = ?";
            $updateStmt = mysqli_prepare($connect_var, $queryUpdatePassword);
            mysqli_stmt_bind_param($updateStmt, "ss", $this->NewPassword, $this->EmployeeID);
            
            if (mysqli_stmt_execute($updateStmt)) {
                echo json_encode(array("status" => "success", "message_text" => "Password Changed Successfully"), JSON_FORCE_OBJECT);
            } else {
                echo json_encode(array("status" => "error", "message_text" => "Failed to update password"), JSON_FORCE_OBJECT);
            }
            
            mysqli_stmt_close($updateStmt);
            mysqli_close($connect_var);
        }
        catch(Exception $e){
            echo json_encode(array("status"=>"error","message_text"=>"Error in changing password: " . $e->getMessage()),JSON_FORCE_OBJECT);
        }
    }
}

class EmployeeProfileMaster {
    public $EmployeeID;
    
    public function loadProfileDetails($decoded_items) {
        if (isset($decoded_items['EmployeeID'])) {
            $this->EmployeeID = $decoded_items['EmployeeID'];
            return true;
        }
        return false;
    }
    
    public function getProfileDetails() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            // First check if employee exists
            $checkQuery = "SELECT employeeID FROM tblEmployee WHERE employeeID = ?";
            $checkStmt = mysqli_prepare($connect_var, $checkQuery);
            mysqli_stmt_bind_param($checkStmt, "s", $this->EmployeeID);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);
            
            if (!$rs = mysqli_fetch_assoc($checkResult)) {
                echo json_encode(array(
                    "status" => "error",
                    "message_text" => "Employee ID does not exist in the system"
                ), JSON_FORCE_OBJECT);
                mysqli_stmt_close($checkStmt);
                return;
            }
            
            mysqli_stmt_close($checkStmt);

            // Now get the full profile details
            $query = "SELECT
                tblE.empID,
                tblE.employeeID,
                tblE.employeeName,
                tblE.employeePhone,
                tblE.Designation,
                tblE.employeeDOB AS DateOfBirth,
                tblE.joiningDate,
                tblE.retirementDate,
                tblE.employeePhoto,
                tblB.branchID,
                tblB.branchName,  
                tblA.SectionName,
                tblManager.employeeName as managerName,
                tblE.managerID
            FROM
                tblEmployee tblE
            LEFT JOIN
                tblmapEmp tblM ON tblE.employeeID = tblM.employeeID
            LEFT JOIN
                tblBranch tblB ON tblM.branchID = tblB.branchID
            LEFT JOIN
                (
                    SELECT 
                        tblA.employeeID,
                        tblS.SectionName
                    FROM 
                        tblAssignedSection tblA
                    JOIN 
                        tblSection tblS ON tblA.sectionID = tblS.sectionID
                    WHERE 
                        tblA.isActive = 1
                ) tblA ON tblE.employeeID = tblA.employeeID
            LEFT JOIN
                tblEmployee tblManager ON tblE.managerID = tblManager.employeeID
            WHERE
                tblE.employeeID = ?";
                
            $stmt = mysqli_prepare($connect_var, $query);
            mysqli_stmt_bind_param($stmt, "s", $this->EmployeeID);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($rs = mysqli_fetch_assoc($result)) {
                $profileData = array(
                    "employeeID" => $rs['empID'],
                    "employeeName" => $rs['employeeName'],
                    "employeePhoto" => $rs['employeePhoto'],
                    "branch" => $rs['branchName'] ?? 'Not Assigned',
                    "section" => $rs['SectionName'] ?? 'Not Assigned',
                    "designation" => $rs['Designation'],
                    "dateOfBirth" => $rs['DateOfBirth'],
                    "dateOfJoining" => $rs['joiningDate'],
                    "dateOfRetirement" => $rs['retirementDate'],
                    "managerName" => $rs['managerName'] ?? 'Not Assigned'
                );
                
                echo json_encode(array(
                    "status" => "success",
                    "data" => $profileData
                ), JSON_FORCE_OBJECT);
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message_text" => "Error fetching employee details"
                ), JSON_FORCE_OBJECT);
            }
            
            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);
            
        } catch(Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Error fetching profile details: " . $e->getMessage()
            ), JSON_FORCE_OBJECT);
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
                $queryUpdatePassword = "UPDATE tblEmployee SET employeePassword = ?, userToken = '' WHERE employeePhone = ?";
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

function getProfileDetails($decoded_items) {
    $profileObject = new EmployeeProfileMaster();
    if($profileObject->loadProfileDetails($decoded_items)) {
        $profileObject->getProfileDetails();
    } else {
        echo json_encode(array(
            "status" => "error",
            "message_text" => "Invalid input parameters"
        ), JSON_FORCE_OBJECT);
    }
}

function updateProfilePhoto() {
    include('config.inc');
    header('Content-Type: application/json');
    try {
        if (!isset($_POST['EmployeeID']) || !isset($_FILES['photo'])) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "EmployeeID and photo are required"
            ), JSON_FORCE_OBJECT);
            return;
        }
        $employeeID = $_POST['EmployeeID'];
        $file = $_FILES['photo'];
        $uploadDir = __DIR__ . '/../uploads/profile_photos/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'profile_' . $employeeID . '_' . time() . '.' . $ext;
        $filePath = $uploadDir . $fileName;
        $dbPath = 'uploads/profile_photos/' . $fileName;
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Update DB
            $query = "UPDATE tblEmployee SET employeePhoto = ? WHERE employeeID = ?";
            $stmt = mysqli_prepare($connect_var, $query);
            mysqli_stmt_bind_param($stmt, "ss", $dbPath, $employeeID);
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(array(
                    "status" => "success",
                    "message_text" => "Profile photo updated successfully",
                    "photoPath" => $dbPath
                ), JSON_FORCE_OBJECT);
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message_text" => "Failed to update database"
                ), JSON_FORCE_OBJECT);
            }
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Failed to upload file"
            ), JSON_FORCE_OBJECT);
        }
        mysqli_close($connect_var);
    } catch(Exception $e) {
        echo json_encode(array(
            "status" => "error",
            "message_text" => "Error uploading photo: " . $e->getMessage()
        ), JSON_FORCE_OBJECT);
    }
}

function updateProfilePhotoPath($decoded_items) {
    include('config.inc');
    header('Content-Type: application/json');
    if (!isset($decoded_items['EmployeeID']) || !isset($decoded_items['photoPath'])) {
        echo json_encode([
            'status' => 'error',
            'message_text' => 'EmployeeID and photoPath are required'
        ]);
        return;
    }
    $employeeID = $decoded_items['EmployeeID'];
    $photoPath = $decoded_items['photoPath'];
    $query = "UPDATE tblEmployee SET employeePhoto = ? WHERE employeeID = ?";
    $stmt = mysqli_prepare($connect_var, $query);
    mysqli_stmt_bind_param($stmt, "ss", $photoPath, $employeeID);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            'status' => 'success',
            'message_text' => 'Profile photo updated successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message_text' => 'Failed to update database'
        ]);
    }
    mysqli_stmt_close($stmt);
    mysqli_close($connect_var);
}

?>