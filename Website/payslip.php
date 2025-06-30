<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if required parameters are present
if (!isset($_GET['EmpID']) || !isset($_GET['Month']) || !isset($_GET['Year']) || !isset($_GET['OrgID'])) {
    die("Missing required parameters. Please provide EmpID, Month, Year, and OrgID");
}

$employeeId = $_GET['EmpID'];
$monthName = $_GET['Month'];
$year = $_GET['Year'];
$organisationId = $_GET['OrgID'];

// Fix month name handling - convert to proper format for date parsing
$monthNameUpper = strtoupper($monthName);
if ($monthNameUpper == 'ARPIL') {
    $monthNameUpper = 'APRIL'; // Fix the typo for date parsing
}

// Get month number (1-12) for database queries
$month = date('n', strtotime("1 $monthNameUpper 2000")); // 1-based month number

// Check if config file exists
if (!file_exists('../config.inc')) {
    die("Config file not found: ../config.inc");
}

include('../config.inc');

// Check if database connection is successful
if (!isset($connect_var) || !$connect_var) {
    die("Database connection failed");
}

// Fetch organisation details
$queryOrg = "SELECT * FROM tblOrganisation WHERE organisationID = '$organisationId'";
$stmtOrg = mysqli_query($connect_var, $queryOrg);

if (!$stmtOrg) {
    die("Organisation query failed: " . mysqli_error($connect_var));
}

$orgName = '';
$orgLogo = '';
$orgAddress1 = '';
$orgAddress2 = '';
$orgCity = '';
$orgState = '';
$orgWebsite = '';
$orgPhone = '';

while($row = mysqli_fetch_assoc($stmtOrg)) {
  $orgName = $row['organisationName'];
  $orgLogo = $row['organisationLogo'];
  $orgAddress1 = $row['AddressLine1'];
  $orgAddress2 = $row['AddressLine2'];
  $orgCity = $row['City'];
  $orgState = $row['State'];
  $orgWebsite = $row['website'];
  $orgPhone = $row['PhoneNumber'];
}

// Fetch employee details with section and branch information
$queryEmp = "SELECT e.*, s.SectionName, b.branchName 
             FROM tblEmployee e 
             LEFT JOIN tblAssignedSection a ON e.employeeID = a.employeeID AND a.isActive = 1
             LEFT JOIN tblSection s ON a.sectionID = s.SectionID 
             LEFT JOIN tblmapEmp m ON e.employeeID = m.employeeID
             LEFT JOIN tblBranch b ON m.branchID = b.branchID
             WHERE e.empID = '$employeeId' AND e.organisationID = '$organisationId'";
$stmtEmp = mysqli_query($connect_var, $queryEmp);

if (!$stmtEmp) {
    die("Employee query failed: " . mysqli_error($connect_var));
}

$employee = mysqli_fetch_assoc($stmtEmp);

// Fetch working days for the specified month and year
$queryWorkingDays = "SELECT noOfWorkingDays FROM tblworkingdays WHERE monthName = '$month' AND year = '$year'";
$stmtWorkingDays = mysqli_query($connect_var, $queryWorkingDays);
$workingDays = 0;
if ($stmtWorkingDays && $workingDaysRow = mysqli_fetch_assoc($stmtWorkingDays)) {
    $workingDays = $workingDaysRow['noOfWorkingDays'];
}

// If not found with month number, try with month name
if ($workingDays == 0) {
    // Handle the typo in database (ARPIL instead of APRIL)
    $dbMonthName = strtoupper($monthName);
    if ($dbMonthName == 'APRIL') {
        $dbMonthName = 'ARPIL'; // Fix the typo for database query
    }
    
    $queryWorkingDaysByName = "SELECT noOfWorkingDays FROM tblworkingdays WHERE monthName = '$dbMonthName' AND year = '$year'";
    $stmtWorkingDaysByName = mysqli_query($connect_var, $queryWorkingDaysByName);
    if ($stmtWorkingDaysByName && $workingDaysRow = mysqli_fetch_assoc($stmtWorkingDaysByName)) {
        $workingDays = $workingDaysRow['noOfWorkingDays'];
    }
}

// Calculate LOP (Loss of Pay) - days with no check-in and check-out
$lopDays = 0;
if ($employee) {
    $employeeID = $employee['employeeID'];
    
    // Use proper date parsing for month start and end
    $monthStart = date('Y-m-01', strtotime("$year-$month-01"));
    $monthEnd = date('Y-m-t', strtotime("$year-$month-01"));
    
    // Count days where employee has no attendance record or no check-in/check-out
    $queryLOP = "SELECT COUNT(*) as absentDays 
                  FROM (
                    SELECT DATE(attendanceDate) as workDate
                    FROM tblAttendance 
                    WHERE employeeID = '$employeeID' 
                    AND organisationID = '$organisationId'
                    AND attendanceDate BETWEEN '$monthStart' AND '$monthEnd'
                    AND (checkInTime IS NULL OR checkOutTime IS NULL)
                  ) as absentDays";
    
    $stmtLOP = mysqli_query($connect_var, $queryLOP);
    if ($stmtLOP && $lopRow = mysqli_fetch_assoc($stmtLOP)) {
        $lopDays = $lopRow['absentDays'];
    }
}

