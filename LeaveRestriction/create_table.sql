-- Drop table if exists and recreate with proper AUTO_INCREMENT
DROP TABLE IF EXISTS `tblRestrictLeave`;

-- Create tblRestrictLeave table with proper AUTO_INCREMENT
CREATE TABLE `tblRestrictLeave` (
  `restrictLeaveID` int NOT NULL AUTO_INCREMENT,
  `restrictLeaveDate` date NOT NULL,
  `Reason` varchar(255) DEFAULT NULL,
  `createdOn` datetime DEFAULT CURRENT_TIMESTAMP,
  `createdBy` int DEFAULT NULL,
  `isPublish` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`restrictLeaveID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Insert sample data (optional)
-- INSERT INTO `tblRestrictLeave` (`restrictLeaveDate`, `Reason`, `createdBy`, `isPublish`) 
-- VALUES ('2025-01-01', 'New Year Holiday', 1, 1); 