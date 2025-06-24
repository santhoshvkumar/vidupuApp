<?php
class VisitorPassComponent {
    public $name;
    public $phone;
    public $toMeet;
    public $reasonForVisit;
    public $photo;
    public $proof;
    public $proofUrl;
    public $visitorId;
    public $createdOn;
    public $createdBy;
    public $organisationId;

    public function loadVisitorPassDetails(array $data) {
        // Debug: Log the received data
        error_log("Debug - Received data: " . print_r($data, true));
        
        if (isset($data['name']) && isset($data['phone']) && 
            isset($data['toMeet']) && isset($data['reasonForVisit']) && 
            isset($data['organisationId'])) {
            
            $this->name = $data['name'];
            $this->phone = $data['phone'];
            $this->toMeet = intval($data['toMeet']);
            $this->reasonForVisit = $data['reasonForVisit'];
            $this->organisationId = intval($data['organisationId']);
            $this->createdBy = isset($data['createdBy']) ? intval($data['createdBy']) : 1;
            
            if (isset($data['visitorId'])) {
                $this->visitorId = intval($data['visitorId']);
            }
            
            // Handle file upload for visitor photo
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $baseUploadDir = '/data/server/live/API/public_html/vidupuApp/uploads/VisitorPass/';
                
                if (!file_exists($baseUploadDir)) {
                    mkdir($baseUploadDir, 0777, true);
                }
        
                $file = $_FILES['photo'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileSize = $file['size'];
                $fileError = $file['error'];
                $fileType = $file['type'];
                
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowed = array('jpg', 'jpeg', 'png', 'gif');
                
                if (in_array($fileExt, $allowed)) {
                    if ($fileError === 0) {
                        if ($fileSize < 5000000) { // 5MB limit
                            $fileNameNew = uniqid('visitor_', true) . "." . $fileExt;
                            
                            if (isset($this->visitorId) && !empty($this->visitorId)) {
                                $visitorFolder = $baseUploadDir . $this->visitorId . '/';
                                $fileDestination = $visitorFolder . $fileNameNew;
                                $this->photo = 'uploads/VisitorPass/' . $this->visitorId . '/' . $fileNameNew;
                            } else {
                                $tempFolder = $baseUploadDir . 'temp/';
                                $fileDestination = $tempFolder . $fileNameNew;
                                $this->photo = 'uploads/VisitorPass/temp/' . $fileNameNew;
                            }
                            
                            $visitorFolder = dirname($fileDestination);
                            if (!file_exists($visitorFolder)) {
                                mkdir($visitorFolder, 0777, true);
                            }
                            
                            move_uploaded_file($fileTmpName, $fileDestination);
                        }
                    }
                }
            }
            
            // Handle file upload for proof image
            if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
                $baseUploadDir = '/data/server/live/API/public_html/vidupuApp/uploads/VisitorPass/';
                
                if (!file_exists($baseUploadDir)) {
                    mkdir($baseUploadDir, 0777, true);
                }
        
                $file = $_FILES['proof'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileSize = $file['size'];
                $fileError = $file['error'];
                $fileType = $file['type'];
                
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowed = array('jpg', 'jpeg', 'png', 'gif', 'pdf');
                
                if (in_array($fileExt, $allowed)) {
                    if ($fileError === 0) {
                        if ($fileSize < 5000000) { // 5MB limit
                            $fileNameNew = uniqid('proof_', true) . "." . $fileExt;
                            
                            if (isset($this->visitorId) && !empty($this->visitorId)) {
                                $visitorFolder = $baseUploadDir . $this->visitorId . '/';
                                $fileDestination = $visitorFolder . $fileNameNew;
                                $this->proofUrl = 'uploads/VisitorPass/' . $this->visitorId . '/' . $fileNameNew;
                            } else {
                                $tempFolder = $baseUploadDir . 'temp/';
                                $fileDestination = $tempFolder . $fileNameNew;
                                $this->proofUrl = 'uploads/VisitorPass/temp/' . $fileNameNew;
                            }
                            
                            $visitorFolder = dirname($fileDestination);
                            if (!file_exists($visitorFolder)) {
                                mkdir($visitorFolder, 0777, true);
                            }
                            
                            move_uploaded_file($fileTmpName, $fileDestination);
                        }
                    }
                }
            }
            
            // Handle proof type (store in proof field as ENUM value)
            if (isset($data['proofType'])) {
                $proofType = strtolower($data['proofType']);
                error_log("Debug - Received proofType: " . $proofType);
                
                // Map the frontend values to ENUM values
                $proofTypeMapping = [
                    'aadhar card' => 'aadhar',
                    'pan card' => 'pan',
                    'driving license' => 'license',
                    'passport' => 'passport',
                    'voter id' => 'voter',
                    'company id' => 'company',
                    'other' => 'other'
                ];
                
                if (isset($proofTypeMapping[$proofType])) {
                    $this->proof = $proofTypeMapping[$proofType];
                    error_log("Debug - Mapped proofType to: " . $this->proof);
                } else {
                    // If no mapping found, check if it's already a valid ENUM value
                    $validEnumValues = ['aadhar', 'pan', 'license', 'passport', 'voter', 'company', 'other'];
                    if (in_array($proofType, $validEnumValues)) {
                        $this->proof = $proofType;
                        error_log("Debug - Set proof to: " . $this->proof);
                    } else {
                        $this->proof = 'other';
                        error_log("Debug - Set proof to 'other' (invalid value: " . $proofType . ")");
                    }
                }
            } else {
                error_log("Debug - proofType not set in data");
                $this->proof = 'other'; // Default value
            }
            
            error_log("Debug - Final proof value: " . $this->proof);
            error_log("Debug - Final proofUrl value: " . $this->proofUrl);
            
            return true;
        } else {
            error_log("Debug - Missing required fields in data");
            return false;
        }
    }

