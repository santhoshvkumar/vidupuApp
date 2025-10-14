<?php
class BranchComponent {
    public $branchID;
    public $branchUniqueID;
    public $branchName;
    public $branchHeadID;
    public $branchAddress;
    public $branchLatitude;
    public $branchLongitude;
    public $branchRadius;
    public $organisationID;
    public $checkInTime;
    public $checkOutTime;
    public $BranchCreateData;
    public $BranchDetailsData;
    public $BranchUpdateData;

    public function loadBranchDetails(array $data) {
        // Check if required fields exist
        if (isset($data['branchName']) && isset($data['branchHeadID']) && 
            isset($data['branchAddress']) && isset($data['organisationID'])) {
            
            $this->branchName = $data['branchName'];
            $this->branchHeadID = intval($data['branchHeadID']);
            $this->branchAddress = $data['branchAddress'];
            $this->organisationID = intval($data['organisationID']);
            
            // Optional fields
            $this->checkInTime = isset($data['checkInTime']) ? $data['checkInTime'] : '';
            $this->checkOutTime = isset($data['checkOutTime']) ? $data['checkOutTime'] : '';
            $this->branchUniqueID = isset($data['branchUniqueID']) ? $data['branchUniqueID'] : '';
            $this->branchLatitude = isset($data['branchLatitude']) ? $data['branchLatitude'] : '';
            $this->branchLongitude = isset($data['branchLongitude']) ? $data['branchLongitude'] : '';
            $this->branchRadius = isset($data['branchRadius']) ? intval($data['branchRadius']) : 0;
            
            // Set branchID if provided (for updates)
            if (isset($data['branchID'])) {
                $this->branchID = intval($data['branchID']);
            }
            
            return true;
        } else {
            return false;
        }
    }
    public function loadBranchDetailsByBranchID(array $data) {
        $this->branchID = intval($data['branchID']);
        $this->organisationID = intval($data['organisationID']);
        return true;
    }

