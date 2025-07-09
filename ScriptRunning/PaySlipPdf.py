from weasyprint import HTML

# 1. Define your HTML content (converted from your PHP file)
html_content = """
<!DOCTYPE html>
<html>
<head>
    <title>Payslip</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { padding: 20px; }
        .header { text-align: center; font-size: 24px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Employee Payslip</div>
        <table>
            <tr><th>Name</th><td>John Doe</td></tr>
            <tr><th>Designation</th><td>Software Engineer</td></tr>
            <tr><th>Basic Salary</th><td>₹50,000</td></tr>
            <tr><th>HRA</th><td>₹10,000</td></tr>
            <tr><th>Gross Salary</th><td>₹60,000</td></tr>
        </table>
    </div>
</body>
</html>
"""

# 2. Create PDF
HTML(string=html_content).write_pdf("payslip_output.pdf")

print("PDF generated and saved as payslip_output.pdf")
