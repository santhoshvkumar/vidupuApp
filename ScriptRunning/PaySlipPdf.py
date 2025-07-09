import mysql.connector
from jinja2 import Template
import pdfkit
import calendar

# Step 1: Input parameters
EmpID = 10199
Month = "June"
Year = 2025
OrgID = 1

# Step 2: Connect to MySQL
conn = mysql.connector.connect(
    host="localhost", user="vsk", password="Password#1", database="tnscVidupuApp"
)
cursor = conn.cursor(dictionary=True)

# Step 3: Convert Month name to number
month_num = list(calendar.month_name).index(Month)

# Step 4: Fetch Organisation Details
cursor.execute(f"SELECT * FROM tblOrganisation WHERE organisationID = {OrgID}")
org = cursor.fetchone()

# Step 5: Fetch Employee Details with all required fields
cursor.execute(f"""
    SELECT e.empID, e.employeeName, e.joiningDate, e.designation, e.department,
           e.bankAccountNumber, e.PANNumber, e.PFNumber, e.PFUAN, e.bankName,
           b.branchName
    FROM tblEmployee e
    LEFT JOIN tblmapEmp m ON e.employeeID = m.employeeID
    LEFT JOIN tblBranch b ON m.branchID = b.branchID
    WHERE e.empID = {EmpID}
""")
emp = cursor.fetchone()

# Step 6: Fetch payroll data (mock data for demonstration)
# In a real implementation, you would fetch from tblAccounts based on the month/year
earnings = {
    "Basic Salary": 25000.00,
    "House Rent Allowance": 12500.00,
    "Conveyance Allowance": 2000.00,
    "Medical Allowance": 1500.00,
    "Special Allowance": 5000.00
}

deductions = {
    "Provident Fund": 3000.00,
    "Professional Tax": 200.00,
    "Income Tax": 2500.00,
    "ESI": 150.00
}

loan_deductions = {
    "Personal Loan": 1000.00,
    "Vehicle Loan": 500.00
}

# Calculate totals
total_earnings = sum(earnings.values())
total_deductions = sum(deductions.values())
total_loan_deductions = sum(loan_deductions.values())
net_pay = total_earnings - total_deductions - total_loan_deductions

# Working days calculation (mock data)
working_days = 22
lop_days = 0

# Convert net pay to words (simplified)
def number_to_words(amount):
    # This is a simplified version - you might want to use a proper library
    return f"Rupees {int(amount)} and {int((amount % 1) * 100)} Paise Only"

net_pay_words = number_to_words(net_pay)

# Step 7: Prepare template data
template_data = {
    'org': org,
    'emp': emp,
    'month': Month,
    'year': Year,
    'earnings': earnings,
    'deductions': deductions,
    'loan_deductions': loan_deductions,
    'total_earnings': total_earnings,
    'total_deductions': total_deductions,
    'total_loan_deductions': total_loan_deductions,
    'net_pay': net_pay,
    'net_pay_words': net_pay_words,
    'working_days': working_days,
    'lop_days': lop_days
}

# Step 8: HTML Template with Jinja2 syntax
html_template = """
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payslip - {{ org.organisationName }}</title>
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
      {% if org.organisationLogo %}
        <img src="{{ org.organisationLogo }}" alt="Logo" class="org-logo">
      {% endif %}
      <div class="org-details">
        <h2>{{ org.organisationName }}</h2>
        <div class="org-meta">{{ org.address1 }}{% if org.address2 %}, {{ org.address2 }}{% endif %}</div>
        <div class="org-meta">{{ org.city }}{% if org.state %}, {{ org.state }}{% endif %}</div>
        <div class="org-meta">Phone: {{ org.phone }}{% if org.website %} | Website: {{ org.website }}{% endif %}</div>
      </div>
    </div>
  </div>

  <h3>Payslip for the month of {{ month }} - {{ year }}</h3>

  <!-- Employee Details Table -->
  <table class="details">
    <tr>
      <td><strong>Name:</strong> {{ emp.employeeName or '' }}</td>
      <td><strong>Employee No:</strong> {{ emp.empID or '' }}</td>
    </tr>
    <tr>
      <td><strong>Joining Date:</strong> {% if emp.joiningDate %}{{ emp.joiningDate.strftime('%d-%m-%Y') }}{% endif %}</td>
      <td><strong>Bank Name:</strong> {{ emp.bankName or '' }}</td>
    </tr>
    <tr>
      <td><strong>Designation:</strong> {{ emp.designation or '' }}</td>
      <td><strong>Bank Account No:</strong> {{ emp.bankAccountNumber or '' }}</td>
    </tr>
    <tr>
      <td><strong>Department:</strong> {{ emp.department or '' }}</td>
      <td><strong>PAN Number:</strong> {{ emp.PANNumber or '' }}</td>
    </tr>
    <tr>
      <td><strong>Location:</strong> {{ emp.branchName or '' }}</td>
      <td><strong>PF No:</strong> {{ emp.PFNumber or '' }}</td>
    </tr>
    <tr>
      <td><strong>Effective Work Days:</strong> {{ working_days }}</td>
      <td><strong>PF UAN:</strong> {{ emp.PFUAN or '' }}</td>
    </tr>
    <tr>
      <td><strong>LOP:</strong> {{ lop_days }}</td>
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
          <td><strong>{% if total_earnings %}₹{{ "{:,.2f}".format(total_earnings) }}{% endif %}</strong></td>
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
        {% for type, amount in loan_deductions.items() %}
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
        <span style="font-size: 15px; font-weight: bold; color: #1a6600;">{% if net_pay %}₹{{ "{:,.2f}".format(net_pay) }}{% endif %}</span>
      </div>
      <div style="margin-left: 8px; display: inline-block;">
        <span style="font-size: 14px;"><strong>(in words):</strong> {{ net_pay_words }}</span>
      </div>
    </div>

    <!-- Right side: Total Deductions -->
    <div style="width: 40%;">
      <div class="total-deductions">
        <span style="text-align:right; flex: 1;">Total Deductions</span>
        <span style="text-align:right; width: 120px; padding-left: 12px;">{% if total_deductions %}₹{{ "{:,.2f}".format(total_deductions) }}{% endif %}</span>
      </div>
      <div class="total-deductions">
        <span style="text-align:right; flex: 1;">Total Loan Deductions</span>
        <span style="text-align:right; width: 120px; padding-left: 12px;">{% if total_loan_deductions %}₹{{ "{:,.2f}".format(total_loan_deductions) }}{% endif %}</span>
      </div>
      <div class="total-deductions" style="font-weight: bold; margin-bottom: 12px;">
        <span style="text-align:right; flex: 1;">Total (Deductions + Loan Deductions)</span>
        <span style="text-align:right; width: 120px; padding-left: 12px;">₹{{ "{:,.2f}".format(total_deductions + total_loan_deductions) }}</span>
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

# Step 9: Render HTML from Template
template = Template(html_template)
html_out = template.render(**template_data)

# Step 10: Export to PDF
pdfkit.from_string(html_out, "payslip_output.pdf")

print("✅ Payslip generated successfully!")

# Close database connection
cursor.close()
conn.close()