// Debug working days query
echo "<!-- Working Days Query: $queryWorkingDays -->";
echo "<!-- Working Days Result: " . ($workingDaysRow ? 'FOUND' : 'NOT FOUND') . " -->";
if ($workingDaysRow) {
    echo "<!-- Working Days Value: " . $workingDaysRow['noOfWorkingDays'] . " -->";
}

// Debug LOP calculation
echo "<!-- LOP Query: $queryLOP -->";
echo "<!-- LOP Days: $lopDays -->";
echo "<!-- Month Start: $monthStart, Month End: $monthEnd -->";

// Check all working days records for debugging
$queryAllWorkingDays = "SELECT monthName, year, noOfWorkingDays FROM tblworkingdays WHERE year = '$year'";
$stmtAllWorkingDays = mysqli_query($connect_var, $queryAllWorkingDays);
echo "<!-- All working days for year $year: -->";
if ($stmtAllWorkingDays) {
    while ($row = mysqli_fetch_assoc($stmtAllWorkingDays)) {
        echo "<!-- Month: " . $row['monthName'] . ", Year: " . $row['year'] . ", Days: " . $row['noOfWorkingDays'] . " -->";
    }
} else {
    echo "<!-- Error fetching working days: " . mysqli_error($connect_var) . " -->";
}

// Debug information
echo "<!-- Employee ID: $employeeId -->";
echo "<!-- Organisation ID: $organisationId -->";
echo "<!-- Month: $monthName, Month Number: $month, Year: $year -->";
echo "<!-- Working Days: $workingDays -->";
echo "<!-- LOP Days: $lopDays -->";

// Initialize employee variables
$employeeName = '';
$empID = '';
$joiningDate = '';
$bankName = '';
$bankAccountNumber = '';
$designation = '';
$panNumber = '';
$pfNumber = '';
$pfUAN = '';
$branchName = '';
$department = '';

if ($employee) {
  $employeeName = $employee['employeeName'] ?? '';
  $empID = $employee['empID'] ?? '';
  $joiningDate = $employee['joiningDate'] ?? '';
  $bankName = $employee['BankName'] ?? '';
  $bankAccountNumber = $employee['BankAccountNumber'] ?? '';
  $designation = $employee['Designation'] ?? '';
  $panNumber = $employee['PANNumber'] ?? '';
  $pfNumber = $employee['PFNumber'] ?? '';
  $pfUAN = $employee['PFUAN'] ?? '';
  $department = $employee['SectionName'] ?? '';
  $branchName = $employee['branchName'] ?? '';
}

// --- Logo path handling ---
$logoWebPath = '';
if (!empty($orgLogo)) {
  // Direct path - use the orgLogo value as is
  $logoWebPath = '/vidupuApi/' . $orgLogo;
}

echo "<!-- orgLogo: $orgLogo -->";
echo "<!-- logoWebPath: $logoWebPath -->";
echo "<!-- Current Organisation ID: $organisationId -->";

// Fetch all earnings for the employee for the given month/year
$earnings = [];
$totalEarnings = 0;

// Debug: show empID, month, year before query
$debugEmpID = var_export($empID, true);
echo "<!-- DEBUG: empID used in query: $debugEmpID -->";
echo "<!-- DEBUG: month: $monthName, year: $year -->";
echo "<!-- DEBUG: month number: $month -->";

// First, let's check if the employee exists in tblAccounts
$checkEmpQuery = "SELECT COUNT(*) as count FROM tblAccounts WHERE TRIM(empID) = '$empID'";
$checkEmpResult = mysqli_query($connect_var, $checkEmpQuery);
if ($checkEmpResult) {
    $empCount = mysqli_fetch_assoc($checkEmpResult);
    echo "<!-- DEBUG: Employee records in tblAccounts: " . $empCount['count'] . " -->";
}

// Check for any accounts data for this employee
$checkAccountsQuery = "SELECT month, year, COUNT(*) as count 
                       FROM tblAccounts 
                       WHERE TRIM(empID) = '$empID' 
                       GROUP BY month, year 
                       ORDER BY year DESC, month DESC 
                       LIMIT 5";
$checkAccountsResult = mysqli_query($connect_var, $checkAccountsQuery);
if ($checkAccountsResult) {
    echo "<!-- DEBUG: Recent accounts data for employee: -->";
    while ($row = mysqli_fetch_assoc($checkAccountsResult)) {
        echo "<!-- Month: " . $row['month'] . ", Year: " . $row['year'] . ", Count: " . $row['count'] . " -->";
    }
}

