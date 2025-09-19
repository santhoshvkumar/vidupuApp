<?php

    include('config.inc');  

    $query = "SELECT employeeID, empID FROM tblEmployee";
    $rsd = mysqli_query($connect_var, $query);
    while($row = mysqli_fetch_assoc($rsd)){
        $employeeID = $row['employeeID'];
        $empID = $row['empID'];
        echo $empID . "<br>";
        $query = "select cl_bal from leave_data where staff_code = '$empID' and year = 2025 and leave_code = 'CL'";
        $rsdToRunPrivilageCOunt = mysqli_query($connect_var, $query);
        $row = mysqli_fetch_assoc($rsdToRunPrivilageCOunt);
        $cl_bal = $row['cl_bal'];
        
        $updateQuery = "update tblLeaveBalance set CasualLeave = '$cl_bal' where employeeID = '$employeeID'";
        $rsdToUpdate = mysqli_query($connect_var, $updateQuery);
        echo $empID . " " . $cl_bal . "<br>";
        

        $query = "select pl_bal from leave_data where staff_code = '$empID' and year = 2025 and leave_code = 'PL'";
        $rsdToRunPrivilageCOunt = mysqli_query($connect_var, $query);
        $row = mysqli_fetch_assoc($rsdToRunPrivilageCOunt);
        $pl_bal = $row['pl_bal'];
        $updateQuery = "update tblLeaveBalance set PrivilegeLeave = '$pl_bal' where employeeID = '$employeeID'";
        $rsdToUpdate = mysqli_query($connect_var, $updateQuery);
        echo $empID . " " . $pl_bal . "<br>";

        $query = "select ml_bal from leave_data where staff_code = '$empID' and year = 2025 and leave_code = 'SL'";
        $rsdToRunPrivilageCOunt = mysqli_query($connect_var, $query);
        $row = mysqli_fetch_assoc($rsdToRunPrivilageCOunt);
        $ml_bal = $row['ml_bal'];
        $updateQuery = "update tblLeaveBalance set MedicalLeave = '$ml_bal' where employeeID = '$employeeID'";
        $rsdToUpdate = mysqli_query($connect_var, $updateQuery);
        echo $empID . " " . $ml_bal . "<br>";

        $query = "select sl_bal from leave_data where staff_code = '$empID' and year = 2025 and leave_code = 'CO'";
        $rsdToRunPrivilageCOunt = mysqli_query($connect_var, $query);
        $row = mysqli_fetch_assoc($rsdToRunPrivilageCOunt);
        $sl_bal = $row['sl_bal'];
        $updateQuery = "update tblLeaveBalance set CompensatoryOff = '$sl_bal' where employeeID = '$employeeID'";
        echo $updateQuery . "<br>";
        $rsdToUpdate = mysqli_query($connect_var, $updateQuery);
        echo $empID . " " . $sl_bal . "<br>";
        
       

    }
    mysqli_close($connect_var);
?>