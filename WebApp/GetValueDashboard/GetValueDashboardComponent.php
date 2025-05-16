<?php
class GetValueDashboardComponent{    
    public $currentDate;    
    
    public function loadGetValueDashboard(array $data){ 
        $this->currentDate = $data['currentDate'];
        $this->getMethod = $data['getMethod'];
        return true;
    }

    public function GetAllCheckInMembersDetails() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       
            // 1. No of Checkins in Head Office
            if($this->getMethod == "CheckIn")  {
            $queryIndividualNoOfCheckinsInHeadOffice = "
                SELECT 
                    emp.employeeName,
                    sec.sectionName, emp.employeePhone,att.checkInTime,
                    COUNT(att.employeeID) AS checked_in
                FROM tblEmployee AS emp
                JOIN tblAssignedSection AS assign 
                    ON emp.employeeID = assign.employeeID
                JOIN tblSection AS sec 
                    ON assign.sectionID = sec.sectionID
                INNER JOIN tblAttendance AS att 
                    ON emp.employeeID = att.employeeID 
                    AND DATE(att.attendanceDate) = ?
                GROUP BY 
                    emp.employeeName,
                    sec.sectionName, emp.employeePhone,att.checkInTime;";
<<<<<<< HEAD
=======
            }
            else if($this->getMethod == "LateCheckin") {
            }
            else if($this->getMethod == "EarlyCheckout") {
            }
            else if($this->getMethod == "OnLeave") {
            }
            // Debug the query with actual values
            $debug_query = str_replace(
                ['?'],
                [
                    "'" . $this->currentDate . "'",                    
                ],
                $queryIndividualNoOfCheckinsInHeadOffice
            );
            error_log("Debug Query: " . $debug_query);
>>>>>>> 852eb49d0f03230fff5b05219b63344c67c2d8b3

            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfCheckinsInHeadOffice);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "s", $this->currentDate);
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Execute failed: " . mysqli_stmt_error($stmt));
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
<<<<<<< HEAD
            $individualEmployeeData = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $individualEmployeeData[] = $row;
=======
            $countEmployee = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $countEmployee++;
                $data[] = $row;
            }
            if ($countEmployee > 0) {
                echo json_encode([
                    "status" => "success",
                    "data" => $data,
                    //"checked_in" => $checked_in
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message_text" => "No data found for any employee"
                ], JSON_FORCE_OBJECT);
>>>>>>> 852eb49d0f03230fff5b05219b63344c67c2d8b3
            }
            echo json_encode([
                "status" => "success",
                "data" => $individualEmployeeData
            ]);
            // Debug the query with actual values
//             $debug_query = str_replace(
//                 ['?'],
//                 [
//                     "'" . $this->currentDate . "'",                    
//                 ],
//                 $queryIndividualNoOfCheckinsInHeadOffice
//             );
//             error_log("Debug Query: " . $debug_query);

//             $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfCheckinsInHeadOffice);
//             if (!$stmt) {
//                 error_log("Prepare failed: " . mysqli_error($connect_var));
//                 throw new Exception("Database prepare failed");
//             }

//             mysqli_stmt_bind_param($stmt, "s", $this->currentDate);
            
//             if (!mysqli_stmt_execute($stmt)) {
//                 error_log("Execute failed: " . mysqli_stmt_error($stmt));
//                 throw new Exception("Database execute failed");
//             }

