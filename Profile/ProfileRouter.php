<?php

/*****************   Login User Temp  *******************/
$f3->route('POST /ChangePassword',

    function($f3){
            header('Content-Type: application/json');
            $decoded_items = json_decode($f3->get('BODY'),true);
            if(!$decoded_items == NULL)
                changePassword($decoded_items);
            else
                echo json_encode(array("status"=>"error Login Value","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);

    }

);
/*****************  End Login User Temp *****************/

/*****************   Forgot Password  *******************/
$f3->route('POST /ForgotPassword',
    function($f3){
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            forgotPassword($decoded_items);
        else
            echo json_encode(array("status"=>"error","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
);
/*****************  End Forgot Password *****************/

/*****************   Get Profile Details  *******************/
$f3->route('POST /GetProfileDetails',
    function($f3){
        header('Content-Type: application/json');
        $decoded_items = json_decode($f3->get('BODY'),true);
        if(!$decoded_items == NULL)
            getProfileDetails($decoded_items);
        else
            echo json_encode(array("status"=>"error","message_text"=>"Invalid input parameters"),JSON_FORCE_OBJECT);
    }
);
/*****************  End Get Profile Details *****************/

/*****************   Update Profile Photo  *******************/
$f3->route('POST /UpdateProfilePhoto',
    function($f3){
        updateProfilePhoto();
    }
);
/*****************  End Update Profile Photo *****************/

/*****************   Update Profile Photo Path  *******************/
$f3->route('POST /UpdateProfilePhotoPath',
    function($f3){
        $decoded_items = json_decode($f3->get('BODY'), true);
        updateProfilePhotoPath($decoded_items);
    }
);
/*****************  End Update Profile Photo Path *****************/

?>