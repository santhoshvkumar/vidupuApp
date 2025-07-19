<?php

/*****************   Create Restricted Leave  *******************/
$f3->route('GET /createRestrictedLeave',

    function($f3){
            header('Content-Type: application/json');
            $decoded_items = $f3->get('GET');
            if(!$decoded_items == NULL)
                createRestrictedLeaveTemp($decoded_items);
            else
                echo json_encode(array("status"=>"error Create Restricted Leave","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);

    }

);
/*****************  End Create Restricted Leave *****************/

/*****************   Get All Restricted Leaves  *******************/
$f3->route('GET /getAllRestrictedLeaves',

    function($f3){
            header('Content-Type: application/json');
            $decoded_items = $f3->get('GET');
            // For fetch operations, we don't need to validate parameters
            getAllRestrictedLeavesTemp($decoded_items);

    }

);
/*****************  End Get All Restricted Leaves *****************/

/*****************   Get All Published Restricted Leave  *******************/
$f3->route('GET /getAllPublishedRestrictedLeave',

    function($f3){
            header('Content-Type: application/json');
            $decoded_items = $f3->get('GET');
            // For fetch operations, we don't need to validate parameters
            getAllPublishedRestrictedLeaveTemp($decoded_items);

    }

);
/*****************  End Get All Published Restricted Leave *****************/

/*****************   Update Restricted Leave  *******************/
$f3->route('GET /updateLeaveRestriction',

    function($f3){
            header('Content-Type: application/json');
            $decoded_items = $f3->get('GET');
            if(!$decoded_items == NULL)
                updateLeaveRestrictionTemp($decoded_items);
            else
                echo json_encode(array("status"=>"error Update Restricted Leave","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);

    }

);
/*****************  End Update Restricted Leave *****************/

/*****************   Delete Restricted Leave  *******************/
$f3->route('GET /deleteLeaveRestriction',

    function($f3){
            header('Content-Type: application/json');
            $decoded_items = $f3->get('GET');
            if(!$decoded_items == NULL)
                deleteLeaveRestrictionTemp($decoded_items);
            else
                echo json_encode(array("status"=>"error Delete Restricted Leave","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);

    }

);
/*****************  End Delete Restricted Leave *****************/

/*****************   Publish Restricted Leave  *******************/
$f3->route('GET /publishRestrictedLeave',

    function($f3){
            header('Content-Type: application/json');
            $decoded_items = $f3->get('GET');
            if(!$decoded_items == NULL)
                publishRestrictedLeaveTemp($decoded_items);
            else
                echo json_encode(array("status"=>"error Publish Restricted Leave","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);

    }

);
/*****************  End Publish Restricted Leave *****************/

/*****************   Unpublish Restricted Leave  *******************/
$f3->route('GET /unPublishRestrictedLeave',

    function($f3){
            header('Content-Type: application/json');
            $decoded_items = $f3->get('GET');
            if(!$decoded_items == NULL)
                unPublishRestrictedLeaveTemp($decoded_items);
            else
                echo json_encode(array("status"=>"error Unpublish Restricted Leave","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);

    }

);
/*****************  End Unpublish Restricted Leave *****************/

?>
