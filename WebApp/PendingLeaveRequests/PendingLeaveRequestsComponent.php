<?php
class PendingLeaveRequestsComponent {
    public $organisationID;
    public $today;

    public function loadPendingLeaveRequests(array $data) {
        if (isset($data['organisationID'])) {
            $this->organisationID = $data['organisationID'];
            $this->today = isset($data['today']) ? $data['today'] : date('Y-m-d');
            return true;
        } else {
            error_log("Missing required parameter organisationID in loadPendingLeaveRequests: " . print_r($data, true));
            return false;
        }
    }

    public function GetPendingLeaveRequests() {
        include('config.inc');
        
        try {
            $query = "SELECT 
                e.employeeName,
                e.empID,
                e.employeePhone,
                e.Designation,
                l.typeOfLeave,
                b.branchName as locationName,
                mng.employeeName AS managerName,
                mng.employeePhone AS managerPhone,
                mng.empID AS managerID,
                l.fromDate,
                l.toDate,
                l.leaveDuration,
                l.reason,
                l.createdOn
            FROM tblApplyLeave l
            JOIN tblEmployee e ON l.employeeID = e.employeeID
            JOIN tblmapEmp m ON e.employeeID = m.employeeID
            JOIN tblBranch b ON m.branchID = b.branchID
            LEFT JOIN tblEmployee mng ON e.managerID = mng.employeeID
            WHERE l.status = 'Yet To Be Approved'
            AND e.organisationID = ?
            AND (l.fromDate <= ? AND l.toDate >= ?)
            ORDER BY l.createdOn DESC";

            $stmt = mysqli_prepare($connect_var, $query);
            mysqli_stmt_bind_param($stmt, 'sss', $this->organisationID, $this->today, $this->today);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            $pendingLeaveRequests = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $pendingLeaveRequests[] = $row;
            }

            mysqli_stmt_close($stmt);

            // Generate Excel file
            $this->generateExcelFile($pendingLeaveRequests);

        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }

    private function generateExcelFile($data) {
        // Set headers for Excel download
        $filename = "PendingLeaveRequests_" . date('Y-m-d_H-i-s') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // Check if PhpSpreadsheet is available
        if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            $this->generatePhpSpreadsheet($data);
        } else {
            // Fallback to CSV format
            $this->generateCSV($data);
        }
    }

    private function generatePhpSpreadsheet($data) {
        // Create new Spreadsheet object
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'Employee Name',
            'Employee ID',
            'Phone',
            'Designation',
            'Leave Type',
            'Location',
            'Manager Name',
            'Manager Phone',
            'Manager ID',
            'From Date',
            'To Date',
            'Duration',
            'Reason',
            'Created On'
        ];

        // Add headers to first row
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Style the header row
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
        ];
        $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);

        // Add data rows
        $row = 2;
        foreach ($data as $record) {
            $sheet->setCellValue('A' . $row, $record['employeeName']);
            $sheet->setCellValue('B' . $row, $record['empID']);
            $sheet->setCellValue('C' . $row, $record['employeePhone']);
            $sheet->setCellValue('D' . $row, $record['Designation']);
            $sheet->setCellValue('E' . $row, $record['typeOfLeave']);
            $sheet->setCellValue('F' . $row, $record['locationName']);
            $sheet->setCellValue('G' . $row, $record['managerName']);
            $sheet->setCellValue('H' . $row, $record['managerPhone']);
            $sheet->setCellValue('I' . $row, $record['managerID']);
            $sheet->setCellValue('J' . $row, $record['fromDate']);
            $sheet->setCellValue('K' . $row, $record['toDate']);
            $sheet->setCellValue('L' . $row, $record['leaveDuration']);
            $sheet->setCellValue('M' . $row, $record['reason']);
            $sheet->setCellValue('N' . $row, $record['createdOn']);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create Excel file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function generateCSV($data) {
        // Change headers for CSV
        $filename = "PendingLeaveRequests_" . date('Y-m-d_H-i-s') . ".csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // Create output stream
        $output = fopen('php://output', 'w');

        // Add UTF-8 BOM for proper Excel encoding
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Headers
        $headers = [
            'Employee Name',
            'Employee ID',
            'Phone',
            'Designation',
            'Leave Type',
            'Location',
            'Manager Name',
            'Manager Phone',
            'Manager ID',
            'From Date',
            'To Date',
            'Duration',
            'Reason',
            'Created On'
        ];

        // Write headers
        fputcsv($output, $headers);

        // Write data rows
        foreach ($data as $record) {
            $row = [
                $record['employeeName'],
                $record['empID'],
                $record['employeePhone'],
                $record['Designation'],
                $record['typeOfLeave'],
                $record['locationName'],
                $record['managerName'],
                $record['managerPhone'],
                $record['managerID'],
                $record['fromDate'],
                $record['toDate'],
                $record['leaveDuration'],
                $record['reason'],
                $record['createdOn']
            ];
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
}
?> 