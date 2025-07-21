<?php

class KeyHandlingComponent {
    private $employeeID;
    private $employeeName;
    private $designation;
    private $branchName;
    private $branchAddress;

    public function __construct() {
        // Constructor
    }

    public function setEmployeeDetails($employeeID, $employeeName, $designation, $branchName, $branchAddress) {
        $this->employeeID = $employeeID;
        $this->employeeName = $employeeName;
        $this->designation = $designation;
        $this->branchName = $branchName;
        $this->branchAddress = $branchAddress;
    }

    public function submitKeyHandover($data) {
        include('config.inc');
        header('Content-Type: application/json');
        
        try {
            $employeeID = $data['employeeID'];
            $employeeName = $data['employeeName'];
            $designation = $data['designation'];
            $branchName = $data['branchName'];
            $branchAddress = $data['branchAddress'];
            $reason = $data['reason'];
            $handoverDate = $data['handoverDate'];
            $otp = $this->generateOTP();
            
            // Validate required fields
            if (empty($employeeID) || empty($employeeName) || 
                empty($branchName) || empty($reason) || empty($handoverDate)) {
                throw new Exception("Missing required fields");
            }

            // Set default designation if empty
            if (empty($designation)) {
                $designation = "Employee";
            }

            // Insert into database
            $sql = "INSERT INTO tblKeyHandling (employeeID, employeeName, designation, branchName, branchAddress, reason, handoverDate, otp, status, createdOn) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";
            
            $stmt = mysqli_prepare($connect_var, $sql);
            mysqli_stmt_bind_param($stmt, "ssssssss", 
                $employeeID, $employeeName, $designation, $branchName, $branchAddress, 
                $reason, $handoverDate, $otp);
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(array(
                    "status" => "success",
                    "message" => "Key handover request submitted successfully",
                    "otp" => $otp,
                    "handoverID" => mysqli_insert_id($connect_var)
                ));
            } else {
                throw new Exception("Failed to submit key handover request");
            }

            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);
            
        } catch (Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message" => $e->getMessage()
            ));
        }
    }

    private function generateOTP() {
        // Generate a 6-digit random OTP
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function getKeyHandoverHistory($employeeID) {
        include('config.inc');
        header('Content-Type: application/json');
        
        try {
            $sql = "SELECT * FROM tblKeyHandling WHERE employeeID = ? ORDER BY createdOn DESC";
            $stmt = mysqli_prepare($connect_var, $sql);
            mysqli_stmt_bind_param($stmt, "s", $employeeID);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $handovers = array();
            while ($row = mysqli_fetch_assoc($result)) {
                $handovers[] = array(
                    'handoverID' => $row['handoverID'],
                    'employeeID' => $row['employeeID'],
                    'employeeName' => $row['employeeName'],
                    'designation' => $row['designation'],
                    'branchName' => $row['branchName'],
                    'branchAddress' => $row['branchAddress'],
                    'reason' => $row['reason'],
                    'handoverDate' => $row['handoverDate'],
                    'otp' => $row['otp'],
                    'status' => $row['status'],
                    'createdOn' => $row['createdOn']
                );
            }
            
            echo json_encode(array(
                "status" => "success",
                "data" => $handovers
            ));
            
            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);
            
        } catch (Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message" => $e->getMessage()
            ));
        }
    }
} 