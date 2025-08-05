<?php

/*****************   Get Newspapers (GET endpoints for frontend compatibility)  *******************/
$f3->route('GET /newspapers',
    function($f3) {
        header('Content-Type: application/json');
        echo json_encode(array(
            "status" => "success",
            "data" => [],
            "message" => "Newspapers endpoint available"
        ));
    }
);

$f3->route('GET /newspaper',
    function($f3) {
        header('Content-Type: application/json');
        echo json_encode(array(
            "status" => "success",
            "data" => [],
            "message" => "Newspaper endpoint available"
        ));
    }
);

$f3->route('GET /GetNewspapers',
    function($f3) {
        header('Content-Type: application/json');
        echo json_encode(array(
            "status" => "success",
            "data" => [],
            "message" => "GetNewspapers endpoint available"
        ));
    }
);

$f3->route('GET /GetNewspaper',
    function($f3) {
        header('Content-Type: application/json');
        echo json_encode(array(
            "status" => "success",
            "data" => [],
            "message" => "GetNewspaper endpoint available"
        ));
    }
);

$f3->route('GET /newspapers/*',
    function($f3) {
        header('Content-Type: application/json');
        echo json_encode(array(
            "status" => "success",
            "data" => [],
            "message" => "Newspapers wildcard endpoint available"
        ));
    }
);

$f3->route('GET /newspaper/*',
    function($f3) {
        header('Content-Type: application/json');
        echo json_encode(array(
            "status" => "success",
            "data" => [],
            "message" => "Newspaper wildcard endpoint available"
        ));
    }
);
/*****************  End Get Newspapers *****************/

/*****************   Get Newspaper Allowances By Organisation ID  *******************/
$f3->route('POST /GetNewspaperAllowancesByOrganisationID',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!is_null($decoded_items)) {
            $newspaperObject = new NewspaperMaster();
            $newspaperObject->getNewspaperAllowancesByOrganisationID($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ));
        }
    }
);
/*****************  End Get Newspaper Allowances By Organisation ID *****************/

/*****************   Approve Newspaper Allowance  *******************/
$f3->route('POST /ApproveNewspaperAllowance',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!is_null($decoded_items)) {
            $newspaperObject = new NewspaperMaster();
            $newspaperObject->approveNewspaperAllowance($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ));
        }
    }   
);
/*****************  End Approve Newspaper Allowance *****************/

/*****************   Reject Newspaper Allowance  *******************/
$f3->route('POST /RejectNewspaperAllowance',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!is_null($decoded_items)) {
            $newspaperObject = new NewspaperMaster();
            $newspaperObject->rejectNewspaperAllowance($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ));
        }
    }   
);
/*****************  End Reject Newspaper Allowance *****************/

/*****************   Delete Newspaper Allowance  *******************/
$f3->route('POST /DeleteNewspaperAllowance',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!is_null($decoded_items)) {
            $newspaperObject = new NewspaperMaster();
            $newspaperObject->deleteNewspaperAllowance($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ));
        }
    }   
);
/*****************  End Delete Newspaper Allowance *****************/

/*****************   Bulk Approve Newspaper Allowances  *******************/
$f3->route('POST /BulkApproveNewspaperAllowances',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!is_null($decoded_items)) {
            $newspaperObject = new NewspaperMaster();
            $newspaperObject->bulkApproveNewspaperAllowances($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ));
        }
    }   
);
/*****************  End Bulk Approve Newspaper Allowances *****************/

/*****************   Catch-all Newspaper GET endpoint  *******************/
$f3->route('GET /@path',
    function($f3) {
        $path = $f3->get('PARAMS.path');
        if (stripos($path, 'newspaper') !== false) {
            header('Content-Type: application/json');
            echo json_encode(array(
                "status" => "success",
                "data" => [],
                "message" => "Newspaper catch-all endpoint available for: " . $path
            ));
        }
    }
);
/*****************  End Catch-all Newspaper GET endpoint *****************/

/*****************   Get Newspaper Details (for React Native app)  *******************/
$f3->route('GET /NewspaperDetails',
    function($f3) {
        header('Content-Type: application/json');
        try {
            include('config.inc');
            
            $query = "SELECT newspaperID as id, newspaperName as name, cost FROM tblNewspaper ORDER BY newspaperName";
            $result = mysqli_query($connect_var, $query);
            
            if (!$result) {
                throw new Exception("Database query failed: " . mysqli_error($connect_var));
            }
            
            $newspapers = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $newspapers[] = $row;
            }
            
            echo json_encode(array(
                "status" => "success",
                "result" => $newspapers,
                "message" => "Newspaper details retrieved successfully"
            ));
            
        } catch (Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message" => $e->getMessage()
            ));
        }
    }
);
/*****************  End Get Newspaper Details *****************/

/*****************   Submit Newspaper Allowance (for React Native app)  *******************/
$f3->route('POST /SubmitNewspaperAllowance',
    function($f3) {
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'), true);
        if (!is_null($decoded_items)) {
            $newspaperObject = new NewspaperMaster();
            $newspaperObject->submitNewspaperAllowance($decoded_items);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Invalid input parameters"
            ));
        }
    }
);
/*****************  End Submit Newspaper Allowance *****************/

/*****************   Get Newspaper History (for React Native app)  *******************/
$f3->route('GET /NewspaperHistory',
    function($f3) {
        header('Content-Type: application/json');
        $employeeID = $f3->get('GET.employeeID');
        if ($employeeID) {
            $newspaperObject = new NewspaperMaster();
            $newspaperObject->getNewspaperHistory($employeeID);
        } else {
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Employee ID is required"
            ));
        }
    }
);
/*****************  End Get Newspaper History *****************/

?> 