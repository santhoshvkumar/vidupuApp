<?php

class CheckInLockedComponent{
    public $organisationID;
    public function loadOrganisationID(array $data) {
        $this->organisationID = $data['organisationID'];
        return true;
    }
    public function getAllCheckInLockedEmployees() {
        include('config.inc');
        $query = "SELECT * FROM tblEmployee WHERE isCheckInLocked = 1";
        $result = mysqli_query($connect_var, $query);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        if(count($data) > 0) {
        echo json_encode([
                "status" => "success",
                "data" => $data
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message_text" => "No data found"
            ], JSON_FORCE_OBJECT);
        }
    }
}

function getAllCheckInLockedEmployees($decoded_items) {
    $CheckInLockedObject = new CheckInLockedComponent();
    if ($CheckInLockedObject->loadOrganisationID($decoded_items)) {
        $CheckInLockedObject->getAllCheckInLockedEmployees();
    } else {
        echo json_encode(array("status"=>"error This value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
}
?>