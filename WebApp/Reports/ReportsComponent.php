<?php
class ReportsComponent {    
public $startDate;
public $endDate;
public $organisationID;
public $selectedMonth;
public $employeeID;
public $selectedYear;
public $selectedDate;
public $employeeType;

public function loadOrganisationID(array $data) {
if (isset($data['organisationID'])) {
$this->organisationID = $data['organisationID'];
return true;
}
return false;
}

public function loadEmployeeID(array $data) {
if (isset($data['employeeID'])) {
$this->employeeID = $data['employeeID'];
return true;
}
return false;
}

public function loadSelectedYear(array $data) {
if (isset($data['selectedYear'])) {
$this->selectedYear = $data['selectedYear'];
return true;
}
return false;
}

public function loadReportsforGivenDate(array $data) { 
$this->startDate = $data['startDate'];
$this->endDate = $data['endDate'];
return true;
}
public function loadReportsforGivenDateforAll(array $data) { 
$this->startDate = $data['startDate'];
$this->endDate = $data['endDate'];
return true;
}

public function GetAttendanceReport() {    
include('config.inc');
header('Content-Type: application/json');
try {
$data = [];
$queryforGetAttendanceReport = "SELECT DISTINCT
   e.empID AS Employee_ID,
   e.employeeName AS Employee_Name,
   e.Designation,
   DATE_FORMAT(DATE_ADD(?, INTERVAL n.num DAY), '%d/%m/%Y') AS attendanceDate,
   TIME_FORMAT(a.checkInTime, '%H:%i:%s') AS CheckIn_Time,
   TIME_FORMAT(a.checkOutTime, '%H:%i:%s') AS CheckOut_Time,
   CASE
       WHEN a.checkInTime IS NOT NULL THEN 'Present'
       WHEN EXISTS (
           SELECT 1
           FROM tblApplyLeave al
           JOIN tblmapEmp map ON al.employeeID = map.employeeID
           WHERE al.employeeID = e.employeeID
             AND DATE_ADD(?, INTERVAL n.num DAY) BETWEEN al.fromDate AND al.toDate
             AND al.Status = 'Approved'
             AND map.organisationID = ?
       ) THEN 'Leave'
       ELSE 'Absent'
   END AS Status,
   b.branchName
FROM 
   (
       SELECT empID, employeeName, Designation, employeeID
       FROM tblEmployee 
       WHERE isTemporary = 0 AND isActive = 1
   ) e
CROSS JOIN (
   SELECT a.N + b.N * 10 + c.N * 100 AS num
   FROM 
       (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
        UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a
   CROSS JOIN 
       (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
        UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) b
   CROSS JOIN 
       (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
        UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) c
   WHERE a.N + b.N * 10 + c.N * 100 <= DATEDIFF(?, ?)
) n
LEFT JOIN tblAttendance a 
   ON e.employeeID = a.employeeID  
   AND a.attendanceDate = DATE_ADD(?, INTERVAL n.num DAY)
INNER JOIN tblmapEmp m ON e.employeeID = m.employeeID
INNER JOIN tblBranch b ON m.branchID = b.branchID
WHERE 
   DATE_ADD(?, INTERVAL n.num DAY) BETWEEN ? AND ?
ORDER BY 
   e.empID, attendanceDate;
";

$stmt = mysqli_prepare($connect_var, $queryforGetAttendanceReport);
if (!$stmt) {
throw new Exception("Database prepare failed");
}

mysqli_stmt_bind_param($stmt, "sssssssss", 
$this->startDate,  // For attendanceDate
$this->startDate,  // For leave check
$this->organisationID,  // For organisationID in leave check
$this->endDate,    // For DATEDIFF
$this->startDate,  // For DATEDIFF
$this->startDate,  // For attendance join
$this->startDate,  // For WHERE clause
$this->startDate,  // For WHERE clause start
$this->endDate     // For WHERE clause end
);

if (!mysqli_stmt_execute($stmt)) {
throw new Exception("Database execute failed");
}

$result = mysqli_stmt_get_result($stmt);
$countEmployee = 0;
while ($row = mysqli_fetch_assoc($result)) {
$countEmployee++;
$data[] = $row;
}

if ($countEmployee > 0) {
echo json_encode([
"status" => "success",  
"data" => $data
]);
} else {
echo json_encode([
"status" => "error",
"message_text" => "No data found for any employee"
], JSON_FORCE_OBJECT);
}

mysqli_stmt_close($stmt);
mysqli_close($connect_var);
} catch (Exception $e) {
echo json_encode([
"status" => "error",
"message_text" => $e->getMessage()
], JSON_FORCE_OBJECT);
}
}
public function GetSectionWiseAttendanceReport() {    
include('config.inc');
header('Content-Type: application/json');
try {
$data = [];
$queryforGetAttendanceReport = "SELECT DISTINCT
   e.empID AS Employee_ID,
   e.employeeName AS Employee_Name,
   e.Designation,
   s.sectionName AS Section_Name,
   DATE_FORMAT(DATE_ADD(?, INTERVAL n.num DAY), '%d/%m/%Y') AS attendanceDate,
   TIME_FORMAT(a.checkInTime, '%H:%i:%s') AS CheckIn_Time,
   TIME_FORMAT(a.checkOutTime, '%H:%i:%s') AS CheckOut_Time,
   CASE
       WHEN a.checkInTime IS NOT NULL THEN 'Present'
       WHEN EXISTS (
           SELECT 1
           FROM tblApplyLeave al
           JOIN tblmapEmp map ON al.employeeID = map.employeeID
           WHERE al.employeeID = e.employeeID
             AND DATE_ADD(?, INTERVAL n.num DAY) BETWEEN al.fromDate AND al.toDate
             AND al.Status = 'Approved'
             AND map.organisationID = ?
       ) THEN 'Leave'
       ELSE 'Absent'
   END AS Status,
   b.branchName
FROM 
   (
       SELECT empID, employeeName, Designation, employeeID
       FROM tblEmployee 
       WHERE isTemporary = 0 AND isActive = 1
   ) e
CROSS JOIN (
   SELECT a.N + b.N * 10 + c.N * 100 AS num
   FROM 
       (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
        UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a
   CROSS JOIN 
       (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
        UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) b
   CROSS JOIN 
       (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
        UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) c
   WHERE a.N + b.N * 10 + c.N * 100 <= DATEDIFF(?, ?)
) n
LEFT JOIN tblAttendance a 
   ON e.employeeID = a.employeeID  
   AND a.attendanceDate = DATE_ADD(?, INTERVAL n.num DAY)
INNER JOIN tblmapEmp m ON e.employeeID = m.employeeID
INNER JOIN tblBranch b ON m.branchID = b.branchID
INNER JOIN tblAssignedSection sa ON sa.employeeID = e.employeeID
INNER JOIN tblSection s ON sa.sectionID = s.sectionID
WHERE 
   b.branchName = 'Head Office'
   AND DATE_ADD(?, INTERVAL n.num DAY) BETWEEN ? AND ?
ORDER BY 
   e.empID, attendanceDate;
";

$stmt = mysqli_prepare($connect_var, $queryforGetAttendanceReport);
if (!$stmt) {
throw new Exception("Database prepare failed");
}

mysqli_stmt_bind_param($stmt, "sssssssss", 
$this->startDate,  // For attendanceDate
$this->startDate,  // For leave check
$this->organisationID,  // For organisationID in leave check
$this->endDate,    // For DATEDIFF
$this->startDate,  // For DATEDIFF
$this->startDate,  // For attendance join
$this->startDate,  // For WHERE clause
$this->startDate,  // For WHERE clause start
$this->endDate     // For WHERE clause end
);

if (!mysqli_stmt_execute($stmt)) {
throw new Exception("Database execute failed");
}

$result = mysqli_stmt_get_result($stmt);
$countEmployee = 0;
while ($row = mysqli_fetch_assoc($result)) {
$countEmployee++;
$data[] = $row;
}

if ($countEmployee > 0) {
echo json_encode([
"status" => "success",  
"data" => $data
]);
} else {
echo json_encode([
"status" => "error",
"message_text" => "No data found for any employee"
], JSON_FORCE_OBJECT);
}

mysqli_stmt_close($stmt);
mysqli_close($connect_var);
} catch (Exception $e) {
echo json_encode([
"status" => "error",
"message_text" => $e->getMessage()
], JSON_FORCE_OBJECT);
}
}
public function GetLeaveReport() {    
include('config.inc');
header('Content-Type: application/json');
try {
$data = [];


if (!empty($this->organisationID)) {
$queryforGetLeaveReport = "SELECT 
e.empID AS Employee_ID, 
e.employeeName AS Employee_Name, 
e.Designation, 
b.branchName AS Branch_Name,
l.MedicalCertificatePath AS Medical_Certificate_Path,
l.FitnessCertificatePath AS Fitness_Certificate_Path,
l.leaveDuration AS Leave_Duration,
DATE_FORMAT(l.createdOn, '%d/%m/%Y') AS Applied_On, 
l.status AS Status, 
DATE_FORMAT(l.fromDate, '%d/%m/%Y') AS From_Date, 
DATE_FORMAT(l.toDate, '%d/%m/%Y') AS To_Date,
l.typeOfLeave AS Type_Of_Leave,
l.reason AS Reason
FROM tblEmployee AS e
JOIN tblApplyLeave AS l ON e.employeeID = l.employeeID
JOIN tblmapEmp AS m ON e.employeeID = m.employeeID
JOIN tblBranch AS b ON m.branchID = b.branchID
WHERE (
(l.createdOn BETWEEN ? AND ?) OR
(l.fromDate BETWEEN ? AND ?) OR
(l.toDate BETWEEN ? AND ?) OR
(l.fromDate <= ? AND l.toDate >= ?)
)
AND m.organisationID = ?
ORDER BY l.createdOn DESC;";
} else {
$queryforGetLeaveReport = "SELECT 
e.empID AS Employee_ID, 
e.employeeName AS Employee_Name, 
e.Designation, 
b.branchName AS Branch_Name,
l.MedicalCertificatePath AS Medical_Certificate_Path,
l.FitnessCertificatePath AS Fitness_Certificate_Path,
l.leaveDuration AS Leave_Duration,
DATE_FORMAT(l.createdOn, '%d/%m/%Y') AS Applied_On, 
l.status AS Status, 
DATE_FORMAT(l.fromDate, '%d/%m/%Y') AS From_Date, 
DATE_FORMAT(l.toDate, '%d/%m/%Y') AS To_Date,
l.typeOfLeave AS Type_Of_Leave,
l.reason AS Reason
FROM tblEmployee AS e
JOIN tblApplyLeave AS l ON e.employeeID = l.employeeID
JOIN tblmapEmp AS m ON e.employeeID = m.employeeID
JOIN tblBranch AS b ON m.branchID = b.branchID
WHERE (
(l.createdOn BETWEEN ? AND ?) OR
(l.fromDate BETWEEN ? AND ?) OR
(l.toDate BETWEEN ? AND ?) OR
(l.fromDate <= ? AND l.toDate >= ?)
)
ORDER BY l.createdOn DESC;";
}
$stmt = mysqli_prepare($connect_var, $queryforGetLeaveReport);
if (!$stmt) {
throw new Exception("Database prepare failed");
}

if (!empty($this->organisationID)) {
    mysqli_stmt_bind_param($stmt, "ssssssssi", 
    $this->startDate,  // For createdOn BETWEEN
    $this->endDate,    // For createdOn BETWEEN
    $this->startDate,  // For fromDate BETWEEN
    $this->endDate,    // For fromDate BETWEEN
    $this->startDate,  // For toDate BETWEEN
    $this->endDate,    // For toDate BETWEEN
    $this->startDate,  // For fromDate <= startDate
    $this->endDate,    // For toDate >= endDate
    $this->organisationID
    );
} else {
    mysqli_stmt_bind_param($stmt, "ssssssss", 
    $this->startDate,  // For createdOn BETWEEN
    $this->endDate,    // For createdOn BETWEEN
    $this->startDate,  // For fromDate BETWEEN
    $this->endDate,    // For fromDate BETWEEN
    $this->startDate,  // For toDate BETWEEN
    $this->endDate,    // For toDate BETWEEN
    $this->startDate,  // For fromDate <= startDate
    $this->endDate     // For toDate >= endDate
    );
}

if (!mysqli_stmt_execute($stmt)) {
throw new Exception("Database execute failed");
}

$result = mysqli_stmt_get_result($stmt);
$countEmployee = 0;
while ($row = mysqli_fetch_assoc($result)) {
$countEmployee++;
$data[] = $row;
}

if ($countEmployee > 0) {
echo json_encode([
"status" => "success",  
"data" => $data
]);
} else {
echo json_encode([
"status" => "error",
"message_text" => "No data found for any employee"
], JSON_FORCE_OBJECT);
}

mysqli_stmt_close($stmt);
mysqli_close($connect_var);
} catch (Exception $e) {
echo json_encode([
"status" => "error",
"message_text" => $e->getMessage()
], JSON_FORCE_OBJECT);
}
}
public function GetDesignationWiseAttendanceReport() {    
include('config.inc');
header('Content-Type: application/json');
try {
$data = [];
$queryforGetAttendanceReport = "SELECT
   e.designation,
   DATE_FORMAT(DATE_ADD(?, INTERVAL n.n DAY), '%d/%m/%Y') AS AttendanceDate,
   SUM(CASE WHEN b.BranchName = 'Head Office' THEN 1 ELSE 0 END) AS HeadOffice_Total,
   SUM(CASE WHEN b.BranchName <> 'Head Office' THEN 1 ELSE 0 END) AS Branch_Total,
   SUM(CASE 
       WHEN b.BranchName = 'Head Office' AND a.CheckInTime IS NOT NULL THEN 1 ELSE 0 
   END) AS HeadOffice_Present,
   SUM(CASE 
       WHEN b.BranchName <> 'Head Office' AND a.CheckInTime IS NOT NULL THEN 1 ELSE 0 
   END) AS Branch_Present,
   SUM(CASE 
       WHEN b.BranchName = 'Head Office' 
            AND l.Status = 'Approved'
            AND l.FromDate <= DATE_ADD(?, INTERVAL n.n DAY)
            AND l.ToDate >= DATE_ADD(?, INTERVAL n.n DAY)
       THEN 1 ELSE 0 
   END) AS HeadOffice_Leave,
   SUM(CASE 
       WHEN b.BranchName <> 'Head Office' 
            AND l.Status = 'Approved'
            AND l.FromDate <= DATE_ADD(?, INTERVAL n.n DAY)
            AND l.ToDate >= DATE_ADD(?, INTERVAL n.n DAY)
       THEN 1 ELSE 0 
   END) AS Branch_Leave,

   -- Absent = Total - Present - Leave
   SUM(CASE WHEN b.BranchName = 'Head Office' THEN 1 ELSE 0 END)
   - SUM(CASE 
       WHEN b.BranchName = 'Head Office' AND a.CheckInTime IS NOT NULL THEN 1 ELSE 0 
   END)
   - SUM(CASE 
       WHEN b.BranchName = 'Head Office' 
            AND l.Status = 'Approved'
            AND l.FromDate <= DATE_ADD(?, INTERVAL n.n DAY)
            AND l.ToDate >= DATE_ADD(?, INTERVAL n.n DAY)
       THEN 1 ELSE 0 
   END) AS HeadOffice_Absent,

   SUM(CASE WHEN b.BranchName <> 'Head Office' THEN 1 ELSE 0 END)
   - SUM(CASE 
       WHEN b.BranchName <> 'Head Office' AND a.CheckInTime IS NOT NULL THEN 1 ELSE 0 
   END)
   - SUM(CASE 
       WHEN b.BranchName <> 'Head Office' 
            AND l.Status = 'Approved'
            AND l.FromDate <= DATE_ADD(?, INTERVAL n.n DAY)
            AND l.ToDate >= DATE_ADD(?, INTERVAL n.n DAY)
       THEN 1 ELSE 0 
   END) AS Branch_Absent

FROM (
   SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 
   UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 
   UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
) n

JOIN tblEmployee e ON 1 = 1
JOIN tblmapEmp m ON e.EmployeeID = m.EmployeeID
JOIN tblBranch b ON m.BranchID = b.BranchID

LEFT JOIN tblAttendance a 
   ON e.EmployeeID = a.EmployeeID 
  AND a.AttendanceDate = DATE_ADD(?, INTERVAL n.n DAY)

LEFT JOIN tblApplyLeave l 
   ON e.EmployeeID = l.EmployeeID 
  AND l.Status = 'Approved'
  AND DATE_ADD(?, INTERVAL n.n DAY) BETWEEN l.FromDate AND l.ToDate

WHERE e.isActive = 1 AND e.isTemporary = 0
 AND m.organisationID = ?
 AND DATE_ADD(?, INTERVAL n.n DAY) <= ?

GROUP BY e.designation, n.n
ORDER BY 
   CASE e.designation
       WHEN 'Deputy General Manager' THEN 1
       WHEN 'Assistant General Manager' THEN 2
       WHEN 'IT Specialist' THEN 3
       WHEN 'PA TO EXECUTIVE' THEN 4
       WHEN 'Chief Manager' THEN 5
       WHEN 'Manager' THEN 6
       WHEN 'Assistant Manager' THEN 7
       WHEN 'Assistant' THEN 8
       WHEN 'System Admin' THEN 9
       WHEN 'Teller' THEN 10
       WHEN 'Sub Staff' THEN 11
       WHEN 'Sweeper' THEN 12
       WHEN 'Intern' THEN 13
       ELSE 14 END,
   e.designation,
   AttendanceDate;
";

$stmt = mysqli_prepare($connect_var, $queryforGetAttendanceReport);
if (!$stmt) {
throw new Exception("Database prepare failed");
}

mysqli_stmt_bind_param($stmt, "ssssssssssssss", 
$this->startDate,  // For attendanceDate
$this->startDate,  // For HeadOffice_Leave
$this->startDate,  // For HeadOffice_Leave
$this->startDate,  // For Branch_Leave
$this->startDate,  // For Branch_Leave
$this->startDate,  // For HeadOffice_Absent
$this->startDate,  // For HeadOffice_Absent
$this->startDate,  // For Branch_Absent
$this->startDate,  // For Branch_Absent
$this->startDate,  // For attendance join
$this->startDate,  // For leave join
$this->organisationID,  // For organisationID in WHERE clause
$this->startDate,  // For WHERE clause
$this->endDate     // For WHERE clause end
);

if (!mysqli_stmt_execute($stmt)) {
throw new Exception("Database execute failed");
}

$result = mysqli_stmt_get_result($stmt);
$designation = [];  
$attendanceDate = [];
$headOfficePresent = [];
$branchPresent = [];
$headOfficeLeave = [];
$branchLeave = [];
$headOfficeTotal = [];
$branchTotal = [];
$headOfficeAbsent = [];
$branchAbsent = [];
$countEmployee = 0;
while ($row = mysqli_fetch_assoc($result)) {
$countEmployee++;
$designation[] = $row['designation'];
$attendanceDate[] = $row['AttendanceDate'];
$headOfficePresent[] = $row['HeadOffice_Present'];
$branchPresent[] = $row['Branch_Present'];
$headOfficeLeave[] = $row['HeadOffice_Leave'];
$branchLeave[] = $row['Branch_Leave'];
$headOfficeTotal[] = $row['HeadOffice_Total'];
$branchTotal[] = $row['Branch_Total'];
$headOfficeAbsent[] = $row['HeadOffice_Absent'];
$branchAbsent[] = $row['Branch_Absent'];
}

if ($countEmployee > 0) {
echo json_encode([
"status" => "success",  
"designation" => $designation ?? [],
"attendanceDate" => $attendanceDate ?? [],
"headOfficePresent" => $headOfficePresent ?? [],
"branchPresent" => $branchPresent ?? [],
"headOfficeLeave" => $headOfficeLeave ?? [],
"branchLeave" => $branchLeave ?? [],
"headOfficeTotal" => $headOfficeTotal ?? [],
"branchTotal" => $branchTotal ?? [],
"headOfficeAbsent" => $headOfficeAbsent ?? [],
"branchAbsent" => $branchAbsent ?? []
]);
} else {
echo json_encode([
"status" => "success",
"designation" => [],
"attendanceDate" => [],
"headOfficePresent" => [],
"branchPresent" => [],
"headOfficeLeave" => [],
"branchLeave" => [],
"headOfficeTotal" => [],
"branchTotal" => [],
"headOfficeAbsent" => [],
"branchAbsent" => []
], JSON_FORCE_OBJECT);
}

mysqli_stmt_close($stmt);
mysqli_close($connect_var);
} catch (Exception $e) {
echo json_encode([
"status" => "error",
"message_text" => $e->getMessage()
], JSON_FORCE_OBJECT);
}
}

public function GetManagementLeaveReport() {
include(dirname(__FILE__) . '/../../config.inc');
header('Content-Type: application/json');
try {

// Check if this is a yearly request (format: 2025-01 means year 2025)
$isYearlyRequest = false;
$year = '';
if (preg_match('/^(\d{4})-01$/', $this->selectedMonth, $matches)) {
$isYearlyRequest = true;
$year = $matches[1];
}

if ($isYearlyRequest) {
// Handle yearly report
$this->getYearlyManagementLeaveReport($year, $connect_var);
} else {
// Handle monthly report (existing logic)
$this->getMonthlyManagementLeaveReport($connect_var);
}

} catch (Exception $e) {
echo json_encode([
"status" => "error",
"message_text" => $e->getMessage()
], JSON_FORCE_OBJECT);
}
}

private function getYearlyManagementLeaveReport($year, $connect_var) {
$query = "
           SELECT 
               (@row_number := @row_number + 1) as sNo,
               subquery.*
           FROM (
               SELECT 
                   e.employeeName,
                   e.empID as employeeCode,
                   e.Designation,
                   b.branchName,
                   COALESCE(SUM(CASE WHEN al.typeOfLeave = 'Casual Leave' THEN al.leaveDuration ELSE 0 END), 0) as cl,
                   COALESCE(SUM(CASE WHEN al.typeOfLeave = 'Privilege Leave' THEN al.leaveDuration ELSE 0 END), 0) as pl,
                   COALESCE(SUM(CASE WHEN al.typeOfLeave = 'Privilege Leave(Medical Grounds)' THEN al.leaveDuration ELSE 0 END), 0) as plMedical,
                   COALESCE(SUM(CASE WHEN al.typeOfLeave = 'Medical Leave' THEN al.leaveDuration ELSE 0 END), 0) as sl,
                   COALESCE(SUM(CASE 
                       WHEN al.typeOfLeave NOT IN ('Maternity Leave') 
                       THEN al.leaveDuration 
                       ELSE 0 
                   END), 0) as total
               FROM 
                   tblEmployee e
               LEFT JOIN 
                   tblmapEmp m ON e.employeeID = m.employeeID
               LEFT JOIN 
                   tblBranch b ON m.branchID = b.branchID
               LEFT JOIN 
                   tblApplyLeave al ON e.employeeID = al.employeeID 
                   AND al.status = 'Approved'
                   AND YEAR(al.fromDate) = ?
                   AND al.typeOfLeave NOT IN ('Maternity Leave')
               WHERE 
                   e.organisationID = ? AND
                   e.isActive = 1
               GROUP BY
                   e.employeeID,
                   e.employeeName,
                   e.empID,
                   e.Designation,
                   b.branchName
               HAVING total > 0
               ORDER BY 
                   total DESC,
                   e.employeeName ASC
           ) subquery,
           (SELECT @row_number := 0) r";

$stmt = mysqli_prepare($connect_var, $query);
if (!$stmt) {
throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
}

mysqli_stmt_bind_param($stmt, "si", 
$year,
$this->organisationID
);

if (!mysqli_stmt_execute($stmt)) {
throw new Exception("Database execute failed: " . mysqli_error($connect_var));
}

$result = mysqli_stmt_get_result($stmt);
$leaveReport = [];
while ($row = mysqli_fetch_assoc($result)) {
$leaveReport[] = $row;
}

mysqli_stmt_close($stmt);

$response = [
"status" => "success",
"data" => $leaveReport
];
echo json_encode($response);
}

private function getMonthlyManagementLeaveReport($connect_var) {
// Convert single month number to YYYY-MM format
$formattedMonth = '2025-' . str_pad($this->selectedMonth, 2, '0', STR_PAD_LEFT);

$query = "
           SELECT 
               (@row_number := @row_number + 1) as sNo,
               subquery.*
           FROM (
               SELECT 
                   e.employeeName,
                   e.empID as employeeCode,
                   e.Designation,
                   b.branchName,
                   COALESCE(SUM(CASE WHEN al.typeOfLeave = 'Casual Leave' THEN al.leaveDuration ELSE 0 END), 0) as cl,
                   COALESCE(SUM(CASE WHEN al.typeOfLeave = 'Privilege Leave' THEN al.leaveDuration ELSE 0 END), 0) as pl,
                   COALESCE(SUM(CASE WHEN al.typeOfLeave = 'Privilege Leave(Medical Grounds)' THEN al.leaveDuration ELSE 0 END), 0) as plMedical,
                   COALESCE(SUM(CASE WHEN al.typeOfLeave = 'Medical Leave' THEN al.leaveDuration ELSE 0 END), 0) as sl,
                   COALESCE(SUM(CASE 
                       WHEN al.typeOfLeave NOT IN ('Maternity Leave') 
                       THEN al.leaveDuration 
                       ELSE 0 
                   END), 0) as total
               FROM 
                   tblEmployee e
               LEFT JOIN 
                   tblmapEmp m ON e.employeeID = m.employeeID
               LEFT JOIN 
                   tblBranch b ON m.branchID = b.branchID
               LEFT JOIN 
                   tblApplyLeave al ON e.employeeID = al.employeeID 
                   AND al.status = 'Approved'
                   AND DATE_FORMAT(al.fromDate, '%Y-%m') = ?
                   AND al.typeOfLeave NOT IN ('Maternity Leave')
               WHERE 
                   e.organisationID = ? AND
                   e.isActive = 1
               GROUP BY
                   e.employeeID,
                   e.employeeName,
                   e.empID,
                   e.Designation,
                   b.branchName
               HAVING total > 0
               ORDER BY 
                   total DESC,
                   e.employeeName ASC
           ) subquery,
           (SELECT @row_number := 0) r";

$stmt = mysqli_prepare($connect_var, $query);
if (!$stmt) {
throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
}

mysqli_stmt_bind_param($stmt, "si", 
$formattedMonth,
$this->organisationID
);

if (!mysqli_stmt_execute($stmt)) {
throw new Exception("Database execute failed: " . mysqli_error($connect_var));
}

$result = mysqli_stmt_get_result($stmt);
$leaveReport = [];
while ($row = mysqli_fetch_assoc($result)) {
$leaveReport[] = $row;
}

mysqli_stmt_close($stmt);

$response = [
"status" => "success",
"data" => $leaveReport
];
echo json_encode($response);
}

public function GetDesignationWiseLeaveReport() {
include(dirname(__FILE__) . '/../../config.inc');
header('Content-Type: application/json');
try {
// Check if this is a yearly request (format: 2025-01 means year 2025)
$isYearlyRequest = false;
$year = '';
if (preg_match('/^(\d{4})-01$/', $this->selectedMonth, $matches)) {
$isYearlyRequest = true;
$year = $matches[1];
}

if ($isYearlyRequest) {
// Handle yearly report
$this->getYearlyDesignationWiseLeaveReport($year, $connect_var);
} else {
// Handle monthly report (existing logic)
$this->getMonthlyDesignationWiseLeaveReport($connect_var);
}

} catch (Exception $e) {
echo json_encode([
"status" => "error",
"message_text" => $e->getMessage()
], JSON_FORCE_OBJECT);
}
}

private function getYearlyDesignationWiseLeaveReport($year, $connect_var) {
// Define the order of designations
$designationOrder = [
'Deputy General Manager',
'Assistant General Manager', 
'IT Specialist',
'PA to Executive',
'Chief Manager',
'Manager',
'Assistant Manager',
'System Admin',
'Assistant',
'Teller',
'Sub Staff',
'Intern',
'Sweeper'
];

// Get leave data for the entire year
$query = "
           SELECT 
               e.Designation,
               MONTH(al.fromDate) as leaveMonth,
               al.fromDate,
               al.toDate,
               e.employeeID,
               e.employeeName,
               e.empID,
               al.typeOfLeave,
               al.leaveDuration
           FROM 
               tblEmployee e
           JOIN 
               tblApplyLeave al ON e.employeeID = al.employeeID
           WHERE 
               e.organisationID = ? AND
               e.isActive = 1 AND
               al.status = 'Approved' AND
               YEAR(al.fromDate) = ?
           ORDER BY 
               al.fromDate";

$stmt = mysqli_prepare($connect_var, $query);
if (!$stmt) {
throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
}

mysqli_stmt_bind_param($stmt, "is", 
$this->organisationID,
$year
);

if (!mysqli_stmt_execute($stmt)) {
throw new Exception("Database execute failed: " . mysqli_error($connect_var));
}

$result = mysqli_stmt_get_result($stmt);
$leaveData = [];
while ($row = mysqli_fetch_assoc($result)) {
if (!isset($leaveData[$row['Designation']])) {
$leaveData[$row['Designation']] = [
'counts' => array_fill(0, 12, 0), // 12 months
'details' => array_fill(0, 12, '')
];
}

// Process leave duration for each month
$fromDate = new DateTime($row['fromDate']);
$toDate = new DateTime($row['toDate']);
$yearStart = new DateTime("$year-01-01");
$yearEnd = new DateTime("$year-12-31");

// Adjust dates to be within the year
$startDate = max($fromDate, $yearStart);
$endDate = min($toDate, $yearEnd);

$typeOfLeave = $row['typeOfLeave'] == 'Privilege Leave(Medical Grounds)' ? 'PL(Medical)' : $row['typeOfLeave'];
$employeeDetail = $row['employeeName'] . ' (' . $row['empID'] . ' - ' . $typeOfLeave . ')';

// Calculate leave days per month
$currentDate = clone $startDate;
while ($currentDate <= $endDate) {
$monthIndex = (int)$currentDate->format('n') - 1; // 0-based month index
$leaveData[$row['Designation']]['counts'][$monthIndex]++;

if ($leaveData[$row['Designation']]['details'][$monthIndex] != '') {
$leaveData[$row['Designation']]['details'][$monthIndex] .= ', ';
}
$leaveData[$row['Designation']]['details'][$monthIndex] .= $employeeDetail;

$currentDate->add(new DateInterval('P1D'));
}
}

mysqli_stmt_close($stmt);

// Format the response with all designations in order
$formattedReport = [];
foreach ($designationOrder as $designation) {
$row = [
'designation' => $designation
];

// Initialize all months with 0 count
for ($i = 0; $i < 12; $i++) {
$row['month_' . ($i + 1)] = 0;
}

// If this designation has leave data, use it
if (isset($leaveData[$designation])) {
foreach ($leaveData[$designation]['counts'] as $index => $count) {
$row['month_' . ($index + 1)] = $count;
if ($count > 0) {
$row['details_' . ($index + 1)] = $leaveData[$designation]['details'][$index];
}
}
}

$formattedReport[] = $row;
}

$response = [
"status" => "success",
"data" => [
"dates" => [], // Empty for yearly report
"report" => $formattedReport
]
];

echo json_encode($response);
}

private function getMonthlyDesignationWiseLeaveReport($connect_var) {
// Original monthly logic
// First get all dates in the selected month
$dates = [];
$daysInMonth = date('t', strtotime($this->selectedMonth));
$monthStart = date('Y-m-01', strtotime($this->selectedMonth));
$monthEnd = date('Y-m-t', strtotime($this->selectedMonth));

for ($i = 1; $i <= $daysInMonth; $i++) {
$dates[] = date('Y-m-d', strtotime($this->selectedMonth . '-' . $i));
}

// Define the order of designations
$designationOrder = [
'Deputy General Manager',
'Assistant General Manager', 
'IT Specialist',
'PA to Executive',
'Chief Manager',
'Manager',
'Assistant Manager',
'System Admin',
'Assistant',
'Teller',
'Sub Staff',
'Intern',
'Sweeper'
];

// Get leave data for the month - using dashboard logic (count ALL approved leaves)
$query = "
           SELECT 
               e.Designation,
               al.fromDate,
               al.toDate,
               e.employeeID,
               e.employeeName,
               e.empID,
               al.typeOfLeave
           FROM 
               tblEmployee e
           JOIN 
               tblApplyLeave al ON e.employeeID = al.employeeID
           WHERE 
               e.organisationID = ? AND
               e.isActive = 1 AND
               al.status = 'Approved' AND
               (
                   (al.fromDate >= ? AND al.fromDate <= ?) OR
                   (al.toDate >= ? AND al.toDate <= ?) OR
                   (al.fromDate <= ? AND al.toDate >= ?)
               )
           ORDER BY 
               al.fromDate";

$stmt = mysqli_prepare($connect_var, $query);
if (!$stmt) {
throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
}

mysqli_stmt_bind_param($stmt, "sssssss", 
$this->organisationID,
$monthStart,
$monthEnd,
$monthStart,
$monthEnd,
$monthStart,
$monthEnd
);

if (!mysqli_stmt_execute($stmt)) {
throw new Exception("Database execute failed: " . mysqli_error($connect_var));
}

$result = mysqli_stmt_get_result($stmt);
$leaveData = [];
while ($row = mysqli_fetch_assoc($result)) {
if (!isset($leaveData[$row['Designation']])) {
$leaveData[$row['Designation']] = [
'counts' => array_fill(0, $daysInMonth, 0),
'details' => array_fill(0, $daysInMonth, '')
];
}

// Process each day in the leave period
$fromDate = new DateTime($row['fromDate']);
$toDate = new DateTime($row['toDate']);
$monthStartDate = new DateTime($monthStart);
$monthEndDate = new DateTime($monthEnd);

// Adjust dates to be within the month
$startDate = max($fromDate, $monthStartDate);
$endDate = min($toDate, $monthEndDate);

$typeOfLeave = $row['typeOfLeave'] == 'PrivilegeLeave(Medical Grounds)' ? 'PL(Medical)' : $row['typeOfLeave'];
$employeeDetail = $row['employeeName'] . ' (' . $row['empID'] . ' - ' . $typeOfLeave . ')';

while ($startDate <= $endDate) {
$day = (int)$startDate->format('d') - 1;
$leaveData[$row['Designation']]['counts'][$day]++;
if ($leaveData[$row['Designation']]['details'][$day] != '') {
$leaveData[$row['Designation']]['details'][$day] .= ', ';
}
$leaveData[$row['Designation']]['details'][$day] .= $employeeDetail;
$startDate->add(new DateInterval('P1D'));
}
}

mysqli_stmt_close($stmt);

// Format the response with all designations in order
$formattedReport = [];
foreach ($designationOrder as $designation) {
$row = [
'designation' => $designation
];

// Initialize all days with 0 count
for ($i = 0; $i < $daysInMonth; $i++) {
$row['day_' . ($i + 1)] = 0;
}

// If this designation has leave data, use it
if (isset($leaveData[$designation])) {
foreach ($leaveData[$designation]['counts'] as $index => $count) {
$row['day_' . ($index + 1)] = $count;
if ($count > 0) {
$row['details_' . ($index + 1)] = $leaveData[$designation]['details'][$index];
}
}
}

$formattedReport[] = $row;
}

$response = [
"status" => "success",
"data" => [
"dates" => $dates,
"report" => $formattedReport
]
];

echo json_encode($response);
}

public function GetMonthlyAttendanceSummaryReport() {
include(dirname(__FILE__) . '/../../config.inc');
header('Content-Type: application/json');
try {
// Get year and month
$currentYear = date('Y');
$selectedMonth = str_pad($this->selectedMonth, 2, '0', STR_PAD_LEFT);
$monthStart = "$currentYear-$selectedMonth-01";
$monthEnd = date('Y-m-t', strtotime($monthStart));

// Get current date to limit data "as of today"
$currentDate = date('Y-m-d');
$currentDay = (int)date('d');
$isCurrentMonth = (date('Y-m') === "$currentYear-$selectedMonth");

// If we're in the selected month, limit to today's date
if ($isCurrentMonth) {
$monthEnd = min($monthEnd, $currentDate);
}

// Get month name for display
$monthNames = [
1 => 'JANUARY', 2 => 'FEBRUARY', 3 => 'MARCH', 4 => 'APRIL',
5 => 'MAY', 6 => 'JUNE', 7 => 'JULY', 8 => 'AUGUST',
9 => 'SEPTEMBER', 10 => 'OCTOBER', 11 => 'NOVEMBER', 12 => 'DECEMBER'
];
$monthName = $monthNames[$this->selectedMonth] ?? '';

// 1. Get working days for the month - use numeric month format
$workingDaysQuery = "SELECT noOfWorkingDays FROM tblworkingdays WHERE monthName = ? AND year = ?";
$stmt = mysqli_prepare($connect_var, $workingDaysQuery);
mysqli_stmt_bind_param($stmt, "ss", $selectedMonth, $currentYear);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$workingDaysData = mysqli_fetch_assoc($result);
$totalWorkingDays = $workingDaysData['noOfWorkingDays'] ?? 0;

// Working days will be calculated later based on current month or full month
$workingDays = $totalWorkingDays;
mysqli_stmt_close($stmt);

// 2. Get all active, non-temporary employees for the org
$employeeQuery = "SELECT e.employeeID FROM tblEmployee e JOIN tblmapEmp m ON e.employeeID = m.employeeID WHERE m.organisationID = ? AND e.isActive = 1 AND e.isTemporary = 0";
$stmt = mysqli_prepare($connect_var, $employeeQuery);
mysqli_stmt_bind_param($stmt, "i", $this->organisationID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$employeeIDs = [];
while ($row = mysqli_fetch_assoc($result)) {
$employeeIDs[] = $row['employeeID'];
}
mysqli_stmt_close($stmt);
$totalEmployees = count($employeeIDs);

// 3. Fetch all attendance records for these employees up to today
$attendanceQuery = "SELECT a.employeeID, a.attendanceDate, a.checkInTime FROM tblAttendance a WHERE a.employeeID IN (" . implode(",", array_fill(0, count($employeeIDs), '?')) . ") AND a.attendanceDate BETWEEN ? AND ?";
$types = str_repeat('i', count($employeeIDs)) . 'ss';
$params = array_merge($employeeIDs, [$monthStart, $monthEnd]);
$stmt = mysqli_prepare($connect_var, $attendanceQuery);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$attendanceMap = [];
while ($row = mysqli_fetch_assoc($result)) {
$attendanceMap[$row['employeeID'] . '_' . $row['attendanceDate']] = $row['checkInTime'];
}
mysqli_stmt_close($stmt);

// 4. Fetch all approved leaves for these employees up to today using dashboard logic
$leaveQuery = "SELECT al.employeeID, al.fromDate, al.toDate FROM tblApplyLeave al 
               JOIN tblmapEmp map ON al.employeeID = map.employeeID 
               WHERE al.employeeID IN (" . implode(",", array_fill(0, count($employeeIDs), '?')) . ") 
               AND al.status = 'Approved' 
               AND map.organisationID = ? 
               AND ((al.fromDate <= ? AND al.toDate >= ?) OR (al.fromDate >= ? AND al.fromDate <= ?))";
$types = str_repeat('i', count($employeeIDs)) . 'issss';
$params = array_merge($employeeIDs, [$this->organisationID, $monthEnd, $monthStart, $monthStart, $monthEnd]);
$stmt = mysqli_prepare($connect_var, $leaveQuery);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$leaveMap = [];
while ($row = mysqli_fetch_assoc($result)) {
$from = $row['fromDate'];
$to = $row['toDate'];
$emp = $row['employeeID'];
$period = new DatePeriod(new DateTime($from), new DateInterval('P1D'), (new DateTime($to))->modify('+1 day'));
foreach ($period as $date) {
$d = $date->format('Y-m-d');
// Only count leaves up to today for current month
if (date('Y-m') === "$currentYear-$selectedMonth" && $d > $currentDate) {
continue;
}
$leaveMap[$emp . '_' . $d] = true;
}
}
mysqli_stmt_close($stmt);

// 5. Calculate counts up to today
$presentCount = 0;
$leaveCount = 0;
$absentCount = 0;

// For current month, only count up to today
if ($isCurrentMonth) {
$workingDaysUpToToday = 0;
$saturdayCount = 0;
for ($day = 1; $day <= $currentDay; $day++) {
$date = date('Y-m-d', strtotime("$currentYear-$selectedMonth-$day"));
$dayOfWeek = date('N', strtotime($date)); // 6 = Saturday, 7 = Sunday
$isWorkingDay = false;
if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
$isWorkingDay = true;
error_log("Working day: $date (Weekday)");
} elseif ($dayOfWeek == 6) {
$saturdayCount++;
if ($saturdayCount == 1 || $saturdayCount == 3) {
$isWorkingDay = true;
error_log("Working day: $date (Saturday, Nth $saturdayCount)");
}
}
if ($isWorkingDay && !$this->isHoliday($date, $connect_var)) {
$workingDaysUpToToday++;
} else if ($isWorkingDay) {
error_log("Holiday: $date");
}
}
$workingDays = $workingDaysUpToToday;
$totalManDays = $totalEmployees * $workingDays;
// Only count attendance and leave for working days up to today
$presentCount = 0;
$leaveCount = 0;
foreach ($employeeIDs as $emp) {
$saturdayCount = 0;
for ($day = 1; $day <= $currentDay; $day++) {
$date = date('Y-m-d', strtotime("$currentYear-$selectedMonth-$day"));
$dayOfWeek = date('N', strtotime($date));
$isWorkingDay = false;
if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
$isWorkingDay = true;
} elseif ($dayOfWeek == 6) {
$saturdayCount++;
if ($saturdayCount == 1 || $saturdayCount == 3) {
$isWorkingDay = true;
}
}
if ($isWorkingDay && !$this->isHoliday($date, $connect_var)) {
$key = $emp . '_' . $date;
if (isset($attendanceMap[$key]) && $attendanceMap[$key]) {
$presentCount++;
} elseif (isset($leaveMap[$key])) {
$leaveCount++;
}
}
}
}
$absentCount = $totalManDays - $presentCount - $leaveCount;
if ($absentCount < 0) $absentCount = 0;
} else {
$daysInMonth = date('t', strtotime($monthStart));
$workingDaysFullMonth = 0;
$saturdayCount = 0;
for ($day = 1; $day <= $daysInMonth; $day++) {
$date = date('Y-m-d', strtotime("$currentYear-$selectedMonth-$day"));
$dayOfWeek = date('N', strtotime($date));
$isWorkingDay = false;
if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
$isWorkingDay = true;
error_log("Working day: $date (Weekday)");
} elseif ($dayOfWeek == 6) {
$saturdayCount++;
if ($saturdayCount == 1 || $saturdayCount == 3) {
$isWorkingDay = true;
error_log("Working day: $date (Saturday, Nth $saturdayCount)");
}
}
if ($isWorkingDay && !$this->isHoliday($date, $connect_var)) {
$workingDaysFullMonth++;
} else if ($isWorkingDay) {
error_log("Holiday: $date");
}
}
$workingDays = $workingDaysFullMonth;
$totalManDays = $totalEmployees * $workingDays;
$presentCount = 0;
$leaveCount = 0;
foreach ($employeeIDs as $emp) {
$saturdayCount = 0;
for ($day = 1; $day <= $daysInMonth; $day++) {
$date = date('Y-m-d', strtotime("$currentYear-$selectedMonth-$day"));
$dayOfWeek = date('N', strtotime($date));
$isWorkingDay = false;
if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
$isWorkingDay = true;
} elseif ($dayOfWeek == 6) {
$saturdayCount++;
if ($saturdayCount == 1 || $saturdayCount == 3) {
$isWorkingDay = true;
}
}
if ($isWorkingDay && !$this->isHoliday($date, $connect_var)) {
$key = $emp . '_' . $date;
if (isset($attendanceMap[$key]) && $attendanceMap[$key]) {
$presentCount++;
} elseif (isset($leaveMap[$key])) {
$leaveCount++;
}
}
}
}
$absentCount = $totalManDays - $presentCount - $leaveCount;
if ($absentCount < 0) $absentCount = 0;
}

// 6. Calculate percentages
$presentPercentage = $totalManDays > 0 ? number_format(($presentCount / $totalManDays) * 100, 2, '.', '') : "0.00";
$absentPercentage = $totalManDays > 0 ? number_format(($absentCount / $totalManDays) * 100, 2, '.', '') : "0.00";
$leavePercentage = $totalManDays > 0 ? number_format(($leaveCount / $totalManDays) * 100, 2, '.', '') : "0.00";

// Add "as of" indicator for current month
$asOfText = $isCurrentMonth ? " (as of " . date('d M Y') . ")" : "";

// Debug logging
error_log("Monthly Attendance Summary Debug:");
error_log("Current Date: " . $currentDate);
error_log("Current Day: " . $currentDay);
error_log("Selected Month: " . $selectedMonth);
error_log("Is Current Month: " . ($isCurrentMonth ? 'Yes' : 'No'));
error_log("Working Days (Total): " . $totalWorkingDays);
error_log("Working Days (Final): " . $workingDays);
error_log("Total Man Days: " . $totalManDays);
error_log("Present Count: " . $presentCount);
error_log("Absent Count: " . $absentCount);
error_log("Leave Count: " . $leaveCount);
error_log("Present %: " . $presentPercentage);
error_log("Absent %: " . $absentPercentage);
error_log("Leave %: " . $leavePercentage);

$response = [
"status" => "success",
"data" => [
"month" => $monthName . $asOfText,
"totalEmployees" => $totalEmployees,
"workingDays" => $workingDays,
"totalManDays" => $totalManDays,
"presentCount" => $presentCount,
"absentCount" => $absentCount,
"leaveCount" => $leaveCount,
"presentPercentage" => $presentPercentage,
"absentPercentage" => $absentPercentage,
"leavePercentage" => $leavePercentage,
"isCurrentMonth" => $isCurrentMonth,
// For pie chart
"pieChart" => [
["label" => "Present", "value" => (float)$presentPercentage],
["label" => "Leave", "value" => (float)$leavePercentage],
["label" => "Absent", "value" => (float)$absentPercentage],
]
]
];
echo json_encode($response);
} catch (Exception $e) {
echo json_encode([
"status" => "error",
"message_text" => $e->getMessage()
], JSON_FORCE_OBJECT);
}
}

