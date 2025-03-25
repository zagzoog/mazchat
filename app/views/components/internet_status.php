<?php
if (!defined('ADMIN_PANEL')) {
    die('Direct access not permitted');
}
?>

<div id="internet-status" class="alert alert-warning alert-dismissible fade show d-none" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i>
    لا يوجد اتصال بالإنترنت. يرجى التحقق من اتصالك والمحاولة مرة أخرى.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusElement = document.getElementById('internet-status');
    
    function updateOnlineStatus() {
        if (!navigator.onLine) {
            statusElement.classList.remove('d-none');
        } else {
            statusElement.classList.add('d-none');
        }
    }

    // Initial check
    updateOnlineStatus();

    // Listen for online/offline events
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
});
</script> 