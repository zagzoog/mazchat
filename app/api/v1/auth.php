<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 3) . '/db_config.php';
require_once __DIR__ . '/ApiController.php';

class AuthController extends ApiController {
    public function login() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                throw new Exception('Invalid JSON data');
            }
            
            $this->validateInput($data, [
                'username' => 'required',
                'password' => 'required'
            ]);

            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$data['username']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($data['password'], $user['password'])) {
                $this->sendError('Invalid credentials', 401);
                return;
            }

            // Generate API token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

            $stmt = $this->db->prepare("UPDATE users SET api_token = ?, api_token_expires = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $user['id']]);

            // Get user's membership
            $stmt = $this->db->prepare("
                SELECT type FROM memberships 
                WHERE user_id = ? 
                AND start_date <= NOW() 
                AND (end_date IS NULL OR end_date >= NOW())
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$user['id']]);
            $membership = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->sendResponse([
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'membership_type' => $membership ? $membership['type'] : 'free'
                ]
            ], 'Login successful');
        } catch (Exception $e) {
            error_log("Auth error: " . $e->getMessage());
            $this->sendError($e->getMessage(), 500);
        }
    }

    public function logout() {
        if (!$this->requireAuth()) {
            return;
        }

        $stmt = $this->db->prepare("UPDATE users SET api_token = NULL, api_token_expires = NULL WHERE id = ?");
        $stmt->execute([$this->user['id']]);

        $this->sendResponse(null, 'Logged out successfully');
    }
}

// Route handling
$controller = new AuthController();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        if (strpos($_SERVER['REQUEST_URI'], '/logout') !== false) {
            $controller->logout();
        } else {
            $controller->login();
        }
        break;
    default:
        $controller->sendError('Method not allowed', 405);
} 