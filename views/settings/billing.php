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
                    <a class="list-group-item list-group-item-action" href="index.php?page=settings&section=password">
                        <i class="fas fa-lock me-2"></i> Change Password
                    </a>
                    <a class="list-group-item list-group-item-action active" href="index.php?page=settings&section=billing">
                        <i class="fas fa-credit-card me-2"></i> Billing Settings
                    </a>
                    <a class="list-group-item list-group-item-action" href="index.php?page=settings&section=notifications">
                        <i class="fas fa-bell me-2"></i> Notifications
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Billing Content -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Billing Settings</h5>
                </div>
                <div class="card-body">
                    <?php if ($billingSuccess): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i> Your billing settings have been updated successfully.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($billingError): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $billingError; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <h6 class="mb-3">Auto Top-up</h6>
                    <p class="text-muted mb-3">
                        When enabled, we'll automatically purchase credits when your balance falls below 100 credits.
                        This ensures you never run out of credits during important calls.
                    </p>
                    
                    <form method="post" action="index.php?page=settings&section=billing">
                        <?php $token = generateCsrfToken(); ?>
                        <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                        <input type="hidden" name="action" value="update_auto_topup">
                        
                        <div class="mb-3 form-check">
                            <input class="form-check-input" type="checkbox" id="enable_auto_topup" name="enable_auto_topup" 
                                   <?php echo $user['auto_topup'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="enable_auto_topup">
                                Enable automatic credit purchase
                            </label>
                        </div>
                        
                        <div id="topupPackageContainer" class="mb-3 <?php echo !$user['auto_topup'] ? 'd-none' : ''; ?>">
                            <label for="topup_package" class="form-label">Select a credit package for auto top-up</label>
                            <select class="form-select" id="topup_package" name="topup_package">
                                <?php foreach ($creditPackages as $package): ?>
                                    <option value="<?php echo $package['id']; ?>" 
                                            <?php echo $user['topup_package'] == $package['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($package['name']); ?> - 
                                        <?php echo formatCredits($package['credits']); ?> credits for 
                                        <?php echo formatCurrency($package['price']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Billing Settings
                        </button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <h6 class="mb-3">Payment Methods</h6>
                    <p>
                        <a href="index.php?page=payment-methods" class="btn btn-outline-primary">
                            <i class="fas fa-credit-card me-2"></i> Manage Payment Methods
                        </a>
                    </p>
                    
                    <hr class="my-4">
                    
                    <h6 class="mb-3">Billing History</h6>
                    <p>
                        <a href="index.php?page=payment-history" class="btn btn-outline-primary">
                            <i class="fas fa-history me-2"></i> View Billing History
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize auto top-up toggle
console.log('Billing page loaded');
document.addEventListener('DOMContentLoaded', function() {
    const autoTopupCheckbox = document.getElementById('enable_auto_topup');
    const topupPackageContainer = document.getElementById('topupPackageContainer');
    
    if (autoTopupCheckbox && topupPackageContainer) {
        console.log('Auto top-up elements found');
        
        autoTopupCheckbox.addEventListener('change', function() {
            console.log('Auto top-up checkbox changed:', this.checked);
            if (this.checked) {
                topupPackageContainer.classList.remove('d-none');
            } else {
                topupPackageContainer.classList.add('d-none');
            }
        });
        
        const billingForm = document.querySelector('form[action*="section=billing"]');
        if (billingForm) {
            console.log('Billing form found');
            billingForm.addEventListener('submit', function(e) {
                console.log('Billing form submitted');
            });
        } else {
            console.error('Billing form not found!');
        }
    } else {
        console.error('Auto top-up elements not found!');
    }
});
</script> 