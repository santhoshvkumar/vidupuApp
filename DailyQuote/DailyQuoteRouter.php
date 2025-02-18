<?php

/*****************   Get Daily Quote  *******************/
$f3->route('GET /GetDailyQuote',
    function($f3){
        header('Content-Type: application/json');
        getDailyQuote();
    }
);
/*****************  End Get Daily Quote *****************/

?>