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

    public function GetMonthlyReport($decoded_items) {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $data = [];

            $queryGetEmployeeDetails = "SELECT employeeID, empID, employeeName, Designation FROM tblEmployee WHERE organisationID = ?";
            $stmt = mysqli_prepare($connect_var, $queryGetEmployeeDetails);
            mysqli_stmt_bind_param($stmt, "i", $this->organisationID);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                
                $getEmployeeID = $row['employeeID'];

                $queryGetAttendanceDetails = "select count(*) as TotalPresent, sum(isLateCheckIN) as LateCheckIN, sum(isEarlyCheckOut) as EarlyCheckOut, sum(isAutoCheckout) as AutoCheckout from tblAttendance where DATE_FORMAT(attendanceDate, '%Y-%m') = ? and employeeID=?";
                $stmt = mysqli_prepare($connect_var, $queryGetAttendanceDetails);
                mysqli_stmt_bind_param($stmt, "si", $this->selectedMonth, $getEmployeeID);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);

                $data[] = $row;
            }

            mysqli_stmt_close($stmt);
            
            echo json_encode(array("status" => "success", "data" => $data), JSON_FORCE_OBJECT);
               
        }
            
         catch (Exception $e) {
            echo json_encode(array("status" => "error", "message_text" => $e->getMessage()), JSON_FORCE_OBJECT);
        }
}


public function GetMonthlyReport() {
    $MonthlyReportObject = new MonthlyReportComponent();
    if($MonthlyReportObject->loadOrganisationID($decoded_items)) {
        $MonthlyReportObject->GetMonthlyReport($decoded_items);
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid input parameters"), JSON_FORCE_OBJECT);
    }
}


?>