// Let's also check what account types exist
$checkAccountTypesQuery = "SELECT accountTypeID, accountTypeName, typeOfAccount FROM tblAccountType WHERE typeOfAccount = 'earnings'";
$checkAccountTypesResult = mysqli_query($connect_var, $checkAccountTypesQuery);
if ($checkAccountTypesResult) {
    echo "<!-- DEBUG: Available earnings account types: -->";
    while ($row = mysqli_fetch_assoc($checkAccountTypesResult)) {
        echo "<!-- ID: " . $row['accountTypeID'] . ", Name: " . $row['accountTypeName'] . ", Type: " . $row['typeOfAccount'] . " -->";
    }
}

// Let's check the actual data in tblAccounts for this employee
$checkActualDataQuery = "SELECT a.empID, a.month, a.year, a.amount, t.accountTypeName, t.typeOfAccount 
                         FROM tblAccounts a 
                         JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID 
                         WHERE a.empID = '$empID' 
                         ORDER BY a.year DESC, a.month DESC 
                         LIMIT 10";
$checkActualDataResult = mysqli_query($connect_var, $checkActualDataQuery);
if ($checkActualDataResult) {
    echo "<!-- DEBUG: Actual accounts data for employee: -->";
    while ($row = mysqli_fetch_assoc($checkActualDataResult)) {
        echo "<!-- EmpID: " . $row['empID'] . ", Month: " . $row['month'] . ", Year: " . $row['year'] . ", Amount: " . $row['amount'] . ", Type: " . $row['accountTypeName'] . " -->";
    }
}

$queryEarnings = "
    SELECT t.accountTypeName, a.amount
    FROM tblAccounts a
    JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
    WHERE TRIM(a.empID) = '$empID'
      AND a.month = $month
      AND a.year = $year
      AND t.typeOfAccount = 'earnings'
";
echo "<!-- DEBUG: Earnings Query: $queryEarnings -->";

$resultEarnings = mysqli_query($connect_var, $queryEarnings);
if (!$resultEarnings) {
    echo "<!-- MySQL Error: " . mysqli_error($connect_var) . " -->";
} else {
    $rowCount = mysqli_num_rows($resultEarnings);
    echo "<!-- DEBUG: Earnings query returned $rowCount rows -->";
}

while ($row = mysqli_fetch_assoc($resultEarnings)) {
    echo "<!-- Row: " . print_r($row, true) . " -->";
    $earnings[$row['accountTypeName']] = $row['amount'];
    $totalEarnings += $row['amount'];
}

// If no earnings found, try alternative queries
if (empty($earnings)) {
    echo "<!-- DEBUG: No earnings found, trying alternative queries -->";
    
    // Try without TRIM
    $altQuery1 = "
        SELECT t.accountTypeName, a.amount
        FROM tblAccounts a
        JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
        WHERE a.empID = '$empID'
          AND a.month = $month
          AND a.year = $year
          AND t.typeOfAccount = 'earnings'
    ";
    echo "<!-- DEBUG: Alternative Query 1: $altQuery1 -->";
    
    $altResult1 = mysqli_query($connect_var, $altQuery1);
    if ($altResult1) {
        $altRowCount1 = mysqli_num_rows($altResult1);
        echo "<!-- DEBUG: Alternative query 1 returned $altRowCount1 rows -->";
        while ($row = mysqli_fetch_assoc($altResult1)) {
            echo "<!-- Alt Row 1: " . print_r($row, true) . " -->";
            $earnings[$row['accountTypeName']] = $row['amount'];
            $totalEarnings += $row['amount'];
        }
    }
    
    // If still no results, try with string month
    if (empty($earnings)) {
        $altQuery2 = "
            SELECT t.accountTypeName, a.amount
            FROM tblAccounts a
            JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
            WHERE a.empID = '$empID'
              AND a.month = '$month'
              AND a.year = '$year'
              AND t.typeOfAccount = 'earnings'
        ";
        echo "<!-- DEBUG: Alternative Query 2: $altQuery2 -->";
        
        $altResult2 = mysqli_query($connect_var, $altQuery2);
        if ($altResult2) {
            $altRowCount2 = mysqli_num_rows($altResult2);
            echo "<!-- DEBUG: Alternative query 2 returned $altRowCount2 rows -->";
            while ($row = mysqli_fetch_assoc($altResult2)) {
                echo "<!-- Alt Row 2: " . print_r($row, true) . " -->";
                $earnings[$row['accountTypeName']] = $row['amount'];
                $totalEarnings += $row['amount'];
            }
        }
    }
    
    // If still no results, try to get the most recent month's data as fallback
    if (empty($earnings)) {
        echo "<!-- DEBUG: No data found for requested month, trying to get most recent data -->";
        $fallbackQuery = "
            SELECT t.accountTypeName, a.amount, a.month, a.year
            FROM tblAccounts a
            JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
            WHERE a.empID = '$empID'
              AND t.typeOfAccount = 'earnings'
            ORDER BY a.year DESC, a.month DESC
            LIMIT 10
        ";
        echo "<!-- DEBUG: Fallback Query: $fallbackQuery -->";
        
        $fallbackResult = mysqli_query($connect_var, $fallbackQuery);
        if ($fallbackResult) {
            $fallbackRowCount = mysqli_num_rows($fallbackResult);
            echo "<!-- DEBUG: Fallback query returned $fallbackRowCount rows -->";
            
            $fallbackMonth = '';
            $fallbackYear = '';
            while ($row = mysqli_fetch_assoc($fallbackResult)) {
                echo "<!-- Fallback Row: " . print_r($row, true) . " -->";
                if (empty($fallbackMonth)) {
                    $fallbackMonth = $row['month'];
                    $fallbackYear = $row['year'];
                }
                $earnings[$row['accountTypeName']] = $row['amount'];
                $totalEarnings += $row['amount'];
            }
            
            if (!empty($earnings)) {
                echo "<!-- DEBUG: Using fallback data from month $fallbackMonth, year $fallbackYear -->";
                $usingFallbackData = true;
                $fallbackDataNote = "Note: Data shown is from " . date('F Y', strtotime("$fallbackYear-$fallbackMonth-01")) . " as no data was available for " . date('F Y', strtotime("$year-$month-01"));
            }
        }
    }
}