    public function CreateVisitorPass() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            // Debug: Log the values being inserted
            error_log("Debug - name: " . $this->name);
            error_log("Debug - phone: " . $this->phone);
            error_log("Debug - toMeet: " . $this->toMeet);
            error_log("Debug - reasonForVisit: " . $this->reasonForVisit);
            error_log("Debug - photo: " . $this->photo);
            error_log("Debug - proof: " . $this->proof);
            error_log("Debug - proofUrl: " . $this->proofUrl);
            error_log("Debug - createdBy: " . $this->createdBy);
            error_log("Debug - organisationId: " . $this->organisationId);
            
            $queryCreateVisitorPass = "INSERT INTO tblVisitor (
                name, phone, toMeet, reasonForVisit, photo, proof, proofUrl,
                createdOn, createdBy, organisationId
            ) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?)";

            $stmt = mysqli_prepare($connect_var, $queryCreateVisitorPass);
            if (!$stmt) {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Database prepare statement failed"
                ));
                return;
            }
            
            mysqli_stmt_bind_param($stmt, "ssissssii",
                $this->name,
                $this->phone,
                $this->toMeet,
                $this->reasonForVisit,
                $this->photo,
                $this->proof,
                $this->proofUrl,
                $this->createdBy,
                $this->organisationId
            );

            if (mysqli_stmt_execute($stmt)) {
                $latestVisitorId = mysqli_insert_id($connect_var);
                
                echo json_encode(array(
                    "status" => "success",
                    "message" => "Visitor pass created successfully",
                    "visitorId" => $latestVisitorId
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Failed to create visitor pass: " . mysqli_error($connect_var)
                ));
            }
            
            mysqli_stmt_close($stmt);
        } catch (Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message" => "Exception: " . $e->getMessage()
            ));
        }
    }

    public function GetAllVisitorPasses() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $queryGetAllVisitorPasses = "SELECT v.*, e.employeeName as toMeetName 
                                       FROM tblVisitor v 
                                       LEFT JOIN tblEmployee e ON v.toMeet = e.employeeID 
                                       WHERE v.organisationId = ? 
                                       ORDER BY v.createdOn DESC";
            
            $stmt = mysqli_prepare($connect_var, $queryGetAllVisitorPasses);
            
            if (!$stmt) {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Database prepare statement failed"
                ));
                return;
            }
            
            mysqli_stmt_bind_param($stmt, "i", $this->organisationId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $visitorPasses = array();
            while ($row = mysqli_fetch_assoc($result)) {
                $visitorPasses[] = $row;
            }
            
            echo json_encode(array(
                "status" => "success",
                "data" => $visitorPasses
            ));
            
            mysqli_stmt_close($stmt);
        } catch (Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message" => "Exception: " . $e->getMessage()
            ));
        }
    }

    public function UpdateVisitorPass() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $queryUpdateVisitorPass = "UPDATE tblVisitor SET 
                name = ?, phone = ?, toMeet = ?, reasonForVisit = ?, 
                photo = ?, proof = ?, proofUrl = ?, createdBy = ?, organisationId = ?
                WHERE visitorId = ?";

            $stmt = mysqli_prepare($connect_var, $queryUpdateVisitorPass);
            if (!$stmt) {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Database prepare statement failed"
                ));
                return;
            }
            
            mysqli_stmt_bind_param($stmt, "ssissssiii",
                $this->name,
                $this->phone,
                $this->toMeet,
                $this->reasonForVisit,
                $this->photo,
                $this->proof,
                $this->proofUrl,
                $this->createdBy,
                $this->organisationId,
                $this->visitorId
            );

            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(array(
                    "status" => "success",
                    "message" => "Visitor pass updated successfully"
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Failed to update visitor pass"
                ));
            }
            
            mysqli_stmt_close($stmt);
        } catch (Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message" => "Exception: " . $e->getMessage()
            ));
        }
    }

    public function GetVisitorPass() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $queryGetVisitorPass = "SELECT v.*, e.employeeName as toMeetName 
                                   FROM tblVisitor v 
                                   LEFT JOIN tblEmployee e ON v.toMeet = e.employeeID 
                                   WHERE v.visitorId = ?";
            $stmt = mysqli_prepare($connect_var, $queryGetVisitorPass);
            
            if (!$stmt) {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Database prepare statement failed"
                ));
                return;
            }
            
            mysqli_stmt_bind_param($stmt, "i", $this->visitorId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                echo json_encode(array(
                    "status" => "success",
                    "data" => $row
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Visitor pass not found"
                ));
            }
            
            mysqli_stmt_close($stmt);
        } catch (Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message" => "Exception: " . $e->getMessage()
            ));
        }
    }

    public function DeleteVisitorPass() {
        include('config.inc');
        header('Content-Type: application/json');
    
        try {
            $queryDeleteVisitorPass = "DELETE FROM tblVisitor WHERE visitorId = ?";
            $stmt = mysqli_prepare($connect_var, $queryDeleteVisitorPass);
            
            if (!$stmt) {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Database prepare statement failed"
                ));
                return;
            }
            
            mysqli_stmt_bind_param($stmt, "i", $this->visitorId);
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(array(
                    "status" => "success",
                    "message" => "Visitor pass deleted successfully"
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Failed to delete visitor pass"
                ));
            }
            
            mysqli_stmt_close($stmt);
        } catch (Exception $e) {
            echo json_encode(array(
                "status" => "error",
                "message" => "Exception: " . $e->getMessage()
            ));
        }
    }
}

