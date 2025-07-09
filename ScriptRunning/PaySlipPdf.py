import mysql.connector
from jinja2 import Template
import pdfkit
import calendar

# Step 1: Input parameters
EmpID = 706
Month = "June"
Year = 2025
OrgID = 1

# Step 2: Connect to MySQL

conn = mysql.connector.connect(
    host="localhost", user="vsk", password="Password#1", database="tnscVidupuApp"
)
cursor = conn.cursor(dictionary=True)

# Step 3: Fix month name handling - convert to proper format for date parsing
monthNameUpper = Month.upper()
if monthNameUpper == 'ARPIL':
    monthNameUpper = 'APRIL'  # Fix the typo for date parsing

# Get month number (1-12) for database queries
month_names = [name.upper() for name in calendar.month_name]
month = month_names.index(monthNameUpper)  # 1-based month number

print(f"<!-- Employee ID: {EmpID} -->")
print(f"<!-- Organisation ID: {OrgID} -->")
print(f"<!-- Month: {Month}, Month Number: {month}, Year: {Year} -->")

# Step 4: Fetch organisation details
queryOrg = f"SELECT * FROM tblOrganisation WHERE organisationID = '{OrgID}'"
cursor.execute(queryOrg)
org_result = cursor.fetchone()

orgName = ''
orgLogo = ''
orgAddress1 = ''
orgAddress2 = ''
orgCity = ''
orgState = ''
orgWebsite = ''
orgPhone = ''

if org_result:
    orgName = org_result['organisationName'] or ''
    orgLogo = org_result['organisationLogo'] or ''
    orgAddress1 = org_result['AddressLine1'] or ''
    orgAddress2 = org_result['AddressLine2'] or ''
    orgCity = org_result['City'] or ''
    orgState = org_result['State'] or ''
    orgWebsite = org_result['website'] or ''
    orgPhone = org_result['PhoneNumber'] or ''

# Step 5: Add a direct check for the employee data
checkQuery = f"""SELECT empID, bankAccountNumber, PANNumber, PFNumber, PFUAN 
                 FROM tblEmployee 
                 WHERE empID = '{EmpID}'"""
cursor.execute(checkQuery)
checkData = cursor.fetchone()
print(f"<!-- DEBUG: Check Query: {checkQuery} -->")
print(f"<!-- DEBUG: Check Data: {checkData} -->")

# Step 6: Fetch employee details with section and branch information
queryEmp = f"""SELECT 
    e.employeeID,
    e.empID,
    e.employeeName,
    e.joiningDate,
    e.bankName,
    e.bankAccountNumber,
    e.designation,
    e.panNumber,
    e.pfNumber,
    e.pfUAN,
    s.SectionName,
    b.branchName,
    e.organisationID
FROM tblEmployee e 
LEFT JOIN tblAssignedSection a ON e.employeeID = a.employeeID AND a.isActive = 1
LEFT JOIN tblSection s ON a.sectionID = s.SectionID 
LEFT JOIN tblmapEmp m ON e.employeeID = m.employeeID
LEFT JOIN tblBranch b ON m.branchID = b.branchID
WHERE e.empID = '{EmpID}'"""

cursor.execute(queryEmp)
employee = cursor.fetchone()

print(f"<!-- DEBUG: Query: {queryEmp} -->")
print(f"<!-- DEBUG: Employee ID being searched: {EmpID} -->")
print(f"<!-- DEBUG: Raw employee data: {employee} -->")

# Initialize employee variables
employeeName = ''
empID = ''
joiningDate = ''
bankName = ''
bankAccountNumber = ''
designation = ''
panNumber = ''
pfNumber = ''
pfUAN = ''
branchName = ''
department = ''

if employee:
    employeeName = employee['employeeName'] or ''
    empID = employee['empID'] or ''
    joiningDate = employee['joiningDate'] or ''
    bankName = employee['bankName'] or ''
    bankAccountNumber = employee['bankAccountNumber'] or ''
    designation = employee['designation'] or ''
    panNumber = employee['panNumber'] or ''
    pfNumber = employee['pfNumber'] or ''
    pfUAN = employee['pfUAN'] or ''
    department = employee['SectionName'] or ''
    branchName = employee['branchName'] or ''
    
    print(f"<!-- DEBUG: Bank Account Number from DB: {bankAccountNumber} -->")
    print(f"<!-- DEBUG: PAN Number from DB: {panNumber} -->")
    print(f"<!-- DEBUG: PF Number from DB: {pfNumber} -->")
    print(f"<!-- DEBUG: PF UAN from DB: {pfUAN} -->")

