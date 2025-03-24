<?php
require_once dirname(__DIR__, 2) . '/ApiController.php';

class DeveloperKeysController extends ApiController {
    public function createKey() {
        if (!$this->requireAuth()) {
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        $this->validateInput($data, [
            'name' => 'required|min:3|max:100',
            'rate_limit' => 'required'
        ]);

        // Generate API key
        $apiKey = bin2hex(random_bytes(32));
        $expiresAt = !empty($data['expires_at']) ? date('Y-m-d H:i:s', strtotime($data['expires_at'])) : null;

        $stmt = $this->db->prepare("
            INSERT INTO api_keys (user_id, api_key, name, rate_limit, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $this->user['id'],
            $apiKey,
            $data['name'],
            $data['rate_limit'],
            $expiresAt
        ]);

        $this->sendResponse([
            'id' => $this->db->lastInsertId(),
            'api_key' => $apiKey
        ], 'API key created successfully');
    }

    public function toggleKey($id) {
        if (!$this->requireAuth()) {
            return;
        }

        // Check if key belongs to user
        $stmt = $this->db->prepare("SELECT * FROM api_keys WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $this->user['id']]);
        $key = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$key) {
            $this->sendError('API key not found', 404);
        }

        $newStatus = $key['status'] === 'active' ? 'inactive' : 'active';

        $stmt = $this->db->prepare("UPDATE api_keys SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);

        $this->sendResponse(null, 'API key status updated successfully');
    }

    public function revokeKey($id) {
        if (!$this->requireAuth()) {
            return;
        }

        // Check if key belongs to user
        $stmt = $this->db->prepare("SELECT * FROM api_keys WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $this->user['id']]);
        if (!$stmt->fetch()) {
            $this->sendError('API key not found', 404);
        }

        $stmt = $this->db->prepare("UPDATE api_keys SET status = 'revoked' WHERE id = ?");
        $stmt->execute([$id]);

        $this->sendResponse(null, 'API key revoked successfully');
    }

    public function getUsageStats($id) {
        if (!$this->requireAuth()) {
            return;
        }

        // Check if key belongs to user
        $stmt = $this->db->prepare("SELECT * FROM api_keys WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $this->user['id']]);
        if (!$stmt->fetch()) {
            $this->sendError('API key not found', 404);
        }

        // Get usage statistics
        $stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as requests,
                AVG(response_time) as avg_response_time,
                COUNT(CASE WHEN status_code >= 400 THEN 1 END) as errors
            FROM api_usage_logs
            WHERE api_key_id = ?
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $stmt->execute([$id]);
        $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->sendResponse($stats);
    }
}

// Route handling
$controller = new DeveloperKeysController();
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$id = null;

// Extract key ID from URI if present
if (preg_match('/\/keys\/(\d+)/', $uri, $matches)) {
    $id = $matches[1];
}

switch ($method) {
    case 'POST':
        if ($id && strpos($uri, '/toggle') !== false) {
            $controller->toggleKey($id);
        } else {
            $controller->createKey();
        }
        break;
    case 'DELETE':
        if ($id) {
            $controller->revokeKey($id);
        } else {
            $controller->sendError('API key ID required', 400);
        }
        break;
    case 'GET':
        if ($id && strpos($uri, '/usage') !== false) {
            $controller->getUsageStats($id);
        } else {
            $controller->sendError('Invalid endpoint', 404);
        }
        break;
    default:
        $controller->sendError('Method not allowed', 405);
} 