public function loadSelectedMonth(array $data) {
if (isset($data['selectedMonth'])) {
$this->selectedMonth = $data['selectedMonth'];
}
if (isset($data['selectedYear'])) {
$this->selectedYear = $data['selectedYear'];
}
return true;
}

public function loadEmployeeType(array $data) {
error_log("loadEmployeeType called with data: " . json_encode($data));
if (isset($data['employeeType'])) {
$this->employeeType = $data['employeeType'];
error_log("Employee type set to: " . $this->employeeType);
return true;
}
error_log("Employee type not found in data, using default (0)");
return false;
}

private function isHoliday($date, $connect_var) {
$formattedDate = date('Y-m-d', strtotime($date));
$sql = "SELECT COUNT(*) as count FROM tblHoliday WHERE date = ?";
$stmt = mysqli_prepare($connect_var, $sql);
mysqli_stmt_bind_param($stmt, "s", $formattedDate);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
return $row['count'] > 0;
}

public function GetEmployees() {
include(dirname(__FILE__) . '/../../config.inc');
header('Content-Type: application/json');
try {
$query = "SELECT 
               employeeID,
               empID,
               employeeName,
               Designation
           FROM 
               tblEmployee 
           WHERE 
               organisationID = ? AND
               isActive = 1
           ORDER BY 
               employeeName ASC";

$stmt = mysqli_prepare($connect_var, $query);
if (!$stmt) {
throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
}

mysqli_stmt_bind_param($stmt, "i", $this->organisationID);

if (!mysqli_stmt_execute($stmt)) {
throw new Exception("Database execute failed: " . mysqli_error($connect_var));
}

$result = mysqli_stmt_get_result($stmt);
$employees = [];
while ($row = mysqli_fetch_assoc($result)) {
$employees[] = $row;
}

mysqli_stmt_close($stmt);

$response = [
"status" => "success",
"data" => $employees
];
echo json_encode($response);

} catch (Exception $e) {
echo json_encode([
"status" => "error",
"message_text" => $e->getMessage()
], JSON_FORCE_OBJECT);
}
}

