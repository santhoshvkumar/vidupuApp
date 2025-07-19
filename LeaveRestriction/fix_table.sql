-- Fix existing table by adding AUTO_INCREMENT to restrictLeaveID
ALTER TABLE `tblRestrictLeave` MODIFY `restrictLeaveID` int NOT NULL AUTO_INCREMENT;

-- Verify the table structure
DESCRIBE `tblRestrictLeave`; 