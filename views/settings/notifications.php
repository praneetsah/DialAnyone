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
                    <a class="list-group-item list-group-item-action" href="index.php?page=settings&section=billing">
                        <i class="fas fa-credit-card me-2"></i> Billing Settings
                    </a>
                    <a class="list-group-item list-group-item-action active" href="index.php?page=settings&section=notifications">
                        <i class="fas fa-bell me-2"></i> Notifications
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Notifications Content -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Notification Settings</h5>
                </div>
                <div class="card-body">
                    <?php if ($notificationSuccess): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i> Your notification settings have been updated successfully.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($notificationError): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $notificationError; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="index.php?page=settings&section=notifications">
                        <?php $token = generateCsrfToken(); ?>
                        <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                        <input type="hidden" name="action" value="update_notifications">
                        
                        <div class="mb-4">
                            <h6 class="mb-3">Email Notifications</h6>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="email_low_credits" 
                                       name="notifications[email_low_credits]" 
                                       <?php echo ($user['notification_settings']['email_low_credits'] ?? true) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="email_low_credits">
                                    Low credit balance alerts
                                </label>
                                <div class="form-text">Get notified when your credit balance falls below 100 credits</div>
                            </div>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="email_payment_confirmation" 
                                       name="notifications[email_payment_confirmation]" 
                                       <?php echo ($user['notification_settings']['email_payment_confirmation'] ?? true) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="email_payment_confirmation">
                                    Payment confirmations
                                </label>
                                <div class="form-text">Receive email receipts when you make a purchase</div>
                            </div>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="email_account_activity" 
                                       name="notifications[email_account_activity]" 
                                       <?php echo ($user['notification_settings']['email_account_activity'] ?? true) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="email_account_activity">
                                    Account activity updates
                                </label>
                                <div class="form-text">Get notified about important account changes</div>
                            </div>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="email_marketing" 
                                       name="notifications[email_marketing]" 
                                       <?php echo ($user['notification_settings']['email_marketing'] ?? false) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="email_marketing">
                                    Special offers and promotions
                                </label>
                                <div class="form-text">Receive information about new features and special offers</div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h6 class="mb-3">SMS Notifications</h6>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="sms_low_credits" 
                                       name="notifications[sms_low_credits]" 
                                       <?php echo ($user['notification_settings']['sms_low_credits'] ?? false) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="sms_low_credits">
                                    Low credit balance alerts (SMS)
                                </label>
                                <div class="form-text">Get SMS notifications when your credit balance is low</div>
                            </div>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="sms_payment_confirmation" 
                                       name="notifications[sms_payment_confirmation]" 
                                       <?php echo ($user['notification_settings']['sms_payment_confirmation'] ?? false) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="sms_payment_confirmation">
                                    Payment confirmations (SMS)
                                </label>
                                <div class="form-text">Receive SMS confirmations when you make a purchase</div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Notification Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div> 