echo "<!-- DEBUG: Final Earnings Array: ";
print_r($earnings);
echo " -->";
echo "<!-- DEBUG: Total Earnings: $totalEarnings -->";

// Fetch all deductions for the employee for the given month/year
$deductions = [];
$totalDeductions = 0;

echo "<!-- DEBUG: Fetching deductions for empID: $empID, month: $month, year: $year -->";

$queryDeductions = "
    SELECT t.accountTypeName, a.amount
    FROM tblAccounts a
    JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
    WHERE TRIM(a.empID) = '$empID'
      AND a.month = $month
      AND a.year = $year
      AND t.typeOfAccount = 'deductions'
";
echo "<!-- DEBUG: Deductions Query: $queryDeductions -->";

$resultDeductions = mysqli_query($connect_var, $queryDeductions);
if (!$resultDeductions) {
    echo "<!-- MySQL Error (Deductions): " . mysqli_error($connect_var) . " -->";
} else {
    $deductionsRowCount = mysqli_num_rows($resultDeductions);
    echo "<!-- DEBUG: Deductions query returned $deductionsRowCount rows -->";
}

while ($row = mysqli_fetch_assoc($resultDeductions)) {
    echo "<!-- Deduction Row: " . print_r($row, true) . " -->";
    $deductions[$row['accountTypeName']] = $row['amount'];
    $totalDeductions += $row['amount'];
}

// If no deductions found, try alternative queries
if (empty($deductions)) {
    echo "<!-- DEBUG: No deductions found, trying alternative queries -->";
    
    // Try without TRIM
    $altDeductionsQuery1 = "
        SELECT t.accountTypeName, a.amount
        FROM tblAccounts a
        JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
        WHERE a.empID = '$empID'
          AND a.month = $month
          AND a.year = $year
          AND t.typeOfAccount = 'deductions'
    ";
    echo "<!-- DEBUG: Alternative Deductions Query 1: $altDeductionsQuery1 -->";
    
    $altDeductionsResult1 = mysqli_query($connect_var, $altDeductionsQuery1);
    if ($altDeductionsResult1) {
        $altDeductionsRowCount1 = mysqli_num_rows($altDeductionsResult1);
        echo "<!-- DEBUG: Alternative deductions query 1 returned $altDeductionsRowCount1 rows -->";
        while ($row = mysqli_fetch_assoc($altDeductionsResult1)) {
            echo "<!-- Alt Deduction Row 1: " . print_r($row, true) . " -->";
            $deductions[$row['accountTypeName']] = $row['amount'];
            $totalDeductions += $row['amount'];
        }
    }
    
    // If still no results, try with string month
    if (empty($deductions)) {
        $altDeductionsQuery2 = "
            SELECT t.accountTypeName, a.amount
            FROM tblAccounts a
            JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
            WHERE a.empID = '$empID'
              AND a.month = '$month'
              AND a.year = '$year'
              AND t.typeOfAccount = 'deductions'
        ";
        echo "<!-- DEBUG: Alternative Deductions Query 2: $altDeductionsQuery2 -->";
        
        $altDeductionsResult2 = mysqli_query($connect_var, $altDeductionsQuery2);
        if ($altDeductionsResult2) {
            $altDeductionsRowCount2 = mysqli_num_rows($altDeductionsResult2);
            echo "<!-- DEBUG: Alternative deductions query 2 returned $altDeductionsRowCount2 rows -->";
            while ($row = mysqli_fetch_assoc($altDeductionsResult2)) {
                echo "<!-- Alt Deduction Row 2: " . print_r($row, true) . " -->";
                $deductions[$row['accountTypeName']] = $row['amount'];
                $totalDeductions += $row['amount'];
            }
        }
    }
    
    // If still no results, try to get the most recent month's deductions data as fallback
    if (empty($deductions)) {
        echo "<!-- DEBUG: No deductions found for requested month, trying to get most recent data -->";
        $fallbackDeductionsQuery = "
            SELECT t.accountTypeName, a.amount, a.month, a.year
            FROM tblAccounts a
            JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
            WHERE a.empID = '$empID'
              AND t.typeOfAccount = 'deductions'
            ORDER BY a.year DESC, a.month DESC
            LIMIT 10
        ";
        echo "<!-- DEBUG: Fallback Deductions Query: $fallbackDeductionsQuery -->";
        
        $fallbackDeductionsResult = mysqli_query($connect_var, $fallbackDeductionsQuery);
        if ($fallbackDeductionsResult) {
            $fallbackDeductionsRowCount = mysqli_num_rows($fallbackDeductionsResult);
            echo "<!-- DEBUG: Fallback deductions query returned $fallbackDeductionsRowCount rows -->";
            
            while ($row = mysqli_fetch_assoc($fallbackDeductionsResult)) {
                echo "<!-- Fallback Deduction Row: " . print_r($row, true) . " -->";
                $deductions[$row['accountTypeName']] = $row['amount'];
                $totalDeductions += $row['amount'];
            }
        }
    }
}

