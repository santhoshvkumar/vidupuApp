<?php

class MonthlyReportComponent {
    public $organisationID;
    public $selectedMonth;
    public function loadOrganisationID($decoded_items) {
        if (isset($decoded_items['organisationID']) && isset($decoded_items['selectedMonth'])) {
            $this->organisationID = $decoded_items['organisationID'];
            $this->selectedMonth = $decoded_items['selectedMonth'];
            return true;
        }
        return false;
    }

    public function GetMonthlyReportComponent($decoded_items) {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $data = [];

            $queryGetEmployeeDetails = "SELECT employeeID, empID, employeeName, Designation FROM tblEmployee WHERE organisationID = ? and isActive = 1 and employeeID = 1";
            echo "Query 1: " . $queryGetEmployeeDetails . "\n";
            echo "Parameter 1 - organisationID: " . $this->organisationID . "\n";
            $stmt = mysqli_prepare($connect_var, $queryGetEmployeeDetails);
            mysqli_stmt_bind_param($stmt, "i", $this->organisationID);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $count = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $getEmployeeID = $row['employeeID'];
                $data[$count] = $row;
                $queryGetAttendanceDetails = "select count(*) as TotalPresent, sum(isLateCheckIN) as LateCheckIN, sum(isEarlyCheckOut) as EarlyCheckOut, sum(isAutoCheckout) as AutoCheckout from tblAttendance where DATE_FORMAT(attendanceDate, '%Y-%m') = ? and employeeID=?";
                echo "Query 2: " . $queryGetAttendanceDetails . "\n";
                echo "Parameter 2 - selectedMonth: " . $this->selectedMonth . "\n";
                echo "Parameter 2 - employeeID: " . $getEmployeeID . "\n";
              
                $stmt2 = mysqli_prepare($connect_var, $queryGetAttendanceDetails);
                mysqli_stmt_bind_param($stmt2, "si", $this->selectedMonth, $getEmployeeID);
                mysqli_stmt_execute($stmt2);
                $result2 = mysqli_stmt_get_result($stmt2);
                $rowGetAttendanceDetails = mysqli_fetch_assoc($result2);
                
                
                $data[$count]['TotalPresent'] = $rowGetAttendanceDetails['TotalPresent'];
                $data[$count]['LateCheckIN'] = $rowGetAttendanceDetails['LateCheckIN'];
                $data[$count]['EarlyCheckOut'] = $rowGetAttendanceDetails['EarlyCheckOut'];
                $data[$count]['AutoCheckout'] = $rowGetAttendanceDetails['AutoCheckout'];


                $queryGetLeaveCount = "SELECT
                                        SUM(
                                            CASE
                                            WHEN typeOfLeave = 'Medical Leave' THEN GREATEST(
                                                0,
                                                DATEDIFF(LEAST(toDate, CURDATE()), fromDate) + 1
                                            )
                                            ELSE leaveDuration
                                            END
                                        ) AS leave_days_till_today
                                        FROM
                                        tblApplyLeave
                                        WHERE
                                        employeeID = ?
                                        AND status = 'Approved'
                                        AND DATE_FORMAT(fromDate, '%Y-%m') = ?";
                $stmt3 = mysqli_prepare($connect_var, $queryGetLeaveCount);
                mysqli_stmt_bind_param($stmt3, "si", $getEmployeeID, $this->selectedMonth);
                mysqli_stmt_execute($stmt3);
                $result3 = mysqli_stmt_get_result($stmt3);
                $rowGetLeaveCount = mysqli_fetch_assoc($result3);
                
                echo "Raw leave count result: ";
                var_dump($rowGetLeaveCount);
                echo "\n";
                echo "leave_days_till_today value: ";
                var_dump($rowGetLeaveCount['leave_days_till_today']);
                echo "\n";
                echo "Type of leave_days_till_today: " . gettype($rowGetLeaveCount['leave_days_till_today']) . "\n";
                
                // Handle NULL values
                $leaveCount = $rowGetLeaveCount['leave_days_till_today'];
                if ($leaveCount === null) {
                    $leaveCount = 0;
                    echo "Converted NULL to 0\n";
                }
                
                $data[$count]['TotalLeaves'] = $leaveCount;
                echo "Final TotalLeaves value: " . $data[$count]['TotalLeaves'] . "\n";
                echo "Final TotalLeaves type: " . gettype($data[$count]['TotalLeaves']) . "\n\n";

                $count++;
                
                mysqli_stmt_close($stmt2);
                mysqli_stmt_close($stmt3);
            }

            mysqli_stmt_close($stmt);
            
            echo "=== FINAL DATA ARRAY ===\n";
            echo "Number of employees processed: " . count($data) . "\n";
            foreach ($data as $index => $employee) {
                echo "Employee " . ($index + 1) . " - TotalLeaves: " . $employee['TotalLeaves'] . " (type: " . gettype($employee['TotalLeaves']) . ")\n";
            }
            echo "=== END FINAL DATA ===\n\n";
            
            echo json_encode(array("status" => "success", "data" => $data), JSON_FORCE_OBJECT);
               
        }
            
         catch (Exception $e) {
            echo json_encode(array("status" => "error", "message_text" => $e->getMessage()), JSON_FORCE_OBJECT);
        }
    }
}

function GetMonthlyReport($decoded_items) {
    $MonthlyReportObject = new MonthlyReportComponent();
    if($MonthlyReportObject->loadOrganisationID($decoded_items)) {
        $MonthlyReportObject->GetMonthlyReportComponent($decoded_items);
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
}


?>