    public function CreateBranch() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
            //Decode Token Start
            $secratekey = "CreateNewBranchFromWeb";
            $decodeVal = decryptDataFunc($this->BranchCreateData['BranchCreateToken'], $secratekey);
            $latestBranchCreatedID = 0;
            // DECODE Token End
            $queryCreateBranch = "INSERT INTO tblBranch (
                branchUniqueID, branchName, branchHeadID, branchAddress, checkInTime, checkOutTime,
                branchLatitude, branchLongitude, branchRadius, organisationID
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($connect_var, $queryCreateBranch);
            if (!$stmt) {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Database prepare statement failed"
                ));
                return;
            }
            
            mysqli_stmt_bind_param($stmt, "ssissssssi",
                $decodeVal->branchUniqueID,
                $decodeVal->branchName,
                $decodeVal->branchHeadID,
                $decodeVal->branchAddress,
                $decodeVal->checkInTime,
                $decodeVal->checkOutTime,
                $decodeVal->branchLatitude,
                $decodeVal->branchLongitude,
                $decodeVal->branchRadius,
                $decodeVal->organisationID
            );

            if (mysqli_stmt_execute($stmt)) {
                $latestBranchCreatedID = mysqli_insert_id($connect_var);
                $responseStatus = "success";
                $responseMessage = "Branch created successfully";
                $latestBranchCreatedID = mysqli_insert_id($connect_var);
            } else {
                $responseStatus = "error";
                $responseMessage = "Error creating branch: " . mysqli_stmt_error($stmt);
                $latestBranchCreatedID = 0;
            }
            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);
            //Encode Token Start
            $payload_info = array(
                "message"=> $responseMessage,
                "branchID" => $latestBranchCreatedID,
                "status" => $responseStatus
            );
            $encodeToken = encryptDataFunc($payload_info, $secratekey);
            //Encode Token End
            echo json_encode(array("status"=>$responseStatus, "response"=>$encodeToken),JSON_FORCE_OBJECT);
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error", 
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }

    public function UpdateBranch() {
        include('config.inc');
        header('Content-Type: application/json');
        
        //Decode Token Start
        $secratekey = "UpdateParticularBranchByID";
        $decodeVal = decryptDataFunc($this->BranchUpdateData['BranchUpdateToken'], $secratekey);
        // DECODE Token End
        try {
            $data = [];
            $affectedRows = 0;
            $queryUpdateBranch = "UPDATE tblBranch SET 
                branchUniqueID = ?,
                branchName = ?,
                branchHeadID = ?,
                branchAddress = ?,
                checkInTime = ?,
                checkOutTime = ?,
                branchLatitude = ?,
                branchLongitude = ?,
                branchRadius = ?,
                organisationID = ?
                WHERE branchID = ?";

            $stmt = mysqli_prepare($connect_var, $queryUpdateBranch);
            if (!$stmt) {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Database prepare statement failed"
                ));
                return;
            }
            
            mysqli_stmt_bind_param($stmt, "ssissssssii",
                $decodeVal->branchUniqueID,
                $decodeVal->branchName,
                $decodeVal->branchHeadID,
                $decodeVal->branchAddress,
                $decodeVal->checkInTime,
                $decodeVal->checkOutTime,
                $decodeVal->branchLatitude,
                $decodeVal->branchLongitude,
                $decodeVal->branchRadius,
                $decodeVal->organisationID,
                $decodeVal->branchID
            );



            if (mysqli_stmt_execute($stmt)) {
                $affectedRows = mysqli_stmt_affected_rows($stmt);
                $responseStatus = "success";
                $responseMessage = "Branch updated successfully";
                
            } else {
               $responseStatus = "error";
               $responseMessage = "Error updating branch: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);
            //Encode Token Start
            $payload_info = array(
                "message"=> $responseMessage,
                "affected_rows" => $affectedRows,
                "status" => $responseStatus
            );
            $encodeToken = encryptDataFunc($payload_info, $secratekey);
            //Encode Token End
            echo json_encode(array("status"=>$responseStatus, "response"=>$encodeToken),JSON_FORCE_OBJECT);
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error", 
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }

    public function GetBranchesByOrganisation() {
        include('config.inc');
        header('Content-Type: application/json');
         //Decode Token Start
         $secratekey = "GetAllBranchByOrganisationIDWeb";
         $decodeVal = decryptDataFunc($this->BranchDetailsData['BranchDetailsToken'], $secratekey);
         // DECODE Token End
        try {
            $data = [];
    
            $queryGetBranchesByOrg = "SELECT * FROM tblBranch WHERE organisationID = ? ORDER BY branchID DESC";
            $stmt = mysqli_prepare($connect_var, $queryGetBranchesByOrg);
            mysqli_stmt_bind_param($stmt, "i", $decodeVal->organisationID);
            $resonseCount = 0;
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                $branches = array();
                while ($row = mysqli_fetch_assoc($result)) {
                    $branches[] = $row;
                }
                $responseStatus = "success";
                $responseMessage = "Branches fetched successfully";
                $resonseCount = count($branches);
                
               
            } else {
                $responseStatus = "error";
                $responseMessage = "Error fetching branches by organisation";
            }
            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);
            //Encode Token Start
            $payload_info = array(
                "data"=>$branches,
                "message"=> $responseMessage,
                "count" => $resonseCount,
                "status" => $responseStatus
            );
            $encodeToken = encryptDataFunc($payload_info, $secratekey);
            //Encode Token End
            echo json_encode(array("status"=>$responseStatus, "response"=>$encodeToken),JSON_FORCE_OBJECT);
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error", 
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
    public function GetBranchDetailsByBranchID() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
    
            $queryGetBranchDetailsByBranchID = "SELECT * FROM tblBranch WHERE branchID = ? and organisationID = ?";
            $stmt = mysqli_prepare($connect_var, $queryGetBranchDetailsByBranchID);
            mysqli_stmt_bind_param($stmt, "ii", $this->branchID, $this->organisationID);

            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                $branchDetails = array();
                while ($row = mysqli_fetch_assoc($result)) {
                    $branchDetails[] = $row;
                }
                
                echo json_encode(array(
                    "status" => "success",
                    "data" => $branchDetails,
                    "count" => count($branchDetails)
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error fetching branches by organisation"
                ));
            }
            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error", 
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
}

// Helper functions to create instances and call methods
function CreateBranch($decoded_items) {
        $BranchObject = new BranchComponent();
        if ($decoded_items) {
            $BranchObject->BranchCreateData = $decoded_items;
            $BranchObject->CreateBranch();
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    
}

function UpdateBranch($decoded_items) {
    // For JSON requests, use the decoded items
    $BranchObject = new BranchComponent();
    if ($decoded_items) {
        $BranchObject->BranchUpdateData = $decoded_items;
        $BranchObject->UpdateBranch();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}

function GetBranchDetailsByBranchID($decoded_items) {
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
        $BranchObject = new BranchComponent();
        if ($BranchObject->loadBranchDetailsByBranchID($_POST)) {
            $BranchObject->GetBranchDetailsByBranchID();
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    } else {
        // For JSON requests, use the decoded items
        $BranchObject = new BranchComponent();
        if ($BranchObject->loadBranchDetailsByBranchID($decoded_items)) {
            $BranchObject->GetBranchDetailsByBranchID();
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    }
}

function GetBranchesByOrganisation($decoded_items) {
    $BranchObject = new BranchComponent();
    if ($decoded_items) {
        $BranchObject->BranchDetailsData = $decoded_items;
        $BranchObject->GetBranchesByOrganisation();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
?>