public function GetEmployeeLeaveReport() {
include(dirname(__FILE__) . '/../../config.inc');
header('Content-Type: application/json');
try {
// Get current date for calculating working days up to today for current month
$currentDate = new DateTime();
$currentYear = (int)$currentDate->format('Y');
$currentMonth = (int)$currentDate->format('n');
$currentDay = (int)$currentDate->format('j');

// First get employee details
$employeeQuery = "SELECT 
               employeeID,
               empID,
               employeeName,
               Designation
           FROM 
               tblEmployee 
           WHERE 
               employeeID = ? AND
               organisationID = ? AND
               isActive = 1";

$stmt = mysqli_prepare($connect_var, $employeeQuery);
if (!$stmt) {
throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
}

mysqli_stmt_bind_param($stmt, "ii", $this->employeeID, $this->organisationID);

if (!mysqli_stmt_execute($stmt)) {
throw new Exception("Database execute failed: " . mysqli_error($connect_var));
}

$result = mysqli_stmt_get_result($stmt);
$employee = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$employee) {
throw new Exception("Employee not found");
}

// Get leave data for the year
$leaveQuery = "SELECT 
               MONTH(fromDate) as month,
               fromDate,
               toDate,
               typeOfLeave,
               leaveDuration,
               reason
           FROM 
               tblApplyLeave 
           WHERE 
               employeeID = ? AND
               status = 'Approved' AND
               YEAR(fromDate) = ? AND
               (MONTH(fromDate) != ? OR fromDate <= ?)
           ORDER BY 
               fromDate";

$stmt = mysqli_prepare($connect_var, $leaveQuery);
if (!$stmt) {
throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
}

mysqli_stmt_bind_param($stmt, "isis", $this->employeeID, $this->selectedYear, $currentMonth, $currentDate->format('Y-m-d'));

if (!mysqli_stmt_execute($stmt)) {
throw new Exception("Database execute failed: " . mysqli_error($connect_var));
}

$result = mysqli_stmt_get_result($stmt);
$leaveData = [];
$monthlyData = array_fill(1, 12, [
'leaveCount' => 0,
'leaveDetails' => [],
'dates' => []
]);

while ($row = mysqli_fetch_assoc($result)) {
$month = (int)$row['month'];
$fromDate = new DateTime($row['fromDate']);
$toDate = new DateTime($row['toDate']);
$yearStart = new DateTime("{$this->selectedYear}-01-01");
$yearEnd = new DateTime("{$this->selectedYear}-12-31");

// Adjust dates to be within the year
$startDate = max($fromDate, $yearStart);
$endDate = min($toDate, $yearEnd);

$typeOfLeave = $row['typeOfLeave'] == 'Privilege Leave(Medical Grounds)' ? 'PL(Medical)' : $row['typeOfLeave'];
$leaveDetail = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y') . ' (' . $typeOfLeave . ')';

// Calculate leave days per month
$currentLeaveDate = clone $startDate;
$daysInMonth = 0;
$datesInMonth = [];

while ($currentLeaveDate <= $endDate) {
$currentLeaveMonth = (int)$currentLeaveDate->format('n');
if ($currentLeaveMonth === $month) {
// For current month, only count leaves up to current date
if ($month == $currentMonth && $currentYear == (int)$this->selectedYear) {
    if ($currentLeaveDate <= $currentDate) {
        $daysInMonth++;
        $datesInMonth[] = $currentLeaveDate->format('Y-m-d');
    }
} else {
    $daysInMonth++;
    $datesInMonth[] = $currentLeaveDate->format('Y-m-d');
}
}
$currentLeaveDate->add(new DateInterval('P1D'));
}

if ($daysInMonth > 0) {
$monthlyData[$month]['leaveCount'] += $daysInMonth;
$monthlyData[$month]['leaveDetails'][] = $leaveDetail;
$monthlyData[$month]['dates'] = array_merge($monthlyData[$month]['dates'], $datesInMonth);
}
}

mysqli_stmt_close($stmt);

// Get working days for the year
$workingDaysQuery = "SELECT 
               monthName,
               noOfWorkingDays
           FROM 
               tblworkingdays 
           WHERE 
               year = ?";

$stmt = mysqli_prepare($connect_var, $workingDaysQuery);
if (!$stmt) {
    throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
}

mysqli_stmt_bind_param($stmt, "s", $this->selectedYear);

if (!mysqli_stmt_execute($stmt)) {
    throw new Exception("Database execute failed: " . mysqli_error($connect_var));
}

$result = mysqli_stmt_get_result($stmt);
$workingDaysData = array_fill(1, 12, 0);

while ($row = mysqli_fetch_assoc($result)) {
    $monthName = $row['monthName'];
    $monthNumber = date('n', strtotime("2000-$monthName-01")); // Convert month name to number
    $workingDaysData[$monthNumber] = (int)$row['noOfWorkingDays'];
}

mysqli_stmt_close($stmt);

// Get attendance data for the year (excluding leave days)
$attendanceQuery = "SELECT 
               MONTH(a.attendanceDate) as month,
               a.attendanceDate,
               a.checkInTime,
               a.checkOutTime,
               a.isLateCheckIN,
               a.isEarlyCheckOut
           FROM 
               tblAttendance a
           WHERE 
               a.employeeID = ? AND
               YEAR(a.attendanceDate) = ? AND
               a.checkInTime IS NOT NULL AND
               NOT EXISTS (
                   SELECT 1 FROM tblApplyLeave al 
                   WHERE al.employeeID = a.employeeID 
                   AND al.status = 'Approved'
                   AND a.attendanceDate BETWEEN al.fromDate AND al.toDate
               ) AND
               (MONTH(a.attendanceDate) != ? OR a.attendanceDate <= ?)
           ORDER BY 
               a.attendanceDate";

$stmt = mysqli_prepare($connect_var, $attendanceQuery);
if (!$stmt) {
throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
}

mysqli_stmt_bind_param($stmt, "isis", $this->employeeID, $this->selectedYear, $currentMonth, $currentDate->format('Y-m-d'));

if (!mysqli_stmt_execute($stmt)) {
throw new Exception("Database execute failed: " . mysqli_error($connect_var));
}

$result = mysqli_stmt_get_result($stmt);
$attendanceData = array_fill(1, 12, [
'presentCount' => 0,
'lateCheckIns' => [],
'earlyCheckOuts' => []
]);

while ($row = mysqli_fetch_assoc($result)) {
$month = (int)$row['month'];
$attendanceData[$month]['presentCount']++;

if ($row['isLateCheckIN'] == 1) {
$attendanceData[$month]['lateCheckIns'][] = $row['attendanceDate'];
}

if ($row['isEarlyCheckOut'] == 1) {
$attendanceData[$month]['earlyCheckOuts'][] = $row['attendanceDate'];
}
}

mysqli_stmt_close($stmt);

// Format the response
$formattedLeaveData = [];
foreach ($monthlyData as $month => $data) {
    $workingDays = $workingDaysData[$month];
    $presentCount = $attendanceData[$month]['presentCount'];
    $leaveCount = $data['leaveCount'];
    
    // For current month, use total working days from tblworkingdays table
    // Present days and leave days are calculated up to today from the queries
if ($month == $currentMonth && $currentYear == (int)$this->selectedYear) {
    // Use total working days for the month from tblworkingdays table
    $workingDays = $workingDaysData[$month];
    
    // Log for debugging
    error_log("Current month ({$month}) working days: Using total month working days = {$workingDays}");
}
    
    // Calculate working days, present days, absent days, and leave days using new logic
    if ($month == $currentMonth && $currentYear == (int)$this->selectedYear) {
        // For current month: Calculate working days up to today minus holidays minus present days minus leave days
        
        // Get number of days till today for this month
        $daysTillToday = $currentDay;
        
        // Get number of holidays till today for this month
        $holidayQuery = "SELECT COUNT(*) AS numberOfHolidays 
                        FROM tblHoliday 
                        WHERE MONTH(date) = MONTH(CURDATE()) 
                        AND YEAR(date) = YEAR(CURDATE()) 
                        AND date <= CURDATE()";
        
        $stmt = mysqli_prepare($connect_var, $holidayQuery);
        if (!$stmt) {
            throw new Exception("Database prepare failed for holiday query: " . mysqli_error($connect_var));
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $holidayRow = mysqli_fetch_assoc($result);
        $numberOfHolidays = (int)$holidayRow['numberOfHolidays'];
        mysqli_stmt_close($stmt);
        
        // Calculate working days up to today
        $workingDaysUpToToday = $daysTillToday - $numberOfHolidays;
        
        // Get present days up to today
        $presentDaysUpToToday = $presentCount;
        
        // Get leave days up to today
        $leaveDaysUpToToday = $leaveCount;
        
        // Calculate absent days: Working days up to today - Present days - Leave days
        $absentDays = max(0, $workingDaysUpToToday - $presentDaysUpToToday - $leaveDaysUpToToday);
        
        // Update working days to show working days up to today for current month
        $workingDays = $workingDaysUpToToday;
    } else {
        
        // Working days from tblworkingdays table
        $workingDays = $workingDaysData[$month];
        
        // Present days and leave days from queries
        $presentDaysUpToToday = $presentCount;
        $leaveDaysUpToToday = $leaveCount;
        
        // Calculate absent days: Working days - Present days - Leave days
        $absentDays = max(0, $workingDays - $presentDaysUpToToday - $leaveDaysUpToToday);
    }
    
    $formattedLeaveData[] = [
        'month' => str_pad($month, 2, '0', STR_PAD_LEFT),
        'leaveCount' => $data['leaveCount'],
        'leaveDetails' => $data['leaveDetails'],
        'dates' => array_unique($data['dates']),
        'presentCount' => $presentDaysUpToToday,
        'absentDays' => $absentDays,
        'lateCheckIns' => $attendanceData[$month]['lateCheckIns'],
        'earlyCheckOuts' => $attendanceData[$month]['earlyCheckOuts'],
        'workingDays' => $workingDays
    ];
}

$totalLeaves = array_sum(array_column($formattedLeaveData, 'leaveCount'));

$response = [
"status" => "success",
"data" => [
"employee" => $employee,
"leaveData" => $formattedLeaveData,
"totalLeaves" => $totalLeaves
]
];

echo json_encode($response);

} catch (Exception $e) {
echo json_encode([
"status" => "error",
"message_text" => $e->getMessage()
], JSON_FORCE_OBJECT);
}
}

