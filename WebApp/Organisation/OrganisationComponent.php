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
        // Debug: Log received data
        error_log("Received organisation data: " . json_encode($data));
        
        // Validate required fields
        $validationErrors = $this->validateOrganisationData($data);
        if (!empty($validationErrors)) {
            error_log("Validation errors: " . json_encode($validationErrors));
            return false;
        }
        
        // Check if required fields exist (excluding organisationLogo since it's optional)
        if (isset($data['organisationName']) && isset($data['website']) && 
            isset($data['emailID']) && isset($data['contactPerson1Name']) && 
            isset($data['contactPerson1Email']) && isset($data['contactPerson1Phone']) && 
            isset($data['contactPerson2Name']) && isset($data['contactPerson2Email']) && 
            isset($data['contactPerson2Phone'])) {
            
            $this->organisationName = $data['organisationName'];
            $this->website = $data['website'];
            $this->emailID = $data['emailID'];
            $this->contactPerson1Name = $data['contactPerson1Name'];
            $this->contactPerson1Email = $data['contactPerson1Email'];
            $this->contactPerson1Phone = $data['contactPerson1Phone'];
            $this->contactPerson2Name = $data['contactPerson2Name'];
            $this->contactPerson2Email = $data['contactPerson2Email'];
            $this->contactPerson2Phone = $data['contactPerson2Phone'];
            $this->createdBy = isset($data['createdBy']) ? intval($data['createdBy']) : 1;
            
            // Set organisationID if provided (for updates)
            if (isset($data['organisationID'])) {
                $this->organisationID = $data['organisationID'];
            }
            
            // Handle file upload for organisation logo
            // For updates, preserve existing logo if no new file is uploaded
            if (isset($data['organisationLogo']) && !empty($data['organisationLogo'])) {
                $this->organisationLogo = $data['organisationLogo'];
            } else {
                $this->organisationLogo = '';
            }
            
            // Check if a new file is being uploaded
            if (isset($_FILES['organisationLogo']) && $_FILES['organisationLogo']['error'] === UPLOAD_ERR_OK) {
                // Use absolute path for upload directory
                $baseUploadDir = '/data/server/live/API/public_html/vidupuApp/uploads/Organisation/';
                
                // Create base directory if it doesn't exist
                if (!file_exists($baseUploadDir)) {
                    mkdir($baseUploadDir, 0777, true);
                }
        
                $file = $_FILES['organisationLogo'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileSize = $file['size'];
                $fileError = $file['error'];
                $fileType = $file['type'];
                
                // Get file extension
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                // Allowed file types
                $allowed = array('jpg', 'jpeg', 'png', 'gif');
                
                if (in_array($fileExt, $allowed)) {
                    if ($fileError === 0) {
                        if ($fileSize < 5000000) { // 5MB limit
                            // Generate unique filename
                            $fileNameNew = uniqid('logo_', true) . "." . $fileExt;
                            
                            // For updates, use existing organisationID, for creates we'll handle it after insertion
                            if (isset($this->organisationID) && !empty($this->organisationID)) {
                                // Update mode - use existing organisationID
                                $organisationFolder = $baseUploadDir . $this->organisationID . '/';
                                $fileDestination = $organisationFolder . $fileNameNew;
                                $this->organisationLogo = 'uploads/Organisation/' . $this->organisationID . '/' . $fileNameNew;
                            } else {
                                // Create mode - we'll need to update this after getting the organisationID
                                $tempFolder = $baseUploadDir . 'temp/';
                                $fileDestination = $tempFolder . $fileNameNew;
                                $this->organisationLogo = 'uploads/Organisation/temp/' . $fileNameNew;
                            }
                            
                            // Create organisation-specific folder
                            $organisationFolder = dirname($fileDestination);
                            if (!file_exists($organisationFolder)) {
                                mkdir($organisationFolder, 0777, true);
                            }
                            
                            move_uploaded_file($fileTmpName, $fileDestination);
                        }
                    }
                }
            } else {
                error_log("No new file uploaded, keeping existing logo: " . $this->organisationLogo);
            }
            
            return true;
        } else {
            // Debug: Log what data was received
            error_log("Missing required fields. Received data keys: " . implode(', ', array_keys($data)));
            error_log("Missing fields check:");
            error_log("- organisationName: " . (isset($data['organisationName']) ? 'present' : 'missing'));
            error_log("- website: " . (isset($data['website']) ? 'present' : 'missing'));
            error_log("- emailID: " . (isset($data['emailID']) ? 'present' : 'missing'));
            error_log("- contactPerson1Name: " . (isset($data['contactPerson1Name']) ? 'present' : 'missing'));
            error_log("- contactPerson1Email: " . (isset($data['contactPerson1Email']) ? 'present' : 'missing'));
            error_log("- contactPerson1Phone: " . (isset($data['contactPerson1Phone']) ? 'present' : 'missing'));
            error_log("- contactPerson2Name: " . (isset($data['contactPerson2Name']) ? 'present' : 'missing'));
            error_log("- contactPerson2Email: " . (isset($data['contactPerson2Email']) ? 'present' : 'missing'));
            error_log("- contactPerson2Phone: " . (isset($data['contactPerson2Phone']) ? 'present' : 'missing'));
            return false;
        }
    }

    /**
     * Validate organisation data
     * @param array $data
     * @return array Validation errors
     */
    private function validateOrganisationData($data) {
        $errors = [];
        
        // Sanitize inputs
        $data = array_map(function($value) {
            return is_string($value) ? trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8')) : $value;
        }, $data);
        
        // Required field validations
        if (empty($data['organisationName']) || strlen(trim($data['organisationName'])) < 2) {
            $errors[] = 'Organisation name is required and must be at least 2 characters long';
        } elseif (strlen($data['organisationName']) > 100) {
            $errors[] = 'Organisation name must be less than 100 characters';
        }
        
        // Check for XSS and SQL injection attempts
        if (!empty($data['organisationName']) && (strpos($data['organisationName'], '<script>') !== false || 
            strpos($data['organisationName'], 'javascript:') !== false || 
            strpos($data['organisationName'], 'onload=') !== false)) {
            $errors[] = 'Organisation name contains invalid characters';
        }
        
        if (empty($data['contactPerson1Name']) || strlen(trim($data['contactPerson1Name'])) < 2) {
            $errors[] = 'Contact Person 1 name is required and must be at least 2 characters long';
        } elseif (strlen($data['contactPerson1Name']) > 50) {
            $errors[] = 'Contact Person 1 name must be less than 50 characters';
        }
        
        if (empty($data['contactPerson1Email'])) {
            $errors[] = 'Contact Person 1 email is required';
        } elseif (!filter_var($data['contactPerson1Email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Contact Person 1 email is invalid';
        } elseif (strlen($data['contactPerson1Email']) > 100) {
            $errors[] = 'Contact Person 1 email must be less than 100 characters';
        }
        
        if (empty($data['contactPerson1Phone'])) {
            $errors[] = 'Contact Person 1 phone is required';
        } elseif (!$this->validatePhoneNumber($data['contactPerson1Phone'])) {
            $errors[] = 'Contact Person 1 phone number is invalid';
        } elseif (strlen($data['contactPerson1Phone']) > 20) {
            $errors[] = 'Contact Person 1 phone must be less than 20 characters';
        }
        
        // Optional field validations
        if (!empty($data['website']) && !filter_var($data['website'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Website URL is invalid';
        } elseif (!empty($data['website']) && strlen($data['website']) > 200) {
            $errors[] = 'Website URL must be less than 200 characters';
        }
        
        if (!empty($data['emailID']) && !filter_var($data['emailID'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Organisation email is invalid';
        } elseif (!empty($data['emailID']) && strlen($data['emailID']) > 100) {
            $errors[] = 'Organisation email must be less than 100 characters';
        }
        
        if (!empty($data['contactPerson2Name']) && strlen($data['contactPerson2Name']) > 50) {
            $errors[] = 'Contact Person 2 name must be less than 50 characters';
        }
        
        if (!empty($data['contactPerson2Email']) && !filter_var($data['contactPerson2Email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Contact Person 2 email is invalid';
        } elseif (!empty($data['contactPerson2Email']) && strlen($data['contactPerson2Email']) > 100) {
            $errors[] = 'Contact Person 2 email must be less than 100 characters';
        }
        
        if (!empty($data['contactPerson2Phone']) && !$this->validatePhoneNumber($data['contactPerson2Phone'])) {
            $errors[] = 'Contact Person 2 phone number is invalid';
        } elseif (!empty($data['contactPerson2Phone']) && strlen($data['contactPerson2Phone']) > 20) {
            $errors[] = 'Contact Person 2 phone must be less than 20 characters';
        }
        
        // Check for duplicate organisation name
        if (!empty($data['organisationName'])) {
            $duplicateCheck = $this->checkDuplicateOrganisationName($data['organisationName'], isset($data['organisationID']) ? $data['organisationID'] : null);
            if ($duplicateCheck) {
                $errors[] = 'An organisation with this name already exists';
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate phone number
     * @param string $phone
     * @return bool
     */
    private function validatePhoneNumber($phone) {
        // Remove spaces, dashes, and parentheses
        $cleanPhone = preg_replace('/[\s\-\(\)]/', '', $phone);
        
        // Check if it's a valid phone number (7-15 digits, optionally starting with +)
        return preg_match('/^[\+]?[1-9][\d]{6,14}$/', $cleanPhone);
    }
    
    /**
     * Check for duplicate organisation name
     * @param string $organisationName
     * @param int|null $excludeId
     * @return bool
     */
    private function checkDuplicateOrganisationName($organisationName, $excludeId = null) {
        include('config.inc');
        
        $query = "SELECT COUNT(*) as count FROM tblOrganisation WHERE organisationName = ?";
        $params = [$organisationName];
        $types = "s";
        
        if ($excludeId) {
            $query .= " AND organisationID != ?";
            $params[] = $excludeId;
            $types .= "i";
        }
        
        $stmt = mysqli_prepare($connect_var, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            return $row['count'] > 0;
        }
        
        return false;
    }

    public function CreateOrganisation() {
        include('config.inc');
        header('Content-Type: application/json');
    
        error_log("CreateOrganisation function called");
        
        try {
            $data = [];
            
            // Validate the data before processing
            $validationData = [
                'organisationName' => $this->organisationName ?? '',
                'website' => $this->website ?? '',
                'emailID' => $this->emailID ?? '',
                'contactPerson1Name' => $this->contactPerson1Name ?? '',
                'contactPerson1Email' => $this->contactPerson1Email ?? '',
                'contactPerson1Phone' => $this->contactPerson1Phone ?? '',
                'contactPerson2Name' => $this->contactPerson2Name ?? '',
                'contactPerson2Email' => $this->contactPerson2Email ?? '',
                'contactPerson2Phone' => $this->contactPerson2Phone ?? '',
                'organisationID' => $this->organisationID ?? null
            ];
            
            $validationErrors = $this->validateOrganisationData($validationData);
            if (!empty($validationErrors)) {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Validation failed",
                    "errors" => $validationErrors
                ));
                return;
            }
    
            $queryCreateOrganisation = "INSERT INTO tblOrganisation (
                organisationName, organisationLogo, website, emailID, 
                createdOn, createdBy, contactPerson1Name, contactPerson1Email, 
                contactPerson1Phone, contactPerson2Name, contactPerson2Email, 
                contactPerson2Phone
            ) VALUES (?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($connect_var, $queryCreateOrganisation);
            if (!$stmt) {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Database prepare statement failed"
                ));
                return;
            }
            
            mysqli_stmt_bind_param($stmt, "sssssssssss",
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
            );

            if (mysqli_stmt_execute($stmt)) {
                $latestOrganisationCreatedID = mysqli_insert_id($connect_var);
                
                
                if (strpos($this->organisationLogo, 'uploads/Organisation/temp/') === 0) {
                    $baseUploadDir = '/data/server/live/API/public_html/vidupuApp/uploads/Organisation/';
                    $tempFilePath = $baseUploadDir . 'temp/' . basename($this->organisationLogo);
                    $newFolderPath = $baseUploadDir . $latestOrganisationCreatedID . '/';
                    $newFilePath = $newFolderPath . 'companyLogo.png';
                    // Create organisation-specific folder
                    if (!file_exists($newFolderPath)) {
                        mkdir($newFolderPath, 0777, true);
                    }
                    
                    // Move file from temp to organisation folder
                    if (file_exists($tempFilePath)) {
                        if (rename($tempFilePath, $newFilePath)) {
                            $this->organisationLogo = 'uploads/Organisation/' . $latestOrganisationCreatedID . '/companyLogo.png';
                            
                            // Update the database with the correct path
                            $updateQuery = "UPDATE tblOrganisation SET organisationLogo = ? WHERE organisationID = ?";
                            $updateStmt = mysqli_prepare($connect_var, $updateQuery);
                            if ($updateStmt) {
                                mysqli_stmt_bind_param($updateStmt, "si", $this->organisationLogo, $latestOrganisationCreatedID);
                                mysqli_stmt_execute($updateStmt);
                                mysqli_stmt_close($updateStmt);
                            }
                        }
                    }
                }
                
                // Insert into tblSection with the latest organisation ID
                $createSectionQuery = "INSERT INTO tblSection (SectionName, sectionHeadID, organisationID) VALUES (?, ?, ?)";
                $sectionStmt = mysqli_prepare($connect_var, $createSectionQuery);
                if ($sectionStmt) {
                    $sectionName = "Establishment";
                    $sectionHeadID = 0;
                    mysqli_stmt_bind_param($sectionStmt, "sii", $sectionName, $sectionHeadID, $latestOrganisationCreatedID);
                    mysqli_stmt_execute($sectionStmt);
                    $latestSectionID = mysqli_insert_id($connect_var);
                    mysqli_stmt_close($sectionStmt);

                  

                    // Insert into tblUser
                    $createUserQuery = "INSERT INTO tblUser (userName, userPhone, userPassword, sectionID, isActive, role, organisationID) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $userStmt = mysqli_prepare($connect_var, $createUserQuery);
                    if ($userStmt) {
                        $userName = $this->contactPerson1Name;
                        $userPhone = $this->contactPerson1Phone;
                        $userPassword = md5('Password#1');
                        $isActive = 1;
                        $role = 'Admin';

                        mysqli_stmt_bind_param($userStmt, "sssiisi",
                            $userName,
                            $userPhone,
                            $userPassword,
                            $latestSectionID,
                            $isActive,
                            $role,
                            $latestOrganisationCreatedID
                        );
                        mysqli_stmt_execute($userStmt);
                        $latestUserID = mysqli_insert_id($connect_var);
                        mysqli_stmt_close($userStmt);


                          // Update sectionHeadID with the latest inserted sectionID
                        $updateSectionHeadQuery = "UPDATE tblSection SET sectionHeadID = ? WHERE sectionID = ?";
                        $updateSectionStmt = mysqli_prepare($connect_var, $updateSectionHeadQuery);
                        if ($updateSectionStmt) {
                            mysqli_stmt_bind_param($updateSectionStmt, "ii", $latestUserID , $latestSectionID);
                            mysqli_stmt_execute($updateSectionStmt);
                            mysqli_stmt_close($updateSectionStmt);
                        }
                    }
                }
                
                echo json_encode(array(
                    "status" => "success",
                    "message" => "Organisation created successfully",
                    "organisationID" => $latestOrganisationCreatedID
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error creating organisation: " . mysqli_stmt_error($stmt)
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
            
            // Validate the data before processing
            $validationData = [
                'organisationName' => $this->organisationName ?? '',
                'website' => $this->website ?? '',
                'emailID' => $this->emailID ?? '',
                'contactPerson1Name' => $this->contactPerson1Name ?? '',
                'contactPerson1Email' => $this->contactPerson1Email ?? '',
                'contactPerson1Phone' => $this->contactPerson1Phone ?? '',
                'contactPerson2Name' => $this->contactPerson2Name ?? '',
                'contactPerson2Email' => $this->contactPerson2Email ?? '',
                'contactPerson2Phone' => $this->contactPerson2Phone ?? '',
                'organisationID' => $this->organisationID ?? null
            ];
            
            $validationErrors = $this->validateOrganisationData($validationData);
            if (!empty($validationErrors)) {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Validation failed",
                    "errors" => $validationErrors
                ));
                return;
            }
    
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
            if (!$stmt) {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Database prepare statement failed"
                ));
                return;
            }
            
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
                $affectedRows = mysqli_stmt_affected_rows($stmt);
                echo json_encode(array(
                    "status" => "success",
                    "message" => "Organisation updated successfully",
                    "affected_rows" => $affectedRows
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error updating organisation: " . mysqli_stmt_error($stmt)
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
            
            // Debug: Check if database connection is working
            if (!$connect_var) {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Database connection failed"
                ));
                return;
            }
    
            $queryGetAllOrganisations = "SELECT * FROM tblOrganisation WHERE isActive = 1 ORDER BY organisationID DESC";
            
            $result = mysqli_query($connect_var, $queryGetAllOrganisations);
            
            if ($result) {
                $organisations = array();
                $count = 0;
                while ($row = mysqli_fetch_assoc($result)) {
                    $organisations[] = $row;
                    $count++;
                }
                
                echo json_encode(array(
                    "status" => "success",
                    "data" => $organisations,
                    "count" => count($organisations)
                ));
            } else {
                echo json_encode(array(
                    "status" => "error",
                    "message" => "Error fetching organisations",
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
}

function CreateOrganisation($decoded_items) {
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
        // For FormData, use $_POST instead of decoded JSON
        $OrganisationObject = new OrganisationComponent();
        if ($OrganisationObject->loadOrganisationDetails($_POST)) {
            $OrganisationObject->CreateOrganisation();
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    } else {
        // For JSON requests, use the decoded items
        $OrganisationObject = new OrganisationComponent();
        if ($OrganisationObject->loadOrganisationDetails($decoded_items)) {
            $OrganisationObject->CreateOrganisation();
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    }
}

function UpdateOrganisation($decoded_items) {
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
        $OrganisationObject = new OrganisationComponent();
        if ($OrganisationObject->loadOrganisationDetails($_POST)) {
            // Set the organisationID from FormData
            if (isset($_POST['organisationID'])) {
                $OrganisationObject->organisationID = $_POST['organisationID'];
            }
            $OrganisationObject->UpdateOrganisation();
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
    } else {
        // For JSON requests, use the decoded items
        $OrganisationObject = new OrganisationComponent();
        if ($OrganisationObject->loadOrganisationDetails($decoded_items)) {
            $OrganisationObject->UpdateOrganisation();
        } else {
            echo json_encode(array("status" => "error", "message_text" => "Invalid Input Parameters"), JSON_FORCE_OBJECT);
        }
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
