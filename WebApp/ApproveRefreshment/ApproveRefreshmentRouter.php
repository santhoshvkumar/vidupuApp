<?php
require_once('ApproveRefreshmentComponent.php');
/*****************  Get Refreshment Allowances *****************/
$f3->route('POST /GetRefreshmentAllowancesByOrganisationID', function($f3) {
    $data = json_decode($f3->get('BODY'), true);
    GetRefreshmentAllowancesByOrganisationID($data);
});
/*****************  End Get Refreshment Allowances *****************/
?>