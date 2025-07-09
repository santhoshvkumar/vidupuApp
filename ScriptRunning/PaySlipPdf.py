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
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payslip - <?php echo $orgName; ?></title>
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
    /* Ensure tables are side by side */
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
    /* Total Deductions line */
    .total-deductions {
      margin: 4px 0;
      width: 100%;
      font-size: 14px;
      display: flex;
      justify-content: flex-end;
    }
    /* Net Pay Box */
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
    /* Amount in words */
    .amount-words {
      margin: 4px 0;
      font-size: 14px;
    }
    /* Footer */
    .footer-text {
      margin-top: 8px;
      color: #666;
      font-size: 13px;
      font-style: italic;
    }
  </style>
  <!-- Add html2pdf.js from CDN -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>
<div id="payslip-container" class="container">
  <div class="org-header">
    <div class="org-title-row">
      <?php if (!empty($logoWebPath)) { ?>
        <img src="{{ org.organisationLogo }}" alt="Logo" class="org-logo">
      <?php } ?>
      <div class="org-details">
        <h2><?php echo $orgName; ?></h2>
        <div class="org-meta"><?php echo $orgAddress1; ?><?php if($orgAddress2) echo ', ' . $orgAddress2; ?></div>
        <div class="org-meta"><?php echo $orgCity; ?><?php if($orgState) echo ', ' . $orgState; ?></div>
        <div class="org-meta">Phone: <?php echo $orgPhone; ?><?php if($orgWebsite) echo ' | Website: ' . $orgWebsite; ?></div>
      </div>
    </div>
  </div>

  <h3>Payslip for the month of <?php echo $monthName; ?> - <?php echo $year; ?></h3>

  <!-- Employee Details Table -->
  <table class="details">
    <tr>
      <td><strong>Name:</strong> <?php echo $employeeName ? $employeeName : ''; ?></td>
      <td><strong>Employee No:</strong> <?php echo $empID ? $empID : ''; ?></td>
    </tr>
    <tr>
      <td><strong>Joining Date:</strong> <?php echo $joiningDate ? date('d-m-Y', strtotime($joiningDate)) : ''; ?></td>
      <td><strong>Bank Name:</strong> <?php echo $bankName ? $bankName : ''; ?></td>
    </tr>
    <tr>
      <td><strong>Designation:</strong> <?php echo $designation ? $designation : ''; ?></td>
      <td><strong>Bank Account No:</strong> <?php echo $bankAccountNumber ? $bankAccountNumber : ''; ?></td>
    </tr>
    <tr>
      <td><strong>Department:</strong> <?php echo $department ? $department : ''; ?></td>
      <td><strong>PAN Number:</strong> <?php echo $panNumber ? $panNumber : ''; ?></td>
    </tr>
    <tr>
      <td><strong>Location:</strong> <?php echo $branchName ? $branchName : ''; ?></td>
      <td><strong>PF No:</strong> <?php echo $pfNumber ? $pfNumber : ''; ?></td>
    </tr>
    <tr>
      <td><strong>Effective Work Days:</strong> <?php echo $workingDays; ?></td>
      <td><strong>PF UAN:</strong> <?php echo $pfUAN ? $pfUAN : ''; ?></td>
    </tr>
    <tr>
      <td><strong>LOP:</strong> <?php echo $lopDays; ?></td>
      <td></td>
    </tr>
  </table>

  <!-- Earnings and Deductions Tables -->
  <div class="tables-container">
    <div class="table-column">
      <p class="section-title">Earnings</p>
      <table class="earnings">
        <?php
        foreach ($earnings as $type => $amount) {
            echo "<tr><td>".htmlspecialchars($type)."</td><td>";
            if ($amount !== '') {
                echo '₹' . number_format($amount, 2);
            }
            echo "</td></tr>";
        }
        ?>
        <tr><td><strong>Total Earnings</strong></td><td><strong><?php echo $totalEarnings !== '' ? '₹' . number_format($totalEarnings, 2) : ''; ?></strong></td></tr>
      </table>
    </div>
    <div class="table-column">
      <p class="section-title">Deductions</p>
      <table class="deductions">
        <?php
        foreach ($deductions as $type => $amount) {
            echo "<tr><td>".htmlspecialchars($type)."</td><td>";
            if ($amount !== '') {
                echo '₹' . number_format($amount, 2);
            }
            echo "</td></tr>";
        }
        ?>
      </table>

      <!-- Loan Deductions Table -->
      <p class="section-title">Loan Deductions</p>
      <table class="deductions">
        <?php
        foreach ($loanDeductions as $type => $amount) {
            echo "<tr><td>".htmlspecialchars($type)."</td><td>";
            if ($amount !== '') {
                echo '₹' . number_format($amount, 2);
            }
            echo "</td></tr>";
        }
        ?>
      </table>
    </div>
  </div>

  <!-- Total Deductions and Net Pay Section -->
  <div style="display: flex; justify-content: space-between; margin-top: 10px;">
    <!-- Left side: Net Pay and Amount in Words -->
    <div style="flex: 1;">
      <div style="border: 2px solid #000; background: #e6ffe6; padding: 8px 16px; display: inline-block; margin-bottom: 8px;">
        <span style="font-size: 15px; font-weight: bold;">Net Pay for the month: </span>
        <span style="font-size: 15px; font-weight: bold; color: #1a6600;"><?php echo $netPay !== '' ? '₹' . number_format($netPay, 2) : ''; ?></span>
      </div>
      <div style="margin-left: 8px; display: inline-block;">
        <span style="font-size: 14px;"><strong>(in words):</strong> <?php echo $netPayInWords; ?></span>
      </div>
    </div>

    <!-- Right side: Total Deductions -->
    <div style="width: 40%;">
      <div class="total-deductions">
        <span style="text-align:right; flex: 1;">Total Deductions</span>
        <span style="text-align:right; width: 120px; padding-left: 12px;"><?php echo $totalDeductions !== '' ? '₹' . number_format($totalDeductions, 2) : ''; ?></span>
      </div>
      <div class="total-deductions">
        <span style="text-align:right; flex: 1;">Total Loan Deductions</span>
        <span style="text-align:right; width: 120px; padding-left: 12px;"><?php echo $totalLoanDeductions !== '' ? '₹' . number_format($totalLoanDeductions, 2) : ''; ?></span>
      </div>
      <div class="total-deductions" style="font-weight: bold; margin-bottom: 12px;">
        <span style="text-align:right; flex: 1;">Total (Deductions + Loan Deductions)</span>
        <span style="text-align:right; width: 120px; padding-left: 12px;"><?php echo ($totalDeductions !== '' || $totalLoanDeductions !== '') ? '₹' . number_format($totalDeductions + $totalLoanDeductions, 2) : ''; ?></span>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <div style="z-index:-1; ">
    <p style="color: #666; font-size: 13px; font-style: italic; margin: 0;">This is a computer generated payslip and does not require a signature.</p>
    <p id="generated-datetime" style="color: #666; font-size: 13px; font-style: italic; margin: 0;"></p>
    <script>
      // Display system generated date and time
      document.getElementById('generated-datetime').textContent =
        'System Generated on: ' + new Date().toLocaleString();
    </script>
  </div>
</div>
</script>
</body>
</html>

"""

template = Template(html_template)
html_out = template.render(emp=emp, org=org, month=Month, year=Year)

# Step 7: Export to PDF
pdfkit.from_string(html_out, "payslip_output.pdf") 

print("✅ Payslip generated successfully!")