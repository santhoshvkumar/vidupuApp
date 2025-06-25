<?php

class AddEmployeeComponent{
    public $empID;
    public $employeeName;
    public $employeePhone;
    public $employeeGender;
    public $Designation;
    public $employeePassword;
    public $employeeBloodGroup;
    public $employeeDOB;
    public $isManager;
    public $employeeID;
    public $branchID;
    public $sectionID;
    public $joiningDate;
    public $retirementDate;
    public $casualLeave;
    public $privilegeLeave;
    public $medicalLeave;
    public $isTemporary;
    public $isActive;
    public $deviceFingerprint;
    public $organisationID;
    public function loadAddEmployeeDetails(array $data){
        if (isset($data['empID']) && isset($data['employeeName']) && isset($data['employeePhone']) && 
            isset($data['employeeGender']) && isset($data['Designation']) && 
            isset($data['employeeBloodGroup']) && isset($data['employeeDOB']) && isset($data['branchID']) && isset($data['organisationID'])) {
            
            $this->empID = $data['empID'];
            $this->employeeName = $data['employeeName'];
            $this->employeePhone = $data['employeePhone'];
            $this->employeeGender = $data['employeeGender'];
            $this->Designation = $data['Designation'];
            $this->employeeBloodGroup = $data['employeeBloodGroup'];
            $this->employeeDOB = $data['employeeDOB'];
            $this->isManager = $data['isManager'] ?? 0;
            $this->branchID = $data['branchID'];
            $this->sectionID = $data['sectionID'] ?? null;
            $this->joiningDate = $data['joiningDate'] ?? date('Y-m-d');
            $this->retirementDate = $data['retirementDate'] ?? null;
            $this->casualLeave = $data['casualLeave'] ?? 0;
            $this->privilegeLeave = $data['privilegeLeave'] ?? 0;
            $this->medicalLeave = $data['medicalLeave'] ?? 0;
            $this->organisationID = $data['organisationID']?? 1;
            return true;
        } else {
            return false;
        }
    }
    public function loadGetAllEmployeeNameAndID(array $data) {  
        if (isset($data['employeeID'])) {
            $this->employeeID = $data['employeeID'];
            return true;
        } else {
            return false;
        }
    }
    public function GetAllEmployeeNameAndID() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $queryAllEmployeeDetails = "SELECT employeeID, employeeName, empID FROM tblEmployee WHERE isActive = 1";
            $result = mysqli_query($connect_var, $queryAllEmployeeDetails);
            $employees = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $employees[] = $row;
            }
            echo json_encode($employees);
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error", 
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }   
    }   
    public function AddEmployeeDetailForEmployee() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            mysqli_begin_transaction($connect_var);

            // 1. Insert into tblEmployee
            $queryEmployee = "INSERT INTO tblEmployee (
                empID, employeeName, employeePhone, employeeGender, Designation, 
                employeePassword, employeeBloodGroup, employeeDOB, isManager, 
                joiningDate, retirementDate, isActive, deviceFingerprint, organisationID
            ) VALUES (?, ?, ?, ?, ?, MD5('Password#1'), ?, ?, ?, ?, ?, 1, '', ?)";
            
            $stmt = mysqli_prepare($connect_var, $queryEmployee);
            mysqli_stmt_bind_param($stmt, "sssssssssss",
                $this->empID,
                $this->employeeName,
                $this->employeePhone,
                $this->employeeGender,
                $this->Designation,
                $this->employeeBloodGroup,
                $this->employeeDOB,
                $this->isManager,
                $this->joiningDate,
                $this->retirementDate,
                $this->organisationID
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error inserting employee: " . mysqli_error($connect_var));
            }
            
            // Get the auto-incremented employeeID
            $employeeID = mysqli_insert_id($connect_var);
            mysqli_stmt_close($stmt);

            // 2. Insert into tblmapEmp (Employee-Branch mapping)
            $queryMapEmp = "INSERT INTO tblmapEmp (employeeID, branchID) VALUES (?, ?)";
            $stmt = mysqli_prepare($connect_var, $queryMapEmp);
            mysqli_stmt_bind_param($stmt, "ii", $employeeID, $this->branchID);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error mapping employee to branch: " . mysqli_error($connect_var));
            }
            mysqli_stmt_close($stmt);

            // 3. If section is provided, insert into tblAssignedSection
            if ($this->sectionID && $this->sectionID !== "") {
                $queryAssignedSection = "INSERT INTO tblAssignedSection (employeeID, sectionID, isActive) VALUES (?, ?, 1)";
                $stmt = mysqli_prepare($connect_var, $queryAssignedSection);
                mysqli_stmt_bind_param($stmt, "ii", $employeeID, $this->sectionID);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error assigning section: " . mysqli_error($connect_var));
                }
                mysqli_stmt_close($stmt);
            }

            // 4. Insert into tblLeaveBalance
            $queryLeaveBalance = "INSERT INTO tblLeaveBalance (employeeID, casualLeave, privilegeLeave, medicalLeave) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($connect_var, $queryLeaveBalance);
            mysqli_stmt_bind_param($stmt, "iiii", $employeeID, $this->casualLeave, $this->privilegeLeave, $this->medicalLeave);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error setting leave balance: " . mysqli_error($connect_var));
            }
            mysqli_stmt_close($stmt);

            mysqli_commit($connect_var);
            
            echo json_encode([
                "status" => "success",
                "message" => "Employee added successfully",
                "employeeID" => $employeeID
            ]);

        } catch (Exception $e) {
            mysqli_rollback($connect_var);
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
}
 

function AddEmployeeDetails($decoded_items) {
    $EmployeeObject = new AddEmployeeComponent();
    if ($EmployeeObject->loadAddEmployeeDetails($decoded_items)) {
        $EmployeeObject->AddEmployeeDetailForEmployee();
    } else {
        echo json_encode([
            "status" => "error", 
            "message_text" => "Invalid Input Parameters"
        ], JSON_FORCE_OBJECT);
    }
}
function GetAllEmployeeNameAndID($decoded_items) {
    $EmployeeObject = new AddEmployeeComponent();
    if ($EmployeeObject->loadGetAllEmployeeNameAndID($decoded_items)) {
        $EmployeeObject->GetAllEmployeeNameAndID($decoded_items);
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }   
}   
?>