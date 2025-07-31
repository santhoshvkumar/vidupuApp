#!/usr/bin/env python3
"""
Process Payslip for Employee ID 147 Only
========================================

This script processes payslip data only for employee ID 147 (employee 939).
Useful for testing and debugging specific employee data.
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

# Excel file path
EXCEL_FILE_PATH = r'C:/MAMP/htdocs/Vidupu/vidupuApi/ScriptRunning/Sal_slip.xlsx'

# Direct pay type to category mapping
PAY_TYPE_MAPPING = {
    'E': 'earnings',
    'OD': 'deductions',
    'LD': 'loans',
    'LD1': 'loans',
    'PT': 'deductions'
}

# Month number to name mapping
MONTH_NAMES = {
    1: 'January', 2: 'February', 3: 'March', 4: 'April', 5: 'May', 6: 'June',
    7: 'July', 8: 'August', 9: 'September', 10: 'October', 11: 'November', 12: 'December'
}

class Employee147Processor:
    """Process payslip data for employee ID 147 only"""
    
    def __init__(self):
        self.connection = None
        self.account_types = {}
        self.lop_data = {}
        self.target_employee_id = 147  # Employee ID 147
        self.target_emp_id = '939'     # Employee number 939
    
    def connect_db(self):
        """Connect to MySQL database"""
        try:
            self.connection = pymysql.connect(host="localhost", user="vsk", password="Password#1", database="tnscVidupuApp")
            logger.info("Database connection established")
            self.load_account_types()
        except Exception as e:
            logger.error(f"Failed to connect to database: {e}")
            raise
    
    def close_db(self):
        """Close database connection"""
        if self.connection:
            self.connection.close()
            logger.info("Database connection closed")
    
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
    
    def clear_employee_data(self, month=7, year=2025):
        """Clear existing data for employee 147 for specific month/year"""
        cursor = self.connection.cursor()
        try:
            logger.info(f"Clearing existing data for employee 147, month {month}, year {year}...")
            cursor.execute(
                "DELETE FROM tblAccounts WHERE employeeID = %s AND month = %s AND year = %s", 
                (self.target_employee_id, month, year)
            )
            deleted_count = cursor.rowcount
            self.connection.commit()
            logger.info(f"Deleted {deleted_count} records for employee 147")
        except Exception as e:
            logger.error(f"Error clearing data: {e}")
            self.connection.rollback()
        finally:
            cursor.close()
    
    def process_employee_147(self, file_path):
        """Process Excel file for employee 147 only"""
        try:
            logger.info(f"Reading Excel file: {file_path}")
            df = pd.read_excel(file_path, engine='openpyxl')
            
            # Filter for employee 939 only
            emp_147_data = df[df.iloc[:, 0] == 939]  # Staff_code column
            
            logger.info(f"Found {len(emp_147_data)} rows for employee 939")
            
            if len(emp_147_data) == 0:
                logger.error("No data found for employee 939 in Excel file")
                return
            
            processed_count = 0
            skipped_count = 0
            
            for index, row in emp_147_data.iterrows():
                try:
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
                    
                    # Get LOP days from Excel columns U and T (index 20 and 19)
                    current_lop_days = row.iloc[20] if len(row) > 20 else 0  # Column U
                    total_lop_days = row.iloc[19] if len(row) > 19 else 0     # Column T
                    
                    # Store LOP data for this employee using employee_id (database ID)
                    emp_key = f"{employee_id}_{month}_{year}"
                    if emp_key not in self.lop_data:
                        self.lop_data[emp_key] = {
                            'current_lop_days': current_lop_days,
                            'total_lop_days': total_lop_days
                        }
                        logger.info(f"LOP data for employee 147: Current={current_lop_days}, Total={total_lop_days}")
                    
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
                                    new_basic_amount = current_basic_amount - amount
                                    
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
                            logger.info(f"LOP component '{component_name}' with amount {amount} found for empID {emp_id}. Will be excluded from deductions.")
                        else:
                            logger.info(f"LOP amount is 0 or negative for empID {emp_id}, component '{component_name}', skipping deduction adjustment")
                    
                    processed_count += 1
                    logger.info(f"Processed: {component_name} = {amount} ({category})")
                        
                except Exception as e:
                    logger.error(f"Error processing row {index + 1}: {e}")
                    skipped_count += 1
                    continue
            
            logger.info(f"Processing complete for employee 147:")
            logger.info(f"  - Processed: {processed_count} records")
            logger.info(f"  - Skipped: {skipped_count} records")
            
            # Generate payslip for employee 147
            self.generate_payslip_for_147(month, year)
            
        except Exception as e:
            logger.error(f"Error processing Excel file: {e}")
            raise
    
    def generate_payslip_for_147(self, month, year):
        """Generate payslip for employee 147"""
        try:
            # Get LOP data for this employee
            emp_key = f"{self.target_employee_id}_{month}_{year}"
            lop_data = self.lop_data.get(emp_key, None)
            
            month_name = MONTH_NAMES.get(month, 'Unknown')
            
            logger.info(f"Generating payslip for employee 147: {month_name} {year}")
            
            # Import the generate_payslip function from the main script
            from vidupuPayslip import generate_payslip
            
            generate_payslip(
                employeeID=self.target_employee_id,
                Month=month_name,
                Year=year,
                OrgID=1,
                lop_data=lop_data
            )
            
            logger.info(f"Payslip generated successfully for employee 147!")
            
        except Exception as e:
            logger.error(f"Error generating payslip for employee 147: {e}")

def main():
    """Main function"""
    processor = Employee147Processor()
    
    try:
        # Connect to database
        processor.connect_db()
        
        # Clear existing data for employee 147 (July 2025)
        processor.clear_employee_data(month=7, year=2025)
        
        # Process Excel file for employee 147 only
        processor.process_employee_147(EXCEL_FILE_PATH)
        
        logger.info("SUCCESS: Employee 147 processing completed!")
        
    except Exception as e:
        logger.error(f"FAILED: {e}")
        sys.exit(1)
    
    finally:
        processor.close_db()

if __name__ == "__main__":
    main() 