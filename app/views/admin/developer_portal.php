<?php
if (!defined('ADMIN_PANEL')) {
    die('Direct access not permitted');
}

$db = getDBConnection();

// Get user's API keys
$stmt = $db->prepare("
    SELECT * FROM api_keys 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$apiKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get usage statistics for the last 30 days
$stmt = $db->prepare("
    SELECT 
        DATE(l.created_at) as date,
        COUNT(*) as requests,
        AVG(l.response_time) as avg_response_time,
        COUNT(CASE WHEN l.status_code >= 400 THEN 1 END) as errors
    FROM api_usage_logs l
    JOIN api_keys k ON l.api_key_id = k.id
    WHERE k.user_id = ? 
    AND l.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(l.created_at)
    ORDER BY date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$usageStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Developer Portal</h2>
            <p class="text-muted">Manage your API keys and monitor usage</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createApiKeyModal">
                <i class="fas fa-plus"></i> Create API Key
            </button>
            <a href="/api/docs" class="btn btn-info ms-2" target="_blank">
                <i class="fas fa-book"></i> API Documentation
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Total API Keys</h6>
                    <h3 class="card-title"><?php echo count($apiKeys); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Total Requests (30d)</h6>
                    <h3 class="card-title"><?php echo array_sum(array_column($usageStats, 'requests')); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Avg Response Time (30d)</h6>
                    <h3 class="card-title"><?php 
                        $avgTime = array_sum(array_column($usageStats, 'avg_response_time')) / count($usageStats);
                        echo number_format($avgTime, 2) . 'ms';
                    ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Error Rate (30d)</h6>
                    <h3 class="card-title"><?php 
                        $totalErrors = array_sum(array_column($usageStats, 'errors'));
                        $totalRequests = array_sum(array_column($usageStats, 'requests'));
                        echo number_format(($totalErrors / $totalRequests) * 100, 1) . '%';
                    ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- API Keys -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">API Keys</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>API Key</th>
                            <th>Status</th>
                            <th>Rate Limit</th>
                            <th>Last Used</th>
                            <th>Expires</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($apiKeys as $key): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($key['name']); ?></td>
                            <td>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?php echo $key['api_key']; ?>" readonly>
                                    <button class="btn btn-outline-secondary copy-btn" type="button" data-clipboard-text="<?php echo $key['api_key']; ?>">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $key['status'] === 'active' ? 'success' : ($key['status'] === 'inactive' ? 'warning' : 'danger'); ?>">
                                    <?php echo ucfirst($key['status']); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($key['rate_limit']); ?>/day</td>
                            <td><?php echo $key['last_used_at'] ? date('Y-m-d H:i', strtotime($key['last_used_at'])) : 'Never'; ?></td>
                            <td><?php echo $key['expires_at'] ? date('Y-m-d', strtotime($key['expires_at'])) : 'Never'; ?></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-warning" onclick="toggleApiKey(<?php echo $key['id']; ?>)">
                                        <?php echo $key['status'] === 'active' ? 'Disable' : 'Enable'; ?>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="revokeApiKey(<?php echo $key['id']; ?>)">
                                        Revoke
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Usage Chart -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">API Usage (Last 30 Days)</h5>
        </div>
        <div class="card-body">
            <canvas id="usageChart" height="100"></canvas>
        </div>
    </div>
</div>

<!-- Create API Key Modal -->
<div class="modal fade" id="createApiKeyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New API Key</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createApiKeyForm" onsubmit="createApiKey(event)">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="keyName" class="form-label">Key Name</label>
                        <input type="text" class="form-control" id="keyName" name="name" required>
                        <div class="form-text">A descriptive name to identify this API key</div>
                    </div>
                    <div class="mb-3">
                        <label for="rateLimit" class="form-label">Rate Limit (requests per day)</label>
                        <input type="number" class="form-control" id="rateLimit" name="rate_limit" value="1000" min="1" max="10000" required>
                    </div>
                    <div class="mb-3">
                        <label for="expiresAt" class="form-label">Expires At</label>
                        <input type="date" class="form-control" id="expiresAt" name="expires_at">
                        <div class="form-text">Leave blank for no expiration</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.8/dist/clipboard.min.js"></script>
<script>
// Initialize clipboard.js
new ClipboardJS('.copy-btn');

// Usage chart
const ctx = document.getElementById('usageChart').getContext('2d');
const usageData = <?php echo json_encode($usageStats); ?>;

new Chart(ctx, {
    type: 'line',
    data: {
        labels: usageData.map(d => d.date),
        datasets: [{
            label: 'Requests',
            data: usageData.map(d => d.requests),
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// API Key management functions
async function createApiKey(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('/api/v1/developer/keys', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(Object.fromEntries(formData))
        });
        
        if (response.ok) {
            location.reload();
        } else {
            alert('Failed to create API key');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to create API key');
    }
}

async function toggleApiKey(id) {
    if (!confirm('Are you sure you want to toggle this API key?')) return;
    
    try {
        const response = await fetch(`/api/v1/developer/keys/${id}/toggle`, {
            method: 'POST'
        });
        
        if (response.ok) {
            location.reload();
        } else {
            alert('Failed to toggle API key');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to toggle API key');
    }
}

async function revokeApiKey(id) {
    if (!confirm('Are you sure you want to revoke this API key? This action cannot be undone.')) return;
    
    try {
        const response = await fetch(`/api/v1/developer/keys/${id}`, {
            method: 'DELETE'
        });
        
        if (response.ok) {
            location.reload();
        } else {
            alert('Failed to revoke API key');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to revoke API key');
    }
}
</script> 