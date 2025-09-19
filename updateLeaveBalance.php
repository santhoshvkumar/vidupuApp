<?php

    include('config.inc');  

    $query = "SELECT employeeID, empID FROM tblEmployee";
    $rsd = mysqli_query($connect_var, $query);
    while($row = mysqli_fetch_assoc($rsd)){
        $employeeID = $row['employeeID'];
        $empID = $row['empID'];
        $cl_bal = 0;
        $pl_bal = 0;
        $ml_bal = 0;
        $sl_bal = 0;
        echo $employeeID ."----". $empID . "<br>";
       $query = "select cl_bal from leave_data where staff_code = '$empID' and year = 2025 and leave_code = 'CL'";
        $rsdToRunPrivilageCOunt = mysqli_query($connect_var, $query);
        $row = mysqli_fetch_assoc($rsdToRunPrivilageCOunt);
        $cl_bal = $row['cl_bal'];
        echo "Casual Leave: " . $cl_bal . "<br>";
        if($cl_bal != 0){
            $updateQuery = "update tblLeaveBalance set CasualLeave = '$cl_bal' where employeeID = '$employeeID'";
            $rsdToUpdate = mysqli_query($connect_var, $updateQuery);
            echo $empID . " " . $cl_bal . "<br>";
        }
        

        $query = "select cl_bal from leave_data where staff_code = '$empID' and year = 2025 and leave_code = 'PL'";
        $rsdToRunPrivilageCOunt = mysqli_query($connect_var, $query);
        $row = mysqli_fetch_assoc($rsdToRunPrivilageCOunt);
        $pl_bal = $row['cl_bal'];
        echo "Privilege Leave: " . $pl_bal . "<br>";
        if($pl_bal != 0){
            $updateQuery = "update tblLeaveBalance set PrivilegeLeave = '$pl_bal' where employeeID = '$employeeID'";
            $rsdToUpdate = mysqli_query($connect_var, $updateQuery);
            echo $empID . " " . $pl_bal . "<br>";
        }
       

        $query = "select cl_bal from leave_data where staff_code = '$empID' and year = 2025 and leave_code = 'SL'";
        $rsdToRunPrivilageCOunt = mysqli_query($connect_var, $query);
        $row = mysqli_fetch_assoc($rsdToRunPrivilageCOunt);
        $ml_bal = $row['cl_bal'];
        echo "Medical Leave: " . $ml_bal . "<br>";
        if($ml_bal != 0){   
            $updateQuery = "update tblLeaveBalance set MedicalLeave = '$ml_bal' where employeeID = '$employeeID'";
            $rsdToUpdate = mysqli_query($connect_var, $updateQuery);
            echo $empID . " " . $ml_bal . "<br>";
        }
        $query = "select cl_bal from leave_data where staff_code = '$empID' and year = 2025 and leave_code = 'CO'";
        $rsdToRunPrivilageCOunt = mysqli_query($connect_var, $query);
        $row = mysqli_fetch_assoc($rsdToRunPrivilageCOunt);
        $sl_bal = $row['cl_bal'];
        echo "Compensatory Off: " . $sl_bal . "<br>";
        if($sl_bal != 0){
            $updateQuery = "update tblLeaveBalance set CompensatoryOff = '$sl_bal' where employeeID = '$employeeID'";
            echo $updateQuery . "<br>";
            $rsdToUpdate = mysqli_query($connect_var, $updateQuery);
            echo $empID . " " . $sl_bal . "<br>";
        }
        
       

    }
   mysqli_close($connect_var);
?>