<div class="container py-4">
    <div class="row">
        <!-- Settings Navigation -->
        <div class="col-md-3 mb-4 mb-md-0">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Settings</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a class="list-group-item list-group-item-action" href="index.php?page=settings&section=profile">
                        <i class="fas fa-user me-2"></i> Profile Information
                    </a>
                    <a class="list-group-item list-group-item-action active" href="index.php?page=settings&section=password">
                        <i class="fas fa-lock me-2"></i> Change Password
                    </a>
                    <a class="list-group-item list-group-item-action" href="index.php?page=settings&section=billing">
                        <i class="fas fa-credit-card me-2"></i> Billing Settings
                    </a>
                    <a class="list-group-item list-group-item-action" href="index.php?page=settings&section=notifications">
                        <i class="fas fa-bell me-2"></i> Notifications
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Password Content -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <?php if ($passwordSuccess): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i> Your password has been updated successfully.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($passwordError): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $passwordError; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="index.php?page=settings&section=password">
                        <?php $token = generateCsrfToken(); ?>
                        <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                        <input type="hidden" name="action" value="update_password">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="form-text">
                                Must be at least 8 characters long
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key me-2"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Add debug logging
console.log('Password page loaded');
document.addEventListener('DOMContentLoaded', function() {
    const passwordForm = document.querySelector('form[action*="section=password"]');
    if (passwordForm) {
        console.log('Password form found');
        passwordForm.addEventListener('submit', function(e) {
            console.log('Password form submitted');
        });
    } else {
        console.error('Password form not found!');
    }
});
</script> 