//             $result = mysqli_stmt_get_result($stmt);
//             $employeeData = [];
//             $employeeName = [];
//             $sectionName = [];
//             $employeePhone = [];
//             $checkInTime = [];
// //$checked_in = [];
//             $countEmployee = 0;
//             while ($row = mysqli_fetch_assoc($result)) {
//                 // error_log("Row data: " . print_r($row, true));
//                 // $employeeData[] = [
//                 //     'employeeName' => $row['employeeName'],
//                 //     'sectionName' => $row['sectionName'],
//                 //     'checked_in' => intval($row['checked_in']),
//                 //     'employeePhone' => $row['employeePhone'],
//                 //     'checkInTime' => $row['checkInTime']
//                 // ];
//                 $employeeName[] = $row['employeeName'];
//                 $sectionName[] = $row['sectionName'];
//                 $employeePhone[] = $row['employeePhone'];
//                 $checkInTime[] = $row['checkInTime'];
//                 // $checked_in[] = intval($row['checked_in']);
//                 $countEmployee++;
//             }
//             if ($countEmployee > 0) {
//                 echo json_encode([
//                     "status" => "success",
//                     "employeeName" => $employeeName,
//                     "sectionName" => $sectionName,
//                     "employeePhone" => $employeePhone,
//                     "checkInTime" => $checkInTime,
//                     //"checked_in" => $checked_in
//                 ]);
//             } else {
//                 echo json_encode([
//                     "status" => "error",
//                     "message_text" => "No data found for any employee"
//                 ], JSON_FORCE_OBJECT);
//             }
        } catch (Exception $e) {
            error_log("Error in GetValueDashboardforCheckin: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }

    public function GetValueDashboardforLateCheckin() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       

            // Debug input values
            error_log("GetValueDashboardforLateCheckin - Input values:");
            error_log("currentDate: " . $this->currentDate);

            // 1. No of Late Checkins in Head Office
            $queryIndividualNoOfLateCheckinsInHeadOffice = "
                SELECT emp.employeeName,sec.sectionName,emp.employeePhone,att.checkInTime,
                COUNT(CASE WHEN att.checkInTime > '10:10:00' THEN 1 END) AS late_checkin
                FROM tblEmployee AS emp JOIN tblAssignedSection AS assign ON emp.employeeID = assign.employeeID JOIN tblSection AS sec ON assign.sectionID = sec.sectionID INNER JOIN tblAttendance AS att ON emp.employeeID = att.employeeID AND DATE(att.attendanceDate) = ? GROUP BY emp.employeeName,sec.sectionName,emp.employeePhone,att.checkInTime HAVING late_checkin > 0;";                                                                   

            // Debug the query with actual values
            $debug_query = str_replace(
                ['?'],
                [
                    "'" . $this->currentDate . "'",                    
                ],
                $queryIndividualNoOfLateCheckinsInHeadOffice
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfLateCheckinsInHeadOffice);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "s", 
                $this->currentDate
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Execute failed: " . mysqli_stmt_error($stmt));
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
            $employeeData = [];           
            
            // Debug number of rows
            $num_rows = mysqli_num_rows($result);
            error_log("Number of rows returned: " . $num_rows);
            
            while ($row = mysqli_fetch_assoc($result)) {
                error_log("Row data: " . print_r($row, true));
                $employeeData[] = [
                    'employeeName' => $row['employeeName'],
                    'sectionName' => $row['sectionName'],
                    'late_checkin' => $row['late_checkin'],
                    'employeePhone' => $row['employeePhone'],
                    'checkInTime' => $row['checkInTime']
                ];
            }
            
            error_log("Final employeeData: " . print_r($employeeData, true));
            
            echo json_encode([
                "status" => "success",
                "data" => $employeeData
            ]);
            
        } catch (Exception $e) {
            error_log("Error in GetValueDashboardforLateCheckin: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
    public function GetValueDashboardforEarlyCheckout() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       

            // Debug input values
            error_log("GetValueDashboardforEarlyCheckout - Input values:");
            error_log("currentDate: " . $this->currentDate);

            // 1. No of Early Checkouts in Head Office
            $queryIndividualNoOfEarlyCheckoutInHeadOffice = "
                SELECT emp.employeeName,sec.sectionName,emp.employeePhone,att.checkOutTime,COUNT(CASE WHEN att.checkOutTime < '17:00:00' THEN 1 END) AS early_checkout
                FROM tblEmployee AS emp JOIN tblAssignedSection AS assign ON emp.employeeID = assign.employeeID JOIN tblSection AS sec ON assign.sectionID = sec.sectionID INNER JOIN tblAttendance AS att ON emp.employeeID = att.employeeID AND DATE(att.attendanceDate) = ? GROUP BY emp.employeeName,sec.sectionName,emp.employeePhone,att.checkOutTime HAVING early_checkout > 0;";               

            // Debug the query with actual values
            $debug_query = str_replace(
                ['?'],
                [
                    "'" . $this->currentDate . "'",                    
                ],
                $queryIndividualNoOfEarlyCheckoutInHeadOffice
            );
            error_log("Debug Query: " . $debug_query);

            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfEarlyCheckoutInHeadOffice);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }

            mysqli_stmt_bind_param($stmt, "s", 
                $this->currentDate
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Execute failed: " . mysqli_stmt_error($stmt));
                throw new Exception("Database execute failed");
            }

            $result = mysqli_stmt_get_result($stmt);
            $employeeData = [];           
            
            // Debug number of rows
            $num_rows = mysqli_num_rows($result);
            error_log("Number of rows returned: " . $num_rows);
            
            while ($row = mysqli_fetch_assoc($result)) {
                error_log("Row data: " . print_r($row, true));
                $employeeData[] = [
                    'employeeName' => $row['employeeName'],
                    'sectionName' => $row['sectionName'],
                    'early_checkout' => $row['early_checkout'],
                    'employeePhone' => $row['employeePhone'],
                    'checkOutTime' => $row['checkOutTime']
                ];
            }
            
            error_log("Final employeeData: " . print_r($employeeData, true));
            
            echo json_encode([
                "status" => "success",
                "data" => $employeeData
            ]);
            
        } catch (Exception $e) {
            error_log("Error in GetValueDashboardforEarlyCheckout: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    } 

    public function GetValueDashboardforOnLeave() {
        include('config.inc');
        header('Content-Type: application/json');
        try {       
            $data = [];                       

            // Debug input values
            error_log("GetValueDashboardforOnLeave - Input values:");
            error_log("currentDate: " . $this->currentDate);

            // 1. No of On Leave in Head Office
            $queryIndividualNoOfOnLeaveInHeadOffice = "
                SELECT emp.employeeName,sec.sectionName, emp.employeePhone, COUNT(CASE WHEN ? BETWEEN lv.fromDate AND lv.toDate THEN 1 END) AS on_leave
                FROM tblEmployee AS emp JOIN tblAssignedSection AS assign ON emp.employeeID = assign.employeeID JOIN tblSection AS sec ON assign.sectionID = sec.sectionID
                INNER JOIN tblApplyLeave AS lv ON emp.employeeID = lv.employeeID 
                GROUP BY emp.employeeName,sec.sectionName HAVING on_leave > 0;";                                          
            // Debug the query with actual values
            $debug_query = str_replace(
                ['?'],
                [
                    "'" . $this->currentDate . "'",                    
                ],
                $queryIndividualNoOfOnLeaveInHeadOffice
            );
            error_log("Debug Query: " . $debug_query);
            $stmt = mysqli_prepare($connect_var, $queryIndividualNoOfOnLeaveInHeadOffice);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }
            mysqli_stmt_bind_param($stmt, "s", 
                $this->currentDate
            );            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Execute failed: " . mysqli_stmt_error($stmt));
                throw new Exception("Database execute failed");
            }
            $result = mysqli_stmt_get_result($stmt);
            $employeeData = [];           
            
            // Debug number of rows
            $num_rows = mysqli_num_rows($result);
            error_log("Number of rows returned: " . $num_rows);
            
            while ($row = mysqli_fetch_assoc($result)) {
                error_log("Row data: " . print_r($row, true));
                $employeeData[] = [
                    'employeeName' => $row['employeeName'],
                    'sectionName' => $row['sectionName'],
                    'on_leave' => $row['on_leave'],
                    'employeePhone' => $row['employeePhone']
                ];
            }
            
            error_log("Final employeeData: " . print_r($employeeData, true));
            
            echo json_encode([
                "status" => "success",
                "data" => $employeeData
            ]);
            
        } catch (Exception $e) {
            error_log("Error in GetValueDashboardforOnLeave: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    } 
}

function GetAllCheckInMembers($decoded_items) {
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadGetValueDashboard($decoded_items)) {
        $GetValueDashboardObject->GetAllCheckInMembersDetails();
    } else {        
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function GetValueDashboardforLateCheckin($decoded_items) {
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadGetValueDashboard($decoded_items)) {
        $GetValueDashboardObject->GetValueDashboardforLateCheckin();
    } else {        
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function GetValueDashboardforEarlyCheckout($decoded_items) {
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadGetValueDashboard($decoded_items)) {
        $GetValueDashboardObject->GetValueDashboardforEarlyCheckout();
    } else {        
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
function GetValueDashboardforOnLeave($decoded_items) {
    $GetValueDashboardObject = new GetValueDashboardComponent();
    if ($GetValueDashboardObject->loadGetValueDashboard($decoded_items)) {
        $GetValueDashboardObject->GetValueDashboardforOnLeave();
    } else {    
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
?>