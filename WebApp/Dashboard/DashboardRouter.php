<?php

$f3->route('GET /DashboardAttendanceDetails',

    function($f3){
                DashboardDetails();

    }
);
$f3->route('GET /DashboardAttendanceForHeadOffice',

    function($f3){
                DashboardDetailsForHO();

    }
);

$f3->route('GET /DashboardGetAllSection',

    function($f3){
                DashboardGetAllSection();

    }
);
/*****************  End Login User Temp *****************/
?>