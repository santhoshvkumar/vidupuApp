<?php

class AdvanceComponent {
    public $advanceTitle;
    public $employeeID;
    public $advanceAmount;

    public function loadAdvanceDetails($decoded_items) {
        $this->advanceTitle = $decoded_items['advanceTitle'];
        $this->employeeID = $decoded_items['employeeId'];
        $this->advanceAmount = $decoded_items['advanceAmount'];
        return true;
    }

    public function applyForAdvance($decoded_items) {
        include('config.inc');
        header('Content-Type: application/json');
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        try {
            $queryAdvanceAlreadyExists = "SELECT * FROM tblAdvances WHERE employeeID = ? AND advanceTitle = ?";
            $stmt = mysqli_prepare($connect_var, $queryAdvanceAlreadyExists);
            mysqli_stmt_bind_param($stmt, "si", $this->employeeID, $this->advanceTitle);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $isAdvanceAlreadyExists = false;
            if(mysqli_num_rows($result) > 0) {
                $isAdvanceAlreadyExists = true;
            }
            if(mysqli_num_rows($result) == 0) {
                $query = "INSERT INTO tblAdvances (advanceTitle, employeeID, advanceAmount, createdON) VALUES (?, ?, ?, NOW())";
                $stmt = mysqli_prepare($connect_var, $query);
                mysqli_stmt_bind_param($stmt, "sii", $this->advanceTitle, $this->employeeID, $this->advanceAmount);
                mysqli_stmt_execute($stmt);
                mysqli_close($connect_var);
            }
            echo json_encode(array(
                "status" => "success",
                "isAdvanceAlreadyExists" => $isAdvanceAlreadyExists,
                "message_text" => "Advance applied successfully"
                ));
        } catch (Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ), JSON_FORCE_OBJECT);
        }       
    }
}

function applyForAdvance($decoded_items) {
    $advanceObject = new AdvanceComponent();
    if($advanceObject->loadAdvanceDetails($decoded_items)) {
        $advanceObject->applyForAdvance($decoded_items);
    } else {
        echo json_encode(array(
            "status" => "error",
            "message_text" => "Invalid input parameters"
        ), JSON_FORCE_OBJECT);
    }
}