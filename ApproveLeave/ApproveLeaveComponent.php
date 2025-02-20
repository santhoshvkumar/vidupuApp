<?php

class ApproveLeaveMaster {
    public $applyLeaveID;
    public $fromDate;
    public $toDate;
    public $typeOfLeave;
    public $numberOfDays;
    public $createdOn;
    public $reason;
    public $status;
    public $employeeID;
    public $managerID;

    /**
     * Set manager ID for leave approval
     * @param string $managerID
     * @return bool
     */
    public function setManagerID($managerID) {
        $this->managerID = $managerID;
        return true;
    }

    public function loadLeaveforApproval($decoded_items) {
        $this->managerID = $decoded_items['managerID'] ?? '5'; // Default to '5' if not provided
        return true;
    }

    public function getLeaveforApprovalInfo() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            if (!$connect_var) {
                throw new Exception("Database connection failed");
            }

            $queryLeaveApproval = "SELECT 
                tblE.employeeID,
                tblE.employeeName,
                tblL.applyLeaveID,
                tblL.fromDate,
                tblL.toDate,
                tblL.typeOfLeave,
                tblL.reason,
                tblL.createdOn,
                tblL.status
            FROM 
                tblEmployee tblE
            INNER JOIN 
                tblApplyLeave tblL ON tblE.employeeID = tblL.employeeID
            WHERE 
                tblE.managerID = '" . mysqli_real_escape_string($connect_var, $this->managerID) . "'  
                AND tblL.status = 'Yet To Be Approved'";
    
            $rsd = mysqli_query($connect_var, $queryLeaveApproval);
            
            if (!$rsd) {
                throw new Exception(mysqli_error($connect_var));
            }
    
            $resultArr = array();
            $count = 0;
            
            while($rs = mysqli_fetch_assoc($rsd)) {
                $resultArr[] = $rs;
                $count++;
            }
            
            mysqli_close($connect_var);
    
            if($count > 0) {
                echo json_encode(array(
                    "status" => "success",
                    "result" => $resultArr,
                    "record_count" => $count
                ));
            } else {
                echo json_encode(array(
                    "status" => "failure",
                    "record_count" => 0,
                    "message_text" => "No leaves pending for approval"
                ), JSON_FORCE_OBJECT);
            }
    
        } catch(Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message_text" => $e->getMessage()
            ), JSON_FORCE_OBJECT);
        }
    }
}

function getLeaveforApproval($decoded_items) {
    $leaveObject = new ApproveLeaveMaster();
    $leaveObject->loadLeaveforApproval($decoded_items);
    $leaveObject->getLeaveforApprovalInfo();
}