public function GetDailyCheckoutReport() {
include(dirname(__FILE__) . '/../../config.inc');
header('Content-Type: application/json');
try {
$query = "SELECT 
               e.employeeID,
               e.empID,
               e.employeeName,
               e.Designation,
               b.branchName,
               a.checkInTime,
               a.checkOutTime,
               a.isAutoCheckout,
               a.TotalWorkingHour,
               a.reasonForCheckOut
           FROM 
               tblEmployee e
           LEFT JOIN 
               tblAttendance a ON e.employeeID = a.employeeID AND a.attendanceDate = ?
           LEFT JOIN 
               tblmapEmp m ON e.employeeID = m.employeeID
           LEFT JOIN 
               tblBranch b ON m.branchID = b.branchID
           WHERE 
               e.organisationID = ? AND
               e.isActive = 1 AND
               e.isTemporary = 0 AND
               a.attendanceDate IS NOT NULL
           ORDER BY 
               a.isAutoCheckout DESC,
               e.employeeName ASC";

$stmt = mysqli_prepare($connect_var, $query);
if (!$stmt) {
throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
}

mysqli_stmt_bind_param($stmt, "si", $this->selectedDate, $this->organisationID);

if (!mysqli_stmt_execute($stmt)) {
throw new Exception("Database execute failed: " . mysqli_error($connect_var));
}

$result = mysqli_stmt_get_result($stmt);
$checkoutData = [];
$autoCheckoutCount = 0;
$manualCheckoutCount = 0;
$noCheckoutCount = 0;

while ($row = mysqli_fetch_assoc($result)) {
$checkoutStatus = 'No Checkout';
$checkoutType = 'None';

if ($row['checkOutTime']) {
if ($row['isAutoCheckout'] == 1) {
$checkoutStatus = 'Auto Checkout';
$checkoutType = 'Auto';
$autoCheckoutCount++;
} else {
$checkoutStatus = 'Manual Checkout';
$checkoutType = 'Manual';
$manualCheckoutCount++;
}
} else {
$noCheckoutCount++;
}

$checkoutData[] = [
'employeeID' => $row['employeeID'],
'empID' => $row['empID'],
'employeeName' => $row['employeeName'],
'designation' => $row['Designation'],
'branchName' => $row['branchName'],
'checkInTime' => $row['checkInTime'],
'checkOutTime' => $row['checkOutTime'],
'totalWorkingHour' => $row['TotalWorkingHour'],
'checkoutStatus' => $checkoutStatus,
'checkoutType' => $checkoutType,
'reasonForCheckOut' => $row['reasonForCheckOut']
];
}

mysqli_stmt_close($stmt);

$response = [
"status" => "success",
"data" => [
"checkoutData" => $checkoutData,
"summary" => [
"autoCheckoutCount" => $autoCheckoutCount,
"manualCheckoutCount" => $manualCheckoutCount,
"noCheckoutCount" => $noCheckoutCount,
"totalEmployees" => count($checkoutData)
],
"selectedDate" => $this->selectedDate
]
];

echo json_encode($response);

} catch (Exception $e) {
echo json_encode([
"status" => "error",
"message_text" => $e->getMessage()
], JSON_FORCE_OBJECT);
}
}