# Step 7: Fetch working days for the specified month and year
queryWorkingDays = f"SELECT noOfWorkingDays FROM tblworkingdays WHERE monthName = '{month}' AND year = '{Year}'"
cursor.execute(queryWorkingDays)
workingDaysResult = cursor.fetchone()
workingDays = 0
if workingDaysResult:
    workingDays = workingDaysResult['noOfWorkingDays']

# If not found with month number, try with month name
if workingDays == 0:
    # Handle the typo in database (ARPIL instead of APRIL)
    dbMonthName = Month.upper()
    if dbMonthName == 'APRIL':
        dbMonthName = 'ARPIL'  # Fix the typo for database query
    
    queryWorkingDaysByName = f"SELECT noOfWorkingDays FROM tblworkingdays WHERE monthName = '{dbMonthName}' AND year = '{Year}'"
    cursor.execute(queryWorkingDaysByName)
    workingDaysResult = cursor.fetchone()
    if workingDaysResult:
        workingDays = workingDaysResult['noOfWorkingDays']

# Step 8: Calculate LOP (Loss of Pay) - days with no check-in and check-out
lopDays = 0
if employee:
    employeeID = employee['employeeID']
    
    # Use proper date parsing for month start and end
    monthStart = f"{Year}-{month:02d}-01"
    import datetime
    monthEnd = (datetime.datetime(Year, month, 1) + datetime.timedelta(days=32)).replace(day=1) - datetime.timedelta(days=1)
    monthEnd = monthEnd.strftime('%Y-%m-%d')
    
    # Count days where employee has no attendance record or no check-in/check-out
    queryLOP = f"""SELECT COUNT(*) as absentDays 
                  FROM (
                    SELECT DATE(attendanceDate) as workDate
                    FROM tblAttendance 
                    WHERE employeeID = '{employeeID}' 
                    AND organisationID = '{OrgID}'
                    AND attendanceDate BETWEEN '{monthStart}' AND '{monthEnd}'
                    AND (checkInTime IS NULL OR checkOutTime IS NULL)
                  ) as absentDays"""
    
    cursor.execute(queryLOP)
    lopResult = cursor.fetchone()
    if lopResult:
        lopDays = lopResult['absentDays']

# Step 9: Logo path handling
logoWebPath = ''
if orgLogo:
    logoWebPath = f"https://vidupuapi.kapiital.com/{orgLogo}"

print(f"<!-- orgLogo: {orgLogo} -->")
print(f"<!-- logoWebPath: {logoWebPath} -->")
print(f"<!-- Current Organisation ID: {OrgID} -->")

# Step 10: Fetch all earnings for the employee for the given month/year
earnings = {}
totalEarnings = 0

print(f"<!-- DEBUG: empID used in query: {empID} -->")
print(f"<!-- DEBUG: month: {Month}, year: {Year} -->")
print(f"<!-- DEBUG: month number: {month} -->")

# First, let's check if the employee exists in tblAccounts
checkEmpQuery = f"SELECT COUNT(*) as count FROM tblAccounts WHERE TRIM(empID) = '{empID}'"
cursor.execute(checkEmpQuery)
empCount = cursor.fetchone()
print(f"<!-- DEBUG: Employee records in tblAccounts: {empCount['count']} -->")

# Check for any accounts data for this employee
checkAccountsQuery = f"""SELECT month, year, COUNT(*) as count 
                       FROM tblAccounts 
                       WHERE TRIM(empID) = '{empID}' 
                       GROUP BY month, year 
                       ORDER BY year DESC, month DESC 
                       LIMIT 5"""
cursor.execute(checkAccountsQuery)
accountsData = cursor.fetchall()
print("<!-- DEBUG: Recent accounts data for employee: -->")
for row in accountsData:
    print(f"<!-- Month: {row['month']}, Year: {row['year']}, Count: {row['count']} -->")

