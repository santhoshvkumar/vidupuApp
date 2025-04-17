<?php

class DashboardComponent{
    public $employeeID;
    public $employeeRole;

    public function loadDashboardDetails(array $data){
        $this->employeeID = $data['employeeID'];
        $this->employeeRole = $data['employeeRole'];
        return true;
    }

    public function DashboardDetails(){
        include('config.inc');
        header('Content-Type: application/json');
        try{
            $queryDashboardDetails = "SELECT * FROM tblDashboardDetails";
            $rsd = mysqli_query($connect_var, $queryDashboardDetails);

            $queryDashboardDetails = "SELECT COUNT(*) FROM tblEmployee WHERE isActive != 1";
            $rsd = mysqli_query($connect_var, $queryDashboardDetails);

            $queryDashboardDetails = "SELECT COUNT(*) FROM tblEmployee WHERE isActive != 1";
            $rsd = mysqli_query($connect_var, $queryDashboardDetails);         

        } catch (Exception $e) {
            echo json_encode(array("status" => "error", "message_text" => $e->getMessage()), JSON_FORCE_OBJECT);
        }
    }

    public function DashboardAttendanceDetails(){
        include('config.inc');
        header('Content-Type: application/json');
        try{
            $queryActiveEmployeeDetails = "SELECT COUNT(*) FROM tblEmployee";
            $rowofactiveEmployees = mysqli_query($connect_var, $queryActiveEmployeeDetails);
            echo $rowofactiveEmployees;
            
            $queryCheckInDetails = "SELECT COUNT(*) FROM tblAttendance WHERE attendanceDate = CURDATE()";
            $rowofCheckInDetails = mysqli_query($connect_var, $queryCheckInDetails);
            echo $rowofCheckInDetails;
            $queryLateCheckInDetails = "SELECT COUNT(*) FROM tblAttendance WHERE checkInTime > '10:10:00' AND attendanceDate = CURDATE()";
            $rowofLateCheckInDetails = mysqli_query($connect_var, $queryLateCheckInDetails);

            $queryEarlyCheckOutDetails = "SELECT COUNT(*) FROM tblAttendance WHERE checkOutTime < '17:00:00' AND attendanceDate = CURDATE()";
            $rowofEarlyCheckoutDetails = mysqli_query($connect_var, $queryEarlyCheckOutDetails);            

            $queryLeaveDetails = "SELECT COUNT(*) FROM tblApplyLeave WHERE CURDATE() BETWEEN fromDate AND toDate";
            $rowofLeaveDetails = mysqli_query($connect_var, $queryLeaveDetails);

           // $rowofAbsentDetails = $queryActiveEmployeeDetails - $rowofLeaveDetails;
        } catch (Exception $e) {
            echo json_encode(array("status" => "error", "message_text" => $e->getMessage()), JSON_FORCE_OBJECT);
        }
    }

}

function DashboardDetails(){
    $dashboardComponent = new DashboardComponent();
   
        $dashboardComponent->DashboardAttendanceDetails();
  
}
?>