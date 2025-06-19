<?php
class SectionComponent {
    public $sectionID;
    public $sectionName;
    public $sectionHeadID;
    public $organisationID;

    public function loadSectionDetails(array $data) {
        // Check if required fields exist
        if (isset($data['sectionName']) && isset($data['organisationID'])) {
            
            $this->sectionName = $data['sectionName'];
            $this->organisationID = intval($data['organisationID']);
            
            // Optional fields
            $this->sectionHeadID = isset($data['sectionHeadID']) ? intval($data['sectionHeadID']) : 0;
            
            // Set sectionID if provided (for updates)
            if (isset($data['sectionID'])) {
                $this->sectionID = intval($data['sectionID']);
            }
            
            return true;
        } else {
            return false;
        }
    }

    public function CreateSection() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
    
            $queryCreateSection = "INSERT INTO tblSection (
                SectionName, sectionHeadID, organisationID
            ) VALUES (?, ?, ?)";

            $stmt = mysqli_prepare($connect_var, $queryCreateSection);
            if (!$stmt) {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Database prepare statement failed"
                ));
                return;
            }
            
            mysqli_stmt_bind_param($stmt, "sii",
                $this->sectionName,
                $this->sectionHeadID,
                $this->organisationID
            );

            if (mysqli_stmt_execute($stmt)) {
                $latestSectionCreatedID = mysqli_insert_id($connect_var);
                
                echo json_encode(array(
                    "status" => "success",
                    "message" => "Section created successfully",
                    "sectionID" => $latestSectionCreatedID
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error creating section: " . mysqli_stmt_error($stmt)
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

    public function UpdateSection() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
    
            $queryUpdateSection = "UPDATE tblSection SET 
                SectionName = ?,
                sectionHeadID = ?,
                organisationID = ?
                WHERE sectionID = ?";

            $stmt = mysqli_prepare($connect_var, $queryUpdateSection);
            if (!$stmt) {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Database prepare statement failed"
                ));
                return;
            }
            
            mysqli_stmt_bind_param($stmt, "siii",
                $this->sectionName,
                $this->sectionHeadID,
                $this->organisationID,
                $this->sectionID
            );

            if (mysqli_stmt_execute($stmt)) {
                $affectedRows = mysqli_stmt_affected_rows($stmt);
                echo json_encode(array(
                    "status" => "success",
                    "message" => "Section updated successfully",
                    "affected_rows" => $affectedRows
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error updating section: " . mysqli_stmt_error($stmt)
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

    public function GetSectionsByOrganisation() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
    //ADDED SECTION HEAD NAME in the query
            $queryGetSectionsByOrg = "SELECT s.*, e.employeeName as sectionHeadName 
                                     FROM tblSection s 
                                     LEFT JOIN tblEmployee e ON s.sectionHeadID = e.employeeID 
                                     WHERE s.organisationID = ? 
                                     ORDER BY s.sectionID DESC";
            $stmt = mysqli_prepare($connect_var, $queryGetSectionsByOrg);
            mysqli_stmt_bind_param($stmt, "i", $this->organisationID);

            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                $sections = array();
                while ($row = mysqli_fetch_assoc($result)) {
                    $sections[] = $row;
                }
                
                echo json_encode(array(
                    "status" => "success",
                    "data" => $sections,
                    "count" => count($sections)
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error fetching sections by organisation"
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

function CreateSection($decoded_items) {
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
        // For FormData, use $_POST instead of decoded JSON
        $SectionObject = new SectionComponent();
        if ($SectionObject->loadSectionDetails($_POST)) {
            $SectionObject->CreateSection();
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    } else {
        // For JSON requests, use the decoded items
        $SectionObject = new SectionComponent();
        if ($SectionObject->loadSectionDetails($decoded_items)) {
            $SectionObject->CreateSection();
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    }
}

function UpdateSection($decoded_items) {
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
        $SectionObject = new SectionComponent();
        if ($SectionObject->loadSectionDetails($_POST)) {
            // Set the sectionID from FormData
            if (isset($_POST['sectionID'])) {
                $SectionObject->sectionID = $_POST['sectionID'];
            }
            $SectionObject->UpdateSection();
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    } else {
        // For JSON requests, use the decoded items
        $SectionObject = new SectionComponent();
        if ($SectionObject->loadSectionDetails($decoded_items)) {
            $SectionObject->UpdateSection();
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    }
}

function GetSectionsByOrganisation($decoded_items) {
    $SectionObject = new SectionComponent();
    if (isset($decoded_items['organisationID'])) {
        $SectionObject->organisationID = $decoded_items['organisationID'];
        $SectionObject->GetSectionsByOrganisation();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
?>
