<?php

class LeaveReasonComponent{
    public $reasonID;
    public $reasonType;
    public $reasonDetails;
    public $createdOn;
    public $createdBy;
    public $isActive;

    public function loadLeaveReasonDetails(array $data){ 
        if (isset($data['reasonType']) && isset($data['reasonDetails'])) {
            $this->reasonType = $data['reasonType'];
            $this->reasonDetails = $data['reasonDetails'];
            return true;
        } else {
            return false;
        }
    }
    public function GetAllLeaveReasonDetails() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
    
            // 1. Get all active employees Name, ID and BranchID
            $queryGetLeaveReasonDetails = "SELECT   
    tblR.reasonID,
    tblR.reasonType,
    tblR.reasonDetails,
    tblR.createdOn,
    tblR.createdBy,
    tblR.isActive
FROM 
    tblReason tblR
;
";
            $result = mysqli_query($connect_var, $queryGetLeaveReasonDetails);            

            // Initialize an array to hold all leave reason details
            $LeaveReason = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row; // Add each row to the leave reason array
            }
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
    public function AddEmployeeDetailForEmployee() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            mysqli_begin_transaction($connect_var);

            // 1. Insert into tblEmployee
            $queryEmployee = "INSERT INTO tblEmployee (
                empID, employeeName, employeePhone, employeeGender, Designation, 
                employeePassword, employeeBloodGroup, employeeDOB, isManager, 
                joiningDate, retirementDate, isActive, deviceFingerprint
            ) VALUES (?, ?, ?, ?, ?, MD5('Password#1'), ?, ?, ?, ?, ?, 1, '')";
            
            $stmt = mysqli_prepare($connect_var, $queryEmployee);
            mysqli_stmt_bind_param($stmt, "ssssssssss",
                $this->empID,
                $this->employeeName,
                $this->employeePhone,
                $this->employeeGender,
                $this->Designation,
                $this->employeeBloodGroup,
                $this->employeeDOB,
                $this->isManager,
                $this->joiningDate,
                $this->retirementDate
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
function GetAllLeaveReasonDetails() {
    $LeaveReasonComponent = new LeaveReasonComponent();
    $LeaveReasonComponent->GetAllLeaveReasonDetails();
}
?>

