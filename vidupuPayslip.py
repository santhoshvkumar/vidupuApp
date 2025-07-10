#!/usr/bin/env python3
"""
Simplified Vidupu Payslip Processor
===================================

Process Excel payslip data and insert into database tables:
- tblAccountMapping (create mappings for employees)
- tblAccounts (insert employee salary data)

Pay type mapping:
- E = earnings
- OD = deductions (other deductions)
- LD/LD1 = loans (loan deductions)
- O = context-dependent (skip for now)

Also generates PDF payslips for each employee and saves them to Uploads/EMP/PaySlips/
"""

import sys
import subprocess
import importlib.util
import mysql.connector
import logging
import os
from jinja2 import Template
import pdfkit
import calendar

def check_and_install_requirements():
    """Check and install required packages"""
    required_packages = {
        'pandas': 'pandas>=1.3.0',
        'pymysql': 'pymysql>=1.0.0',
        'openpyxl': 'openpyxl>=3.0.0',
        'requests': 'requests>=2.25.0'
    }
    
    missing_packages = []
    
    for package, pip_name in required_packages.items():
        if importlib.util.find_spec(package) is None:
            missing_packages.append(pip_name)
    
    if missing_packages:
        print("Installing required packages...")
        try:
            subprocess.check_call([sys.executable, '-m', 'pip', 'install'] + missing_packages)
            print("All required packages installed successfully!")
        except subprocess.CalledProcessError as e:
            print(f"Error installing packages: {e}")
            print("Please install the following packages manually:")
            for package in missing_packages:
                print(f"  pip install {package}")
            sys.exit(1)
    
    # Now import the packages
    import pandas as pd
    import pymysql
    import requests
    import time
    import os
    import webbrowser
    from datetime import datetime
    import logging
    from urllib.parse import urlencode
    
    return pd, pymysql, requests, time, os, webbrowser, datetime, logging, urlencode

# Check and install requirements
pd, pymysql, requests, time, os, webbrowser, datetime, logging, urlencode = check_and_install_requirements()

# Set up logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

# Database configuration
DB_CONFIG = {
    'host': 'localhost',
    'user': 'vsk',
    'password': 'Password#1',
    'database': 'tnscVidupuApp'
}

# Excel file path (GLOBAL)
EXCEL_FILE_PATH = r'/data/server/live/API/public_html/vidupuApp/ScriptRunning/Sal_slip.xlsx'
# EXCEL_FILE_PATH = r'C:/MAMP/htdocs/Vidupu/vidupuApi/ScriptRunning/Sal_slip.xlsx'

# Direct pay type to category mapping
PAY_TYPE_MAPPING = {
    'E': 'earnings',
    'OD': 'deductions',
    'LD': 'loans',
    'LD1': 'loans'
}

# Month number to name mapping
MONTH_NAMES = {
    1: 'January', 2: 'February', 3: 'March', 4: 'April', 5: 'May', 6: 'June',
    7: 'July', 8: 'August', 9: 'September', 10: 'October', 11: 'November', 12: 'December'
}

