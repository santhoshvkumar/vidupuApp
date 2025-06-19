<?php
class AddOrganizationComponent{
    public $organizationName;
    public $createdOn;
    public $organizationAddress;
    public $organizationPhone;
    public $organizationEmail;
    public $organizationWebsite;
    public $organizationLogo;
    public $isActive;
    public function loadAddOrganizationDetails(array $data){
        if (isset($data['organizationName']) && isset($data['organizationAddress']) && 
            isset($data['organizationPhone']) && isset($data['organizationEmail']) && 
            isset($data['organizationWebsite']) && isset($data['organizationLogo'])) {            
            $this->organizationName = $data['organizationName'];
            $this->organizationAddress = $data['organizationAddress'];
            $this->organizationPhone = $data['organizationPhone'];
            $this->organizationEmail = $data['organizationEmail'];
            $this->organizationWebsite = $data['organizationWebsite'];
            $this->organizationLogo = $data['organizationLogo'];
            return true;
        } else {
            return false;
        }
    }
    public function AddOrganizationDetailForOrganization() {
        include('config.inc');
        header('Content-Type: application/json');
        $this->isActive = 1;    
        try {
            $queryOrganization = "INSERT INTO tblOrganization(organizationName, organizationAddress, organizationPhone, organizationEmail, organizationWebsite, organizationLogo, createdOn, isActive) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?);";
            $stmt = mysqli_prepare($connect_var, $queryOrganization);
            mysqli_stmt_bind_param($stmt, "sssssss", $this->organizationName, $this->organizationAddress, $this->organizationPhone, $this->organizationEmail, $this->organizationWebsite, $this->organizationLogo, $this->isActive); 
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error inserting organization: " . mysqli_error($connect_var));
            }
            mysqli_stmt_close($stmt);
            echo json_encode([
                "status" => "success",
                "message" => "Organization added successfully"
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error", 
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        } finally {
            if (isset($connect_var)) {
                mysqli_close($connect_var);
            }
        }
    }
    public function GetOrganizationDetails() {
        include('config.inc');
        header('Content-Type: application/json');
        try {
            $queryGetOrganizationDetails = "SELECT organizationName, organizationPhone, organizationEmail, organizationWebsite, organizationLogo, createdOn, isActive, createdBy, contactPerson1Name, person1PhoneNumber, person1EmailID, contactPerson2Name, person2PhoneNumber, person2EmailID FROM tblOrganization";
            $stmt = mysqli_prepare($connect_var, $queryGetOrganizationDetails);
            if (!$stmt) {
                error_log("Prepare failed: " . mysqli_error($connect_var));
                throw new Exception("Database prepare failed");
            }   
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Execute failed: " . mysqli_stmt_error($stmt));
                throw new Exception("Database execute failed");
            }
            $result = mysqli_stmt_get_result($stmt);
            $organizationDetails = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $organizationDetails[] = [
                    'organizationName' => $row['organizationName'],
                    'organizationPhone' => $row['organizationPhone'],
                    'organizationEmail' => $row['organizationEmail'],
                    'organizationWebsite' => $row['organizationWebsite'],   
                    'organizationLogo' => $row['organizationLogo'],
                    'createdOn' => $row['createdOn'],
                    'isActive' => $row['isActive'],
                    'createdBy' => $row['createdBy'],
                    'contactPerson1Name' => $row['contactPerson1Name'],
                    'person1PhoneNumber' => $row['person1PhoneNumber'],
                    'person1EmailID' => $row['person1EmailID'],
                    'contactPerson2Name' => $row['contactPerson2Name'],
                    'person2PhoneNumber' => $row['person2PhoneNumber'],
                    'person2EmailID' => $row['person2EmailID']
                ];
            }
            
            if (!empty($organizationDetails)) {
                echo json_encode([
                    "status" => "success",
                    "data" => $organizationDetails
                ]);
            } else {
                error_log("No data found for organization details");
                echo json_encode([
                    "status" => "error",
                    "message_text" => "No data found for organization details"
                ], JSON_FORCE_OBJECT);
            }
            
        } catch (Exception $e) {
            error_log("Error in GetOrganizationDetails: " . $e->getMessage());
            echo json_encode([
                "status" => "error",
                "message_text" => $e->getMessage()
            ], JSON_FORCE_OBJECT);
        }
    }
}
function AddOrganizationDetails($decoded_items) {
    $OrganizationObject = new AddOrganizationComponent();
    if ($OrganizationObject->loadAddOrganizationDetails($decoded_items)) {
        $OrganizationObject->AddOrganizationDetailForOrganization();
    } else {
        echo json_encode([
            "status" => "error", 
            "message_text" => "Invalid Input Parameters"
        ], JSON_FORCE_OBJECT);
    }   
}
function GetOrganizationDetails() {
    $OrganizationObject = new AddOrganizationComponent();
    $OrganizationObject->GetOrganizationDetails();
}
?>