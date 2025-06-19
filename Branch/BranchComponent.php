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

    public function loadBranchDetails(array $data) {
        // Check if required fields exist
        if (isset($data['branchName']) && isset($data['branchHeadID']) && 
            isset($data['branchAddress']) && isset($data['organisationID'])) {
            
            $this->branchName = $data['branchName'];
            $this->branchHeadID = intval($data['branchHeadID']);
            $this->branchAddress = $data['branchAddress'];
            $this->organisationID = intval($data['organisationID']);
            
            // Optional fields
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

    public function CreateBranch() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
    
            $queryCreateBranch = "INSERT INTO tblBranch (
                branchUniqueID, branchName, branchHeadID, branchAddress, 
                branchLatitude, branchLongitude, branchRadius, organisationID
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($connect_var, $queryCreateBranch);
            if (!$stmt) {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Database prepare statement failed"
                ));
                return;
            }
            
            mysqli_stmt_bind_param($stmt, "ssissssi",
                $this->branchUniqueID,
                $this->branchName,
                $this->branchHeadID,
                $this->branchAddress,
                $this->branchLatitude,
                $this->branchLongitude,
                $this->branchRadius,
                $this->organisationID
            );

            if (mysqli_stmt_execute($stmt)) {
                $latestBranchCreatedID = mysqli_insert_id($connect_var);
                
                echo json_encode(array(
                    "status" => "success",
                    "message" => "Branch created successfully",
                    "branchID" => $latestBranchCreatedID
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error creating branch: " . mysqli_stmt_error($stmt)
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

    public function UpdateBranch() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
    
            $queryUpdateBranch = "UPDATE tblBranch SET 
                branchUniqueID = ?,
                branchName = ?,
                branchHeadID = ?,
                branchAddress = ?,
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
            
            mysqli_stmt_bind_param($stmt, "ssissssii",
                $this->branchUniqueID,
                $this->branchName,
                $this->branchHeadID,
                $this->branchAddress,
                $this->branchLatitude,
                $this->branchLongitude,
                $this->branchRadius,
                $this->organisationID,
                $this->branchID
            );

            if (mysqli_stmt_execute($stmt)) {
                $affectedRows = mysqli_stmt_affected_rows($stmt);
                echo json_encode(array(
                    "status" => "success",
                    "message" => "Branch updated successfully",
                    "affected_rows" => $affectedRows
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error updating branch: " . mysqli_stmt_error($stmt)
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

    public function GetBranch() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
    
            $queryGetBranch = "SELECT * FROM tblBranch WHERE branchID = ?";
            $stmt = mysqli_prepare($connect_var, $queryGetBranch);
            mysqli_stmt_bind_param($stmt, "i", $this->branchID);

            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);
                
                if ($row) {
                    echo json_encode(array(
                        "status" => "success",
                        "data" => $row
                    ));
                } else {
                    echo json_encode(array(
                        "status" => "error",
                        "message" => "Branch not found"
                    ));
                }
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error fetching branch"
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

    public function GetAllBranches() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
            
            // Debug: Check if database connection is working
            if (!$connect_var) {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Database connection failed"
                ));
                return;
            }
    
            $queryGetAllBranches = "SELECT * FROM tblBranch ORDER BY branchID DESC";
            
            $result = mysqli_query($connect_var, $queryGetAllBranches);
            
            if ($result) {
                $branches = array();
                $count = 0;
                while ($row = mysqli_fetch_assoc($result)) {
                    $branches[] = $row;
                    $count++;
                }
                
                echo json_encode(array(
                    "status" => "success",
                    "data" => $branches,
                    "count" => count($branches)
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error fetching branches",
                    "mysql_error" => mysqli_error($connect_var)
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

    public function GetBranchesByOrganisation() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
    
            $queryGetBranchesByOrg = "SELECT * FROM tblBranch WHERE organisationID = ? ORDER BY branchID DESC";
            $stmt = mysqli_prepare($connect_var, $queryGetBranchesByOrg);
            mysqli_stmt_bind_param($stmt, "i", $this->organisationID);

            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                $branches = array();
                while ($row = mysqli_fetch_assoc($result)) {
                    $branches[] = $row;
                }
                
                echo json_encode(array(
                    "status" => "success",
                    "data" => $branches,
                    "count" => count($branches)
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
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
        // For FormData, use $_POST instead of decoded JSON
        $BranchObject = new BranchComponent();
        if ($BranchObject->loadBranchDetails($_POST)) {
            $BranchObject->CreateBranch();
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    } else {
        // For JSON requests, use the decoded items
        $BranchObject = new BranchComponent();
        if ($BranchObject->loadBranchDetails($decoded_items)) {
            $BranchObject->CreateBranch();
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    }
}

function UpdateBranch($decoded_items) {
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
        $BranchObject = new BranchComponent();
        if ($BranchObject->loadBranchDetails($_POST)) {
            // Set the branchID from FormData
            if (isset($_POST['branchID'])) {
                $BranchObject->branchID = $_POST['branchID'];
            }
            $BranchObject->UpdateBranch();
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    } else {
        // For JSON requests, use the decoded items
        $BranchObject = new BranchComponent();
        if ($BranchObject->loadBranchDetails($decoded_items)) {
            $BranchObject->UpdateBranch();
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    }
}

function GetBranch($decoded_items) {
    $BranchObject = new BranchComponent();
    if (isset($decoded_items['branchID'])) {
        $BranchObject->branchID = $decoded_items['branchID'];
        $BranchObject->GetBranch();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}

function GetAllBranches() {
    $BranchObject = new BranchComponent();
    $BranchObject->GetAllBranches();
}

function GetBranchesByOrganisation($decoded_items) {
    $BranchObject = new BranchComponent();
    if (isset($decoded_items['organisationID'])) {
        $BranchObject->organisationID = $decoded_items['organisationID'];
        $BranchObject->GetBranchesByOrganisation();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
?>