echo "<!-- DEBUG: Final Deductions Array: ";
print_r($deductions);
echo " -->";
echo "<!-- DEBUG: Total Deductions: $totalDeductions -->";

// Fetch all loan deductions for the employee for the given month/year
$loanDeductions = [];
$totalLoanDeductions = 0;

echo "<!-- DEBUG: Fetching loan deductions for empID: $empID, month: $month, year: $year -->";

$queryLoanDeductions = "
    SELECT t.accountTypeName, a.amount
    FROM tblAccounts a
    JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
    WHERE TRIM(a.empID) = '$empID'
      AND a.month = $month
      AND a.year = $year
      AND t.typeOfAccount = 'loans'
";
echo "<!-- DEBUG: Loan Deductions Query: $queryLoanDeductions -->";

$resultLoanDeductions = mysqli_query($connect_var, $queryLoanDeductions);
if (!$resultLoanDeductions) {
    echo "<!-- MySQL Error (Loan Deductions): " . mysqli_error($connect_var) . " -->";
} else {
    $loanDeductionsRowCount = mysqli_num_rows($resultLoanDeductions);
    echo "<!-- DEBUG: Loan deductions query returned $loanDeductionsRowCount rows -->";
}

while ($row = mysqli_fetch_assoc($resultLoanDeductions)) {
    echo "<!-- Loan Deduction Row: " . print_r($row, true) . " -->";
    $loanDeductions[$row['accountTypeName']] = $row['amount'];
    $totalLoanDeductions += $row['amount'];
}

// If no loan deductions found, try alternative queries
if (empty($loanDeductions)) {
    echo "<!-- DEBUG: No loan deductions found, trying alternative queries -->";
    
    // Try without TRIM
    $altLoanQuery1 = "
        SELECT t.accountTypeName, a.amount
        FROM tblAccounts a
        JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
        WHERE a.empID = '$empID'
          AND a.month = $month
          AND a.year = $year
          AND t.typeOfAccount = 'loans'
    ";
    echo "<!-- DEBUG: Alternative Loan Query 1: $altLoanQuery1 -->";
    
    $altLoanResult1 = mysqli_query($connect_var, $altLoanQuery1);
    if ($altLoanResult1) {
        $altLoanRowCount1 = mysqli_num_rows($altLoanResult1);
        echo "<!-- DEBUG: Alternative loan query 1 returned $altLoanRowCount1 rows -->";
        while ($row = mysqli_fetch_assoc($altLoanResult1)) {
            echo "<!-- Alt Loan Row 1: " . print_r($row, true) . " -->";
            $loanDeductions[$row['accountTypeName']] = $row['amount'];
            $totalLoanDeductions += $row['amount'];
        }
    }
    
    // If still no results, try with string month
    if (empty($loanDeductions)) {
        $altLoanQuery2 = "
            SELECT t.accountTypeName, a.amount
            FROM tblAccounts a
            JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
            WHERE a.empID = '$empID'
              AND a.month = '$month'
              AND a.year = '$year'
              AND t.typeOfAccount = 'loans'
        ";
        echo "<!-- DEBUG: Alternative Loan Query 2: $altLoanQuery2 -->";
        
        $altLoanResult2 = mysqli_query($connect_var, $altLoanQuery2);
        if ($altLoanResult2) {
            $altLoanRowCount2 = mysqli_num_rows($altLoanResult2);
            echo "<!-- DEBUG: Alternative loan query 2 returned $altLoanRowCount2 rows -->";
            while ($row = mysqli_fetch_assoc($altLoanResult2)) {
                echo "<!-- Alt Loan Row 2: " . print_r($row, true) . " -->";
                $loanDeductions[$row['accountTypeName']] = $row['amount'];
                $totalLoanDeductions += $row['amount'];
            }
        }
    }
    
    // If still no results, try to get the most recent month's loan data as fallback
    if (empty($loanDeductions)) {
        echo "<!-- DEBUG: No loan deductions found for requested month, trying to get most recent data -->";
        $fallbackLoanQuery = "
            SELECT t.accountTypeName, a.amount, a.month, a.year
            FROM tblAccounts a
            JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
            WHERE a.empID = '$empID'
              AND t.typeOfAccount = 'loans'
            ORDER BY a.year DESC, a.month DESC
            LIMIT 10
        ";
        echo "<!-- DEBUG: Fallback Loan Query: $fallbackLoanQuery -->";
        
        $fallbackLoanResult = mysqli_query($connect_var, $fallbackLoanQuery);
        if ($fallbackLoanResult) {
            $fallbackLoanRowCount = mysqli_num_rows($fallbackLoanResult);
            echo "<!-- DEBUG: Fallback loan query returned $fallbackLoanRowCount rows -->";
            
            while ($row = mysqli_fetch_assoc($fallbackLoanResult)) {
                echo "<!-- Fallback Loan Row: " . print_r($row, true) . " -->";
                $loanDeductions[$row['accountTypeName']] = $row['amount'];
                $totalLoanDeductions += $row['amount'];
            }
        }
    }
}

