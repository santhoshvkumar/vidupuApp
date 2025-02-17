<?php
class LoginRouter {
    private $loginComponent;

    public function __construct() {
        global $f3;
        if (!$f3->exists('DB')) {
            error_log("Database connection not available in LoginRouter");
            throw new Exception("Database connection not available");
        }
        $this->loginComponent = new LoginComponent($f3);
    }

    // Handle login logic
    public function login($f3) {
        try {
            // Get raw input
            $rawBody = file_get_contents('php://input');
            error_log("Raw request body: " . $rawBody);

            // Parse JSON
            $data = json_decode($rawBody, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON parse error: " . json_last_error_msg());
                $f3->status(400);
                echo json_encode([
                    'error' => 'Invalid JSON format',
                    'details' => json_last_error_msg()
                ]);
                return;
            }

            // Validate required fields
            if (!isset($data['phoneNumber']) || !isset($data['password'])) {
                $f3->status(400);
                echo json_encode([
                    'error' => 'Missing required fields',
                    'required' => ['phoneNumber', 'password'],
                    'received' => array_keys($data)
                ]);
                return;
            }

            // Call login component
            $this->loginComponent->login($f3);
            
        } catch (Exception $e) {
            error_log("Login router error: " . $e->getMessage());
            $f3->status(500);
            echo json_encode([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
?>