<?php
require_once __DIR__ . '/../../models/ApiKey.php';
require_once __DIR__ . '/../../utils/Logger.php';

$apiKeys = ApiKey::where('user_id', $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer Portal - Chat App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../components/navbar.php'; ?>

    <div class="container mt-4">
        <h1>Developer Portal</h1>
        
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">API Keys</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createKeyModal">
                            <i class="fas fa-plus"></i> Create New API Key
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Key</th>
                                        <th>Created</th>
                                        <th>Last Used</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="apiKeysList">
                                    <?php foreach ($apiKeys as $key): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($key['name']); ?></td>
                                        <td>
                                            <div class="input-group">
                                                <input type="text" class="form-control" value="<?php echo $key['api_key']; ?>" readonly>
                                                <button class="btn btn-outline-secondary copy-btn" type="button" data-key="<?php echo $key['api_key']; ?>">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($key['created_at'])); ?></td>
                                        <td><?php echo $key['last_used_at'] ? date('Y-m-d H:i', strtotime($key['last_used_at'])) : 'Never'; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $key['is_active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $key['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning toggle-key" data-id="<?php echo $key['id']; ?>">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-key" data-id="<?php echo $key['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Links</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item">
                                <a href="/api/docs" target="_blank" class="text-decoration-none">
                                    <i class="fas fa-book"></i> API Documentation
                                </a>
                            </li>
                            <li class="list-group-item">
                                <a href="/admin/plugin_marketplace.php" class="text-decoration-none">
                                    <i class="fas fa-store"></i> Plugin Marketplace
                                </a>
                            </li>
                            <li class="list-group-item">
                                <a href="https://github.com/yourusername/chat-app" target="_blank" class="text-decoration-none">
                                    <i class="fab fa-github"></i> GitHub Repository
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create API Key Modal -->
    <div class="modal fade" id="createKeyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New API Key</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createKeyForm">
                        <div class="mb-3">
                            <label for="keyName" class="form-label">Key Name</label>
                            <input type="text" class="form-control" id="keyName" required>
                        </div>
                        <div class="mb-3">
                            <label for="keyDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="keyDescription" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="createKeyBtn">Create</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Copy API Key
            document.querySelectorAll('.copy-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const key = this.dataset.key;
                    navigator.clipboard.writeText(key).then(() => {
                        alert('API Key copied to clipboard!');
                    });
                });
            });

            // Create API Key
            document.getElementById('createKeyBtn').addEventListener('click', function() {
                const name = document.getElementById('keyName').value;
                const description = document.getElementById('keyDescription').value;

                fetch('/api/developer/keys.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ name, description })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error creating API key: ' + data.message);
                    }
                });
            });

            // Toggle API Key
            document.querySelectorAll('.toggle-key').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;
                    fetch('/api/developer/keys.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ action: 'toggle', id })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        }
                    });
                });
            });

            // Delete API Key
            document.querySelectorAll('.delete-key').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (confirm('Are you sure you want to delete this API key?')) {
                        const id = this.dataset.id;
                        fetch('/api/developer/keys.php', {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ id })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>
</html> 