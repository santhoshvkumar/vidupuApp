<?php

class EmployeeComponent{
    public $employeeID;
    public $employeeRole;
    public $employeePassword;
    public $PresentBranchID;
    public $NewBranchID;
    public $interChangeDate;
    public $branchName;
    public $empID;
    public $employeePhone;
    public $employeeGender;
    public $Designation;
    public $isManager;
    public $employeeDOB;
    public $joiningDate;
    public $retirementDate;
    public $branchID;
    public $sectionID;
    public $casualLeave;
    public $privilegeLeave;
    public $medicalLeave;
    public $employeeName;
    public $deviceFingerprint;
    public $employeeBloodGroup;
    public $organisationID;

    public function loadAllEmployeeList(array $data){
        if(isset($data['organisationID'])) {
            $this->organisationID = $data['organisationID'];
            return true;
        } else {
            return false;
        }
    }

    public function loadResetPassword(array $data){ 
        if (isset($data['empID'])) {  
            $this->empID = $data['empID'];
            return true;
        } else {
            return false;
        }
    }
    public function loadResetEmployeeActiveStatus(array $data){ 
        if (isset($data['empID'])) {  
            $this->empID = $data['empID'];
            return true;
        } else {
            return false;
        }
    }
    public function loadDeviceFingerprint(array $data){ 
        if (isset($data['empID'])) {  
            $this->empID = $data['empID'];
            return true;
        } else {
            return false;
        }
    }

