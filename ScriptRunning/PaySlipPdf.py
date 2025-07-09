import mysql.connector
from jinja2 import Template
import pdfkit

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
import calendar
month_num = list(calendar.month_name).index(Month)

# Step 4: Fetch Organisation Details
cursor.execute(f"SELECT * FROM tblOrganisation WHERE organisationID = {OrgID}")
org = cursor.fetchone()

# Step 5: Fetch Employee Details
cursor.execute(f"""
    SELECT empID, bankAccountNumber, PANNumber, PFNumber, PFUAN 
    FROM tblEmployee 
    WHERE empID = {EmpID}
""")
emp = cursor.fetchone()

# Step 6: Render HTML from Template
html_template = """
<html>
<head>
    <style>
        body { font-family: Arial; }
        h1 { text-align: center; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 10px; border: 1px solid black; }
    </style>
</head>
<body>
    <h1>{{ org.organisationName }} - Payslip</h1>
    <p><strong>Month:</strong> {{ month }} {{ year }}</p>
    <table>
        <tr><th>Employee ID</th><td>{{ emp.empID }}</td></tr>
        <tr><th>Bank Account</th><td>{{ emp.bankAccountNumber }}</td></tr>
        <tr><th>PAN Number</th><td>{{ emp.PANNumber }}</td></tr>
        <tr><th>PF Number</th><td>{{ emp.PFNumber }}</td></tr>
        <tr><th>UAN</th><td>{{ emp.PFUAN }}</td></tr>
    </table>
</body>
</html>
"""

template = Template(html_template)
html_out = template.render(emp=emp, org=org, month=Month, year=Year)

# Step 7: Export to PDF
pdfkit.from_string(html_out, "payslip_output.pdf")

print("✅ Payslip generated successfully!")