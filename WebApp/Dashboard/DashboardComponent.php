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
        }
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