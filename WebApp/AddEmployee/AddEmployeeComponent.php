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
    public function loadAddEmployeeDetails(array $data){
        if (isset($data['empID']) && isset($data['employeeName']) && isset($data['employeePhone']) && isset($data['employeeGender']) && isset($data['Designation']) && isset($data['employeePassword']) && isset($data['employeeBloodGroup']) && isset($data['employeeDOB'])) {
            $this->empID = $data['empID'];
            $this->employeeName = $data['employeeName'];
            $this->employeePhone = $data['employeePhone'];
            $this->employeeGender = $data['employeeGender'];
            $this->Designation = $data['Designation'];
            $this->employeePassword = $data['employeePassword'];
            $this->employeeBloodGroup = $data['employeeBloodGroup'];
            $this->employeeDOB = $data['employeeDOB'];
            $this->isManager = $data['isManager'];
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
            $queryAllEmployeeDetails = "SELECT employeeID, employeeName FROM tblEmployee WHERE isActive = 1";
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
            $data = [];
    
            // 1. Get all active employees Name, ID and BranchID
            $queryAllEmployeeDetails = "INSERT INTO tblEmployee (empID, employeeName, employeePhone, employeeGender, Designation, employeePassword, employeeBloodGroup, employeeDOB, isManager) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);";
            $stmt = mysqli_prepare($connect_var, $queryAllEmployeeDetails);
            mysqli_stmt_bind_param($stmt, "sssssssss",
                $this->empID,
                $this->employeeName,
                $this->employeePhone,
                $this->employeeGender,
                $this->Designation,
                $this->employeePassword,
                $this->employeeBloodGroup,
                $this->employeeDOB,
                $this->isManager
            );

            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(array(
                    "status" => "success",
                    "message" => "Employee added successfully"
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error adding employee"
                ));
            }
            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error", 
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
}
 

function AddEmployeeDetails($decoded_items) {
    $EmployeeObject = new AddEmployeeComponent();
    if ($EmployeeObject->loadAddEmployeeDetails($decoded_items)) {
        $EmployeeObject->AddEmployeeDetailForEmployee($decoded_items);
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
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