# Let's also check what account types exist
checkAccountTypesQuery = "SELECT accountTypeID, accountTypeName, typeOfAccount FROM tblAccountType WHERE typeOfAccount = 'earnings'"
cursor.execute(checkAccountTypesQuery)
accountTypes = cursor.fetchall()
print("<!-- DEBUG: Available earnings account types: -->")
for row in accountTypes:
    print(f"<!-- ID: {row['accountTypeID']}, Name: {row['accountTypeName']}, Type: {row['typeOfAccount']} -->")

# Let's check the actual data in tblAccounts for this employee
checkActualDataQuery = f"""SELECT a.empID, a.month, a.year, a.amount, t.accountTypeName, t.typeOfAccount 
                         FROM tblAccounts a 
                         JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID 
                         WHERE a.empID = '{empID}' 
                         ORDER BY a.year DESC, a.month DESC 
                         LIMIT 10"""
cursor.execute(checkActualDataQuery)
actualData = cursor.fetchall()
print("<!-- DEBUG: Actual accounts data for employee: -->")
for row in actualData:
    print(f"<!-- EmpID: {row['empID']}, Month: {row['month']}, Year: {row['year']}, Amount: {row['amount']}, Type: {row['accountTypeName']} -->")

queryEarnings = f"""
    SELECT t.accountTypeName, a.amount
    FROM tblAccounts a
    JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
    WHERE TRIM(a.empID) = '{empID}'
      AND a.month = {month}
      AND a.year = {Year}
      AND t.typeOfAccount = 'earnings'
"""
print(f"<!-- DEBUG: Earnings Query: {queryEarnings} -->")

cursor.execute(queryEarnings)
earningsResult = cursor.fetchall()
print(f"<!-- DEBUG: Earnings query returned {len(earningsResult)} rows -->")

for row in earningsResult:
    print(f"<!-- Row: {row} -->")
    earnings[row['accountTypeName']] = row['amount']
    totalEarnings += row['amount']

# If no earnings found, try alternative queries
if not earnings:
    print("<!-- DEBUG: No earnings found, trying alternative queries -->")
    
    # Try without TRIM
    altQuery1 = f"""
        SELECT t.accountTypeName, a.amount
        FROM tblAccounts a
        JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
        WHERE a.empID = '{empID}'
          AND a.month = {month}
          AND a.year = {Year}
          AND t.typeOfAccount = 'earnings'
    """
    print(f"<!-- DEBUG: Alternative Query 1: {altQuery1} -->")
    
    cursor.execute(altQuery1)
    altResult1 = cursor.fetchall()
    print(f"<!-- DEBUG: Alternative query 1 returned {len(altResult1)} rows -->")
    for row in altResult1:
        print(f"<!-- Alt Row 1: {row} -->")
        earnings[row['accountTypeName']] = row['amount']
        totalEarnings += row['amount']
    
    # If still no results, try with string month
    if not earnings:
        altQuery2 = f"""
            SELECT t.accountTypeName, a.amount
            FROM tblAccounts a
            JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
            WHERE a.empID = '{empID}'
              AND a.month = '{month}'
              AND a.year = '{Year}'
              AND t.typeOfAccount = 'earnings'
        """
        print(f"<!-- DEBUG: Alternative Query 2: {altQuery2} -->")
        
        cursor.execute(altQuery2)
        altResult2 = cursor.fetchall()
        print(f"<!-- DEBUG: Alternative query 2 returned {len(altResult2)} rows -->")
        for row in altResult2:
            print(f"<!-- Alt Row 2: {row} -->")
            earnings[row['accountTypeName']] = row['amount']
            totalEarnings += row['amount']
    
    # If still no results, try to get the most recent month's data as fallback
    if not earnings:
        print("<!-- DEBUG: No data found for requested month, trying to get most recent data -->")
        fallbackQuery = f"""
            SELECT t.accountTypeName, a.amount, a.month, a.year
            FROM tblAccounts a
            JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
            WHERE a.empID = '{empID}'
              AND t.typeOfAccount = 'earnings'
            ORDER BY a.year DESC, a.month DESC
            LIMIT 10
        """
        print(f"<!-- DEBUG: Fallback Query: {fallbackQuery} -->")
        
        cursor.execute(fallbackQuery)
        fallbackResult = cursor.fetchall()
        print(f"<!-- DEBUG: Fallback query returned {len(fallbackResult)} rows -->")
        
        fallbackMonth = ''
        fallbackYear = ''
        for row in fallbackResult:
            print(f"<!-- Fallback Row: {row} -->")
            if not fallbackMonth:
                fallbackMonth = row['month']
                fallbackYear = row['year']
            earnings[row['accountTypeName']] = row['amount']
            totalEarnings += row['amount']
        
        if earnings:
            print(f"<!-- DEBUG: Using fallback data from month {fallbackMonth}, year {fallbackYear} -->")

