document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('n8n-settings-form');
    if (!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = 'Saving...';
        
        try {
            const formData = new FormData(form);
            
            const response = await fetch('<?php echo getFullUrlPath("plugins/N8nWebhookHandler/admin_handler.php"); ?>', {
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

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    const form = document.getElementById('n8n-settings-form');
    form.parentNode.insertBefore(alertDiv, form);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
} 