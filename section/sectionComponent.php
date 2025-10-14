<?php
class SectionComponent {
    public $sectionID;
    public $sectionName;
    public $sectionHeadID;
    public $organisationID;
    public $SectionDetailsData;
    public $UpdateSectionData;
    public $CreateSectionData;

    public function CreateSection() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
            //Decode Token Start
            $secratekey = "CreateNewSectionFromWeb";
            $decodeVal = decryptDataFunc($this->CreateSectionData['CreateSectionToken'], $secratekey);
            // DECODE Token End
            $queryCreateSection = "INSERT INTO tblSection (
                SectionName, sectionHeadID, organisationID
            ) VALUES (?, ?, ?)";

            $stmt = mysqli_prepare($connect_var, $queryCreateSection);
            
            mysqli_stmt_bind_param($stmt, "sii",
                $decodeVal->sectionName,
                $decodeVal->sectionHeadID,
                $decodeVal->organisationID
            );

            if (mysqli_stmt_execute($stmt)) {
                $latestSectionCreatedID = mysqli_insert_id($connect_var);
                $responseStatus = "success";
                $responseMessage = "Section created successfully";
                $latestSectionCreatedID = mysqli_insert_id($connect_var);
               
            } else {
                $responseStatus = "error";
                $responseMessage = "Error creating section: " . mysqli_stmt_error($stmt);
                $latestSectionCreatedID = 0;
            }
            mysqli_stmt_close($stmt);
            mysqli_close($connect_var);
            //Encode Token Start
            $payload_info = array(
                "message"=> $responseMessage,
                "sectionID" => $latestSectionCreatedID,
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

    public function UpdateSection() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $data = [];
            //Decode Token Start
            $secratekey = "UpdateSectionToken";
            $decodeVal = decryptDataFunc($this->UpdateSectionData['UpdateSectionToken'], $secratekey);
            // DECODE Token End
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
                $decodeVal->sectionName,
                $decodeVal->sectionHeadID,
                $decodeVal->organisationID,
                $decodeVal->sectionID
            );

            if (mysqli_stmt_execute($stmt)) {
                $affectedRows = mysqli_stmt_affected_rows($stmt);
                $responseStatus = "success";
                $responseMessage = "Section updated successfully";
                $resonseCount = $affectedRows;
            } else {
                $responseStatus = "error";
                $responseMessage = "Error updating section: " . mysqli_stmt_error($stmt);
                $resonseCount = 0;
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

    public function GetSectionsByOrganisation() {
        include('config.inc');
        header('Content-Type: application/json');
        //Decode Token Start
        $secratekey = "GetAllSectionByOrganisationIDWeb";
        $decodeVal = decryptDataFunc($this->SectionDetailsData['SectionDetailsToken'], $secratekey);
        // DECODE Token End
        try {
            $data = [];
            $resonseCount = 0;
            $queryGetSectionsByOrg = "SELECT s.*, e.employeeName as sectionHeadName 
                                     FROM tblSection s 
                                     LEFT JOIN tblEmployee e ON s.sectionHeadID = e.employeeID AND e.isActive = 1
                                     WHERE s.organisationID = ? 
                                     ORDER BY s.sectionID DESC";
            $stmt = mysqli_prepare($connect_var, $queryGetSectionsByOrg);
            mysqli_stmt_bind_param($stmt, "i", $decodeVal->organisationID);

            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                $sections = array();
                while ($row = mysqli_fetch_assoc($result)) {
                    $sections[] = $row;
                }
                $responseStatus = "success";
                $responseMessage = "Sections fetched successfully";
                $resonseCount = count($sections);
            } else {
                $responseStatus = "error";
                $responseMessage = "Error fetching sections by organisation";
                $resonseCount = 0;
            }
            //Encode Token Start
            $payload_info = array(
                "data"=>$sections,
                "message"=> $responseMessage,
                "count" => $resonseCount,
                "status" => $responseStatus
            );
            $encodeToken = encryptDataFunc($payload_info, $secratekey);
            //Encode Token End
            echo json_encode(array("status"=>$responseStatus, "response"=>$encodeToken),JSON_FORCE_OBJECT);
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
        $SectionObject = new SectionComponent();
        if ($decoded_items) {
            $SectionObject->CreateSectionData = $decoded_items;
            $SectionObject->CreateSection();
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    
}

function UpdateSection($decoded_items) {
    // For JSON requests, use the decoded items
    $SectionObject = new SectionComponent();
    if($decoded_items){
        $SectionObject->UpdateSectionData = $decoded_items;
        $SectionObject->UpdateSection();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    } 
}

function GetSectionsByOrganisation($decoded_items) {
    $SectionObject = new SectionComponent();
    if ($decoded_items) {
        $SectionObject->SectionDetailsData = $decoded_items;
        $SectionObject->GetSectionsByOrganisation();
    } else {
        echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
    }
}
?>