print(f"<!-- DEBUG: Final Earnings Array: {earnings} -->")
print(f"<!-- DEBUG: Total Earnings: {totalEarnings} -->")

# Step 11: Fetch all deductions for the employee for the given month/year
deductions = {}
totalDeductions = 0

print(f"<!-- DEBUG: Fetching deductions for empID: {empID}, month: {month}, year: {Year} -->")

queryDeductions = f"""
    SELECT t.accountTypeName, a.amount
    FROM tblAccounts a
    JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
    WHERE TRIM(a.empID) = '{empID}'
      AND a.month = {month}
      AND a.year = {Year}
      AND t.typeOfAccount = 'deductions'
"""
print(f"<!-- DEBUG: Deductions Query: {queryDeductions} -->")

cursor.execute(queryDeductions)
deductionsResult = cursor.fetchall()
print(f"<!-- DEBUG: Deductions query returned {len(deductionsResult)} rows -->")

for row in deductionsResult:
    print(f"<!-- Deduction Row: {row} -->")
    deductions[row['accountTypeName']] = row['amount']
    totalDeductions += row['amount']

# If no deductions found, try alternative queries
if not deductions:
    print("<!-- DEBUG: No deductions found, trying alternative queries -->")
    
    # Try without TRIM
    altDeductionsQuery1 = f"""
        SELECT t.accountTypeName, a.amount
        FROM tblAccounts a
        JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
        WHERE a.empID = '{empID}'
          AND a.month = {month}
          AND a.year = {Year}
          AND t.typeOfAccount = 'deductions'
    """
    print(f"<!-- DEBUG: Alternative Deductions Query 1: {altDeductionsQuery1} -->")
    
    cursor.execute(altDeductionsQuery1)
    altDeductionsResult1 = cursor.fetchall()
    print(f"<!-- DEBUG: Alternative deductions query 1 returned {len(altDeductionsResult1)} rows -->")
    for row in altDeductionsResult1:
        print(f"<!-- Alt Deduction Row 1: {row} -->")
        deductions[row['accountTypeName']] = row['amount']
        totalDeductions += row['amount']
    
    # If still no results, try with string month
    if not deductions:
        altDeductionsQuery2 = f"""
            SELECT t.accountTypeName, a.amount
            FROM tblAccounts a
            JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
            WHERE a.empID = '{empID}'
              AND a.month = '{month}'
              AND a.year = '{Year}'
              AND t.typeOfAccount = 'deductions'
        """
        print(f"<!-- DEBUG: Alternative Deductions Query 2: {altDeductionsQuery2} -->")
        
        cursor.execute(altDeductionsQuery2)
        altDeductionsResult2 = cursor.fetchall()
        print(f"<!-- DEBUG: Alternative deductions query 2 returned {len(altDeductionsResult2)} rows -->")
        for row in altDeductionsResult2:
            print(f"<!-- Alt Deduction Row 2: {row} -->")
            deductions[row['accountTypeName']] = row['amount']
            totalDeductions += row['amount']
    
    # If still no results, try to get the most recent month's deductions data as fallback
    if not deductions:
        print("<!-- DEBUG: No deductions found for requested month, trying to get most recent data -->")
        fallbackDeductionsQuery = f"""
            SELECT t.accountTypeName, a.amount, a.month, a.year
            FROM tblAccounts a
            JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
            WHERE a.empID = '{empID}'
              AND t.typeOfAccount = 'deductions'
            ORDER BY a.year DESC, a.month DESC
            LIMIT 10
        """
        print(f"<!-- DEBUG: Fallback Deductions Query: {fallbackDeductionsQuery} -->")
        
        cursor.execute(fallbackDeductionsQuery)
        fallbackDeductionsResult = cursor.fetchall()
        print(f"<!-- DEBUG: Fallback deductions query returned {len(fallbackDeductionsResult)} rows -->")
        
        for row in fallbackDeductionsResult:
            print(f"<!-- Fallback Deduction Row: {row} -->")
            deductions[row['accountTypeName']] = row['amount']
            totalDeductions += row['amount']

