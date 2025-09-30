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

            $queryHolidayDetails = "SELECT 
                                        COUNT(*) AS holiday_count_till_today,
                                        DAY(CURDATE()) AS days_till_today
                                    FROM tblHoliday
                                    WHERE DATE_FORMAT(date, '%Y-%m') = ?
                                    AND date <= CURDATE()";
            $stmtHolidayDetails = mysqli_prepare($connect_var, $queryHolidayDetails);
            mysqli_stmt_bind_param($stmtHolidayDetails, "s", $this->selectedMonth);
            mysqli_stmt_execute($stmtHolidayDetails);
            $resultHolidayDetails = mysqli_stmt_get_result($stmtHolidayDetails);
            $rowHolidayDetails = mysqli_fetch_assoc($resultHolidayDetails);
            $holidayCountTillToday = $rowHolidayDetails['holiday_count_till_today'];
            $totalWorkingDaysTillToday = $rowHolidayDetails['days_till_today'];

            $queryGetEmployeeDetails = "SELECT employeeID, empID, employeeName, Designation FROM tblEmployee WHERE organisationID = ? and isActive = 1";
            $stmt = mysqli_prepare($connect_var, $queryGetEmployeeDetails);
            mysqli_stmt_bind_param($stmt, "i", $this->organisationID);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $count = 0;
            $data = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $getEmployeeID = $row['employeeID'];
                $data[$count] = $row;
                $queryGetAttendanceDetails = "select count(*) as TotalPresent, sum(isLateCheckIN) as LateCheckIN, sum(isEarlyCheckOut) as EarlyCheckOut, sum(isAutoCheckout) as AutoCheckout from tblAttendance where DATE_FORMAT(attendanceDate, '%Y-%m') = ? and employeeID=?";
               
                $stmt2 = mysqli_prepare($connect_var, $queryGetAttendanceDetails);
                mysqli_stmt_bind_param($stmt2, "si", $this->selectedMonth, $getEmployeeID);
                mysqli_stmt_execute($stmt2);
                $result2 = mysqli_stmt_get_result($stmt2);
                $rowGetAttendanceDetails = mysqli_fetch_assoc($result2);
                
                $data[$count]['TotalWorkingDays'] = (int)($totalWorkingDaysTillToday) - (int)($holidayCountTillToday);
                $data[$count]['TotalPresent'] = (int)($rowGetAttendanceDetails['TotalPresent']);
                $data[$count]['LateCheckIN'] = (int)($rowGetAttendanceDetails['LateCheckIN']);
                $data[$count]['EarlyCheckOut'] = (int)($rowGetAttendanceDetails['EarlyCheckOut']);
                $data[$count]['AutoCheckout'] = (int)($rowGetAttendanceDetails['AutoCheckout']);

                $selectedMonth = $this->selectedMonth."-01";

                $queryGetLeaveCount = "SELECT
                                            SUM(
                                                GREATEST(
                                                0,
                                                DATEDIFF(
                                                    LEAST(LEAST(al.toDate, CURDATE()), LAST_DAY(?)),
                                                    GREATEST(al.fromDate, ?)
                                                ) + 1
                                                )
                                            ) AS leave_days_till_today
                                            FROM tblApplyLeave al
                                            WHERE al.employeeID = ?
                                            AND al.status = 'Approved'
                                            AND al.fromDate <= LAST_DAY(?)
                                            AND (al.toDate IS NULL OR al.toDate >= ?);";
                $stmt3 = mysqli_prepare($connect_var, $queryGetLeaveCount);
                mysqli_stmt_bind_param($stmt3, "ssiss", $selectedMonth, $selectedMonth, $getEmployeeID, $selectedMonth, $selectedMonth);

                mysqli_stmt_execute($stmt3);
                $result3 = mysqli_stmt_get_result($stmt3);
                $rowGetLeaveCount = mysqli_fetch_assoc($result3);
                
               
                // Handle NULL values
                $leaveCount = $rowGetLeaveCount['leave_days_till_today'];

                if ($leaveCount === null) {
                    $leaveCount = 0;
                }
                $workingDays =  (int)($totalWorkingDaysTillToday) - (int)($holidayCountTillToday) - (int)$leaveCount;
                if ($workingDays < 0) {
                    $workingDays = 0;
                } else {
                    $workingDays = $workingDays;
                }
                $data[$count]['WorkingDays'] = $workingDays;
                
                $absentDays = $data[$count]['TotalWorkingDays'] - $data[$count]['TotalPresent'] - $leaveCount;
                if ($absentDays < 0) {
                    $absentDays = 0;
                } else {
                    $absentDays = $absentDays;
                }
                $data[$count]['AbsentDays'] = $absentDays;
                $data[$count]['TotalLeaves'] = (int)$leaveCount;
                $count++;
                
                mysqli_stmt_close($stmt2);
                mysqli_stmt_close($stmt3);
            }

            mysqli_stmt_close($stmt);     
            echo json_encode(array("status" => "success", "Month" => $selectedMonth, "data" => $data));
               
        }
            
         catch (Exception $e) {
            echo json_encode(array("status" => "error", "message_text" => $e->getMessage()));
        }
    }
}

function GetMonthlyReport($decoded_items) {
    $MonthlyReportObject = new MonthlyReportComponent();
    if($MonthlyReportObject->loadOrganisationID($decoded_items)) {
        $MonthlyReportObject->GetMonthlyReportComponent($decoded_items);
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"));
    }
}


?>