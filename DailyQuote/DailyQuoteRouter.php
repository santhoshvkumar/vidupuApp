<?php

/*****************   Get Daily Quote  *******************/
$f3->route('GET /GetDailyQuote',
    function($f3){
        header('Content-Type: application/json');
        getDailyQuote();
    }
);
/*****************  End Get Daily Quote *****************/


/*****************   Version Check  *******************/
$f3->route('GET /VersionCheck',
    function($f3){
        header('Content-Type: application/json');
        checkVersion();
    }
);
/*****************  End Version Check *****************/

/*****************   Submit Newspaper Allowance  *******************/
$f3->route('POST /SubmitNewspaperAllowance',
    function($f3){
        header('Content-Type: application/json');
        include('SubmitNewspaperAllowance.php');
    }
);
/*****************  End Submit Newspaper Allowance *****************/

?>