print(f"<!-- DEBUG: Final Deductions Array: {deductions} -->")
print(f"<!-- DEBUG: Total Deductions: {totalDeductions} -->")

# Step 12: Fetch all loan deductions for the employee for the given month/year
loanDeductions = {}
totalLoanDeductions = 0

print(f"<!-- DEBUG: Fetching loan deductions for empID: {empID}, month: {month}, year: {Year} -->")

queryLoanDeductions = f"""
    SELECT t.accountTypeName, a.amount
    FROM tblAccounts a
    JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
    WHERE TRIM(a.empID) = '{empID}'
      AND a.month = {month}
      AND a.year = {Year}
      AND t.typeOfAccount = 'loans'
"""
print(f"<!-- DEBUG: Loan Deductions Query: {queryLoanDeductions} -->")

cursor.execute(queryLoanDeductions)
loanDeductionsResult = cursor.fetchall()
print(f"<!-- DEBUG: Loan deductions query returned {len(loanDeductionsResult)} rows -->")

for row in loanDeductionsResult:
    print(f"<!-- Loan Deduction Row: {row} -->")
    loanDeductions[row['accountTypeName']] = row['amount']
    totalLoanDeductions += row['amount']

# If no loan deductions found, try alternative queries
if not loanDeductions:
    print("<!-- DEBUG: No loan deductions found, trying alternative queries -->")
    
    # Try without TRIM
    altLoanQuery1 = f"""
        SELECT t.accountTypeName, a.amount
        FROM tblAccounts a
        JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
        WHERE a.empID = '{empID}'
          AND a.month = {month}
          AND a.year = {Year}
          AND t.typeOfAccount = 'loans'
    """
    print(f"<!-- DEBUG: Alternative Loan Query 1: {altLoanQuery1} -->")
    
    cursor.execute(altLoanQuery1)
    altLoanResult1 = cursor.fetchall()
    print(f"<!-- DEBUG: Alternative loan query 1 returned {len(altLoanResult1)} rows -->")
    for row in altLoanResult1:
        print(f"<!-- Alt Loan Row 1: {row} -->")
        loanDeductions[row['accountTypeName']] = row['amount']
        totalLoanDeductions += row['amount']
    
    # If still no results, try with string month
    if not loanDeductions:
        altLoanQuery2 = f"""
            SELECT t.accountTypeName, a.amount
            FROM tblAccounts a
            JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
            WHERE a.empID = '{empID}'
              AND a.month = '{month}'
              AND a.year = '{Year}'
              AND t.typeOfAccount = 'loans'
        """
        print(f"<!-- DEBUG: Alternative Loan Query 2: {altLoanQuery2} -->")
        
        cursor.execute(altLoanQuery2)
        altLoanResult2 = cursor.fetchall()
        print(f"<!-- DEBUG: Alternative loan query 2 returned {len(altLoanResult2)} rows -->")
        for row in altLoanResult2:
            print(f"<!-- Alt Loan Row 2: {row} -->")
            loanDeductions[row['accountTypeName']] = row['amount']
            totalLoanDeductions += row['amount']
    
    # If still no results, try to get the most recent month's loan data as fallback
    if not loanDeductions:
        print("<!-- DEBUG: No loan deductions found for requested month, trying to get most recent data -->")
        fallbackLoanQuery = f"""
            SELECT t.accountTypeName, a.amount, a.month, a.year
            FROM tblAccounts a
            JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
            WHERE a.empID = '{empID}'
              AND t.typeOfAccount = 'loans'
            ORDER BY a.year DESC, a.month DESC
            LIMIT 10
        """
        print(f"<!-- DEBUG: Fallback Loan Query: {fallbackLoanQuery} -->")
        
        cursor.execute(fallbackLoanQuery)
        fallbackLoanResult = cursor.fetchall()
        print(f"<!-- DEBUG: Fallback loan query returned {len(fallbackLoanResult)} rows -->")
        
        for row in fallbackLoanResult:
            print(f"<!-- Fallback Loan Row: {row} -->")
            loanDeductions[row['accountTypeName']] = row['amount']
            totalLoanDeductions += row['amount']

