<?php

class ApproveRefreshmentComponent{
    public $employeeID;
    public $empID;
    public $employeeName;    
    public $Month;
    public $Year;
    public $TotalWorkingDays;
    public $ApprovedLeaveDays;
    public $EligibleDays;
    public $AmountPerDay;
    public $TotalAmount;
    public $Status;
    public $CreatedDate;
    public $Designation;
    public $approvedDate;

    public function loadApproveRefreshmentDetails(array $data){ 
        if (isset($data['Month']) && isset($data['Year'])) {
            $this->Month = $data['Month'];
            $this->Year = $data['Year'];
            return true;
        } else {
            return false;
        }
    }
    public function loadApproveRefreshmentDetailsByID(array $data){ 
        if (isset($data['empID']) && isset($data['Status'])) {
            $this->empID = $data['empID'];
            $this->Status = $data['Status'];
            return true;
        } else {
            return false;
        }
    }

    public function GetAllEmployeeRefreshmentDetails($data) {
        include('config.inc');
        header('Content-Type: application/json');
        
        try {
            // Validate required fields
            if (!isset($data['Month']) || !isset($data['Year'])) {
                throw new Exception("Month and Year are required");
            }

            $month = $data['Month'];
            $year = $data['Year'];

            // Get all employee refreshment details for the specified month and year
            $query = "SELECT 
    r.EmployeeID, 
    e.employeeName, 
    e.empID,
    r.Month,
    r.Year,
    r.TotalWorkingDays,
    r.ApprovedLeaveDays,
    r.EligibleDays,
    r.AmountPerDay,
    r.TotalAmount,
    r.Status,
    r.CreatedDate,
    r.approvedDate
FROM 
    tblrefreshment r
JOIN 
    tblEmployee e ON r.EmployeeID = e.employeeID WHERE r.Month = ? AND r.Year = ?";

            $stmt = mysqli_prepare($connect_var, $query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . mysqli_error($connect_var));
            }

            mysqli_stmt_bind_param($stmt, "ss", $month, $year);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to execute statement: " . mysqli_error($connect_var));
            }

            $result = mysqli_stmt_get_result($stmt);
            $data = array();

            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = array(
                    "EmployeeID" => $row['EmployeeID'],
                    "employeeName" => $row['employeeName'],
                    "empID" => $row['empID'],
                    "Month" => $row['Month'],
                    "Year" => $row['Year'],
                    "TotalWorkingDays" => $row['TotalWorkingDays'],
                    "ApprovedLeaveDays" => $row['ApprovedLeaveDays'],
                    "EligibleDays" => $row['EligibleDays'],
                    "AmountPerDay" => $row['AmountPerDay'],
                    "TotalAmount" => $row['TotalAmount'],
                    "Status" => $row['Status'],
                    "CreatedDate" => $row['CreatedDate'],
                    "approvedDate" => $row['approvedDate']
                );
            }

            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);

            echo json_encode(array(
                "status" => "success",
                "data" => $data
            ));

        } catch (Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message" => $e->getMessage()
            ));
        }
    }
    public function ApproveEmployeeRefreshmentDetailsByID($data) {
        include('config.inc');
        header('Content-Type: application/json');   
        try {
            if (!isset($data['empID'])) {
                throw new Exception("EmployeeID is required");
            }
            $empID = $data['empID'];      
            $Status = $data['Status'];
        $query = "UPDATE tblrefreshment SET Status = ? , approvedDate = CURDATE() WHERE EmployeeID =(SELECT employeeID FROM tblEmployee WHERE empID = ?)";
        $stmt = mysqli_prepare($connect_var, $query);
        mysqli_stmt_bind_param($stmt, "si", $Status, $empID);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($connect_var);
        echo json_encode(array(
            "status" => "success",
            "message" => "Employee Refreshment Details Approved Successfully"
        ));
    } catch (Exception $e) {
        echo json_encode(array(
            "status" => "error",
            "message" => $e->getMessage()
        ));
    }
    }
}        

function GetAllEmployeeRefreshmentDetails($decoded_items) {
    $ApproveRefreshmentComponent = new ApproveRefreshmentComponent();
    if ($ApproveRefreshmentComponent->loadApproveRefreshmentDetails($decoded_items)) {
        $ApproveRefreshmentComponent->GetAllEmployeeRefreshmentDetails();
    } else {    
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function ApproveEmployeeRefreshmentDetailsByID($decoded_items) {
    $ApproveRefreshmentComponent = new ApproveRefreshmentComponent();
    if ($ApproveRefreshmentComponent->loadApproveRefreshmentDetailsByID($decoded_items)) {
        $ApproveRefreshmentComponent->ApproveEmployeeRefreshmentDetailsByID();
    } else {    
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
?>