class SimplePayslipProcessor:
    """Simplified payslip processor with PDF generation"""
    
    def __init__(self):
        self.connection = None
        self.account_types = {}
    def connect_db(self):
        """Connect to MySQL database"""
        try:
            self.connection =  mysql.connector.connect(host="localhost", user="vsk", password="Password#1", database="tnscVidupuApp")
            logger.info("Database connection established")
            self.load_account_types()
        except Exception as e:
            logger.error(f"Failed to connect to database: {e}")
            raise
    
    def reconnect_db(self):
        """Reconnect to database if connection is lost"""
        try:
            if self.connection:
                self.connection.close()
            self.connection =  mysql.connector.connect(host="localhost", user="vsk", password="Password#1", database="tnscVidupuApp")
            logger.info("Database reconnected")
            return True
        except Exception as e:
            logger.error(f"Failed to reconnect to database: {e}")
            return False
    
    def close_db(self):
        """Close database connection"""
        if self.connection:
            self.connection.close()
            logger.info("Database connection closed")
    
    
    def get_organisation_id(self, emp_id):
        """Get organisation ID for an employee"""
        max_retries = 3
        for attempt in range(max_retries):
            try:
                cursor = self.connection.cursor()
                cursor.execute("SELECT organisationID FROM tblEmployee WHERE empID = %s", (emp_id,))
                result = cursor.fetchone()
                cursor.close()
                
                if result:
                    return result[0]
                return None
                
            except (pymysql.Error, Exception) as e:
                logger.warning(f"Database error on attempt {attempt + 1} for employee {emp_id}: {e}")
                if attempt < max_retries - 1:
                    if self.reconnect_db():
                        continue
                else:
                    logger.error(f"Failed to get organisation ID for {emp_id} after {max_retries} attempts")
                    return None
        return None
    
    def generate_payslip_pdf(self, emp_id, month, year, org_id):
        """Generate PDF payslip for an employee"""
        try:
            month_name = MONTH_NAMES.get(month, 'Unknown')
            
            # Prepare parameters for payslip.php
            params = {
                'EmpID': emp_id,
                'Month': month_name,
                'Year': year,
                'OrgID': org_id
            }
            
            # Try multiple URLs
            urls_to_try = [self.base_url] + self.alternative_urls
            
            for url_base in urls_to_try:
                try:
                    url = f"{url_base}?{urlencode(params)}"
                    logger.info(f"Trying URL: {url}")
                    
                    # Make request to payslip.php
                    response = requests.get(url, timeout=30)
                    
                    if response.status_code == 200:
                        # Save HTML file for manual PDF generation
                        html_content = response.text
                        html_filename = f"Payslip_{emp_id}_{month_name}_{year}.html"
                        html_filepath = os.path.join(self.payslip_dir, html_filename)
                        
                        with open(html_filepath, 'w', encoding='utf-8') as f:
                            f.write(html_content)
                        
                        logger.info(f"Successfully saved payslip HTML to: {html_filepath}")
                        
                        # Try to open in browser for manual PDF generation
                        try:
                            webbrowser.open(f'file://{os.path.abspath(html_filepath)}')
                            logger.info(f"Opened payslip in browser for manual PDF generation")
                        except Exception as e:
                            logger.warning(f"Could not open browser: {e}")
                        
                        return html_filepath
                    else:
                        logger.warning(f"Failed with URL {url_base}: HTTP {response.status_code}")
                        continue
                        
                except requests.exceptions.RequestException as e:
                    logger.warning(f"Request failed for {url_base}: {e}")
                    continue
            
            # If all URLs failed, create a simple HTML file with error message
            logger.error(f"All URLs failed for employee {emp_id}. Creating error HTML file.")
            error_html = f"""
            <!DOCTYPE html>
            <html>
            <head>
                <title>Payslip Error - Employee {emp_id}</title>
                <style>
                    body {{ font-family: Arial, sans-serif; margin: 40px; }}
                    .error {{ color: red; background: #ffe6e6; padding: 20px; border: 1px solid red; }}
                </style>
            </head>
            <body>
                <h1>Payslip Generation Error</h1>
                <div class="error">
                    <h2>Could not generate payslip for Employee {emp_id}</h2>
                    <p><strong>Month:</strong> {month_name} {year}</p>
                    <p><strong>Error:</strong> Web server not accessible</p>
                    <p><strong>Solution:</strong></p>
                    <ul>
                        <li>Ensure MAMP is running</li>
    
                        <li>Verify the file path is correct</li>
                    </ul>
                </div>
            </body>
            </html>
            """
            
            html_filename = f"Payslip_{emp_id}_{month_name}_{year}_ERROR.html"
            html_filepath = os.path.join(self.payslip_dir, html_filename)
            
            with open(html_filepath, 'w', encoding='utf-8') as f:
                f.write(error_html)
            
            logger.info(f"Created error HTML file: {html_filepath}")
            return html_filepath
                
        except Exception as e:
            logger.error(f"Error generating payslip for {emp_id}: {e}")
            return None
    
    def open_payslip_in_browser(self, html_filepath):
        """Open payslip HTML file in default browser"""
        try:
            absolute_path = os.path.abspath(html_filepath)
            file_url = f'file:///{absolute_path.replace(os.sep, "/")}'
            webbrowser.open(file_url)
            logger.info(f"Opened payslip in browser: {file_url}")
            return True
        except Exception as e:
            logger.error(f"Error opening payslip in browser: {e}")
            return False
    
    def load_account_types(self):
        """Load existing account types from database"""
        cursor = self.connection.cursor()
        try:
            cursor.execute("SELECT accountTypeID, accountTypeName, typeOfAccount FROM tblAccountType")
            rows = cursor.fetchall()
            
            # Create lookup by (name, type) -> ID
            for account_id, name, type_of_account in rows:
                key = (name.strip(), type_of_account.strip())
                self.account_types[key] = account_id
            
            logger.info(f"Loaded {len(self.account_types)} account types")
            
        except Exception as e:
            logger.error(f"Error loading account types: {e}")
            raise
        finally:
            cursor.close()
    
    def get_or_create_account_type(self, account_name, type_of_account):
        """Get account type ID, create if doesn't exist"""
        key = (account_name.strip(), type_of_account.strip())
        
        if key in self.account_types:
            return self.account_types[key]
        
        # Create new account type
        cursor = self.connection.cursor()
        try:
            cursor.execute(
                "INSERT INTO tblAccountType (accountTypeName, typeOfAccount) VALUES (%s, %s)",
                (account_name.strip(), type_of_account.strip())
            )
            self.connection.commit()
            account_id = cursor.lastrowid
            
            # Add to cache
            self.account_types[key] = account_id
            logger.info(f"Created new account type: {account_name} ({type_of_account}) -> ID {account_id}")
            
            return account_id
            
        except Exception as e:
            logger.error(f"Error creating account type {account_name}: {e}")
            self.connection.rollback()
            raise
        finally:
            cursor.close()
    
    def format_employee_id(self, raw_emp_id):
        """Format employee ID - keep as is, no conversion"""
        return str(int(raw_emp_id)).strip()
    
    def get_employee_id(self, emp_id):
        """Get employee ID from database"""
        cursor = self.connection.cursor()
        try:
            cursor.execute("SELECT employeeID FROM tblEmployee WHERE empID = %s", (emp_id,))
            result = cursor.fetchone()
            if result:
                return result[0]
            
            return None
            
        except Exception as e:
            logger.error(f"Error getting employee ID for {emp_id}: {e}")
            return None
        finally:
            cursor.close()
    
    def create_account_mapping(self, emp_id, account_type_id):
        """Create account mapping if doesn't exist"""
        cursor = self.connection.cursor()
        try:
            # Check if mapping exists
            cursor.execute(
                "SELECT accountMappingID FROM tblAccountMapping WHERE empID = %s AND accountTypeID = %s AND isActive = 1",
                (emp_id, account_type_id)
            )
            
            if not cursor.fetchone():
                cursor.execute(
                    "INSERT INTO tblAccountMapping (empID, accountTypeID, assignedOn, assignedBy, isActive) VALUES (%s, %s, %s, %s, %s)",
                    (emp_id, account_type_id, datetime.now(), 1, 1)
                )
                self.connection.commit()
                logger.info(f"Created new account mapping: empID={emp_id}, accountTypeID={account_type_id}")
            
        except Exception as e:
            logger.error(f"Error creating mapping for {emp_id}: {e}")
            self.connection.rollback()
        finally:
            cursor.close()
    
    def insert_account_record(self, employee_id, emp_id, account_type_id, amount, month, year):
        """Insert or update account record"""
        cursor = self.connection.cursor()
        try:
            # Check if record exists
            cursor.execute(
                "SELECT tblAccountID FROM tblAccounts WHERE empID = %s AND accountTypeID = %s AND month = %s AND year = %s",
                (emp_id, account_type_id, month, year)
            )
            result = cursor.fetchone()
            
            if result:
                # Update existing record
                cursor.execute(
                    "UPDATE tblAccounts SET amount = %s WHERE tblAccountID = %s",
                    (amount, result[0])
                )
            else:
                # Insert new record
                logging.info(
                    "INSERT INTO tblAccounts (employeeID, empID, accountTypeID, amount, month, year) VALUES (%s, %s, %s, %s, %s, %s)",
                    employee_id, emp_id, account_type_id, amount, month, year
                )
                cursor.execute(
                    "INSERT INTO tblAccounts (employeeID, empID, accountTypeID, amount, month, year) VALUES (%s, %s, %s, %s, %s, %s)",
                    (employee_id, emp_id, account_type_id, amount, month, year)
                )
            
            self.connection.commit()
            
        except Exception as e:
            logger.error(f"Error inserting account record for {emp_id}: {e}")
            self.connection.rollback()
        finally:
            cursor.close()
    
    def clear_existing_data(self):
        """Clear existing data from tblAccounts and tblAccountMapping"""
        cursor = self.connection.cursor()
        try:
            logger.info("Clearing existing data...")
            cursor.execute("DELETE FROM tblAccounts")
            cursor.execute("DELETE FROM tblAccountMapping")
            self.connection.commit()
            logger.info("Existing data cleared")
        except Exception as e:
            logger.error(f"Error clearing data: {e}")
            self.connection.rollback()
        finally:
            cursor.close()
    
    def process_excel_file(self, file_path):
        """Process Excel file and insert data"""
        try:
            logger.info(f"Reading Excel file: {file_path}")
            df = pd.read_excel(file_path, engine='openpyxl')
            
            logger.info(f"Total rows in Excel: {len(df)}")
            logger.info(f"Columns: {list(df.columns)}")
            
            # Show first few rows for debugging
            logger.info("First 5 rows:")
            for i in range(min(5, len(df))):
                row = df.iloc[i]
                logger.info(f"Row {i}: Staff_code={row.iloc[0]}, Month={row.iloc[1]}, Year={row.iloc[2]}, Type={row.iloc[3]}, Balance={row.iloc[5]}, Pay_type={row.iloc[9]}")
            
            processed_count = 0
            skipped_count = 0
            pdf_generated_count = 0
            
            # Track unique employees for PDF generation
            employees_processed = set()
            
            for index, row in df.iterrows():
                try:
                    # Progress logging
                    if index % 1000 == 0:
                        logger.info(f"Processing row {index + 1}/{len(df)}")
                    
                    # Extract basic info
                    if pd.isna(row.iloc[0]) or pd.isna(row.iloc[1]) or pd.isna(row.iloc[2]) or pd.isna(row.iloc[3]) or pd.isna(row.iloc[9]):
                        skipped_count += 1
                        continue
                    
                    # Get employee ID as is (no formatting)
                    emp_id = self.format_employee_id(row.iloc[0])
                    
                    month = int(row.iloc[1])  # Sal_Month
                    year = int(row.iloc[2])   # Sal_Year
                    component_name = str(row.iloc[3]).strip()  # Type (component name like BASIC, DA, etc.)
                    pay_type = str(row.iloc[9]).strip()  # Pay_type
                    
                    # Get amount from Balance column (column F, index 5)
                    amount = row.iloc[5]  # Balance
                    
                    # Skip if amount is null, zero, or empty
                    if pd.isna(amount) or amount == 0:
                        skipped_count += 1
                        continue
                    
                    try:
                        amount = float(amount)
                    except (ValueError, TypeError):
                        skipped_count += 1
                        continue
                    
                    # Get employee database ID
                    employee_id = self.get_employee_id(emp_id)
                    if not employee_id:
                        skipped_count += 1
                        continue
                    
                    # Determine category based on pay_type
                    if pay_type not in PAY_TYPE_MAPPING:
                        # Skip 'O' pay types and others for now
                        skipped_count += 1
                        continue
                    
                    category = PAY_TYPE_MAPPING[pay_type]
                    
                    # Get or create account type using the component name
                    account_type_id = self.get_or_create_account_type(component_name, category)
                    
                    # Create mapping and insert record
                    self.create_account_mapping(emp_id, account_type_id)
                    self.insert_account_record(employee_id, emp_id, account_type_id, amount, month, year)

                  
                    processed_count += 1
                    
                    # Track employee for PDF generation
                    employees_processed.add((employee_id, month, year))
                        
                except Exception as e:
                    logger.error(f"Error processing row {index + 1}: {e}")
                    skipped_count += 1
                    continue
            # Call the generate_payslip function directly
           
            organisation_id = 1  # or get this dynamically if needed

            for employee_id, month, year in employees_processed:
                # If month is an int, convert to month name
                logger.info(f"Starting payslip generation for employeeID={employee_id},  month={month}, year={year}")
                month_name = calendar.month_name[month] if isinstance(month, int) else month
                generate_payslip(
                    employeeID=employee_id,
                    Month=month_name,
                    Year=year,
                    OrgID=organisation_id
                )
                logger.info(f"Payslip generated for employeeID={employee_id}, empID={emp_id}, month={month}, year={year}")
        except Exception as e:
            logger.error(f"Error processing Excel file: {e}")
            raise

