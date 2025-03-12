<div class="container py-4">
    <div class="row">
        <!-- Call Interface -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Make a Call</h5>
                </div>
                <div class="card-body">
                    <?php if (!$hasCredits): ?>
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-coins text-warning" style="font-size: 4rem;"></i>
                            </div>
                            <h3 class="mb-3">Insufficient Credits</h3>
                            <p class="lead mb-4">You need to add credits to your account before you can make calls.</p>
                            <a href="index.php?page=credits" class="btn btn-primary btn-lg">
                                <i class="fas fa-shopping-cart me-2"></i>Buy Credits
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Phone Display -->
                        <div class="mb-3">
                            <input type="tel" id="phone-number" class="form-control form-control-lg text-center" 
                                   placeholder="Enter phone number with country code" 
                                   pattern="[0-9+*#]+"
                                   autocomplete="off">
                            <div class="text-center mt-2">
                                <div class="alert alert-info py-2">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Important:</strong> Always include the country code (e.g., +1 for US, +44 for UK) for all calls
                                </div>
                            </div>
                        </div>
                        
                        <!-- Dialer Pad -->
                        <div class="dialer-pad mb-4">
                            <button class="dialer-btn" data-digit="1">1</button>
                            <button class="dialer-btn" data-digit="2">2</button>
                            <button class="dialer-btn" data-digit="3">3</button>
                            <button class="dialer-btn" data-digit="4">4</button>
                            <button class="dialer-btn" data-digit="5">5</button>
                            <button class="dialer-btn" data-digit="6">6</button>
                            <button class="dialer-btn" data-digit="7">7</button>
                            <button class="dialer-btn" data-digit="8">8</button>
                            <button class="dialer-btn" data-digit="9">9</button>
                            <button class="dialer-btn" data-digit="*">*</button>
                            <button class="dialer-btn" data-digit="0">0</button>
                            <button class="dialer-btn" data-digit="#">#</button>
                            <button id="btn-backspace" class="dialer-btn action-btn" title="Backspace">
                                <i class="fas fa-backspace"></i>
                            </button>
                            <button class="dialer-btn" data-digit="+">+</button>
                            <button id="btn-clear" class="dialer-btn action-btn" title="Clear">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <!-- Call Controls -->
                        <div class="call-controls d-flex justify-content-center">
                            <button id="btn-call" class="btn btn-success btn-lg call-btn full-width">
                                <i class="fas fa-phone-alt"></i> <span class="call-text">Call</span>
                            </button>
                            <button id="btn-hangup" class="btn btn-danger btn-lg call-btn full-width" style="display: none;">
                                <i class="fas fa-phone-slash"></i> Hang Up
                            </button>
                            <button id="btn-mute" class="btn btn-outline-secondary" style="display: none;" title="Mute">
                                <i class="fas fa-microphone-slash"></i>
                            </button>
                        </div>
                        
                        <!-- Call Status -->
                        <div id="call-status" class="call-status mt-4 text-center"></div>
                        
                        <!-- Credit Info -->
                        <div class="mt-4 text-center">
                            <p>Available Credits: <strong class="credits-display"><?php echo formatCredits($userCredits); ?></strong></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Calls and Info -->
        <div class="col-lg-4">
            <!-- Recent Calls -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Recent Calls</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentCalls)): ?>
                        <div class="p-3 text-center">
                            <p class="text-muted mb-0">No recent calls found.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentCalls as $call): ?>
                                <a href="#" class="list-group-item list-group-item-action recent-call-item" data-number="<?php echo htmlspecialchars($call['destination_number']); ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($call['destination_number']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo date('M j, g:i A', strtotime($call['started_at'])); ?>
                                            </small>
                                        </div>
                                        <div>
                                            <i class="fas fa-phone-alt text-primary"></i>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Call Tips -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Calling Tips</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li class="mb-2">For international calls, include the country code (e.g., +1 for US).</li>
                        <li class="mb-2">Calls are charged per minute based on destination rates.</li>
                        <li class="mb-2">Ensure you have sufficient credits before starting a call.</li>
                        <li>Use headphones for better call quality.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($hasCredits): ?>
<!-- Hidden Twilio Token for JavaScript -->
<input type="hidden" id="twilio-token" value="<?php echo htmlspecialchars($twilioToken); ?>">
<input type="hidden" id="user-id" value="<?php echo htmlspecialchars($userId); ?>">
<input type="hidden" id="twilio-phone" value="<?php echo defined('TWILIO_PHONE_NUMBER') ? htmlspecialchars(TWILIO_PHONE_NUMBER) : ''; ?>">

<style>
    .dialer-pad {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        max-width: 300px;
        margin: 0 auto;
    }
    
    .dialer-btn {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 50%;
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .dialer-btn:hover {
        background: #e9ecef;
    }
    
    .action-btn {
        font-size: 1.2rem;
    }
    
    .call-status {
        font-size: 1.1rem;
        min-height: 2rem;
    }
    
    /* Enhanced Call Button Styling */
    .btn-call {
        padding: 12px 20px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);
        transition: all 0.15s ease;
    }
    
    .full-width {
        width: 100%;
        max-width: 300px;
        border-radius: 10px;
        margin: 0 auto;
    }
    
    #btn-call {
        background: linear-gradient(to right, #28a745, #20c997);
        border: none;
    }
    
    #btn-call:hover {
        transform: translateY(-1px);
        box-shadow: 0 7px 14px rgba(50, 50, 93, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08);
    }
    
    #btn-call i {
        margin-right: 8px;
    }
    
    .call-text {
        font-size: 18px;
    }
</style>
<?php endif; ?> 