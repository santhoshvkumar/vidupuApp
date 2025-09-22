<?php

class AutoTransferComponent{
    public $dataOfTransfer;
    public function loadAutoTransfer(array $data){
        if (isset($data['dataOfTransfer'])) {
            $this->dataOfTransfer = $data['dataOfTransfer'];
            return true;
        }
        return false;
    }
    public function autoTransferProcess() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
                    $data = [];
                    $systemDate = $this->dataOfTransfer;
                    $transfersProcessed = 0;
                    $allProcessedTransfers = [];
                // First process returns for expired transfers from previous day
                $queryExpiredTransfers = "SELECT th.transferHistoryID, th.fromBranch, th.employeeID 
                FROM tblTransferHistory th
                WHERE DATE(?) > DATE(th.toDate)
                AND th.isActive = 1 
                AND th.transferType != 'Permanent'";
                $stmtExpired = mysqli_prepare($connect_var, $queryExpiredTransfers);
                mysqli_stmt_bind_param($stmtExpired, "s", $systemDate);
                mysqli_stmt_execute($stmtExpired);
                $resultExpired = mysqli_stmt_get_result($stmtExpired);
                $expiredTransfers = mysqli_fetch_all($resultExpired, MYSQLI_ASSOC);
                mysqli_stmt_close($stmtExpired);

                // Process returns
                foreach ($expiredTransfers as $transfer) {
                    // Return employee to original branch
                    $updateMapEmp = "UPDATE tblmapEmp 
                        SET branchID = ?, transferHistoryID = NULL 
                        WHERE employeeID = ?";
                    $stmtUpdateMap = mysqli_prepare($connect_var, $updateMapEmp);
                    mysqli_stmt_bind_param($stmtUpdateMap, "ss", 
                        $transfer['fromBranch'], 
                        $transfer['employeeID']
                    );
                    mysqli_stmt_execute($stmtUpdateMap);
                    mysqli_stmt_close($stmtUpdateMap);

                    // Deactivate the transfer
                    $updateTransferHistory = "UPDATE tblTransferHistory 
                                                SET isActive = 0 
                                                WHERE transferHistoryID = ?";
                    $stmtUpdateHistory = mysqli_prepare($connect_var, $updateTransferHistory);
                    mysqli_stmt_bind_param($stmtUpdateHistory, "s", $transfer['transferHistoryID']);
                    mysqli_stmt_execute($stmtUpdateHistory);
                    mysqli_stmt_close($stmtUpdateHistory);

                    $transfer['status'] = 'returned';
                    $allProcessedTransfers[] = $transfer;
                    $transfersProcessed++;
                }

                // Then process new/active transfers for current date
                // Handle both permanent transfers (toDate IS NULL) and temporary transfers
                $queryActiveTransfers = "SELECT transferHistoryID, fromBranch, toBranch, employeeID, toDate, transferType 
                    FROM tblTransferHistory 
                    WHERE DATE(fromDate) = DATE(?) 
                    AND isActive = 1 ";
                $stmtActive = mysqli_prepare($connect_var, $queryActiveTransfers);
                mysqli_stmt_bind_param($stmtActive, "s", $systemDate);
                mysqli_stmt_execute($stmtActive);
                $resultActive = mysqli_stmt_get_result($stmtActive);
                $activeTransfers = mysqli_fetch_all($resultActive, MYSQLI_ASSOC);
                mysqli_stmt_close($stmtActive);

                foreach ($activeTransfers as $transfer) {
                    // Update employee branch
                    $updateQuery = "UPDATE tblmapEmp 
                        SET branchID = ?, transferHistoryID = ? 
                        WHERE employeeID = ?";
                    $updateStmt = mysqli_prepare($connect_var, $updateQuery);
                    mysqli_stmt_bind_param($updateStmt, "sss",
                        $transfer['toBranch'],
                        $transfer['transferHistoryID'],
                        $transfer['employeeID']
                    );
                    mysqli_stmt_execute($updateStmt);
                    mysqli_stmt_close($updateStmt);

                    // If permanent transfer, deactivate it
                    if ($transfer['transferType'] === 'Permanent') {
                        // For permanent transfers, we don't deactivate immediately
                        // They remain active until the employee gets another transfer
                        // or until manually deactivated
                    }

                    $transfer['status'] = 'transferred';
                    $allProcessedTransfers[] = $transfer;
                    $transfersProcessed++;
                }

                if ($transfersProcessed > 0) {
                    echo json_encode(array(
                        "status" => "success",
                        "message" => "Successfully processed " . $transfersProcessed . " transfers",
                        "data" => $allProcessedTransfers
                    ));
                } else {
                    echo json_encode(array(
                        "status" => "success",
                        "message" => "No transfers to process for the given date",
                        "data" => []
                    ));
                }
                mysqli_close($connect_var);
            } catch (Exception $e) {
                echo json_encode([
                    "status" => "error", 
                    "message_text" => $e->getMessage()
                ], JSON_FORCE_OBJECT);
            }
    }
}

function AutoTransfer($decoded_items) {
    try {
        $transferObject = new AutoTransferComponent();
        if($transferObject->loadAutoTransfer($decoded_items)){
            $transferObject->autoTransferProcess();
        }
        else{
            echo json_encode(array("status"=>"error","message_text"=>"Invalid Input Parameters"),JSON_FORCE_OBJECT);
        }
    } catch(Exception $e) {
        echo json_encode(array(
            "status" => "error",
            "message_text" => "Failed to process auto checkout: " . $e->getMessage()
        ), JSON_FORCE_OBJECT);
    }
}

?>