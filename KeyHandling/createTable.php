<?php
include('config.inc');

// Create tblKeyHandling table
$sql = "CREATE TABLE IF NOT EXISTS `tblKeyHandling` (
  `handoverID` int(11) NOT NULL AUTO_INCREMENT,
  `employeeID` varchar(50) NOT NULL,
  `employeeName` varchar(255) NOT NULL,
  `designation` varchar(255) NOT NULL,
  `branchName` varchar(255) NOT NULL,
  `branchAddress` text,
  `reason` text NOT NULL,
  `handoverDate` date NOT NULL,
  `otp` varchar(10) NOT NULL,
  `status` enum('Pending','Approved','Rejected','Completed') DEFAULT 'Pending',
  `createdOn` timestamp DEFAULT CURRENT_TIMESTAMP,
  `approvedBy` varchar(255) DEFAULT NULL,
  `approvedOn` timestamp NULL DEFAULT NULL,
  `rejectionReason` text DEFAULT NULL,
  PRIMARY KEY (`handoverID`),
  KEY `idx_employeeID` (`employeeID`),
  KEY `idx_status` (`status`),
  KEY `idx_createdOn` (`createdOn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($connect_var, $sql)) {
    echo "Table tblKeyHandling created successfully\n";
} else {
    echo "Error creating table: " . mysqli_error($connect_var) . "\n";
}

mysqli_close($connect_var);
?> 