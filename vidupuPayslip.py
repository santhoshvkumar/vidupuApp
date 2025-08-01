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

# Excel file path (live server)
EXCEL_FILE_PATH = r'/data/server/live/API/public_html/vidupuApp/ScriptRunning/Sal_slip.xlsx'
# EXCEL_FILE_PATH = r'C:/MAMP/htdocs/Vidupu/vidupuApi/ScriptRunning/Sal_slip.xlsx'

# Direct pay type to category mapping
PAY_TYPE_MAPPING = {
    'E': 'earnings',
    'OD': 'deductions',
    'LD': 'loans',
    'LD1': 'loans',
    'PT': 'deductions'  # Added PT as deduction
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
        self.lop_data = {}  # Store LOP data for each employee
    def connect_db(self):
        """Connect to MySQL database"""
        try:
            self.connection = pymysql.connect(host="localhost", user="vsk", password="Password#1", database="tnscVidupuApp")
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
            self.connection = pymysql.connect(host="localhost", user="vsk", password="Password#1", database="tnscVidupuApp")
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
        """Create account mapping if doesn't exist for this specific employee"""
        cursor = self.connection.cursor()
        try:
            # Check if mapping exists for this specific employee and account type
            cursor.execute(
                "SELECT accountMappingID FROM tblAccountMapping WHERE empID = %s AND accountTypeID = %s AND isActive = 1",
                (emp_id, account_type_id)
            )
            
            existing_mapping = cursor.fetchone()
            
            if not existing_mapping:
                # Only insert if this specific employee doesn't have this account type mapped
                cursor.execute(
                    "INSERT INTO tblAccountMapping (empID, accountTypeID, assignedOn, assignedBy, isActive) VALUES (%s, %s, %s, %s, %s)",
                    (emp_id, account_type_id, datetime.now(), 1, 1)
                )
                self.connection.commit()
                logger.info(f"Created new account mapping: empID={emp_id}, accountTypeID={account_type_id}")
            else:
                logger.info(f"Account mapping already exists for empID={emp_id}, accountTypeID={account_type_id} - skipping")
            
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
                "SELECT accountID FROM tblAccounts WHERE empID = %s AND accountTypeID = %s AND month = %s AND year = %s",
                (emp_id, account_type_id, month, year)
            )
            result = cursor.fetchone()
            
            if result:
                # Update existing record
                cursor.execute(
                    "UPDATE tblAccounts SET amount = %s WHERE accountID = %s",
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
                    
                    # Get LOP days from Excel columns U and V (index 20 and 21)
                    current_lop_days = row.iloc[20] if len(row) > 20 else 0  # Column U
                    total_lop_days = row.iloc[19] if len(row) > 21 else 0     # Column T
                    
                    # Store LOP data for this employee
                    emp_key = f"{emp_id}_{month}_{year}"
                    if emp_key not in self.lop_data:
                        self.lop_data[emp_key] = {
                            'current_lop_days': current_lop_days,
                            'total_lop_days': total_lop_days
                        }
                    
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
                    
                    # Special handling for SPA: Subtract SPA amount from BASIC only if SPA exists
                    if component_name == 'SPA' and pay_type == 'E':
                        # Check if SPA amount is greater than 0 (SPA exists)
                        if amount > 0:
                            # Find BASIC account type ID
                            basic_account_type_id = self.get_or_create_account_type('BASIC', 'earnings')

                            logger.info("Account Details SPA", basic_account_type_id)
                            # Get current BASIC amount for this employee
                            cursor = self.connection.cursor()
                            try:
                                cursor.execute(
                                    "SELECT accountID, amount FROM tblAccounts WHERE empID = %s AND accountTypeID = %s AND month = %s AND year = %s",
                                    (emp_id, basic_account_type_id, month, year)
                                )
                                basic_result = cursor.fetchone()
                                
                                if basic_result:
                                    # Subtract SPA amount from BASIC
                                    current_basic_amount = basic_result[1] or 0
                                    new_basic_amount = float(current_basic_amount) - float(amount)
                                    
                                    cursor.execute(
                                        "UPDATE tblAccounts SET amount = %s WHERE accountID = %s",
                                        (new_basic_amount, basic_result[0])
                                    )
                                    self.connection.commit()
                                    logger.info(f"Subtracted SPA amount {amount} from BASIC for empID {emp_id}. New BASIC amount: {new_basic_amount}")
                                else:
                                    logger.warning(f"BASIC record not found for empID {emp_id} to subtract SPA amount")
                            except Exception as e:
                                logger.error(f"Error subtracting SPA from BASIC for {emp_id}: {e}")
                                self.connection.rollback()
                            finally:
                                cursor.close()
                        else:
                            logger.info(f"SPA amount is 0 or negative for empID {emp_id}, skipping BASIC adjustment")
                    
                    # Special handling for CMonthLOP and LOP: Remove from deductions if they exist
                    if component_name in ['CMonthLOP', 'LOP'] and pay_type in ['LD', 'LD1']:
                        # Check if LOP amount is greater than 0 (LOP exists)
                        if amount > 0:
                            # Find the corresponding deduction account type (usually TOTAL_DEDUCTIONS or similar)
                            # We'll remove it from the total deductions calculation in the payslip generation
                            logger.info(f"LOP component '{component_name}' with amount {amount} found for empID {emp_id}. Will be excluded from deductions.")
                        else:
                            logger.info(f"LOP amount is 0 or negative for empID {emp_id}, component '{component_name}', skipping deduction adjustment")

                  
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
                
                # Get LOP data for this employee
                emp_key = f"{employee_id}_{month}_{year}"
                lop_data = self.lop_data.get(emp_key, None)
                
                generate_payslip(
                    employeeID=employee_id,
                    Month=month_name,
                    Year=year,
                    OrgID=organisation_id,
                    lop_data=lop_data
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
       # processor.clear_existing_data()
        
        # Process Excel file (use global EXCEL_FILE_PATH)
        processor.process_excel_file(EXCEL_FILE_PATH)
        
        logger.info("SUCCESS: Payslip processing completed!")
        logger.info("PDF payslips generated successfully!")
        
    except Exception as e:
        logger.error(f"FAILED: {e}")
        sys.exit(1)
    
    finally:
        processor.close_db()

def generate_payslip(employeeID, Month, Year, OrgID, lop_data=None):
    logger.info("inisde Function")
    # Step 2: Connect to MySQL
    conn = pymysql.connect(
            host="localhost", user="vsk", password="Password#1", database="tnscVidupuApp"
    )
    cursor = conn.cursor(pymysql.cursors.DictCursor)

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

    # Step 8: Get LOP days from Excel data or calculate from attendance
    current_lop_days = 0
    total_lop_days = 0
    
    if lop_data:
        # Use LOP data from Excel
        current_lop_days = lop_data.get('current_lop_days', 0)
        total_lop_days = lop_data.get('total_lop_days', 0)
        logger.info(f"Using LOP data from Excel - Current: {current_lop_days}, Total: {total_lop_days}")
    else:
        # Fallback to attendance calculation
        lopDays = 0
        if employee:
            # Set LOP to 0 for April (month 4) and May (month 5) and June (month 6) and July (month 7)
            if month == 4 or month == 5 or month == 6 or month == 7:
                lopDays = 0
            else:
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
            current_lop_days = lopDays
            total_lop_days = lopDays

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
    
    # Use a single, clean query with proper debugging
    earnings_query = f"""
    SELECT accType.accountTypeName, acc.amount, acc.month, acc.year
    FROM tblAccounts acc
    JOIN tblAccountType accType ON acc.accountTypeID = accType.accountTypeID
    WHERE acc.empID = '{empIDFromDB}'
      AND acc.month = {month}
      AND acc.year = {Year}
      AND accType.typeOfAccount = 'earnings'
    GROUP BY acc.accountID
    ORDER BY accType.accountTypeName
    """
    
    print(f"<!-- DEBUG: Earnings Query: {earnings_query} -->")
    cursor.execute(earnings_query)
    result = cursor.fetchall()
    
    print(f"<!-- DEBUG: Raw earnings result count: {len(result)} -->")
    for i, row in enumerate(result):
        print(f"<!-- DEBUG: Row {i}: {row} -->")
    
    # Check for duplicates and sum properly
    earnings_temp = {}
    for row in result:
        account_name = row['accountTypeName']
        amount = row['amount']
        
        if account_name in earnings_temp:
            print(f"<!-- WARNING: Duplicate account type '{account_name}' found! -->")
            print(f"<!-- Previous amount: {earnings_temp[account_name]}, New amount: {amount} -->")
            # Sum the amounts for the same account type
            earnings_temp[account_name] += amount
        else:
            earnings_temp[account_name] = amount
    
    earnings = earnings_temp
    totalEarnings = sum(earnings.values())
    
    print(f"<!-- DEBUG: Final Earnings Array: {earnings} -->")
    print(f"<!-- DEBUG: Total Earnings: {totalEarnings} -->")
    # Step 11: Fetch all deductions for the employee for the given month/year
    deductions = {}
    totalDeductions = 0
    print(f"<!-- DEBUG: Fetching deductions for empID: {empIDFromDB}, month: {month}, year: {Year} -->")
    
    deductions_query = f"""
    SELECT accType.accountTypeName, acc.amount, acc.month, acc.year
    FROM tblAccounts acc
    JOIN tblAccountType accType ON acc.accountTypeID = accType.accountTypeID
    WHERE acc.empID = '{empIDFromDB}'
      AND acc.month = {month}
      AND acc.year = {Year}
      AND accType.typeOfAccount = 'deductions'
      AND accType.accountTypeName NOT IN ('CMonthLOP', 'LOP')
    GROUP BY acc.accountID
    ORDER BY accType.accountTypeName
    """
    
    print(f"<!-- DEBUG: Deductions Query: {deductions_query} -->")
    cursor.execute(deductions_query)
    result = cursor.fetchall()
    
    print(f"<!-- DEBUG: Raw deductions result count: {len(result)} -->")
    for i, row in enumerate(result):
        print(f"<!-- DEBUG: Deduction Row {i}: {row} -->")
    
    # Check for duplicates and sum properly
    deductions_temp = {}
    for row in result:
        account_name = row['accountTypeName']
        amount = row['amount']
        
        if account_name in deductions_temp:
            print(f"<!-- WARNING: Duplicate deduction account type '{account_name}' found! -->")
            print(f"<!-- Previous amount: {deductions_temp[account_name]}, New amount: {amount} -->")
            # Sum the amounts for the same account type
            deductions_temp[account_name] += amount
        else:
            deductions_temp[account_name] = amount
    
    deductions = deductions_temp
    totalDeductions = sum(deductions.values())
    
    print(f"<!-- DEBUG: Final Deductions Array: {deductions} -->")
    print(f"<!-- DEBUG: Total Deductions: {totalDeductions} -->")
    # Step 12: Fetch all loan deductions for the employee for the given month/year
    loanDeductions = {}
    totalLoanDeductions = 0
    print(f"<!-- DEBUG: Fetching loan deductions for empID: {empIDFromDB}, month: {month}, year: {Year} -->")
    
    loan_query = f"""
    SELECT accType.accountTypeName, acc.amount, acc.month, acc.year
    FROM tblAccounts acc
    JOIN tblAccountType accType ON acc.accountTypeID = accType.accountTypeID
    WHERE acc.empID = '{empIDFromDB}'
      AND acc.month = {month}
      AND acc.year = {Year}
      AND accType.typeOfAccount = 'loans'
    GROUP BY acc.accountID
    ORDER BY accType.accountTypeName
    """
    
    print(f"<!-- DEBUG: Loan Query: {loan_query} -->")
    cursor.execute(loan_query)
    result = cursor.fetchall()
    
    print(f"<!-- DEBUG: Raw loan deductions result count: {len(result)} -->")
    for i, row in enumerate(result):
        print(f"<!-- DEBUG: Loan Row {i}: {row} -->")
    
    # Check for duplicates and sum properly
    loanDeductions_temp = {}
    for row in result:
        account_name = row['accountTypeName']
        amount = row['amount']
        
        if account_name in loanDeductions_temp:
            print(f"<!-- WARNING: Duplicate loan account type '{account_name}' found! -->")
            print(f"<!-- Previous amount: {loanDeductions_temp[account_name]}, New amount: {amount} -->")
            # Sum the amounts for the same account type
            loanDeductions_temp[account_name] += amount
        else:
            loanDeductions_temp[account_name] = amount
    
    loanDeductions = loanDeductions_temp
    totalLoanDeductions = sum(loanDeductions.values())
    
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
        'currentLopDays': current_lop_days,
        'totalLopDays': total_lop_days,
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
      <td><strong>Current LOP:</strong> {{ currentLopDays }}</td>
      <td><strong>Total LOP:</strong> {{ totalLopDays }}</td>
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