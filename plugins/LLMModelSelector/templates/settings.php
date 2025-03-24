<?php
// Ensure this file is included within the admin panel
if (!defined('ADMIN_PANEL')) {
    die('Direct access not permitted');
}
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">LLM Model Settings</h3>
    </div>
    <div class="card-body">
        <form method="post" action="admin/update_llm_settings.php">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Model Name</th>
                            <th>Provider</th>
                            <th>API Key</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($models as $model): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($model['name']); ?></td>
                            <td><?php echo htmlspecialchars($model['provider']); ?></td>
                            <td>
                                <input type="password" 
                                       name="api_key[<?php echo $model['id']; ?>]" 
                                       value="<?php echo htmlspecialchars($model['api_key']); ?>"
                                       class="form-control">
                            </td>
                            <td>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" 
                                           class="custom-control-input" 
                                           id="model_<?php echo $model['id']; ?>"
                                           name="is_active[<?php echo $model['id']; ?>]"
                                           <?php echo $model['is_active'] ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="model_<?php echo $model['id']; ?>">
                                        <?php echo $model['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </label>
                                </div>
                            </td>
                            <td>
                                <button type="button" 
                                        class="btn btn-sm btn-danger delete-model" 
                                        data-id="<?php echo $model['id']; ?>">
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" class="btn btn-success" id="add-model">Add New Model</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle model deletion
    document.querySelectorAll('.delete-model').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this model?')) {
                const modelId = this.dataset.id;
                fetch('admin/delete_llm_model.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: modelId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.closest('tr').remove();
                    } else {
                        alert('Error deleting model: ' + data.message);
                    }
                });
            }
        });
    });
    
    // Handle adding new model
    document.getElementById('add-model').addEventListener('click', function() {
        const tbody = document.querySelector('table tbody');
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td><input type="text" name="new_model[name]" class="form-control" required></td>
            <td><input type="text" name="new_model[provider]" class="form-control" required></td>
            <td><input type="password" name="new_model[api_key]" class="form-control" required></td>
            <td>
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" name="new_model[is_active]" checked>
                    <label class="custom-control-label">Active</label>
                </div>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger remove-new-model">Remove</button>
            </td>
        `;
        tbody.appendChild(newRow);
        
        // Handle removing new model row
        newRow.querySelector('.remove-new-model').addEventListener('click', function() {
            newRow.remove();
        });
    });
});
</script> 