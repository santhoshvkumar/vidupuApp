<?php

class ApproveRefreshmentComponent{
    public $employeeID;
    public $empID;
    public $employeeName;    
    public $month;
    public $year;
    public $TotalWorkingDays;
    public $ApprovedLeaveDays;
    public $EligibleDays;
    public $AmountPerDay;
    public $TotalAmount;
    public $Status;
    public $CreatedDate;
    public $Designation;
    public $approvedDate;
    public $organisationID;
    public $fromDate;
    public $toDate;

    public function loadRefreshmentData(array $data) {      
        if (isset($data['fromDate']) && isset($data['toDate']) && isset($data['organisationID'])) {
            $this->fromDate = $data['fromDate'];
            $this->toDate = $data['toDate'];
            $this->organisationID = $data['organisationID'];
            
            // Extract month and year from fromDate
            $date = new DateTime($this->fromDate);
            $this->month = $date->format('m'); // Numeric month (01, 02, etc.)
            $this->year = $date->format('Y'); // 4-digit year
            
            echo "Data loaded successfully. Month: " . $this->month . ", Year: " . $this->year . "<br>";
            return true;
        }
        echo "Data loading failed - missing required parameters<br>";
        return false;
    }

    public function GetRefreshmentAllowancesByOrganisationID() {
        include('config.inc');
        header('Content-Type: application/json');

        try {
            $data = [];         
            // Get Refreshment Allowances By Organisation ID
            $query = "SELECT 
    e.employeeID,
    e.empID,
    e.employeeName,
    e.isWashingAllowance,
    e.isPhysicallyHandicapped,
    w.noOfWorkingDays,
    w.noOfDays AS totalCalendarDays,
    IFNULL(l.leaveDaysInMonth, 0) AS leaveDaysInMonth,
    w.noOfWorkingDays - IFNULL(l.leaveDaysInMonth, 0) AS eligibleDays,

    -- TotalRefreshmentAmount
    (w.noOfWorkingDays - IFNULL(l.leaveDaysInMonth, 0)) * 90 AS TotalRefreshmentAmount,

    -- WashingAllowanceAmount
    CASE 
        WHEN e.isWashingAllowance = 1 THEN (w.noOfWorkingDays - IFNULL(l.leaveDaysInMonth, 0)) * 25
        ELSE 0
    END AS WashingAllowanceAmount,

    -- PhysicallyChallangedAllowance
    CASE 
        WHEN e.isPhysicallyHandicapped = 1 THEN 2500
        ELSE 0
    END AS PhysicallyChallangedAllowance,

    -- TotalAllowances = sum of all 3
    (
        ((w.noOfWorkingDays - IFNULL(l.leaveDaysInMonth, 0)) * 90) +
        CASE 
            WHEN e.isWashingAllowance = 1 THEN (w.noOfWorkingDays - IFNULL(l.leaveDaysInMonth, 0)) * 25
            ELSE 0
        END +
        CASE 
            WHEN e.isPhysicallyHandicapped = 1 THEN 2500
            ELSE 0
        END
    ) AS TotalAllowances

FROM tblEmployee e

JOIN tblOrganisation o ON e.organisationID = o.organisationID

LEFT JOIN tblworkingdays w 
    ON w.month = ? AND w.year = ?

LEFT JOIN (
    SELECT 
        a.employeeID,
        SUM(
            DATEDIFF(
                LEAST(a.toDate, ?),
                GREATEST(a.fromDate, ?)
            ) + 1
        ) AS leaveDaysInMonth
    FROM tblApplyLeave a
    WHERE 
        a.status = 'Approved' AND
        a.fromDate <= ? AND
        a.toDate >= ?
    GROUP BY a.employeeID
) l ON l.employeeID = e.employeeID

WHERE 
    e.organisationID = ? AND 
    e.isActive = 1 AND 
    e.isTemporary = 0";
            
            $stmt = mysqli_prepare($connect_var, $query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . mysqli_error($connect_var));
            }
            
            mysqli_stmt_bind_param($stmt, "ssssssi", $this->month, $this->year, $this->fromDate, $this->toDate, $this->fromDate, $this->toDate, $this->organisationID);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to execute statement: " . mysqli_error($connect_var));
            }
            $result = mysqli_stmt_get_result($stmt);
            if (!$result) {
                throw new Exception("Failed to get result: " . mysqli_error($connect_var));
            }
            $RefreshmentAllowances = [];
            
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            mysqli_stmt_close($stmt);
            
            if (count($data) > 0) {
                echo json_encode([
                    "status" => "success",  
                    "data" => $data
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => "No refreshment allowances found"
                ], JSON_FORCE_OBJECT);
            }           
           
        } catch (Exception $e) {
            error_log("Error in GetRefreshmentAllowances: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        } finally {
            if (isset($connect_var)) {
                mysqli_close($connect_var);
            }
        }
    }
}
function GetRefreshmentAllowancesByOrganisationID($decoded_items) {
    $ApproveRefreshmentComponent = new ApproveRefreshmentComponent();
    if ($ApproveRefreshmentComponent->loadRefreshmentData($decoded_items)) {
        $ApproveRefreshmentComponent->GetRefreshmentAllowancesByOrganisationID();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }       
}

