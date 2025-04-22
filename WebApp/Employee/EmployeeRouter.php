<?php

/*****************   Login User Temp  *******************/
$f3->route('GET /EmployeeDetails',

    function($f3){
            header('Content-Type: application/json');
            $decoded_items = json_decode($f3->get('BODY'),true);
            if(!$decoded_items == NULL)
                DashboardDetails($decoded_items);
            else
                echo json_encode(array("status"=>"error Here for Dashboard Value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);

    }
);
$f3->route('GET /AllEmployeeDetails',

    function($f3){
                EmployeeDetails();

    }
);


$f3->route('POST /UpdateEmployeeDetails',

    function($f3){
            header('Content-Type: application/json');
            $decoded_items = json_decode($f3->get('BODY'),true);
            if(!$decoded_items == NULL)
                UpdateEmployeeDetails($decoded_items);
            else
                echo json_encode(array("status"=>"error Login Value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);

    }

);
$f3->route('POST /GetEmployeeDetails',

    function($f3){
            header('Content-Type: application/json');
            $decoded_items = json_decode($f3->get('BODY'),true);
            if(!$decoded_items == NULL)
                GetEmployeeDetails($decoded_items);
            else
                echo json_encode(array("status"=>"error Login Value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);

    }

);

$f3->route('POST /GetEmployeeBasedOnBranch',

    function($f3){
            header('Content-Type: application/json');
            $decoded_items = json_decode($f3->get('BODY'),true);
            if(!$decoded_items == NULL)
                GetEmployeeBasedOnBranch($decoded_items);
            else
                echo json_encode(array("status"=>"error Login Value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);

    }

);



/*****************  End Login User Temp *****************/
?>