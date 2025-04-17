<?php

class DashboardComponent{
    public $employeeID;
    public $employeeRole;

    public function loadDashboardDetails(array $data){
        $this->employeeID = $data['employeeID'];
        $this->employeeRole = $data['employeeRole'];
        return true;
    }

    public function getActiveEmployees(){
        include('config.inc');
        header('Content-Type: application/json');
        $queryActiveEmployees = "SELECT  count(*) as totalActiveEmployees FROM tblEmployee WHERE isActive = 1";
        $rsd = mysqli_query($connect_var, $queryActiveEmployees);
        $row = mysqli_fetch_assoc($rsd);
        return $row['totalActiveEmployees'];
    }

    function ActiveEmployees(array $data){
        $dashboardComponent = new DashboardComponent(); 
        if($dashboardComponent->loadDashboardDetails($data)){
            $dashboardComponent->getActiveEmployees();
        }else{
            echo json_encode(array("status"=>"error","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
        }
    }




    
    public function DashboardDetails(){
        include('config.inc');
        header('Content-Type: application/json');
            $queryDashboardDetails = "SELECT * FROM tblDashboardDetails";
        $rsd = mysqli_query($connect_var, $queryDashboardDetails);
        $row = mysqli_fetch_assoc($rsd);
        return $row;
    }

}

function DashboardDetails(array $data){
    $dashboardComponent = new DashboardComponent();
    if($dashboardComponent->loadDashboardDetails($data)){
        $dashboardComponent->DashboardDetails();
    } else {
        echo json_encode(array("status"=>"error","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
}