echo "<!-- DEBUG: Final Loan Deductions Array: ";
print_r($loanDeductions);
echo " -->";
echo "<!-- DEBUG: Total Loan Deductions: $totalLoanDeductions -->";

// Function to convert number to words
function numberToWords($number) {
    $ones = array(
        0 => "", 1 => "One", 2 => "Two", 3 => "Three", 4 => "Four", 5 => "Five",
        6 => "Six", 7 => "Seven", 8 => "Eight", 9 => "Nine", 10 => "Ten",
        11 => "Eleven", 12 => "Twelve", 13 => "Thirteen", 14 => "Fourteen", 15 => "Fifteen",
        16 => "Sixteen", 17 => "Seventeen", 18 => "Eighteen", 19 => "Nineteen"
    );
    $tens = array(
        2 => "Twenty", 3 => "Thirty", 4 => "Forty", 5 => "Fifty",
        6 => "Sixty", 7 => "Seventy", 8 => "Eighty", 9 => "Ninety"
    );
    
    if ($number == 0) return "Zero";
    
    $words = "";
    
    if ($number >= 10000000) {
        $crores = floor($number / 10000000);
        $words .= numberToWords($crores) . " Crore ";
        $number %= 10000000;
    }
    
    if ($number >= 100000) {
        $lakhs = floor($number / 100000);
        $words .= numberToWords($lakhs) . " Lakh ";
        $number %= 100000;
    }
    
    if ($number >= 1000) {
        $thousands = floor($number / 1000);
        $words .= numberToWords($thousands) . " Thousand ";
        $number %= 1000;
    }
    
    if ($number >= 100) {
        $hundreds = floor($number / 100);
        $words .= $ones[$hundreds] . " Hundred ";
        $number %= 100;
    }
    
    if ($number >= 20) {
        $tens_digit = floor($number / 10);
        $words .= $tens[$tens_digit] . " ";
        $number %= 10;
    }
    
    if ($number > 0) {
        $words .= $ones[$number] . " ";
    }
    
    return trim($words);
}

// Calculate net pay (total earnings minus deductions and loan deductions)
$netPay = $totalEarnings - $totalDeductions - $totalLoanDeductions;
$netPayInWords = numberToWords($netPay) . " Rupees Only";

