<?php
require_once dirname(__DIR__, 3) . '/db_config.php';

class ApiController {
    protected $db;
    protected $user;
    protected $response;
    protected $isTestMode;

    public function __construct($isTest = false) {
        $this->isTestMode = $isTest;
        
        try {
            $this->db = getDBConnection();
            if (!$this->db) {
                throw new Exception('Failed to connect to database');
            }
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            $this->sendError('Database connection error', 500);
            return;
        }

        $this->response = [
            'success' => false,
            'message' => '',
            'data' => null
        ];
    }

    protected function requireAuth() {
        if ($this->isTestMode) {
            return true;
        }
        
        try {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? '';
            
            if (!preg_match('/^Bearer\s+(.+)$/', $authHeader, $matches)) {
                $this->sendError('Authorization header missing or invalid', 401);
                return false;
            }
            
            $token = $matches[1];
            
            $stmt = $this->db->prepare("
                SELECT * FROM users 
                WHERE api_token = ? 
                AND (api_token_expires IS NULL OR api_token_expires > NOW())
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $this->sendError('Invalid or expired token', 401);
                return false;
            }
            
            $this->user = $user;
            return true;
        } catch (Exception $e) {
            error_log("Auth error: " . $e->getMessage());
            $this->sendError('Authentication error', 500);
            return false;
        }
    }

    protected function requireAdmin() {
        if ($this->isTestMode) {
            return true;
        }
        
        if (!$this->requireAuth()) {
            return false;
        }
        
        if ($this->user['role'] !== 'admin') {
            $this->sendError('Admin access required', 403);
            return false;
        }
        
        return true;
    }

    protected function sendResponse($data = null, $message = 'Success') {
        try {
            $this->response['success'] = true;
            $this->response['message'] = $message;
            $this->response['data'] = $data;
            
            header('Content-Type: application/json');
            echo json_encode($this->response, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Exception $e) {
            error_log("Error sending response: " . $e->getMessage());
            $this->sendError('Error processing response', 500);
        }
    }

    protected function sendError($message = 'Error', $code = 400, $data = null) {
        try {
            $this->response['success'] = false;
            $this->response['message'] = $message;
            $this->response['data'] = $data;
            
            http_response_code($code);
            header('Content-Type: application/json');
            echo json_encode($this->response, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Exception $e) {
            error_log("Error sending error response: " . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Internal server error'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    protected function validateInput($data, $rules) {
        try {
            $errors = [];
            foreach ($rules as $field => $rule) {
                if (!isset($data[$field]) && strpos($rule, 'required') !== false) {
                    $errors[] = "$field is required";
                    continue;
                }

                if (isset($data[$field])) {
                    if (strpos($rule, 'email') !== false && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "$field must be a valid email";
                    }
                    if (strpos($rule, 'min:') !== false) {
                        $min = substr($rule, strpos($rule, 'min:') + 4);
                        if (strlen($data[$field]) < $min) {
                            $errors[] = "$field must be at least $min characters";
                        }
                    }
                    if (strpos($rule, 'max:') !== false) {
                        $max = substr($rule, strpos($rule, 'max:') + 4);
                        if (strlen($data[$field]) > $max) {
                            $errors[] = "$field must not exceed $max characters";
                        }
                    }
                }
            }

            if (!empty($errors)) {
                $this->sendError('Validation failed', 422);
            }

            return true;
        } catch (Exception $e) {
            error_log("Validation error: " . $e->getMessage());
            $this->sendError('Validation error', 500);
            return false;
        }
    }
} 