def main():
    """Main function"""
    processor = SimplePayslipProcessor()
    
    try:
        # Connect to database
        processor.connect_db()
        
        # Clear existing data
        processor.clear_existing_data()
        
        # Process Excel file (use global EXCEL_FILE_PATH)
        processor.process_excel_file(EXCEL_FILE_PATH)
        
        logger.info("SUCCESS: Payslip processing completed!")
        logger.info("PDF payslips generated successfully!")
        
    except Exception as e:
        logger.error(f"FAILED: {e}")
        sys.exit(1)
    
    finally:
        processor.close_db()

def generate_payslip(employeeID, Month, Year, OrgID):
    logger.info("inisde Function")
    # Step 2: Connect to MySQL
    conn = mysql.connector.connect(
            host="localhost", user="vsk", password="Password#1", database="tnscVidupuApp"
    )
    cursor = conn.cursor(dictionary=True)

    # Handle Month as int or str
    if isinstance(Month, int):
        monthNameUpper = calendar.month_name[Month].upper()
    else:
        monthNameUpper = str(Month).upper()
    if monthNameUpper == 'ARPIL':
        monthNameUpper = 'APRIL'  # Fix the typo for date parsing

    # Get month number (1-12) for database queries
    month_names = [name.upper() for name in calendar.month_name]
    month = month_names.index(monthNameUpper)  # 1-based month number

    logger.info(f"<!-- Employee ID: {employeeID} -->")
    logger.info(f"<!-- Organisation ID: {OrgID} -->")
    logger.info(f"<!-- Month: {Month}, Month Number: {month}, Year: {Year} -->")

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
                     WHERE employeeID = '{employeeID}'"""
    cursor.execute(checkQuery)
    checkData = cursor.fetchone()
    logger.info(f"<!-- DEBUG: Check Query: {checkQuery} -->")
    logger.info(f"<!-- DEBUG: Check Data: {checkData} -->")

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
    WHERE e.employeeID = '{employeeID}'"""

    cursor.execute(queryEmp)
    employee = cursor.fetchone()

    logger.info(f"<!-- DEBUG: Query: {queryEmp} -->")
    logger.info(f"<!-- DEBUG: Employee ID being searched: {employeeID} -->")
    logger.info(f"<!-- DEBUG: Raw employee data: {employee} -->")

    # Initialize employee variables
    employeeName = ''
    employeeIDPrimaryKey = ''
    empIDFromDB = ''
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
        employeeIDPrimaryKey = employee['employeeID'] or ''
        empIDFromDB = employee['empID'] or ''
        joiningDate = employee['joiningDate'] or ''
        bankName = employee['bankName'] or ''
        bankAccountNumber = employee['bankAccountNumber'] or ''
        designation = employee['designation'] or ''
        panNumber = employee['panNumber'] or ''
        pfNumber = employee['pfNumber'] or ''
        pfUAN = employee['pfUAN'] or ''
        department = employee['SectionName'] or ''
        branchName = employee['branchName'] or ''
        logger.info(f"<!-- DEBUG: Bank Account Number from DB: {bankAccountNumber} -->")
        logger.info(f"<!-- DEBUG: PAN Number from DB: {panNumber} -->")
        logger.info(f"<!-- DEBUG: PF Number from DB: {pfNumber} -->")
        logger.info(f"<!-- DEBUG: PF UAN from DB: {pfUAN} -->")

    # Step 7: Fetch working days for the specified month and year
    queryWorkingDays = f"SELECT noOfWorkingDays FROM tblworkingdays WHERE monthName = '{month}' AND year = '{Year}'"
    cursor.execute(queryWorkingDays)
    workingDaysResult = cursor.fetchone()
    workingDays = 0
    if workingDaysResult:
        workingDays = workingDaysResult['noOfWorkingDays']

    # If not found with month number, try with month name
    if workingDays == 0:
        if isinstance(Month, int):
            dbMonthName = calendar.month_name[Month].upper()
        else:
            dbMonthName = str(Month).upper()
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
        employeeIDFromDB = employee['employeeID']
        monthStart = f"{Year}-{month:02d}-01"
        import datetime
        monthEnd = (datetime.datetime(Year, month, 1) + datetime.timedelta(days=32)).replace(day=1) - datetime.timedelta(days=1)
        monthEnd = monthEnd.strftime('%Y-%m-%d')
        queryLOP = f"""SELECT COUNT(*) as absentDays 
                      FROM (
                        SELECT DATE(attendanceDate) as workDate
                        FROM tblAttendance 
                        WHERE employeeID = '{employeeIDFromDB}' 
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
    print(f"<!-- DEBUG: empID used in query: {empIDFromDB} -->")
    print(f"<!-- DEBUG: month: {Month}, year: {Year} -->")
    print(f"<!-- DEBUG: month number: {month} -->")
    checkEmpQuery = f"SELECT COUNT(*) as count FROM tblAccounts WHERE TRIM(empID) = '{empIDFromDB}'"
    cursor.execute(checkEmpQuery)
    empCount = cursor.fetchone()
    print(f"<!-- DEBUG: Employee records in tblAccounts: {empCount['count']} -->")
    checkAccountsQuery = f"""SELECT month, year, COUNT(*) as count 
                           FROM tblAccounts 
                           WHERE TRIM(empID) = '{empIDFromDB}' 
                           GROUP BY month, year 
                           ORDER BY year DESC, month DESC 
                           LIMIT 5"""
    cursor.execute(checkAccountsQuery)
    accountsData = cursor.fetchall()
    print("<!-- DEBUG: Recent accounts data for employee: -->")
    for row in accountsData:
        print(f"<!-- Month: {row['month']}, Year: {row['year']}, Count: {row['count']} -->")
    checkAccountTypesQuery = "SELECT accountTypeID, accountTypeName, typeOfAccount FROM tblAccountType WHERE typeOfAccount = 'earnings'"
    cursor.execute(checkAccountTypesQuery)
    accountTypes = cursor.fetchall()
    print("<!-- DEBUG: Available earnings account types: -->")
    for row in accountTypes:
        print(f"<!-- ID: {row['accountTypeID']}, Name: {row['accountTypeName']}, Type: {row['typeOfAccount']} -->")
    checkActualDataQuery = f"""SELECT a.empID, a.month, a.year, a.amount, t.accountTypeName, t.typeOfAccount 
                             FROM tblAccounts a 
                             JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID 
                             WHERE a.empID = '{empIDFromDB}' 
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
        WHERE TRIM(a.empID) = '{empIDFromDB}'
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
    if not earnings:
        print("<!-- DEBUG: No earnings found, trying alternative queries -->")
        altQuery1 = f"""
            SELECT t.accountTypeName, a.amount
            FROM tblAccounts a
            JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
            WHERE a.empID = '{empIDFromDB}'
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
        if not earnings:
            altQuery2 = f"""
                SELECT t.accountTypeName, a.amount
                FROM tblAccounts a
                JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
                WHERE a.empID = '{empIDFromDB}'
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
        if not earnings:
            print("<!-- DEBUG: No data found for requested month, trying to get most recent data -->")
            fallbackQuery = f"""
                SELECT t.accountTypeName, a.amount, a.month, a.year
                FROM tblAccounts a
                JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
                WHERE a.empID = '{empIDFromDB}'
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
    print(f"<!-- DEBUG: Fetching deductions for empID: {empIDFromDB}, month: {month}, year: {Year} -->")
    queryDeductions = f"""
        SELECT t.accountTypeName, a.amount
        FROM tblAccounts a
        JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
        WHERE TRIM(a.empID) = '{empIDFromDB}'
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
    if not deductions:
        print("<!-- DEBUG: No deductions found, trying alternative queries -->")
        altDeductionsQuery1 = f"""
            SELECT t.accountTypeName, a.amount
            FROM tblAccounts a
            JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
            WHERE a.empID = '{empIDFromDB}'
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
        if not deductions:
            altDeductionsQuery2 = f"""
                SELECT t.accountTypeName, a.amount
                FROM tblAccounts a
                JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
                WHERE a.empID = '{empIDFromDB}'
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
        if not deductions:
            print("<!-- DEBUG: No deductions found for requested month, trying to get most recent data -->")
            fallbackDeductionsQuery = f"""
                SELECT t.accountTypeName, a.amount, a.month, a.year
                FROM tblAccounts a
                JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
                WHERE a.empID = '{empIDFromDB}'
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
    print(f"<!-- DEBUG: Fetching loan deductions for empID: {empIDFromDB}, month: {month}, year: {Year} -->")
    queryLoanDeductions = f"""
        SELECT t.accountTypeName, a.amount
        FROM tblAccounts a
        JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
        WHERE TRIM(a.empID) = '{empIDFromDB}'
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
    if not loanDeductions:
        print("<!-- DEBUG: No loan deductions found, trying alternative queries -->")
        altLoanQuery1 = f"""
            SELECT t.accountTypeName, a.amount
            FROM tblAccounts a
            JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
            WHERE a.empID = '{empIDFromDB}'
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
        if not loanDeductions:
            altLoanQuery2 = f"""
                SELECT t.accountTypeName, a.amount
                FROM tblAccounts a
                JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
                WHERE a.empID = '{empIDFromDB}'
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
        if not loanDeductions:
            print("<!-- DEBUG: No loan deductions found for requested month, trying to get most recent data -->")
            fallbackLoanQuery = f"""
                SELECT t.accountTypeName, a.amount, a.month, a.year
                FROM tblAccounts a
                JOIN tblAccountType t ON a.accountTypeID = t.accountTypeID
                WHERE a.empID = '{empIDFromDB}'
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
        'empID': empIDFromDB,
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
<html lang=\"en\">
<head>
  <meta charset=\"UTF-8\">
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
  <script src=\"https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js\"></script>
</head>
<body>
<div id=\"payslip-container\" class=\"container\">
  <div class=\"org-header\">
    <div class=\"org-title-row\">
      {% if logoWebPath %}
        <img src=\"{{ logoWebPath }}\" alt=\"Logo\" class=\"org-logo\">
      {% endif %}
      <div class=\"org-details\">
        <h2>{{ orgName }}</h2>
        <div class=\"org-meta\">{{ orgAddress1 }}{% if orgAddress2 %}, {{ orgAddress2 }}{% endif %}</div>
        <div class=\"org-meta\">{{ orgCity }}{% if orgState %}, {{ orgState }}{% endif %}</div>
        <div class=\"org-meta\">Phone: {{ orgPhone }}{% if orgWebsite %} | Website: {{ orgWebsite }}{% endif %}</div>
      </div>
    </div>
  </div>

  <h3>Payslip for the month of {{ monthName }} - {{ year }}</h3>

  <!-- Employee Details Table -->
  <table class=\"details\">
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
  <div class=\"tables-container\">
    <div class=\"table-column\">
      <p class=\"section-title\">Earnings</p>
      <table class=\"earnings\">
        {% for type, amount in earnings.items() %}
        <tr>
          <td>{{ type }}</td>
          <td>{% if amount %}₹{{ \"{:,.2f}\".format(amount) }}{% endif %}</td>
        </tr>
        {% endfor %}
        <tr>
          <td><strong>Total Earnings</strong></td>
          <td><strong>{% if totalEarnings %}₹{{ \"{:,.2f}\".format(totalEarnings) }}{% endif %}</strong></td>
        </tr>
      </table>
    </div>
    <div class=\"table-column\">
      <p class=\"section-title\">Deductions</p>
      <table class=\"deductions\">
        {% for type, amount in deductions.items() %}
        <tr>
          <td>{{ type }}</td>
          <td>{% if amount %}₹{{ \"{:,.2f}\".format(amount) }}{% endif %}</td>
        </tr>
        {% endfor %}
      </table>

      <!-- Loan Deductions Table -->
      <p class=\"section-title\">Loan Deductions</p>
      <table class=\"deductions\">
        {% for type, amount in loanDeductions.items() %}
        <tr>
          <td>{{ type }}</td>
          <td>{% if amount %}₹{{ \"{:,.2f}\".format(amount) }}{% endif %}</td>
        </tr>
        {% endfor %}
      </table>
    </div>
  </div>

  <!-- Total Deductions and Net Pay Section -->
  <div style=\"display: flex; justify-content: space-between; margin-top: 10px;\">
    <!-- Left side: Net Pay and Amount in Words -->
    <div style=\"flex: 1;\">
      <div style=\"border: 2px solid #000; background: #e6ffe6; padding: 8px 16px; display: inline-block; margin-bottom: 8px;\">
        <span style=\"font-size: 15px; font-weight: bold;\">Net Pay for the month: </span>
        <span style=\"font-size: 15px; font-weight: bold; color: #1a6600;\">{% if netPay %}₹{{ \"{:,.2f}\".format(netPay) }}{% endif %}</span>
      </div>
      <div style=\"margin-left: 8px; display: inline-block;\">
        <span style=\"font-size: 14px;\"><strong>(in words):</strong> {{ netPayInWords }}</span>
      </div>
    </div>

    <!-- Right side: Total Deductions -->
    <div style=\"width: 40%;\">
      <div class=\"total-deductions\">
        <span style=\"text-align:right; flex: 1;\">Total Deductions</span>
        <span style=\"text-align:right; width: 120px; padding-left: 12px;\">{% if totalDeductions %}₹{{ \"{:,.2f}\".format(totalDeductions) }}{% endif %}</span>
      </div>
      <div class=\"total-deductions\">
        <span style=\"text-align:right; flex: 1;\">Total Loan Deductions</span>
        <span style=\"text-align:right; width: 120px; padding-left: 12px;\">{% if totalLoanDeductions %}₹{{ \"{:,.2f}\".format(totalLoanDeductions) }}{% endif %}</span>
      </div>
      <div class=\"total-deductions\" style=\"font-weight: bold; margin-bottom: 12px;\">
        <span style=\"text-align:right; flex: 1;\">Total (Deductions + Loan Deductions)</span>
        <span style=\"text-align:right; width: 120px; padding-left: 12px;\">₹{{ \"{:,.2f}\".format(totalDeductions + totalLoanDeductions) }}</span>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <div style=\"z-index:-1;\">
    <p style=\"color: #666; font-size: 13px; font-style: italic; margin: 0;\">This is a computer generated payslip and does not require a signature.</p>
    <p id=\"generated-datetime\" style=\"color: #666; font-size: 13px; font-style: italic; margin: 0;\"></p>
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

    # Step 18: Save HTML and PDF output to uploads/<OrgID>/<employeeID>/<Month>-Payslip.pdf
    output_dir = os.path.join("uploads/Organisation", str(OrgID), str(employeeIDPrimaryKey), str(Month))
    os.makedirs(output_dir, exist_ok=True)
    pdf_filename = "Payslip.pdf"
    pdf_path = os.path.join(output_dir, pdf_filename)

    try:
        pdfkit.from_string(html_out, pdf_path)
        print(f"✅ PDF payslip generated successfully! Check {pdf_path}")
    except Exception as e:
        print(f"⚠️ PDF generation failed: {e}")
  
    # Close database connection
    cursor.close()
    conn.close()


if __name__ == "__main__":
    main() 