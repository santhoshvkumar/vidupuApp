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
    'database': 'tnscVidupuApp',
}

# Excel file path (GLOBAL)
EXCEL_FILE_PATH = r'/data/server/live/API/public_html/vidupuApp/ScriptRunning/Sal_slip.xlsx'

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
                        <li>Check that payslip.php is accessible at: http://localhost:8888/Vidupu/vidupuApi/Website/payslip.php</li>
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

                    # Call the generate_payslip function directly
                    from ScriptRunning.PaySlipPdf import generate_payslip
                    generate_payslip(
                        employeeID=employee_id,
                        Month=month,
                        Year=year,
                        OrgID=1
                    )
                    
                    processed_count += 1
                    
                    # Track employee for PDF generation
                    employees_processed.add((emp_id, month, year))
                        
                except Exception as e:
                    logger.error(f"Error processing row {index + 1}: {e}")
                    skipped_count += 1
                    continue
            
            
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
        logger.info(f"PDF payslips saved to: {processor.payslip_dir}")
        
    except Exception as e:
        logger.error(f"FAILED: {e}")
        sys.exit(1)
    
    finally:
        processor.close_db()

if __name__ == "__main__":
    main() 