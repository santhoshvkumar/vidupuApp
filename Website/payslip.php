
<?php
$employeeId = $_GET['EmpID'];
$month = $_GET['Month'];
$year = $_GET['Year'];
$organisationId = $_GET['OrgID'];

include('config.inc');
$queryOrg = "SELECT * FROM tblOrganisation WHERE organisationID = $organisationId";
$stmtOrg = mysqli_query($connect_var, $queryOrg);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payslip - </title>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 40px;
      color: #000;
    }
    .container {
      border: 1px solid #000;
      padding: 20px;
    }
    h2, h3 {
      text-align: center;
      margin: 10px 0;
    }
    .details, .earnings, .deductions, .tax, .tds {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    .details td, .earnings td, .deductions td, .tax td, .tds td {
      border: 1px solid #000;
      padding: 8px;
    }
    .section-title {
      margin-top: 40px;
      font-weight: bold;
    }
  </style>
</head>
<body>

<div class="container">
  <h2>TNSC Bank</h2>
  <p style="text-align:center;">Chennai</p>

  <h3>Payslip for the month of <b><?php echo $month; ?> - <?php echo $year; ?></b></h3>

  <table class="details">
    <tr>
      <td><strong>Name:</strong> _____________</td>
      <td><strong>Employee No:</strong> _____________</td>
    </tr>
    <tr>
      <td><strong>Joining Date:</strong> _____________</td>
      <td><strong>Bank Name:</strong> _____________</td>
    </tr>
    <tr>
      <td><strong>Designation:</strong> _____________</td>
      <td><strong>Bank Account No:</strong> _____________</td>
    </tr>
    <tr>
      <td><strong>Department:</strong> _____________</td>
      <td><strong>PAN Number:</strong> _____________</td>
    </tr>
    <tr>
      <td><strong>Location:</strong> _____________</td>
      <td><strong>PF No:</strong> _____________</td>
    </tr>
    <tr>
      <td><strong>Effective Work Days:</strong> _____________</td>
      <td><strong>PF UAN:</strong> _____________</td>
    </tr>
    <tr>
      <td colspan="2"><strong>LOP:</strong> _____________</td>
    </tr>
  </table>

  <p class="section-title">Earnings</p>
  <table class="earnings">
    <tr><td>BASIC</td><td>___________</td></tr>
    <tr><td>HRA</td><td>___________</td></tr>
    <tr><td>CONVEYANCE</td><td>___________</td></tr>
    <tr><td>MEDICAL ALLOWANCE</td><td>___________</td></tr>
    <tr><td>OTHER ALLOWANCE</td><td>___________</td></tr>
    <tr><td><strong>Total Earnings</strong></td><td>___________</td></tr>
  </table>

  <p class="section-title">Deductions</p>
  <table class="deductions">
    <tr><td>PF</td><td>___________</td></tr>
    <tr><td>INCOME TAX</td><td>___________</td></tr>
    <tr><td>MEDICLAIM</td><td>___________</td></tr>
    <tr><td><strong>Total Deduction</strong></td><td>___________</td></tr>
  </table>

  <p><strong>Net Pay for the month:</strong> _____________</p>
  <p><strong>(in words):</strong> _______________________________________</p>

  <p class="section-title">TDS Details</p>
  <table class="tds">
    <tr><td>Description</td><td>Gross</td><td>Exempt</td><td>Taxable</td></tr>
    <tr><td>BASIC</td><td>__________</td><td>__________</td><td>__________</td></tr>
    <tr><td>HRA</td><td>__________</td><td>__________</td><td>__________</td></tr>
    <tr><td>CONVEYANCE</td><td>__________</td><td>__________</td><td>__________</td></tr>
    <tr><td>MEDICAL ALLOWANCE</td><td>__________</td><td>__________</td><td>__________</td></tr>
    <tr><td>OTHER ALLOWANCE</td><td>__________</td><td>__________</td><td>__________</td></tr>
  </table>

  <p class="section-title">Deduction Under Chapter VI-A</p>
  <table class="tax">
    <tr><td>Life Insurance Premium</td><td>___________</td></tr>
    <tr><td>Mutual Funds</td><td>___________</td></tr>
    <tr><td>PF</td><td>___________</td></tr>
    <tr><td>Income after Section 10 Exemption</td><td>___________</td></tr>
    <tr><td>Standard Deduction</td><td>___________</td></tr>
    <tr><td>Total VI A Deduction</td><td>___________</td></tr>
    <tr><td>Taxable Income</td><td>___________</td></tr>
    <tr><td>Total Tax</td><td>___________</td></tr>
    <tr><td>Education Cess</td><td>___________</td></tr>
    <tr><td>Tax Deducted Till Date</td><td>___________</td></tr>
    <tr><td>Tax to be Deducted</td><td>___________</td></tr>
    <tr><td>Monthly Projected Tax</td><td>___________</td></tr>
  </table>

  <p class="section-title">Tax Paid Details</p>
  <p>APR - ________ | MAY - ________ | JUN - ________ | JUL - ________ | AUG - ________ | SEP - ________</p>
  <p>OCT - ________ | NOV - ________ | DEC - ________ | JAN - ________ | FEB - ________ | MAR - ________</p>

  <p style="margin-top: 30px;"><em>This is a computer generated payslip and does not require a signature.</em></p>
</div>

</body>
</html>
 