<?php

class EmployeePaySlipComponent {
    private $employeeID;
    private $month;
    private $year;
    /*****************  Get Employee Pay Slip  *******************/
    public function loadEmployeePaySlipDetails(array $data): bool {
        if (isset($data['employeeID']) && isset($data['month']) && isset($data['year'])) {
            $this->employeeID = $data['employeeID'];
            $this->month = $data['month'];
            $this->year = $data['year'];
            return true;
        }
        return false;
    }
    public function getEmployeePaySlipDetails(): void {
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
                basic,
                da,
                hra,
                cca,
                (basic + da + hra + cca) AS grossSalary,
                pf,
                fbf,
                otherDeductions,
                homeLoan1,
                edfMedical,
                epfNonMedical,
                specialPersonalLoan,
                society,
                (pf + fbf + otherDeductions + homeLoan1 + edfMedical + epfNonMedical + specialPersonalLoan + society) AS totalDeductions,
                (basic + da + hra + cca) - 
                (pf + fbf + otherDeductions + homeLoan1 + edfMedical + epfNonMedical + specialPersonalLoan + society) AS netSalary
            FROM tblSalaryMaster
            WHERE month = ? AND year = ? AND employeeID = ?";

            $stmt = mysqli_prepare($connect_var, $SalarySlipQuery);
            if (!$stmt) {
                throw new \Exception("Failed to prepare statement: " . mysqli_error($connect_var));
            }

            mysqli_stmt_bind_param($stmt, "sii", $this->month, $this->year, $this->employeeID);
            if (!mysqli_stmt_execute($stmt)) {
                throw new \Exception("Failed to execute statement: " . mysqli_stmt_error($stmt));
            }

            $result = mysqli_stmt_get_result($stmt);
            $SalarySlip = [];
            
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $SalarySlip[] = [
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
                    ];
                }
                
                echo json_encode([
                    "status" => "success",
                    "record_count" => count($SalarySlip),
                    "result" => $SalarySlip
                ]);
            } else {
                echo json_encode([
                    "status" => "failure",
                    "message_text" => "Failed to fetch salary slip details"
                ]);
            }
        } catch (\Exception $e) {
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ]);
        } finally {
            if (isset($stmt)) {
                mysqli_stmt_close($stmt);
            }
            if (isset($connect_var)) {
                mysqli_close($connect_var);
            }
        }
    }    
}

function EmployeePaySlip(array $decoded_items): void {
    $employeePaySlipObject = new EmployeePaySlipComponent();
    if ($employeePaySlipObject->loadEmployeePaySlipDetails($decoded_items)) {
        $employeePaySlipObject->getEmployeePaySlipDetails();
    } else {
        echo json_encode([
            "status" => "error",
            "message_text" => "Invalid Input Parameters"
        ], JSON_FORCE_OBJECT);
    }
}
?>
