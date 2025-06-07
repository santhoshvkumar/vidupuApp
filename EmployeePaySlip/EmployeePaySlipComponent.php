<?php
class EmployeePaySlipComponent {
    public $employeeID;
    public $month;
    public $year;
    /*****************  Get Employee Pay Slip  *******************/
    public function loadEmployeePaySlip(array $data) {
        if (isset($data['employeeID']) && isset($data['month']) && isset($data['year'])) {
            $this->employeeID = $data['employeeID'];
            $this->month = $data['month'];
            $this->year = $data['year'];
            return true;
        } else {
            return false;
        }
    }
    public function EmployeePaySlip() {
        include('config.inc');
        header('Content-Type: application/json');
        
        try {
            $SalarySlipQuery = "SELECT 
    employeeID,
    employeeName,
    Designation,
    branchName,
    bank_account,
    month,
    year,
    
    -- Earnings
    basic,
    da,
    hra,
    cca,
    (basic + da + hra + cca) AS grossSalary,
    
    -- Deductions
    pf,
    fbf,
    otherDeductions,
    homeLoan1,
    edfMedical,
    epfNonMedical,
    specialPersonalLoan,
    society,
    (pf + fbf + otherDeductions + homeLoan1 + edfMedical + epfNonMedical + specialPersonalLoan + society) AS totalDeductions,
    
    -- Net Pay
    (basic + da + hra + cca) - 
    (pf + fbf + otherDeductions + homeLoan1 + edfMedical + epfNonMedical + specialPersonalLoan + society) AS netSalary

FROM tblSalaryMaster
WHERE month = ? AND year = ? AND employeeID = ?;";

            $stmt = mysqli_prepare($connect_var, $SalarySlipQuery);
            mysqli_stmt_bind_param($stmt, "sii", $this->month, $this->year, $this->employeeID);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $SalarySlip = array();
            
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $SalarySlip[] = array(
                        'employeeID' => $row['employeeID'],
                        'employeeName' => $row['employeeName'],
                        'Designation' => $row['Designation'],
                        'branchName' => $row['branchName'],
                        'bank_account' => $row['bank_account'],
                        'month' => $row['month'],
                        'year' => $row['year'],
                        'basic' => $row['basic'],
                        'da' => $row['da'],
                        'hra' => $row['hra'],
                        'cca' => $row['cca'],
                        'grossSalary' => $row['grossSalary'],
                        'pf' => $row['pf'],
                        'fbf' => $row['fbf'],
                        'otherDeductions' => $row['otherDeductions'],   
                        'homeLoan1' => $row['homeLoan1'],
                        'edfMedical' => $row['edfMedical'],
                        'epfNonMedical' => $row['epfNonMedical'],
                        'specialPersonalLoan' => $row['specialPersonalLoan'],
                        'society' => $row['society'],
                        'totalDeductions' => $row['totalDeductions'],
                        'netSalary' => $row['netSalary']
                    );
                }
                
                echo json_encode(array(
                    "status" => "success",
                    "record_count" => count($SalarySlip),
                    "result" => $SalarySlip
                ));
            } else {
                echo json_encode(array(
                    "status" => "failure",
                    "message_text" => "Failed to fetch salary slip details"
                ));
            }
        } catch (Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ));
        }

        mysqli_close($connect_var);
    }    
}

function EmployeePaySlip() {
    $employeePaySlipObject = new EmployeePaySlipComponent();
    if ($employeePaySlipObject->loadEmployeePaySlip($decoded_items)) {
        $employeePaySlipObject->EmployeePaySlip();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
?>
