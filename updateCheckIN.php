<?php
    include('config.inc');  
    // Check for employees with more than 2 auto checkouts in current month
    $currentDate = date('Y-m-d');
    $currentMonth = date('Y-m', strtotime($currentDate));
    $autoCheckoutCountQuery = "SELECT 
                                employeeID,
                                COUNT(*) as autoCheckoutCount
                            FROM tblAttendance 
                            WHERE isAutoCheckout = 1
                            AND DATE_FORMAT(attendanceDate, '%Y-%m') = ?
                            GROUP BY employeeID
                            HAVING COUNT(*) > 2";
    $autoCheckoutCountStmt = mysqli_prepare($connect_var, $autoCheckoutCountQuery);
    mysqli_stmt_bind_param($autoCheckoutCountStmt, "s", $currentMonth);
    mysqli_stmt_execute($autoCheckoutCountStmt);
    $autoCheckoutCountResult = mysqli_stmt_get_result($autoCheckoutCountStmt);
    
    // Update isCheckInLocked for employees with more than 2 auto checkouts
    while ($autoCheckoutRow = mysqli_fetch_assoc($autoCheckoutCountResult)) {
        $employeeID = $autoCheckoutRow['employeeID'];
        echo $employeeID ."<br />";
        $autoCheckoutCount = $autoCheckoutRow['autoCheckoutCount'];
        
        // Update isCheckInLocked field in tblEmployee
        $updateCheckInLocked = "UPDATE tblEmployee 
                              SET isCheckInLocked = 1 
                              WHERE employeeID = ? and Designation != 'Deputy General Manager' and Designation != 'Assistant General Manager'";
        
        $updateLockedStmt = mysqli_prepare($connect_var, $updateCheckInLocked);
        mysqli_stmt_bind_param($updateLockedStmt, "s", $employeeID);
        mysqli_stmt_execute($updateLockedStmt);
        mysqli_stmt_close($updateLockedStmt);
        
        error_log("Employee $employeeID locked due to $autoCheckoutCount auto checkouts in $currentMonth");
    }
    mysqli_stmt_close($autoCheckoutCountStmt);
    mysqli_close($connect_var);
?>