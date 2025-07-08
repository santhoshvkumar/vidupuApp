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
"""

import pandas as pd
import pymysql
import sys
from datetime import datetime
import logging

# Set up logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

# Database configuration
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': 'root',
    'database': 'tnscvidupuapp',
    'port': 8889,
    'charset': 'utf8'
}

# Direct pay type to category mapping
PAY_TYPE_MAPPING = {
    'E': 'earnings',
    'OD': 'deductions',
    'LD': 'loans',
    'LD1': 'loans'
}

class SimplePayslipProcessor:
    """Simplified payslip processor"""
    
    def __init__(self):
        self.connection = None
        self.account_types = {}
        
    def connect_db(self):
        """Connect to MySQL database"""
        try:
            self.connection = pymysql.connect(**DB_CONFIG)
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
                        
                except Exception as e:
                    logger.error(f"Error processing row {index + 1}: {e}")
                    skipped_count += 1
                    continue
            
            logger.info("=" * 50)
            logger.info("PROCESSING COMPLETE")
            logger.info("=" * 50)
            logger.info(f"Total rows processed: {processed_count}")
            logger.info(f"Total rows skipped: {skipped_count}")
            logger.info(f"Success rate: {(processed_count/(processed_count+skipped_count)*100):.1f}%")
            
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
        
        # Process Excel file
        excel_file = "Payslip/Sal_slip_062025.xlsx"
        processor.process_excel_file(excel_file)
        
        logger.info("SUCCESS: Payslip processing completed!")
        
    except Exception as e:
        logger.error(f"FAILED: {e}")
        sys.exit(1)
    
    finally:
        processor.close_db()

if __name__ == "__main__":
    main() 