print(f"<!-- DEBUG: Final Loan Deductions Array: {loanDeductions} -->")
print(f"<!-- DEBUG: Total Loan Deductions: {totalLoanDeductions} -->")

# Step 13: Function to convert number to words (exact copy from PHP)
def numberToWords(number):
    # Convert to integer to avoid float to int conversion warnings
    number = int(number)
    
    ones = [
        "", "One", "Two", "Three", "Four", "Five",
        "Six", "Seven", "Eight", "Nine", "Ten",
        "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen",
        "Sixteen", "Seventeen", "Eighteen", "Nineteen"
    ]
    tens = [
        "", "", "Twenty", "Thirty", "Forty", "Fifty",
        "Sixty", "Seventy", "Eighty", "Ninety"
    ]
    
    if number == 0:
        return "Zero"
    
    words = ""
    
    if number >= 10000000:
        crores = number // 10000000
        words += numberToWords(crores) + " Crore "
        number %= 10000000
    
    if number >= 100000:
        lakhs = number // 100000
        words += numberToWords(lakhs) + " Lakh "
        number %= 100000
    
    if number >= 1000:
        thousands = number // 1000
        words += numberToWords(thousands) + " Thousand "
        number %= 1000
    
    if number >= 100:
        hundreds = number // 100
        words += ones[hundreds] + " Hundred "
        number %= 100
    
    if number >= 20:
        tens_digit = number // 10
        words += tens[tens_digit] + " "
        number %= 10
    
    if number > 0:
        words += ones[number] + " "
    
    return words.strip()

# Step 14: Calculate net pay (total earnings minus deductions and loan deductions)
netPay = totalEarnings - totalDeductions - totalLoanDeductions
netPayInWords = numberToWords(netPay) + " Rupees Only"

# Step 15: Prepare template data
template_data = {
    'orgName': orgName,
    'logoWebPath': logoWebPath,
    'orgAddress1': orgAddress1,
    'orgAddress2': orgAddress2,
    'orgCity': orgCity,
    'orgState': orgState,
    'orgPhone': orgPhone,
    'orgWebsite': orgWebsite,
    'monthName': Month,
    'year': Year,
    'employeeName': employeeName,
    'empID': empID,
    'joiningDate': joiningDate,
    'bankName': bankName,
    'bankAccountNumber': bankAccountNumber,
    'designation': designation,
    'panNumber': panNumber,
    'pfNumber': pfNumber,
    'pfUAN': pfUAN,
    'department': department,
    'branchName': branchName,
    'workingDays': workingDays,
    'lopDays': lopDays,
    'earnings': earnings,
    'deductions': deductions,
    'loanDeductions': loanDeductions,
    'totalEarnings': totalEarnings,
    'totalDeductions': totalDeductions,
    'totalLoanDeductions': totalLoanDeductions,
    'netPay': netPay,
    'netPayInWords': netPayInWords
}

