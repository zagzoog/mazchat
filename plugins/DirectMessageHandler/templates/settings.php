<?php
require_once __DIR__ . '/../../../../path_config.php';
// Ensure this file is included within the admin panel
if (!defined('ADMIN_PANEL')) {
    die('Direct access not permitted');
}
?>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Direct Message Handler Settings</h5>
    </div>
    <div class="card-body">
        <form id="direct-message-settings-form" method="POST">
            <input type="hidden" name="action" value="directmessagehandler_settings">
            
            <div class="mb-3">
                <label for="provider" class="form-label">AI Provider</label>
                <select class="form-select" id="provider" name="provider">
                    <option value="openai" <?php echo ($settings['provider'] === 'openai') ? 'selected' : ''; ?>>OpenAI</option>
                    <option value="anthropic" <?php echo ($settings['provider'] === 'anthropic') ? 'selected' : ''; ?>>Anthropic</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="model" class="form-label">Model</label>
                <select class="form-select" id="model" name="model">
                    <!-- OpenAI Models -->
                    <optgroup label="OpenAI Models" class="openai-models">
                        <option value="gpt-4" <?php echo ($settings['model'] === 'gpt-4') ? 'selected' : ''; ?>>GPT-4</option>
                        <option value="gpt-3.5-turbo" <?php echo ($settings['model'] === 'gpt-3.5-turbo') ? 'selected' : ''; ?>>GPT-3.5 Turbo</option>
                    </optgroup>
                    <!-- Anthropic Models -->
                    <optgroup label="Anthropic Models" class="anthropic-models">
                        <option value="claude-2" <?php echo ($settings['model'] === 'claude-2') ? 'selected' : ''; ?>>Claude 2</option>
                        <option value="claude-instant" <?php echo ($settings['model'] === 'claude-instant') ? 'selected' : ''; ?>>Claude Instant</option>
                    </optgroup>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="api_key" class="form-label">API Key</label>
                <input type="password" class="form-control" id="api_key" name="api_key" 
                       value="<?php echo htmlspecialchars($settings['api_key']); ?>" required>
                <div class="form-text">Enter your API key for the selected provider</div>
            </div>
            
            <div class="mb-3">
                <label for="temperature" class="form-label">Temperature</label>
                <input type="number" class="form-control" id="temperature" name="temperature" 
                       value="<?php echo htmlspecialchars($settings['temperature']); ?>" 
                       min="0" max="2" step="0.1">
                <div class="form-text">Controls randomness in responses (0 = deterministic, 2 = most random)</div>
            </div>
            
            <div class="mb-3">
                <label for="max_tokens" class="form-label">Max Tokens</label>
                <input type="number" class="form-control" id="max_tokens" name="max_tokens" 
                       value="<?php echo htmlspecialchars($settings['max_tokens']); ?>" 
                       min="1" max="8000">
                <div class="form-text">Maximum number of tokens in the response</div>
            </div>
            
            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                           <?php echo ($settings['is_active'] ?? true) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_active">Enable Direct Message Handler</label>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('direct-message-settings-form');
    const providerSelect = document.getElementById('provider');
    const modelSelect = document.getElementById('model');
    
    // Function to update visible model options based on selected provider
    function updateModelOptions() {
        const provider = providerSelect.value;
        const openaiModels = modelSelect.querySelector('.openai-models');
        const anthropicModels = modelSelect.querySelector('.anthropic-models');
        
        if (provider === 'openai') {
            openaiModels.style.display = '';
            anthropicModels.style.display = 'none';
            // Select first OpenAI model if current selection is not an OpenAI model
            if (!modelSelect.value.includes('gpt')) {
                modelSelect.value = 'gpt-3.5-turbo';
            }
        } else {
            openaiModels.style.display = 'none';
            anthropicModels.style.display = '';
            // Select first Anthropic model if current selection is not an Anthropic model
            if (!modelSelect.value.includes('claude')) {
                modelSelect.value = 'claude-2';
            }
        }
    }
    
    // Update model options when provider changes
    providerSelect.addEventListener('change', updateModelOptions);
    
    // Initial update of model options
    updateModelOptions();
    
    // Handle form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = 'Saving...';
        
        try {
            const formData = new FormData(form);
            
            const response = await fetch('<?php echo getFullUrlPath("plugins/DirectMessageHandler/admin_handler.php"); ?>', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showAlert('success', 'Settings saved successfully');
            } else {
                showAlert('danger', data.message || 'Failed to save settings');
            }
        } catch (error) {
            showAlert('danger', 'An error occurred while saving settings');
            console.error('Error:', error);
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    });
});
</script> 