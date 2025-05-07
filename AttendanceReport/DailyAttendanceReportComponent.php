<?php

class DailyAttendanceReport {
    private $currentDate;
    private $reportDirectory = 'reports/';
    
    public function __construct() {
        $this->currentDate = date('Y-m-d');
        // Create reports directory if it doesn't exist
        if (!file_exists($this->reportDirectory)) {
            mkdir($this->reportDirectory, 0777, true);
        }
    }
    
    public function generateDailyReport() {
        include('config.inc');
        header('Content-Type: application/json');
        
        try {
            // Get all employees with their attendance and leave status
            $query = "SELECT 
                        e.employeeID,
                        e.employeeName,
                        e.employeePhone,
                        CASE 
                            WHEN l.employeeID IS NOT NULL THEN 'On Leave'
                            WHEN a.employeeID IS NOT NULL THEN 'Present'
                            ELSE 'Absent'
                        END as status,
                        TIME_FORMAT(a.checkInTime, '%H:%i') as checkInTime,
                        TIME_FORMAT(a.checkOutTime, '%H:%i') as checkOutTime,
                        CASE 
                            WHEN TIME(a.checkInTime) > '10:00:00' THEN 'Yes'
                            ELSE 'No'
                        END as isLateCheckIn,
                        CASE 
                            WHEN TIME(a.checkOutTime) < '17:00:00' THEN 'Yes'
                            ELSE 'No'
                        END as isEarlyCheckOut,
                        l.typeOfLeave,
                        l.reason as leaveReason
                    FROM tblEmployee e
                    LEFT JOIN tblAttendance a ON e.employeeID = a.employeeID 
                        AND DATE(a.checkInTime) = CURDATE()
                    LEFT JOIN tblApplyLeave l ON e.employeeID = l.employeeID 
                        AND CURDATE() BETWEEN l.fromDate AND l.toDate 
                        AND l.status = 'Approved'
                    WHERE e.isActive = 1
                    ORDER BY e.employeeID";
            
            $result = mysqli_query($connect_var, $query);
            
            if (!$result) {
                throw new Exception("Error executing query: " . mysqli_error($connect_var));
            }

            // Get summary counts
            $lateCheckInQuery = "SELECT COUNT(*) as count 
                               FROM tblAttendance 
                               WHERE DATE(checkInTime) = CURDATE() 
                               AND TIME(checkInTime) > '10:00:00'";
            $lateCheckInResult = mysqli_query($connect_var, $lateCheckInQuery);
            $lateCheckInCount = mysqli_fetch_assoc($lateCheckInResult)['count'];

            $earlyCheckOutQuery = "SELECT COUNT(*) as count 
                                 FROM tblAttendance 
                                 WHERE DATE(checkInTime) = CURDATE() 
                                 AND TIME(checkOutTime) < '17:00:00'";
            $earlyCheckOutResult = mysqli_query($connect_var, $earlyCheckOutQuery);
            $earlyCheckOutCount = mysqli_fetch_assoc($earlyCheckOutResult)['count'];

            $absentQuery = "SELECT COUNT(*) as count 
                          FROM tblEmployee e 
                          LEFT JOIN tblAttendance a ON e.employeeID = a.employeeID 
                              AND DATE(a.checkInTime) = CURDATE()
                          LEFT JOIN tblApplyLeave l ON e.employeeID = l.employeeID 
                              AND CURDATE() BETWEEN l.fromDate AND l.toDate 
                              AND l.status = 'Approved'
                          WHERE e.isActive = 1 
                          AND a.employeeID IS NULL 
                          AND l.employeeID IS NULL";
            $absentResult = mysqli_query($connect_var, $absentQuery);
            $absentCount = mysqli_fetch_assoc($absentResult)['count'];

            $onLeaveQuery = "SELECT COUNT(*) as count 
                           FROM tblApplyLeave 
                           WHERE CURDATE() BETWEEN fromDate AND toDate 
                           AND status = 'Approved'";
            $onLeaveResult = mysqli_query($connect_var, $onLeaveQuery);
            $onLeaveCount = mysqli_fetch_assoc($onLeaveResult)['count'];
            
            // Create filename 
            $filename = 'daily_attendance_report_' . $this->currentDate . '_' . date('His') . '.csv';
            $filepath = $this->reportDirectory . $filename;
            
            // Create CSV file
            $output = fopen($filepath, 'w');
            
            // Add headers
            fputcsv($output, array(
                'Employee ID',
                'Employee Name',
                'Phone Number',
                'Status',
                'Check In Time',
                'Check Out Time',
                'Late Check In',
                'Early Check Out',
                'Leave Type',
                'Leave Reason'
            ));
            
            while ($row = mysqli_fetch_assoc($result)) {
                // Write row to CSV
                fputcsv($output, array(
                    $row['employeeID'],
                    $row['employeeName'],
                    $row['employeePhone'],
                    $row['status'],
                    $row['checkInTime'],
                    $row['checkOutTime'],
                    $row['isLateCheckIn'],
                    $row['isEarlyCheckOut'],
                    $row['typeOfLeave'],
                    $row['leaveReason']
                ));
            }
            
            // Add summary at the end of the file
            fputcsv($output, array(''));
            fputcsv($output, array('Summary'));
            fputcsv($output, array('Late Check-ins', $lateCheckInCount));
            fputcsv($output, array('Early Check-outs', $earlyCheckOutCount));
            fputcsv($output, array('Absent', $absentCount));
            fputcsv($output, array('On Leave', $onLeaveCount));
            
            fclose($output);
            mysqli_close($connect_var);
            
            echo json_encode(array(
                "status" => "success",
                "message_text" => "Report generated successfully",
                "data" => array(
                    "filename" => $filename,
                    "filepath" => $filepath,
                    "download_url" => "downloadReport.php?file=" . urlencode($filename)
                )
            ), JSON_FORCE_OBJECT);
            
        } catch(Exception $e) {
            error_log("Error in generateDailyReport: " . $e->getMessage());
            echo json_encode(array(
                "status" => "error",
                "message_text" => "Error generating report: " . $e->getMessage()
            ), JSON_FORCE_OBJECT);
        }
    }
}

function generateDailyReport() {
    try {
        $report = new DailyAttendanceReport();
        $report->generateDailyReport();
    } catch(Exception $e) {
        echo json_encode(array(
            "status" => "error",
            "message_text" => "Failed to generate report: " . $e->getMessage()
        ), JSON_FORCE_OBJECT);
    }
}

?> 