public function loadSelectedDate(array $data) {
if (isset($data['selectedDate'])) {
$this->selectedDate = $data['selectedDate'];
return true;
}
return false;
}

    public function GetMonthlyCheckoutReport() {
        include(dirname(__FILE__) . '/../../config.inc');
        header('Content-Type: application/json');
        try {
            // Get year and month - use current year
            $currentYear = date('Y');
            $selectedMonth = str_pad($this->selectedMonth, 2, '0', STR_PAD_LEFT);
            $monthStart = "$currentYear-$selectedMonth-01";
            $monthEnd = date('Y-m-t', strtotime($monthStart));

            // Get current date to limit data "as of today"
            $currentDate = date('Y-m-d');
            $isCurrentMonth = (date('Y-m') === "$currentYear-$selectedMonth");

            // If we're in the selected month, limit to today's date
            if ($isCurrentMonth) {
                $monthEnd = min($monthEnd, $currentDate);
            }

            // Get employee type filter - default to permanent (0) if not specified
            $employeeType = isset($this->employeeType) ? $this->employeeType : 0;
            error_log("Final employee type used in query: " . $employeeType);



            // Calculate working days using the same logic as employee leave report
            $monthNames = [
                1 => 'JANUARY', 2 => 'FEBRUARY', 3 => 'MARCH', 4 => 'APRIL',
                5 => 'MAY', 6 => 'JUNE', 7 => 'JULY', 8 => 'AUGUST',
                9 => 'SEPTEMBER', 10 => 'OCTOBER', 11 => 'NOVEMBER', 12 => 'DECEMBER'
            ];
            $monthName = $monthNames[$this->selectedMonth] ?? '';

            if ($isCurrentMonth) {
                // For current month: Calculate working days up to today minus holidays
                $currentDay = (int)date('j'); // Current day of month
                
                // Get number of holidays till today for this month
                $holidayQuery = "SELECT COUNT(*) AS numberOfHolidays 
                                FROM tblHoliday 
                                WHERE MONTH(date) = MONTH(CURDATE()) 
                                AND YEAR(date) = YEAR(CURDATE()) 
                                AND date <= CURDATE()";
                
                $stmt = mysqli_prepare($connect_var, $holidayQuery);
                if (!$stmt) {
                    throw new Exception("Database prepare failed for holiday query: " . mysqli_error($connect_var));
                }
                
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $holidayRow = mysqli_fetch_assoc($result);
                $numberOfHolidays = (int)$holidayRow['numberOfHolidays'];
                mysqli_stmt_close($stmt);
                
                // Calculate working days up to today
                $totalWorkingDays = $currentDay - $numberOfHolidays;
                
                // Log for debugging (commented out for production)
                // error_log("Current month working days calculation:");
                // error_log("Days till today: {$currentDay}");
                // error_log("Number of holidays: {$numberOfHolidays}");
                // error_log("Working days up to today: {$totalWorkingDays}");
                
            } else {
                // For previous months: Use working days from tblworkingdays table
                $workingDaysQuery = "SELECT noOfWorkingDays FROM tblworkingdays WHERE monthName = ? AND year = ?";
                $workingDaysStmt = mysqli_prepare($connect_var, $workingDaysQuery);
                mysqli_stmt_bind_param($workingDaysStmt, "ss", $monthName, $currentYear);
                mysqli_stmt_execute($workingDaysStmt);
                $workingDaysResult = mysqli_stmt_get_result($workingDaysStmt);
                $workingDaysRow = mysqli_fetch_assoc($workingDaysResult);
                $totalWorkingDays = $workingDaysRow['noOfWorkingDays'] ?? 0;
                mysqli_stmt_close($workingDaysStmt);
            }

            $query = "
               SELECT 
                   (@row_number := @row_number + 1) as sNo,
                   e.employeeID,
                   e.empID,
                   e.employeeName,
                   e.Designation,
                   ? as total_working_days, -- Working days (up to today for current month, full month for previous months)
                   COALESCE(attendance_stats.present_days, 0) as present_days, -- Present days (up to today for current month)
                   COALESCE(leave_stats.leave_days, 0) as leave_days, -- Leave days (up to today for current month)
                   GREATEST(0, ? - COALESCE(attendance_stats.present_days, 0) - COALESCE(leave_stats.leave_days, 0)) as absent_days, -- Absent days = Working days - Present days - Leave days
                   GREATEST(0, COALESCE(attendance_stats.present_days, 0) - ?) as extra_days,
                   COALESCE(attendance_stats.late_checkins, 0) as late_checkins,
                   COALESCE(attendance_stats.early_checkouts, 0) as early_checkouts,
                   COALESCE(attendance_stats.auto_checkouts, 0) as auto_checkouts,
                   attendance_stats.late_checkin_dates,
                   attendance_stats.early_checkout_dates,
                   attendance_stats.auto_checkout_dates
               FROM 
                   tblEmployee e
                   JOIN tblmapEmp emp_map ON e.employeeID = emp_map.employeeID
               LEFT JOIN (
                   SELECT 
                       a.employeeID,
                       COUNT(CASE 
                           WHEN a.checkInTime IS NOT NULL 
                           AND NOT EXISTS (
                               SELECT 1 FROM tblApplyLeave al 
                               WHERE al.employeeID = a.employeeID 
                               AND al.status = 'Approved'
                               AND a.attendanceDate BETWEEN al.fromDate AND al.toDate
                           )
                           THEN 1 
                           END) as present_days,
                       COUNT(CASE 
                           WHEN a.checkInTime IS NOT NULL 
                           AND b.checkInTime IS NOT NULL 
                           AND a.checkInTime > b.checkInTime 
                           THEN 1 
                           END) as late_checkins,
                       COUNT(CASE 
                           WHEN a.checkOutTime IS NOT NULL 
                           AND b.checkOutTime IS NOT NULL 
                           AND a.checkOutTime < b.checkOutTime 
                           THEN 1 
                           END) as early_checkouts,
                       COUNT(CASE WHEN a.isAutoCheckout = 1 THEN 1 END) as auto_checkouts,
                       GROUP_CONCAT(
                           CASE 
                               WHEN a.checkInTime IS NOT NULL 
                               AND b.checkInTime IS NOT NULL 
                               AND a.checkInTime > b.checkInTime
                               THEN DATE_FORMAT(a.attendanceDate, '%d/%m/%Y') 
                               ELSE NULL 
                           END
                           ORDER BY a.attendanceDate
                           SEPARATOR ', '
                       ) as late_checkin_dates,
                       GROUP_CONCAT(
                           CASE 
                               WHEN a.checkOutTime IS NOT NULL 
                               AND b.checkOutTime IS NOT NULL 
                               AND a.checkOutTime < b.checkOutTime
                               THEN DATE_FORMAT(a.attendanceDate, '%d/%m/%Y') 
                               ELSE NULL 
                           END
                           ORDER BY a.attendanceDate
                           SEPARATOR ', '
                       ) as early_checkout_dates,
                       GROUP_CONCAT(
                           CASE WHEN a.isAutoCheckout = 1 
                               THEN DATE_FORMAT(a.attendanceDate, '%d/%m/%Y') 
                               ELSE NULL 
                           END
                           ORDER BY a.attendanceDate
                           SEPARATOR ', '
                       ) as auto_checkout_dates
                   FROM 
                       tblAttendance a
                   JOIN tblmapEmp m ON a.employeeID = m.employeeID
                   LEFT JOIN tblBranch b ON m.branchID = b.branchID
                   WHERE 
                       a.attendanceDate BETWEEN ? AND ? -- Date range (up to today for current month, full month for previous months)
                       AND m.organisationID = ?
                   GROUP BY 
                       a.employeeID
               ) attendance_stats ON e.employeeID = attendance_stats.employeeID
               LEFT JOIN (
                   SELECT 
                       al.employeeID,
                       COUNT(DISTINCT leave_date.date) as leave_days
                   FROM 
                       tblApplyLeave al
                   CROSS JOIN (
                       SELECT DATE(?) + INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY as date
                       FROM (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) as a
                       CROSS JOIN (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) as b
                       CROSS JOIN (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) as c
                   ) leave_date
                   WHERE 
                       al.status = 'Approved'
                       AND leave_date.date BETWEEN al.fromDate AND al.toDate
                       AND leave_date.date BETWEEN ? AND ? -- Date range (up to today for current month, full month for previous months)
                       AND DAYOFWEEK(leave_date.date) NOT IN (1, 7)
                   GROUP BY 
                       al.employeeID
               ) leave_stats ON e.employeeID = leave_stats.employeeID,
               (SELECT @row_number := 0) r
               WHERE 
                   emp_map.organisationID = ? AND
                   e.isActive = 1 AND
                   e.isTemporary = ?
               ORDER BY 
                   e.employeeName ASC";

$stmt = mysqli_prepare($connect_var, $query);
if (!$stmt) {
throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
}

mysqli_stmt_bind_param($stmt, "iiissssssii", 
$totalWorkingDays,
$totalWorkingDays,
$totalWorkingDays,
$monthStart,
$monthEnd,
$this->organisationID,
$monthStart,
$monthStart,
$monthEnd,
$this->organisationID,
$employeeType
);

if (!mysqli_stmt_execute($stmt)) {
throw new Exception("Database execute failed: " . mysqli_error($connect_var));
}

$result = mysqli_stmt_get_result($stmt);
$checkoutReport = [];
while ($row = mysqli_fetch_assoc($result)) {
$checkoutReport[] = $row;
}

mysqli_stmt_close($stmt);





$response = [
"status" => "success",
"data" => $checkoutReport
];
echo json_encode($response);

} catch (Exception $e) {
echo json_encode([
"status" => "error",
"message_text" => $e->getMessage()
], JSON_FORCE_OBJECT);
}
}

    public function DebugAutoCheckoutRecords() {
        include(dirname(__FILE__) . '/../../config.inc');
        header('Content-Type: application/json');
        try {
            $currentYear = date('Y');
            $selectedMonth = str_pad($this->selectedMonth, 2, '0', STR_PAD_LEFT);
            $monthStart = "$currentYear-$selectedMonth-01";
            $monthEnd = date('Y-m-t', strtotime($monthStart));
            
            // Debug query to check auto checkout records
            $debugQuery = "
                SELECT 
                    COUNT(*) as total_auto_checkouts,
                    COUNT(DISTINCT employeeID) as unique_employees_with_auto_checkout,
                    GROUP_CONCAT(DISTINCT employeeID) as employee_ids_with_auto_checkout
                FROM tblAttendance 
                WHERE isAutoCheckout = 1 
                AND attendanceDate BETWEEN ? AND ?
                AND organisationID = ?";
            
            $stmt = mysqli_prepare($connect_var, $debugQuery);
            if (!$stmt) {
                throw new Exception("Database prepare failed: " . mysqli_error($connect_var));
            }

            mysqli_stmt_bind_param($stmt, "ssi", 
                $monthStart,
                $monthEnd,
                $this->organisationID
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Database execute failed: " . mysqli_error($connect_var));
            }

            $result = mysqli_stmt_get_result($stmt);
            $debugData = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            // Also check the table structure
            $structureQuery = "DESCRIBE tblAttendance";
            $structureResult = mysqli_query($connect_var, $structureQuery);
            $tableStructure = [];
            while ($row = mysqli_fetch_assoc($structureResult)) {
                $tableStructure[] = $row;
            }

            // Check for any auto checkout records in the entire table
            $totalQuery = "SELECT COUNT(*) as total_records FROM tblAttendance WHERE isAutoCheckout = 1";
            $totalResult = mysqli_query($connect_var, $totalQuery);
            $totalData = mysqli_fetch_assoc($totalResult);

            $response = [
                "status" => "success",
                "data" => [
                    "debug_info" => [
                        "monthStart" => $monthStart,
                        "monthEnd" => $monthEnd,
                        "organisationID" => $this->organisationID,
                        "selectedMonth" => $this->selectedMonth
                    ],
                    "auto_checkout_records" => $debugData,
                    "total_auto_checkout_records" => $totalData,
                    "table_structure" => $tableStructure
                ]
            ];
            echo json_encode($response);

        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }

    public function GetWorkingDays() {
        include('config.inc');
        header('Content-Type: application/json');

        try {
            $selectedMonth = isset($this->selectedMonth) ? $this->selectedMonth : date('n');
            $selectedYear = isset($this->selectedYear) ? $this->selectedYear : date('Y');
            
            error_log("GetWorkingDays called with month: $selectedMonth, year: $selectedYear");

            // Try numeric month first, then text month
            $query = "SELECT noOfWorkingDays, monthName FROM tblworkingdays WHERE (monthName = ? OR monthName = ?) AND year = ?";
            $stmt = mysqli_prepare($connect_var, $query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . mysqli_error($connect_var));
            }

            // Convert numeric month to text month name
            $monthNames = [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
            ];
            $textMonth = isset($monthNames[$selectedMonth]) ? $monthNames[$selectedMonth] : '';

            mysqli_stmt_bind_param($stmt, "ssi", $selectedMonth, $textMonth, $selectedYear);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to execute statement: " . mysqli_error($connect_var));
            }

            $result = mysqli_stmt_get_result($stmt);
            if (!$result) {
                throw new Exception("Failed to get result: " . mysqli_error($connect_var));
            }

            $data = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if ($data) {
                echo json_encode([
                    "status" => "success",
                    "data" => [
                        "noOfWorkingDays" => (int)$data['noOfWorkingDays'],
                        "monthName" => $data['monthName'],
                        "month" => $selectedMonth,
                        "year" => $selectedYear
                    ]
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => "No working days found for the selected month and year"
                ]);
            }

        } catch (Exception $e) {
            error_log("Error in GetWorkingDays: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
}
?>