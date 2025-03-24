<?php
if (!defined('ADMIN_PANEL')) {
    die('Direct access not permitted');
}

$pluginManager = PluginManager::getInstance();
$activePlugins = $pluginManager->getActivePlugins();
$allPlugins = $pluginManager->getAllPlugins();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2>Plugin Marketplace</h2>
            <p class="text-muted">Browse, install, and manage plugins for your chat application.</p>
        </div>
        <div class="col-auto">
            <a href="admin/plugin_developer_guide.php" class="btn btn-info me-2">
                <i class="fas fa-book"></i> Developer Guide
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPluginModal">
                <i class="fas fa-plus"></i> Add New Plugin
            </button>
        </div>
    </div>

    <!-- Active Plugins Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Active Plugins</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Plugin Name</th>
                            <th>Version</th>
                            <th>Description</th>
                            <th>Author</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activePlugins as $plugin): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($plugin->getName()); ?></td>
                            <td><?php echo htmlspecialchars($plugin->getVersion()); ?></td>
                            <td><?php echo htmlspecialchars($plugin->getDescription()); ?></td>
                            <td><?php echo htmlspecialchars($plugin->getAuthor()); ?></td>
                            <td>
                                <button type="button" 
                                        class="btn btn-sm btn-danger deactivate-plugin" 
                                        data-plugin="<?php echo htmlspecialchars($plugin->getName()); ?>">
                                    Deactivate
                                </button>
                                <button type="button" 
                                        class="btn btn-sm btn-info view-settings" 
                                        data-plugin="<?php echo htmlspecialchars($plugin->getName()); ?>">
                                    Settings
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Available Plugins Section -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Available Plugins</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($allPlugins as $plugin): ?>
                    <?php if (!isset($activePlugins[$plugin->getName()])): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($plugin->getName()); ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted">v<?php echo htmlspecialchars($plugin->getVersion()); ?></h6>
                                <p class="card-text"><?php echo htmlspecialchars($plugin->getDescription()); ?></p>
                                <p class="card-text"><small class="text-muted">By <?php echo htmlspecialchars($plugin->getAuthor()); ?></small></p>
                                <button type="button" 
                                        class="btn btn-success activate-plugin" 
                                        data-plugin="<?php echo htmlspecialchars($plugin->getName()); ?>">
                                    Activate
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Plugin Modal -->
<div class="modal fade" id="addPluginModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Plugin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addPluginForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="pluginFile" class="form-label">Plugin ZIP File</label>
                        <input type="file" class="form-control" id="pluginFile" name="plugin_file" accept=".zip" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="uploadPlugin">Upload & Install</button>
            </div>
        </div>
    </div>
</div>

<!-- Plugin Settings Modal -->
<div class="modal fade" id="pluginSettingsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Plugin Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="pluginSettingsContent">
                <!-- Settings content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle plugin activation
    document.querySelectorAll('.activate-plugin').forEach(button => {
        button.addEventListener('click', function() {
            const pluginName = this.dataset.plugin;
            if (confirm('Are you sure you want to activate this plugin?')) {
                fetch('admin/activate_plugin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ plugin: pluginName })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error activating plugin: ' + data.message);
                    }
                });
            }
        });
    });
    
    // Handle plugin deactivation
    document.querySelectorAll('.deactivate-plugin').forEach(button => {
        button.addEventListener('click', function() {
            const pluginName = this.dataset.plugin;
            if (confirm('Are you sure you want to deactivate this plugin?')) {
                fetch('admin/deactivate_plugin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ plugin: pluginName })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deactivating plugin: ' + data.message);
                    }
                });
            }
        });
    });
    
    // Handle plugin settings view
    document.querySelectorAll('.view-settings').forEach(button => {
        button.addEventListener('click', function() {
            const pluginName = this.dataset.plugin;
            const modal = new bootstrap.Modal(document.getElementById('pluginSettingsModal'));
            
            fetch(`admin/get_plugin_settings.php?plugin=${encodeURIComponent(pluginName)}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('pluginSettingsContent').innerHTML = html;
                    modal.show();
                });
        });
    });
    
    // Handle plugin upload
    document.getElementById('uploadPlugin').addEventListener('click', function() {
        const form = document.getElementById('addPluginForm');
        const formData = new FormData(form);
        
        fetch('admin/upload_plugin.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error uploading plugin: ' + data.message);
            }
        });
    });
});
</script> 