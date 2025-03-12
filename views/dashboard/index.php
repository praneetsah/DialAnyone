<div class="container py-4">
    <!-- Welcome Banner -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h4>
                            <p class="mb-0">Your current balance: <strong><?php echo formatCredits($user['credits']); ?> credits</strong></p>
                        </div>
                        <div>
                            <a href="index.php?page=call" class="btn btn-light me-2">
                                <i class="fas fa-phone"></i> Make a Call
                            </a>
                            <a href="index.php?page=credits" class="btn btn-outline-light">
                                <i class="fas fa-coins"></i> Buy Credits
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($creditsLow): ?>
    <!-- Low Credits Warning -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Your credit balance is low!</strong> You may not be able to make longer calls.
                    </div>
                    <a href="index.php?page=credits" class="btn btn-sm btn-warning">Buy Credits</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Calls</h5>
                    <div class="display-4 mb-2"><?php echo number_format($callStats['total_calls']); ?></div>
                    <p class="text-muted">Calls made from your account</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">Talk Time</h5>
                    <div class="display-4 mb-2"><?php echo $callStats['formatted_duration']; ?></div>
                    <p class="text-muted">Total time spent on calls</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">Credits Used</h5>
                    <div class="display-4 mb-2"><?php echo $callStats['formatted_credits']; ?></div>
                    <p class="text-muted">Credits spent on calls</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Auto Top-up Status -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Auto Top-up Status</h5>
                </div>
                <div class="card-body">
                    <?php if ($autoTopUp['enabled']): ?>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1"><i class="fas fa-check-circle text-success me-2"></i> Auto top-up is <strong>enabled</strong></p>
                                <p class="mb-0">When your credits fall below 100, we'll automatically add <?php echo number_format($autoTopUp['package']['credits']); ?> credits to your account.</p>
                            </div>
                            <a href="index.php?page=settings" class="btn btn-outline-primary">Update Settings</a>
                        </div>
                    <?php else: ?>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1"><i class="fas fa-times-circle text-danger me-2"></i> Auto top-up is <strong>disabled</strong></p>
                                <p class="mb-0">Enable auto top-up to avoid running out of credits during important calls.</p>
                            </div>
                            <a href="index.php?page=settings" class="btn btn-outline-primary">Enable Now</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <!-- Recent Calls -->
        <div class="col-md-6 mb-4 mb-md-0">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Calls</h5>
                    <a href="index.php?page=call-history" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentCalls)): ?>
                        <div class="p-4 text-center">
                            <p class="text-muted mb-0">You haven't made any calls yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentCalls as $call): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($call['destination_number']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y g:i A', strtotime($call['started_at'])); ?>
                                                • <?php echo ucfirst($call['status']); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <div><?php echo formatDuration($call['duration']); ?></div>
                                            <small class="text-muted"><?php echo formatCredits($call['credits_used']); ?> credits</small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Payments -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Payments</h5>
                    <a href="index.php?page=payment-history" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentPayments)): ?>
                        <div class="p-4 text-center">
                            <p class="text-muted mb-0">You haven't made any payments yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentPayments as $payment): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">
                                                <?php echo formatCredits($payment['credits']); ?> Credits
                                                <?php if ($payment['is_auto_topup']): ?>
                                                    <span class="badge bg-info">Auto Top-up</span>
                                                <?php endif; ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y g:i A', strtotime($payment['created_at'])); ?>
                                                • <?php echo ucfirst($payment['status']); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <div><?php echo formatCurrency($payment['amount']); ?></div>
                                            <?php if ($payment['discount_amount'] > 0): ?>
                                                <small class="text-success">
                                                    <?php echo formatCurrency($payment['discount_amount']); ?> discount
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div> 