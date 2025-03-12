<div class="container py-4">
    <div class="row">
        <!-- Settings Navigation -->
        <div class="col-md-3 mb-4 mb-md-0">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Settings</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a class="list-group-item list-group-item-action active" href="index.php?page=settings&section=profile">
                        <i class="fas fa-user me-2"></i> Profile Information
                    </a>
                    <a class="list-group-item list-group-item-action" href="index.php?page=settings&section=password">
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
        
        <!-- Profile Content -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($updateSuccess): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i> Your profile has been updated successfully.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($updateError): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $updateError; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="index.php?page=settings&section=profile">
                        <?php $token = generateCsrfToken(); ?>
                        <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   <?php echo ($user['is_email_verified']) ? 'readonly' : ''; ?> required>
                            <?php if ($user['is_email_verified']): ?>
                            <div class="form-text">
                                <i class="fas fa-check-circle text-success me-1"></i> Email verified. Email address cannot be changed once verified.
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" 
                                   value="<?php echo htmlspecialchars($user['phone']); ?>" disabled>
                            <div class="form-text">
                                Phone number cannot be changed once verified.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Account Status</label>
                            <p class="mb-0">
                                <?php if ($user['status'] === 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php elseif ($user['status'] === 'suspended'): ?>
                                    <span class="badge bg-warning">Suspended</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?php echo ucfirst($user['status']); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Member Since</label>
                            <p class="mb-0">
                                <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                                (<?php echo timeAgo($user['created_at']); ?>)
                            </p>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Add debug logging
console.log('Profile page loaded');
document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.querySelector('form[action*="section=profile"]');
    if (profileForm) {
        console.log('Profile form found');
        profileForm.addEventListener('submit', function(e) {
            console.log('Profile form submitted');
        });
    } else {
        console.error('Profile form not found!');
    }
});
</script> 