// Initialize fallback variables
$usingFallbackData = false;
$fallbackDataNote = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payslip - <?php echo $orgName; ?></title>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 40px;
      color: #000;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
      background: #fff;
    }
    .container {
      border: 1px solid #000;
      padding: 20px;
    }
    h2, h3 {
      text-align: center;
      margin: 10px 0;
    }
    .details {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    .details td {
      border-left: 1px solid #000;
      border-right: 1px solid #000;
      border-top: none;
      border-bottom: none;
      padding: 8px;
    }
    .details tr:first-child td {
      border-top: 1px solid #000;
    }
    .details tr:last-child td {
      border-bottom: 1px solid #000;
    }
    .earnings td, .deductions td, .tax td, .tds td {
      border: 1px solid #000;
      padding: 8px;
    }
    .section-title {
      margin-top: 40px;
      font-weight: bold;
    }
    .org-header {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
    }
    .org-logo {
      height: 80px;
      width: 80px;
      object-fit: contain;
      margin-right: 20px;
      border-radius: 8px;
      background: #f5f5f5;
      border: 1px solid #ccc;
    }
    .org-details {
      flex: 1;
    }
    .org-details h2 {
      text-align: left;
      margin: 0 0 5px 0;
    }
    .org-details .org-meta {
      font-size: 14px;
      color: #333;
      margin-bottom: 2px;
    }
    .org-details .org-meta:last-child {
      margin-bottom: 0;
    }
  </style>
  <style>
    @media (max-width: 700px) {
      table[style*='width:100%'] td {
        display: block;
        width: 100% !important;
      }
      .org-header {
        flex-direction: column;
        align-items: flex-start;
      }
      .org-logo {
        margin-bottom: 10px;
        margin-right: 0;
      }
      .org-details h2 {
        text-align: left;
      }
    }
  </style>
</head>
<body>

<div class="container">
  <div class="org-header">
    <?php if (!empty($logoWebPath)) { ?>
      <img src="<?php echo $logoWebPath; ?>" alt="Logo" class="org-logo">
    <?php } else { ?>
      <!-- No logo found -->
    <?php } ?>
    <div class="org-details">
      <h2><?php echo $orgName; ?></h2>
      <div style="text-align:center; margin-top: 5px;">
        <div class="org-meta">
          <?php echo $orgAddress1; ?><?php if($orgAddress2) echo ', ' . $orgAddress2; ?>
        </div>
        <div class="org-meta">
          <?php echo $orgCity; ?><?php if($orgState) echo ', ' . $orgState; ?>
        </div>
        <div class="org-meta">
          Phone: <?php echo $orgPhone; ?><?php if($orgWebsite) echo ' | Website: ' . $orgWebsite; ?>
        </div>
      </div>
    </div>
  </div>

  <h3>Payslip for the month of <b><?php echo $monthName; ?> - <?php echo $year; ?></b></h3>

  <?php if ($usingFallbackData): ?>
    <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin: 10px 0; border-radius: 4px; color: #856404;">
      <strong>⚠️ Notice:</strong> <?php echo $fallbackDataNote; ?>
    </div>
  <?php endif; ?>

  <table class="details">
    <tr>
      <td><strong>Name:</strong> <?php echo $employeeName ? $employeeName : '_____________'; ?></td>
      <td><strong>Employee No:</strong> <?php echo $empID ? $empID : '_____________'; ?></td>
    </tr>
    <tr>
      <td><strong>Joining Date:</strong> <?php echo $joiningDate ? date('d-m-Y', strtotime($joiningDate)) : '_____________'; ?></td>
      <td><strong>Bank Name:</strong> <?php echo $bankName ? $bankName : '_____________'; ?></td>
    </tr>
    <tr>
      <td><strong>Designation:</strong> <?php echo $designation ? $designation : '_____________'; ?></td>
      <td><strong>Bank Account No:</strong> <?php echo $bankAccountNumber ? $bankAccountNumber : '_____________'; ?></td>
    </tr>
    <tr>
      <td><strong>Department:</strong> <?php echo $department ? $department : '_____________'; ?></td>
      <td><strong>PAN Number:</strong> <?php echo $panNumber ? $panNumber : '_____________'; ?></td>
    </tr>
    <tr>
      <td><strong>Location:</strong> <?php echo $branchName ? $branchName : '_____________'; ?></td>
      <td><strong>PF No:</strong> <?php echo $pfNumber ? $pfNumber : '_____________'; ?></td>
    </tr>
    <tr>
      <td><strong>Effective Work Days:</strong> <?php echo $workingDays; ?></td>
      <td><strong>PF UAN:</strong> <?php echo $pfUAN ? $pfUAN : '_____________'; ?></td>
    </tr>
    <tr>
      <td><strong>LOP:</strong> <?php echo $lopDays; ?></td>
      <td></td>
    </tr>
  </table>

  <!-- Earnings and Deductions Side by Side -->
  <table style="width:100%; margin-top:1px;">
    <tr>
      <!-- Earnings Column -->
      <td style="vertical-align:top; width:50%;">
        <p class="section-title">Earnings</p>
        <table class="earnings">
          <tr><td>BASIC</td><td><?php echo isset($earnings['BASIC']) ? $earnings['BASIC'] : '___________'; ?></td></tr>
          <tr><td>HRA</td><td><?php echo isset($earnings['HRA']) ? $earnings['HRA'] : '___________'; ?></td></tr>
          <tr><td>CONVEYANCE</td><td><?php echo isset($earnings['CONVEYANCE']) ? $earnings['CONVEYANCE'] : '___________'; ?></td></tr>
          <tr><td>MEDICAL ALLOWANCE</td><td><?php echo isset($earnings['MEDICAL ALLOWANCE']) ? $earnings['MEDICAL ALLOWANCE'] : '___________'; ?></td></tr>
          <tr><td>OTHER ALLOWANCE</td><td><?php echo isset($earnings['OTHER ALLOWANCE']) ? $earnings['OTHER ALLOWANCE'] : '___________'; ?></td></tr>
          <?php
            // Display any additional earning types
            $standard = ['BASIC','HRA','CONVEYANCE','MEDICAL ALLOWANCE','OTHER ALLOWANCE'];
            $extraEarningsRows = 0;
            foreach ($earnings as $type => $amount) {
              if (!in_array($type, $standard)) {
                echo "<tr><td>".htmlspecialchars($type)."</td><td>$amount</td></tr>";
                $extraEarningsRows++;
              }
            }
            $earningsRows = 5 + $extraEarningsRows + 1; // 5 standard + extra + total
          ?>
          <tr><td><strong>Total Earnings</strong></td><td><strong><?php echo $totalEarnings; ?></strong></td></tr>
        </table>
      </td>
      <!-- Deductions Column -->
      <td style="vertical-align:top; width:50%;">
        <p class="section-title">Deductions</p>
        <table class="deductions">
          <tr><td>PF</td><td><?php echo isset($deductions['PF']) ? $deductions['PF'] : '___________'; ?></td></tr>
          <tr><td>INCOME TAX</td><td><?php echo isset($deductions['INCOME TAX']) ? $deductions['INCOME TAX'] : '___________'; ?></td></tr>
          <tr><td>MEDICLAIM</td><td><?php echo isset($deductions['MEDICLAIM']) ? $deductions['MEDICLAIM'] : '___________'; ?></td></tr>
          <?php
            // Display any additional deduction types
            $standardDeductions = ['PF','INCOME TAX','MEDICLAIM','TOTAL_DED'];
            $extraDeductionsRows = 0;
            foreach ($deductions as $type => $amount) {
              if (!in_array($type, $standardDeductions)) {
                echo "<tr><td>".htmlspecialchars($type)."</td><td>$amount</td></tr>";
                $extraDeductionsRows++;
              }
            }
            $deductionsRows = 3 + $extraDeductionsRows; // 3 standard + extra
          ?>
        </table>

        <!-- Loan Deductions Table -->
        <p class="section-title">Loan Deductions</p>
        <table class="deductions">
          <tr><td>Housing Loan 1</td><td><?php echo isset($loanDeductions['HL1']) ? $loanDeductions['HL1'] : '___________'; ?></td></tr>
          <tr><td>Housing Loan 2</td><td><?php echo isset($loanDeductions['HOUSING LOAN 2']) ? $loanDeductions['HOUSING LOAN 2'] : '___________'; ?></td></tr>
          <tr><td>Vehicle Loan</td><td><?php echo isset($loanDeductions['VEHICLE LOAN']) ? $loanDeductions['VEHICLE LOAN'] : '___________'; ?></td></tr>
          <tr><td>Personal Loan</td><td><?php echo isset($loanDeductions['SPL PER.LI']) ? $loanDeductions['SPL PER.LI'] : '___________'; ?></td></tr>
          <tr><td>Other Loan</td><td><?php echo isset($loanDeductions['OTHER LOAN']) ? $loanDeductions['OTHER LOAN'] : '___________'; ?></td></tr>
          <?php
            // Display any additional loan types except HL1 and SPL PER.LI (already mapped above)
            $standardLoans = ['HL1','HOUSING LOAN 2','VEHICLE LOAN','SPL PER.LI','OTHER LOAN'];
            $extraLoanRows = 0;
            foreach ($loanDeductions as $type => $amount) {
              if (!in_array($type, $standardLoans)) {
                echo "<tr><td>".htmlspecialchars($type)."</td><td>$amount</td></tr>";
                $extraLoanRows++;
              }
            }
            $loanRows = 5 + $extraLoanRows; // 5 standard + extra
          ?>
        </table>
        <?php
          // Make all tables the same height by adding empty rows
          $maxRows = max($earningsRows, $deductionsRows, $loanRows);
          $deductionsPad = $maxRows - $deductionsRows;
          $loanPad = $maxRows - $loanRows;
          if ($deductionsPad > 0) {
            for ($i = 0; $i < $deductionsPad; $i++) {
              echo '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';
            }
          }
          if ($loanPad > 0) {
            for ($i = 0; $i < $loanPad; $i++) {
              echo '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';
            }
          }
        ?>
      </td>
    </tr>
  </table>

  <div style="margin-top: 8px; margin-bottom: 8px; width: 100%; font-size: 1.1em; display: flex; justify-content: flex-end;">
    <span style="text-align:right; flex: 1;">Total Deductions (Deductions + Loan Deductions)</span>
    <span style="text-align:left; width: 120px; padding-left: 12px;"><?php echo $totalDeductions + $totalLoanDeductions; ?></span>
  </div>

  <div style="margin-top: 4px; margin-bottom: 8px; display: flex; justify-content: flex-end;">
    <div style="border: 2px solid #000; background: #e6ffe6; padding: 12px 24px; font-size: 1.3em; font-weight: bold; display: inline-block;">
      Net Pay for the month: <span style="font-weight:bold; color: #1a6600;"><?php echo $netPay; ?></span>
    </div>
  </div>

  <p><strong>(in words):</strong> <?php echo $netPayInWords; ?></p>

  <p style="margin-top: 20px; color: #888;"><em>This is a computer generated payslip and does not require a signature.</em></p>
</div>

</body>
</html>
 