    public function loadEmployeeDetails(array $data){
        if (isset($data['employeeID']) && isset($data['currentBranchID'])) {
            $this->employeeID = $data['employeeID'];
            $this->PresentBranchID = $data['currentBranchID'];
            $this->NewBranchID = $data['newBranchID'];
            $this->interChangeDate = $data['interChangeDate'];
            return true;
        } else {
            return false;
        }
    }
    public function loadUpdateEmployeeDetails(array $data){
        // Debug log the incoming data
        error_log("Received data in loadUpdateEmployeeDetails: " . print_r($data, true));

        // Check for required fields
        $required_fields = [
            'empID',
            'employeeName',
            'employeePhone',
            'employeeGender',
            'Designation',
            'employeeBloodGroup',
            'isManager',
            'employeeDOB',
            'joiningDate',
            'retirementDate',
            'branchID',
            'casualLeave',
            'privilegeLeave',
            'medicalLeave'
        ];

        // Validate required fields
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                error_log("Missing required field: " . $field);
                return false;
            }
            // Skip empty check for numeric fields
            if (!in_array($field, ['branchID', 'casualLeave', 'privilegeLeave', 'medicalLeave', 'isManager']) && $data[$field] === '') {
                error_log("Empty required field: " . $field);
                return false;
            }
        }

        try {
            // Set the properties for update employee details
            $this->empID = trim($data['empID']);
            $this->employeeName = trim($data['employeeName']);
            $this->employeePhone = trim($data['employeePhone']);
            $this->employeeGender = trim($data['employeeGender']);
            $this->Designation = trim($data['Designation']);
            $this->employeeBloodGroup = trim($data['employeeBloodGroup']);
            $this->isManager = (int)$data['isManager'];
            $this->employeeDOB = $data['employeeDOB'];
            $this->joiningDate = $data['joiningDate'];
            $this->retirementDate = $data['retirementDate'];
            $this->branchID = (int)$data['branchID'];
            $this->sectionID = isset($data['sectionID']) && $data['sectionID'] !== '' ? (int)$data['sectionID'] : 0;
            $this->casualLeave = (int)$data['casualLeave'];
            $this->privilegeLeave = (int)$data['privilegeLeave'];
            $this->medicalLeave = (int)$data['medicalLeave'];
            
            // Debug log the set properties
            error_log("Properties set successfully in loadUpdateEmployeeDetails");
            return true;
        } catch (Exception $e) {
            error_log("Error setting properties: " . $e->getMessage());
            return false;
        }
    }
    public function loadGetEmployeeDetails(array $data) {
        if (isset($data['employeeID'])) {
            $this->employeeID = $data['employeeID'];
            return true;
        } else {
            return false;
        }
    }
    public function loadGetEmployeeDetailsBasedOnID(array $data) {
        if (isset($data['empID'])) {
            $this->empID = $data['empID'];
            return true;
        } else {
            return false;
        }
    }
    public function loadGetEmployeeDetailsBasedOnBranch(array $data) {
        if (isset($data['branchName'])) {
            $this->branchName = $data['branchName'];
            return true;
        } else {
            return false;
        }
    }

    public function EmployeeDetails() {
        include(dirname(__FILE__) . '/../../config.inc');
        header('Content-Type: application/json');
    
        try {
            // Initialize an array to hold the results
            $data = [];
    
            // 1. Fetch all dashboard details
            $queryEmployeeDetails = "SELECT * FROM tblDashboardDetails";
            $rsd = mysqli_query($connect_var, $queryEmployeeDetails);
            $EmployeeDetails = [];
            while ($row = mysqli_fetch_assoc($rsd)) {
                $EmployeeDetails[] = $row;
            }
            $data['EmployeeDetails'] = $EmployeeDetails;    
            
    
            echo json_encode([
                "status" => "success",
                "data" => $data
            ]);
    
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    } 
    public function ResetPassword() {
        include(dirname(__FILE__) . '/../../config.inc');
        header('Content-Type: application/json');    
        try {       
            $data = [];                       

            $ResetPasswordQuery = "
               UPDATE tblEmployee 
               SET employeePassword = MD5('Password#1') 
               WHERE empID = ?;";


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
    public function ResetDeviceFingerprint() {
        include(dirname(__FILE__) . '/../../config.inc');
        header('Content-Type: application/json');
        try {
            $data = [];                       

            $ResetDeviceFingerprintQuery = "
               UPDATE tblEmployee 
               SET deviceFingerprint = NULL 
               WHERE empID = ?;";


            $stmt = mysqli_prepare($connect_var, $ResetDeviceFingerprintQuery);
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
            $ResetDeviceFingerprintResult = [];    
            $ResetDeviceFingerprintResult['status'] = "success";
            $ResetDeviceFingerprintResult['message_text'] = "Device fingerprint reset successfully";
            
            echo json_encode($ResetDeviceFingerprintResult, JSON_FORCE_OBJECT);
            
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
    public function ResetEmployeeActiveStatus() {
        include(dirname(__FILE__) . '/../../config.inc');
        header('Content-Type: application/json');
        try {
            $data = [];                       

            $ResetEmployeeActiveStatusQuery = "
               UPDATE tblEmployee 
               SET isActive = 1 
               WHERE empID = ?;";


            $stmt = mysqli_prepare($connect_var, $ResetEmployeeActiveStatusQuery);
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
            $ResetEmployeeActiveStatusResult = [];    
            $ResetEmployeeActiveStatusResult['status'] = "success";
            $ResetEmployeeActiveStatusResult['message_text'] = "Employee active status reset successfully";
            
            echo json_encode($ResetEmployeeActiveStatusResult, JSON_FORCE_OBJECT);
            
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
    public function AllEmployeeDetails() {
        include(dirname(__FILE__) . '/../../config.inc');
        header('Content-Type: application/json');
    
        try {
            // Initialize an array to hold the results
            $data = [];
    
            // 1. Fetch all dashboard details
            $queryEmployeeDetails = "SELECT * FROM tblDashboardDetails";
            $rsd = mysqli_query($connect_var, $queryEmployeeDetails);
            $EmployeeDetails = [];
            while ($row = mysqli_fetch_assoc($rsd)) {
                $EmployeeDetails[] = $row;
            }
            $data['EmployeeDetails'] = $EmployeeDetails;    
            
    
            echo json_encode([
                "status" => "success",
                "data" => $data
            ]);
    
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
    public function GetEmployeeDetails($decoded_items) {
        include(dirname(__FILE__) . '/../../config.inc');
        header('Content-Type: application/json');
    
        try {
            // Initialize an array to hold the results
            $data = [];
    
            // 1. Fetch all dashboard details
            $queryEmployeeDetails = "SELECT * FROM tblDashboardDetails";
            $rsd = mysqli_query($connect_var, $queryEmployeeDetails);
            $EmployeeDetails = [];
            while ($row = mysqli_fetch_assoc($rsd)) {
                $EmployeeDetails[] = $row;
            }
            $data['EmployeeDetails'] = $EmployeeDetails;    
            
    
            echo json_encode([
                "status" => "success",
                "data" => $data
            ]);
    
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
    public function GetAllEmployeeDetails() {
        include(dirname(__FILE__) . '/../../config.inc');
        header('Content-Type: application/json');
    
        try {
            // Debug: Log the organisationID being used
            error_log("GetAllEmployeeDetails: organisationID = " . $this->organisationID);
            
            $data = [];
    
            // 1. Get all active employees Name, ID and BranchID
            $queryGetEmployeeDetails = "SELECT DISTINCT
                tblE.employeeID,
                tblE.empID, 
                tblE.employeeName, 
                tblE.employeePhone,
                tblE.employeeGender, 
                tblE.Designation, 
                tblE.employeeBloodGroup,
                CASE 
                    WHEN tblE.isManager = 1 THEN 'Yes'
                    ELSE 'No'
                END AS HasApprovalAuthority,
                tblE.employeeDOB AS DateOfBirth,
                tblE.joiningDate,
                tblE.retirementDate,
                tblB.branchID, 
                tblB.branchName,   
                tblS.sectionName,
                tblL.casualLeave,
                tblL.privilegeLeave,
                tblL.medicalLeave,
                tblE.deviceFingerprint,
                tblE.employeePassword
            FROM 
                tblEmployee tblE
            JOIN tblmapEmp tblM ON tblE.employeeID = tblM.employeeID
            JOIN tblBranch tblB ON tblM.branchID = tblB.branchID
            LEFT JOIN tblAssignedSection tblA 
                ON tblE.employeeID = tblA.employeeID AND tblA.isActive = 1
            LEFT JOIN tblSection tblS ON tblA.sectionID = tblS.sectionID
            LEFT JOIN tblLeaveBalance tblL ON tblE.employeeID = tblL.employeeID
            WHERE tblE.isTemporary = 0 AND tblE.organisationID = ?";
            
            $stmt = mysqli_prepare($connect_var, $queryGetEmployeeDetails);
            if (!$stmt) {
                throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
            }
            
            mysqli_stmt_bind_param($stmt, "s", $this->organisationID);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Database execute failed: " . mysqli_error($connect_var));
            }
            
            $result = mysqli_stmt_get_result($stmt);
            if (!$result) {
                throw new Exception("Failed to get result: " . mysqli_error($connect_var));
            }

            // Initialize an array to hold all employee details
            $employees = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            
            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);
            
            // Debug: Log the number of employees found
            error_log("GetAllEmployeeDetails: Found " . count($data) . " employees");
            
            echo json_encode([
                "status" => "success",
                "data" => $data
            ]);
    
        } catch (Exception $e) {
            error_log("Error in GetAllEmployeeDetails: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        } finally {
            if (isset($connect_var)) {
                mysqli_close($connect_var);
            }
        }
    }
    public function GetEmployeeDetailsBasedOnID($decoded_items) {
        include(dirname(__FILE__) . '/../../config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
    
            // 1. Get all active employees Name, ID and BranchID
            $queryGetEmployeeDetails = "SELECT 
            tblE.empID, 
            tblE.employeeName, 
            tblE.employeePhone,
            tblE.employeeGender, 
            tblE.Designation, 
            tblE.employeeBloodGroup,
            CASE 
                WHEN tblE.isManager = 1 THEN 'Yes'
                ELSE 'No'
            END AS HasApprovalAuthority,
            tblE.employeeDOB AS DateOfBirth,
            tblE.joiningDate,
            tblE.retirementDate,
            tblB.branchID, 
            tblB.branchName,   
            tblS.sectionName,
            tblL.casualLeave,
            tblL.privilegeLeave,
            tblL.medicalLeave
        FROM 
            tblEmployee tblE
        JOIN 
            tblmapEmp tblM ON tblE.employeeID = tblM.employeeID
        JOIN 
            tblBranch tblB ON tblM.branchID = tblB.branchID
        LEFT JOIN 
            tblAssignedSection tblA ON tblE.employeeID = tblA.employeeID AND tblA.isActive = 1
        LEFT JOIN 
            tblSection tblS ON tblA.sectionID = tblS.sectionID
        LEFT JOIN 
            tblLeaveBalance tblL ON tblE.employeeID = tblL.employeeID
        WHERE 
            tblE.isTemporary = 0 AND tblE.empID = ?";
            $debug_query = str_replace(
                ['?'],
                [
                "'" . $this->empID. "'",
                ],
            $queryGetEmployeeDetails
        );
        error_log("Debug Query: " . $debug_query);

        $stmt = mysqli_prepare($connect_var, $queryGetEmployeeDetails);
        if (!$stmt) {           
        throw new Exception("Database prepare failed");
        }

        mysqli_stmt_bind_param($stmt, "s", $this->empID);

        if (!mysqli_stmt_execute($stmt)) {  
            throw new Exception("Database execute failed");
        }

        $result = mysqli_stmt_get_result($stmt);
        $countEmployee = 0;
        while ($row = mysqli_fetch_assoc($result)) {
        $countEmployee++;   
        $data[] = $row;
        }

        if ($countEmployee > 0) {
        echo json_encode([
            "status" => "success",          
            "data" => $data
        ]);
        } else {
        echo json_encode([
            "status" => "error",
            "message_text" => "No data found for any employee"
        ], JSON_FORCE_OBJECT);  
        }
        mysqli_stmt_close($stmt);
        mysqli_close($connect_var);
        } catch (Exception $e) {
        error_log("Error in GetEmployeeDetailsBasedOnID: " . $e->getMessage());
        echo json_encode([
        "status" => "error",
        "message_text" => $e->getMessage()
        ], JSON_FORCE_OBJECT);
        }
    }
    public function UpdateEmployeeDetailsBasedOnID($decoded_items) {
        include(dirname(__FILE__) . '/../../config.inc');
        header('Content-Type: application/json');
    
        try {
            // Start transaction
            mysqli_begin_transaction($connect_var);
            
            // 1. Update employee details
            $queryUpdateEmployee = "UPDATE tblEmployee 
                SET 
                    employeeName = ?,
                    employeePhone = ?,  
                    employeeGender = ?,
                    Designation = ?,
                    employeeBloodGroup = ?,
                    isManager = ?,
                    employeeDOB = ?,
                    joiningDate = ?,
                    retirementDate = ?
                WHERE empID = ?";
                
            $stmt = mysqli_prepare($connect_var, $queryUpdateEmployee);
            if (!$stmt) {
                throw new Exception("Failed to prepare employee update statement: " . mysqli_error($connect_var));
            }
            
            mysqli_stmt_bind_param($stmt, "ssssssssss", 
                $this->employeeName,
                $this->employeePhone,
                $this->employeeGender,
                $this->Designation,
                $this->employeeBloodGroup,
                $this->isManager,
                $this->employeeDOB,
                $this->joiningDate,
                $this->retirementDate,
                $this->empID
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to update employee details: " . mysqli_error($connect_var));
            }
            mysqli_stmt_close($stmt);
            
            // 2. Update branch mapping
            $queryUpdateBranch = "UPDATE tblmapEmp 
                SET branchID = ? 
                WHERE employeeID = (SELECT employeeID FROM tblEmployee WHERE empID = ?)";
                
            $stmt = mysqli_prepare($connect_var, $queryUpdateBranch);
            if (!$stmt) {
                throw new Exception("Failed to prepare branch update statement: " . mysqli_error($connect_var));
            }
            
            mysqli_stmt_bind_param($stmt, "ss", $this->branchID, $this->empID);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to update branch mapping: " . mysqli_error($connect_var));
            }
            mysqli_stmt_close($stmt);
            
            // 3. Update section assignment if sectionID is not empty
            if (!empty($this->sectionID)) {
                // First deactivate all existing section assignments
                $queryUpdateSection = "UPDATE tblAssignedSection 
                    SET isActive = 0 
                    WHERE employeeID = (SELECT employeeID FROM tblEmployee WHERE empID = ?)";
                    
                $stmt = mysqli_prepare($connect_var, $queryUpdateSection);
                if (!$stmt) {
                    throw new Exception("Failed to prepare section update statement: " . mysqli_error($connect_var));
                }
                
                mysqli_stmt_bind_param($stmt, "s", $this->empID);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to update section assignment: " . mysqli_error($connect_var));
                }
                mysqli_stmt_close($stmt);
                
                // Insert new section assignment
                $queryInsertSection = "INSERT INTO tblAssignedSection (employeeID, sectionID, isActive, createdOn) 
                    SELECT employeeID, ?, 1, CURDATE()
                    FROM tblEmployee 
                    WHERE empID = ?";
                    
                $stmt = mysqli_prepare($connect_var, $queryInsertSection);
                if (!$stmt) {
                    throw new Exception("Failed to prepare section insert statement: " . mysqli_error($connect_var));
                }
                
                mysqli_stmt_bind_param($stmt, "ss", $this->sectionID, $this->empID);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to insert new section assignment: " . mysqli_error($connect_var));
                }
                mysqli_stmt_close($stmt);
            } else {
                // If sectionID is empty, just deactivate all existing section assignments
                $queryUpdateSection = "UPDATE tblAssignedSection 
                    SET isActive = 0 
                    WHERE employeeID = (SELECT employeeID FROM tblEmployee WHERE empID = ?)";
                    
                $stmt = mysqli_prepare($connect_var, $queryUpdateSection);
                if (!$stmt) {
                    throw new Exception("Failed to prepare section update statement: " . mysqli_error($connect_var));
                }
                
                mysqli_stmt_bind_param($stmt, "s", $this->empID);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to update section assignment: " . mysqli_error($connect_var));
                }
                mysqli_stmt_close($stmt);
            }
            
            // 4. Update leave balance
            $queryUpdateLeave = "UPDATE tblLeaveBalance 
                SET casualLeave = ?,
                    privilegeLeave = ?,
                    medicalLeave = ?
                WHERE employeeID = (SELECT employeeID FROM tblEmployee WHERE empID = ?)";
                
            $stmt = mysqli_prepare($connect_var, $queryUpdateLeave);
            if (!$stmt) {
                throw new Exception("Failed to prepare leave update statement: " . mysqli_error($connect_var));
            }
            
            mysqli_stmt_bind_param($stmt, "ssss", 
                $this->casualLeave,
                $this->privilegeLeave,
                $this->medicalLeave,
                $this->empID
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to update leave balance: " . mysqli_error($connect_var));
            }
            mysqli_stmt_close($stmt);
            
            // Commit transaction
            mysqli_commit($connect_var);
            
            echo json_encode([
                "status" => "success",
                "message" => "Employee details updated successfully"
            ]);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($connect_var);
            
            error_log("Error in UpdateEmployeeDetailsBasedOnID: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        } finally {
            mysqli_close($connect_var);
        }
    }
        
} // Close the DashboardComponent class

function EmployeeDetails() {
    $EmployeeComponent = new EmployeeComponent();
    $EmployeeComponent->AllEmployeeDetails();
}

function GetAllEmployees($decoded_items) {
    $EmployeeComponent = new EmployeeComponent();
    if($EmployeeComponent-> loadAllEmployeeList($decoded_items)) {
        $EmployeeComponent->GetAllEmployeeDetails();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}

function UpdateEmployeeDetails($decoded_items) {
    $EmployeeObject = new EmployeeComponent();
    if ($EmployeeObject->loadEmployeeDetails($decoded_items)) {
        // Pass the $decoded_items to the UpdateEmployeeDetails method
        $EmployeeObject->UpdateEmployeeDetails($decoded_items);
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}

function GetEmployeeDetails($decoded_items) {
    $EmployeeObject = new EmployeeComponent();
    if ($EmployeeObject->loadGetEmployeeDetails($decoded_items)) {
        // Pass the $decoded_items to the UpdateEmployeeDetails method
        $EmployeeObject->GetEmployeeDetails($decoded_items);
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}

function GetEmployeeBasedOnBranch($decoded_items) {
    $EmployeeObject = new EmployeeComponent();
    if ($EmployeeObject->loadGetEmployeeDetailsBasedOnBranch($decoded_items)) {
        // Pass the $decoded_items to the UpdateEmployeeDetails method
        $EmployeeObject->GetEmployeeBasedOnBranch($decoded_items);
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function GetEmployeeDetailsBasedOnID($decoded_items) {
    $EmployeeObject = new EmployeeComponent();
    if ($EmployeeObject->loadGetEmployeeDetailsBasedOnID($decoded_items)) {
        // Pass the $decoded_items to the UpdateEmployeeDetails method
        $EmployeeObject->GetEmployeeDetailsBasedOnID($decoded_items);
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }   
}
function UpdateEmployeeDetailsBasedOnID($decoded_items) {
    error_log("UpdateEmployeeDetailsBasedOnID called with data: " . print_r($decoded_items, true));
    
    $EmployeeObject = new EmployeeComponent();
    if ($EmployeeObject->loadUpdateEmployeeDetails($decoded_items)) {
        // Pass the $decoded_items to the UpdateEmployeeDetails method
        $EmployeeObject->UpdateEmployeeDetailsBasedOnID($decoded_items);
    } else {
        error_log("loadUpdateEmployeeDetails validation failed");
        echo json_encode(array(
            "status" => "error", 
            "message_text" => "Invalid Input Parameters",
            "debug_info" => "Validation failed in loadUpdateEmployeeDetails"
        ), JSON_FORCE_OBJECT);
    }
}
function ResetPassword($decoded_items) {
    $ResetPasswordObject = new EmployeeComponent();
    if ($ResetPasswordObject->loadResetPassword($decoded_items)) {
        $ResetPasswordObject->ResetPassword();
    } else {    
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function ResetDeviceFingerprint($decoded_items) {
    $ResetDeviceFingerprintObject = new EmployeeComponent();
    if ($ResetDeviceFingerprintObject->loadDeviceFingerprint($decoded_items)) {
        $ResetDeviceFingerprintObject->ResetDeviceFingerprint();
    } else {    
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function ResetEmployeeActiveStatus($decoded_items) {
    $ResetEmployeeActiveStatusObject = new EmployeeComponent();
    if ($ResetEmployeeActiveStatusObject->loadResetEmployeeActiveStatus($decoded_items)) {
        $ResetEmployeeActiveStatusObject->ResetEmployeeActiveStatus();
    } else {    
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
?>