# Step 16: HTML Template with Jinja2 syntax (exact copy from payslip.php structure)
html_template = """
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payslip - {{ orgName }}</title>
  <style>
    body {
      margin: 0;
      padding: 15px;
      font-family: Arial, sans-serif;
      font-size: 14px;
      line-height: 1.3;
    }
    .container {
      max-width: 1000px;
      margin: 0 auto;
      padding: 15px;
      background: white;
    }
    .org-header {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 10px;
    }
    .org-title-row {
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 0;
    }
    .org-logo {
      max-width: 50px;
      margin-right: 10px;
    }
    .org-details {
      text-align: center;
    }
    .org-details h2 {
      margin: 0 0 1px 0;
      font-size: 20px;
      display: inline-block;
    }
    .org-meta {
      text-align: center;
      font-size: 14px;
      line-height: 1.2;
      margin: 0;
    }
    h3 {
      text-align: center;
      margin: 8px 0;
      font-size: 16px;
    }
    .details {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 8px;
    }
    .details td {
      padding: 2px 4px;
      border-left: 1px solid #000;
      border-right: 1px solid #000;
      border-top: none;
      border-bottom: none;
      width: 50%;
      font-size: 14px;
    }
    .details tr:first-child td {
      border-top: 1px solid #000;
    }
    .details tr:last-child td {
      border-bottom: 1px solid #000;
    }
    .section-title {
      font-weight: bold;
      margin: 4px 0;
      font-size: 15px;
    }
    .earnings td, .deductions td {
      padding: 2px 4px;
      border: 1px solid #000;
      font-size: 14px;
    }
    .earnings td:last-child, .deductions td:last-child {
      width: 120px;
      text-align: right;
    }
    table.earnings, table.deductions {
      width: 100%;
      border-collapse: collapse;
    }
    .tables-container {
      display: flex;
      gap: 10px;
      margin-bottom: 8px;
    }
    .table-column {
      flex: 1;
    }
    @media print {
      body {
        padding: 0;
      }
      .container {
        max-width: none;
        padding: 10px;
      }
    }
    .total-deductions {
      margin: 4px 0;
      width: 100%;
      font-size: 14px;
      display: flex;
      justify-content: flex-end;
    }
    .net-pay-box {
      margin: 4px 0;
      display: flex;
      justify-content: flex-end;
    }
    .net-pay-content {
      border: 2px solid #000;
      background: #e6ffe6;
      padding: 8px 16px;
      font-size: 15px;
      font-weight: bold;
    }
    .amount-words {
      margin: 4px 0;
      font-size: 14px;
    }
    .footer-text {
      margin-top: 8px;
      color: #666;
      font-size: 13px;
      font-style: italic;
    }
  </style>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>
<div id="payslip-container" class="container">
  <div class="org-header">
    <div class="org-title-row">
      {% if logoWebPath %}
        <img src="{{ logoWebPath }}" alt="Logo" class="org-logo">
      {% endif %}
      <div class="org-details">
        <h2>{{ orgName }}</h2>
        <div class="org-meta">{{ orgAddress1 }}{% if orgAddress2 %}, {{ orgAddress2 }}{% endif %}</div>
        <div class="org-meta">{{ orgCity }}{% if orgState %}, {{ orgState }}{% endif %}</div>
        <div class="org-meta">Phone: {{ orgPhone }}{% if orgWebsite %} | Website: {{ orgWebsite }}{% endif %}</div>
      </div>
    </div>
  </div>

  <h3>Payslip for the month of {{ monthName }} - {{ year }}</h3>

  <!-- Employee Details Table -->
  <table class="details">
    <tr>
      <td><strong>Name:</strong> {{ employeeName }}</td>
      <td><strong>Employee No:</strong> {{ empID }}</td>
    </tr>
    <tr>
      <td><strong>Joining Date:</strong> {% if joiningDate %}{{ joiningDate.strftime('%d-%m-%Y') if joiningDate.__class__.__name__ == 'date' else joiningDate }}{% endif %}</td>
      <td><strong>Bank Name:</strong> {{ bankName }}</td>
    </tr>
    <tr>
      <td><strong>Designation:</strong> {{ designation }}</td>
      <td><strong>Bank Account No:</strong> {{ bankAccountNumber }}</td>
    </tr>
    <tr>
      <td><strong>Department:</strong> {{ department }}</td>
      <td><strong>PAN Number:</strong> {{ panNumber }}</td>
    </tr>
    <tr>
      <td><strong>Location:</strong> {{ branchName }}</td>
      <td><strong>PF No:</strong> {{ pfNumber }}</td>
    </tr>
    <tr>
      <td><strong>Effective Work Days:</strong> {{ workingDays }}</td>
      <td><strong>PF UAN:</strong> {{ pfUAN }}</td>
    </tr>
    <tr>
      <td><strong>LOP:</strong> {{ lopDays }}</td>
      <td></td>
    </tr>
  </table>

  <!-- Earnings and Deductions Tables -->
  <div class="tables-container">
    <div class="table-column">
      <p class="section-title">Earnings</p>
      <table class="earnings">
        {% for type, amount in earnings.items() %}
        <tr>
          <td>{{ type }}</td>
          <td>{% if amount %}₹{{ "{:,.2f}".format(amount) }}{% endif %}</td>
        </tr>
        {% endfor %}
        <tr>
          <td><strong>Total Earnings</strong></td>
          <td><strong>{% if totalEarnings %}₹{{ "{:,.2f}".format(totalEarnings) }}{% endif %}</strong></td>
        </tr>
      </table>
    </div>
    <div class="table-column">
      <p class="section-title">Deductions</p>
      <table class="deductions">
        {% for type, amount in deductions.items() %}
        <tr>
          <td>{{ type }}</td>
          <td>{% if amount %}₹{{ "{:,.2f}".format(amount) }}{% endif %}</td>
        </tr>
        {% endfor %}
      </table>

      <!-- Loan Deductions Table -->
      <p class="section-title">Loan Deductions</p>
      <table class="deductions">
        {% for type, amount in loanDeductions.items() %}
        <tr>
          <td>{{ type }}</td>
          <td>{% if amount %}₹{{ "{:,.2f}".format(amount) }}{% endif %}</td>
        </tr>
        {% endfor %}
      </table>
    </div>
  </div>

  <!-- Total Deductions and Net Pay Section -->
  <div style="display: flex; justify-content: space-between; margin-top: 10px;">
    <!-- Left side: Net Pay and Amount in Words -->
    <div style="flex: 1;">
      <div style="border: 2px solid #000; background: #e6ffe6; padding: 8px 16px; display: inline-block; margin-bottom: 8px;">
        <span style="font-size: 15px; font-weight: bold;">Net Pay for the month: </span>
        <span style="font-size: 15px; font-weight: bold; color: #1a6600;">{% if netPay %}₹{{ "{:,.2f}".format(netPay) }}{% endif %}</span>
      </div>
      <div style="margin-left: 8px; display: inline-block;">
        <span style="font-size: 14px;"><strong>(in words):</strong> {{ netPayInWords }}</span>
      </div>
    </div>

    <!-- Right side: Total Deductions -->
    <div style="width: 40%;">
      <div class="total-deductions">
        <span style="text-align:right; flex: 1;">Total Deductions</span>
        <span style="text-align:right; width: 120px; padding-left: 12px;">{% if totalDeductions %}₹{{ "{:,.2f}".format(totalDeductions) }}{% endif %}</span>
      </div>
      <div class="total-deductions">
        <span style="text-align:right; flex: 1;">Total Loan Deductions</span>
        <span style="text-align:right; width: 120px; padding-left: 12px;">{% if totalLoanDeductions %}₹{{ "{:,.2f}".format(totalLoanDeductions) }}{% endif %}</span>
      </div>
      <div class="total-deductions" style="font-weight: bold; margin-bottom: 12px;">
        <span style="text-align:right; flex: 1;">Total (Deductions + Loan Deductions)</span>
        <span style="text-align:right; width: 120px; padding-left: 12px;">₹{{ "{:,.2f}".format(totalDeductions + totalLoanDeductions) }}</span>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <div style="z-index:-1;">
    <p style="color: #666; font-size: 13px; font-style: italic; margin: 0;">This is a computer generated payslip and does not require a signature.</p>
    <p id="generated-datetime" style="color: #666; font-size: 13px; font-style: italic; margin: 0;"></p>
    <script>
      document.getElementById('generated-datetime').textContent =
        'System Generated on: ' + new Date().toLocaleString();
    </script>
  </div>
</div>
</body>
</html>
"""

# Step 17: Render HTML from Template
template = Template(html_template)
html_out = template.render(**template_data)

# Step 18: Save HTML output first
with open("payslip_output.html", "w", encoding="utf-8") as f:
    f.write(html_out)
print("✅ HTML payslip generated successfully! Check payslip_output.html")

# Step 19: Export to PDF (requires wkhtmltopdf)
try:
    pdfkit.from_string(html_out, "payslip_output.pdf")
    print("✅ PDF payslip generated successfully!")
except Exception as e:
    print(f"⚠️ PDF generation failed: {e}")
    print("📄 You can open payslip_output.html in your browser to view the payslip")

# Close database connection
cursor.close()
conn.close()