<?php
class OrganisationComponent {
    public $organisationID;
    public $organisationName;
    public $organisationLogo;
    public $website;
    public $emailID;
    public $createdOn;
    public $createdBy;
    public $contactPerson1Name;
    public $contactPerson1Email;
    public $contactPerson1Phone;
    public $contactPerson2Name;
    public $contactPerson2Email;
    public $contactPerson2Phone;
    public $isActive;

    public function loadOrganisationDetails(array $data) {
        if (isset($data['organisationName']) && isset($data['organisationLogo']) && 
            isset($data['website']) && isset($data['emailID']) && 
            isset($data['contactPerson1Name']) && isset($data['contactPerson1Email']) && 
            isset($data['contactPerson1Phone']) && isset($data['contactPerson2Name']) && 
            isset($data['contactPerson2Email']) && isset($data['contactPerson2Phone'])) {
            
            $this->organisationName = $data['organisationName'];
            $this->organisationLogo = $data['organisationLogo'];
            $this->website = $data['website'];
            $this->emailID = $data['emailID'];
            $this->contactPerson1Name = $data['contactPerson1Name'];
            $this->contactPerson1Email = $data['contactPerson1Email'];
            $this->contactPerson1Phone = $data['contactPerson1Phone'];
            $this->contactPerson2Name = $data['contactPerson2Name'];
            $this->contactPerson2Email = $data['contactPerson2Email'];
            $this->contactPerson2Phone = $data['contactPerson2Phone'];
            $this->isActive = isset($data['isActive']) ? $data['isActive'] : 1;
            return true;
        } else {
            return false;
        }
    }

    public function CreateOrganisation() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
    
            $queryCreateOrganisation = "INSERT INTO tblOrganisation (
                organisationName, organisationLogo, website, emailID, 
                createdOn, createdBy, contactPerson1Name, contactPerson1Email, 
                contactPerson1Phone, contactPerson2Name, contactPerson2Email, 
                contactPerson2Phone, isActive
            ) VALUES (?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($connect_var, $queryCreateOrganisation);
            mysqli_stmt_bind_param($stmt, "ssssssssssss",
                $this->organisationName,
                $this->organisationLogo,
                $this->website,
                $this->emailID,
                $this->createdBy,
                $this->contactPerson1Name,
                $this->contactPerson1Email,
                $this->contactPerson1Phone,
                $this->contactPerson2Name,
                $this->contactPerson2Email,
                $this->contactPerson2Phone,
                $this->isActive
            );

            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(array(
                    "status" => "success",
                    "message" => "Organisation created successfully"
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error creating organisation"
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

    public function UpdateOrganisation() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
    
            $queryUpdateOrganisation = "UPDATE tblOrganisation SET 
                organisationName = ?,
                organisationLogo = ?,
                website = ?,
                emailID = ?,
                contactPerson1Name = ?,
                contactPerson1Email = ?,
                contactPerson1Phone = ?,
                contactPerson2Name = ?,
                contactPerson2Email = ?,
                contactPerson2Phone = ?,
                isActive = ?
                WHERE organisationID = ?";

            $stmt = mysqli_prepare($connect_var, $queryUpdateOrganisation);
            mysqli_stmt_bind_param($stmt, "ssssssssssss",
                $this->organisationName,
                $this->organisationLogo,
                $this->website,
                $this->emailID,
                $this->contactPerson1Name,
                $this->contactPerson1Email,
                $this->contactPerson1Phone,
                $this->contactPerson2Name,
                $this->contactPerson2Email,
                $this->contactPerson2Phone,
                $this->isActive,
                $this->organisationID
            );

            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(array(
                    "status" => "success",
                    "message" => "Organisation updated successfully"
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error updating organisation"
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

    public function GetOrganisation() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
    
            $queryGetOrganisation = "SELECT * FROM tblOrganisation WHERE organisationID = ? AND isActive = ?";
            $stmt = mysqli_prepare($connect_var, $queryGetOrganisation);
            mysqli_stmt_bind_param($stmt, "si", 
                $this->organisationID,
                $this->isActive
            );

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
                        "message" => "Organisation not found"
                    ));
                }
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error fetching organisation"
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

    public function UpdateOrganisationStatus() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
    
            $queryUpdateStatus = "UPDATE tblOrganisation SET isActive = ? WHERE organisationID = ?";
            $stmt = mysqli_prepare($connect_var, $queryUpdateStatus);
            mysqli_stmt_bind_param($stmt, "is", 
                $this->isActive,
                $this->organisationID
            );

            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(array(
                    "status" => "success",
                    "message" => "Organisation status updated successfully"
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error updating organisation status"
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

    public function GetAllOrganisations() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
    
            $queryGetAllOrganisations = "SELECT * FROM tblOrganisation WHERE isActive = 1 ORDER BY organisationName ASC";
            $result = mysqli_query($connect_var, $queryGetAllOrganisations);
            
            if ($result) {
                $organisations = array();
                while ($row = mysqli_fetch_assoc($result)) {
                    $organisations[] = $row;
                }
                echo json_encode(array(
                    "status" => "success",
                    "data" => $organisations
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error fetching organisations"
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

function CreateOrganisation($decoded_items) {
    $OrganisationObject = new OrganisationComponent();
    if ($OrganisationObject->loadOrganisationDetails($decoded_items)) {
        $OrganisationObject->CreateOrganisation();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}

function UpdateOrganisation($decoded_items) {
    $OrganisationObject = new OrganisationComponent();
    if ($OrganisationObject->loadOrganisationDetails($decoded_items)) {
        $OrganisationObject->UpdateOrganisation();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}

function GetOrganisation($decoded_items) {
    $OrganisationObject = new OrganisationComponent();
    if (isset($decoded_items['organisationID']) && isset($decoded_items['isActive'])) {
        $OrganisationObject->organisationID = $decoded_items['organisationID'];
        $OrganisationObject->isActive = $decoded_items['isActive'];
        $OrganisationObject->GetOrganisation();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}

function UpdateOrganisationStatus($decoded_items) {
    $OrganisationObject = new OrganisationComponent();
    if (isset($decoded_items['organisationID']) && isset($decoded_items['isActive'])) {
        $OrganisationObject->organisationID = $decoded_items['organisationID'];
        $OrganisationObject->isActive = $decoded_items['isActive'];
        $OrganisationObject->UpdateOrganisationStatus();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}

function GetAllOrganisations() {
    $OrganisationObject = new OrganisationComponent();
    $OrganisationObject->GetAllOrganisations();
}
?>
