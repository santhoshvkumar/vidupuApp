<?php

include '../config.inc';

$queryGetAllEmployees = "SELECT employeeID, employeeName FROM tblEmployee";


$resultGetAllEmployees = mysqli_query($connect_var, $queryGetAllEmployees);

$data = [];

while ($row = mysqli_fetch_assoc($resultGetAllEmployees)) {
    $employeeID = $row['employeeID'];
    $employeeName = $row['employeeName'];

    $queryGetCOffCount = "select count(*) as count from tblCompOff where EmployeeID='$employeeID' and isUsed=0";
    
    $resultGetCOffCount = mysqli_query($connect_var, $queryGetCOffCount);

    while($rowGetCOffCount = mysqli_fetch_assoc($resultGetCOffCount)){
        $COffCount = $rowGetCOffCount['count'];
    }
    // if($rowGetCOffCount['count'] != NULL){
    //     $COffCount = $rowGetCOffCount['count'];
    // } else {
    //     $COffCount = 0;
    // }
   // $COffCount = $rowGetCOffCount['count'];
    
    $queryUpdateCOffLeaveBalance = "update tblLeaveBalance set CompensatoryOff='$COffCount' where employeeID='$employeeID'";
    $resultUpdateCOffLeaveBalance = mysqli_query($connect_var, $queryUpdateCOffLeaveBalance);

    $data[] = [
        'employeeID' => $employeeID,
        'queryforCoffCOunt' => $queryGetCOffCount,
        'employeeName' => $employeeName,
        'queryUpdateCOffLeaveBalance' => $queryUpdateCOffLeaveBalance,
        'COffCount' => $COffCount
    ];
    
}

echo json_encode($data);

mysqli_close($connect_var);

?>