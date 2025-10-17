<?php

/*****************   Create Restricted Leave  *******************/
$f3->route('POST /createRestrictedLeave',

    function($f3){
            header('Content-Type: application/json');
            $decoded_items = json_decode($f3->get('BODY'),true);
            if(!$decoded_items == NULL)
                createRestrictedLeaveTemp($decoded_items);
            else
                echo json_encode(array("status"=>"error Create Restricted Leave","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);

    }

);
/*****************  End Create Restricted Leave *****************/

/*****************   Get All Restricted Leaves  *******************/
$f3->route('POST /getAllRestrictedLeaves',

    function($f3){
            header('Content-Type: application/json');
            $decoded_items = json_decode($f3->get('BODY'),true);
            // For fetch operations, we don't need to validate parameters
            if(!$decoded_items == NULL)
                getAllRestrictedLeavesTemp($decoded_items);
            else
                echo json_encode(array("status"=>"error Get All Restricted Leaves","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);

    }

);
/*****************  End Get All Restricted Leaves *****************/


/*****************   Update Restricted Leave  *******************/
$f3->route('POST /updateLeaveRestriction',

    function($f3){
            header('Content-Type: application/json');
            $decoded_items = json_decode($f3->get('BODY'),true);
            if(!$decoded_items == NULL)
                updateLeaveRestrictionTemp($decoded_items);
            else
                echo json_encode(array("status"=>"error Update Restricted Leave","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);

    }

);
/*****************  End Update Restricted Leave *****************/


/*****************   Publish Restricted Leave  *******************/
$f3->route('POST /publishRestrictedLeave',

    function($f3){
            header('Content-Type: application/json');
            $decoded_items = json_decode($f3->get('BODY'),true);
            if(!$decoded_items == NULL)
                publishRestrictedLeaveTemp($decoded_items);
            else
                echo json_encode(array("status"=>"error Publish Restricted Leave","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);

    }

);
/*****************  End Publish Restricted Leave *****************/

/*****************   Unpublish Restricted Leave  *******************/
$f3->route('POST /unPublishRestrictedLeave',

    function($f3){
            header('Content-Type: application/json');
            $decoded_items = json_decode($f3->get('BODY'),true);
            if(!$decoded_items == NULL)
                unPublishRestrictedLeaveTemp($decoded_items);
            else
                echo json_encode(array("status"=>"error Unpublish Restricted Leave","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);

    }

);
/*****************  End Unpublish Restricted Leave *****************/

?>