// Helper functions for API endpoints
function CreateVisitorPass($decoded_items) {
    $visitorPass = new VisitorPassComponent();
    if ($visitorPass->loadVisitorPassDetails($decoded_items)) {
        $visitorPass->CreateVisitorPass();
    } else {
        echo json_encode(array(
            "status" => "error",
            "message" => "Invalid input parameters"
        ));
    }
}

function UpdateVisitorPass($decoded_items) {
    $visitorPass = new VisitorPassComponent();
    if ($visitorPass->loadVisitorPassDetails($decoded_items)) {
        $visitorPass->UpdateVisitorPass();
    } else {
        echo json_encode(array(
            "status" => "error",
            "message" => "Invalid input parameters"
        ));
    }
}

function GetVisitorPass($decoded_items) {
    $visitorPass = new VisitorPassComponent();
    $visitorPass->visitorId = isset($decoded_items['visitorId']) ? intval($decoded_items['visitorId']) : 0;
    $visitorPass->GetVisitorPass();
}

function GetAllVisitorPasses($decoded_items) {
    $visitorPass = new VisitorPassComponent();
    $visitorPass->organisationId = isset($decoded_items['organisationId']) ? intval($decoded_items['organisationId']) : 1;
    $visitorPass->GetAllVisitorPasses();
}

function DeleteVisitorPass($decoded_items) {
    $visitorPass = new VisitorPassComponent();
    $visitorPass->visitorId = isset($decoded_items['visitorId']) ? intval($decoded_items['visitorId']) : 0;
    $visitorPass->DeleteVisitorPass();
}
?> 