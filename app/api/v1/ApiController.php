<?php
require_once dirname(__DIR__, 3) . '/db_config.php';

class ApiController {
    protected $db;
    protected $user;
    protected $response;

    public function __construct() {
        $this->db = getDBConnection();
        $this->response = [
            'success' => false,
            'message' => '',
            'data' => null
        ];
    }

    protected function requireAuth() {
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
    }

    protected function requireAdmin() {
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
        $this->response['success'] = true;
        $this->response['message'] = $message;
        $this->response['data'] = $data;
        
        header('Content-Type: application/json');
        echo json_encode($this->response);
        exit;
    }

    protected function sendError($message = 'Error', $code = 400) {
        $this->response['success'] = false;
        $this->response['message'] = $message;
        
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($this->response);
        exit;
    }

    protected function validateInput($data, $rules) {
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
    }
} 