<div class="container py-4">
    <div class="row">
        <div class="col-12 mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php?page=settings&section=billing">Billing Settings</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Payment Methods</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Manage Payment Methods</h5>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php if (isset($debug_message)): ?>
                            <div class="alert alert-warning">
                                <strong>Technical information:</strong> <?php echo $debug_message; ?>
                                <hr>
                                <p>For server administrators: Run the following SQL command in phpMyAdmin or your database manager:</p>
                                <pre class="bg-light p-2 mt-2"><code>ALTER TABLE users ADD COLUMN stripe_customer_id VARCHAR(100) NULL AFTER credits;</code></pre>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <p class="text-muted mb-4">
                        Manage your payment methods for automatic credit purchases when your balance falls below 100 credits.
                        Your payment information is securely stored with our payment provider and is not saved on our servers.
                    </p>
                    
                    <!-- Existing Payment Methods -->
                    <h6 class="mb-3">Your Payment Methods</h6>
                    
                    <?php if (empty($paymentMethods)): ?>
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i> You don't have any payment methods saved yet. Add a card below to enable automatic credit purchases.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive mb-4">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Card</th>
                                        <th>Expiration</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($paymentMethods as $method): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php 
                                                        $brandIcon = 'fa-credit-card';
                                                        switch ($method->card->brand) {
                                                            case 'visa':
                                                                $brandIcon = 'fa-cc-visa';
                                                                break;
                                                            case 'mastercard':
                                                                $brandIcon = 'fa-cc-mastercard';
                                                                break;
                                                            case 'amex':
                                                                $brandIcon = 'fa-cc-amex';
                                                                break;
                                                            case 'discover':
                                                                $brandIcon = 'fa-cc-discover';
                                                                break;
                                                        }
                                                    ?>
                                                    <i class="fab <?php echo $brandIcon; ?> fa-lg me-2"></i>
                                                    <span>•••• <?php echo $method->card->last4; ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo $method->card->exp_month; ?>/<?php echo $method->card->exp_year; ?></td>
                                            <td>
                                                <?php if ($defaultPaymentMethod === $method->id): ?>
                                                    <span class="badge bg-success">Default</span>
                                                <?php else: ?>
                                                    <form method="post" class="d-inline">
                                                        <?php $token = generateCsrfToken(); ?>
                                                        <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                                                        <input type="hidden" name="action" value="set_default_payment_method">
                                                        <input type="hidden" name="payment_method_id" value="<?php echo $method->id; ?>">
                                                        <button type="submit" class="btn btn-sm btn-link p-0">Make Default</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($defaultPaymentMethod !== $method->id): ?>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this payment method?');">
                                                        <?php $token = generateCsrfToken(); ?>
                                                        <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                                                        <input type="hidden" name="action" value="remove_payment_method">
                                                        <input type="hidden" name="payment_method_id" value="<?php echo $method->id; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash-alt"></i> Remove
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Add New Payment Method -->
                    <h6 class="mb-3">Add a Payment Method</h6>
                    
                    <?php if ($setupIntent): ?>
                        <div class="card mb-4">
                            <div class="card-body">
                                <form id="payment-form" class="needs-validation" novalidate>
                                    <div class="mb-3">
                                        <label for="cardholder-name" class="form-label">Cardholder Name</label>
                                        <input type="text" class="form-control" id="cardholder-name" placeholder="Cardholder Name" required>
                                        <div class="invalid-feedback">
                                            Please enter the name on your card.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="card-element" class="form-label">Credit or Debit Card</label>
                                        <div id="card-element" class="form-control"></div>
                                        <div id="card-errors" class="text-danger mt-2" role="alert"></div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary" id="submit-button">
                                        <i class="fas fa-credit-card me-2"></i> Add Payment Method
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="text-center mt-4">
                        <a href="index.php?page=settings&section=billing" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Back to Billing Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($setupIntent): ?>
<!-- Include Stripe.js directly here to ensure it's loaded -->
<script src="https://js.stripe.com/v3/"></script>
<script>
// Utility function to log to both console and visible page for debugging
function debugLog(message, isError = false) {
    // Always log to console
    if (isError) {
        console.error(message);
    } else {
        console.log(message);
    }
    
    // Create or update a debug div on the page
    let debugDiv = document.getElementById('stripe-debug-log');
    if (!debugDiv) {
        debugDiv = document.createElement('div');
        debugDiv.id = 'stripe-debug-log';
        debugDiv.className = 'alert alert-info mt-4 p-2';
        debugDiv.style.maxHeight = '200px';
        debugDiv.style.overflow = 'auto';
        debugDiv.style.fontSize = '12px';
        debugDiv.style.fontFamily = 'monospace';
        debugDiv.style.whiteSpace = 'pre-wrap';
        debugDiv.style.display = 'none'; // Hidden by default
        
        // Add toggle button for showing/hiding debug info
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'btn btn-sm btn-outline-secondary mb-2';
        toggleBtn.textContent = 'Show Debug Info';
        toggleBtn.onclick = function() {
            if (debugDiv.style.display === 'none') {
                debugDiv.style.display = 'block';
                this.textContent = 'Hide Debug Info';
            } else {
                debugDiv.style.display = 'none';
                this.textContent = 'Show Debug Info';
            }
        };
        
        // Add to page
        const formElement = document.getElementById('payment-form');
        if (formElement && formElement.parentNode) {
            formElement.parentNode.appendChild(toggleBtn);
            formElement.parentNode.appendChild(debugDiv);
        }
    }
    
    // Add message with timestamp
    const timestamp = new Date().toISOString().split('T')[1].split('.')[0];
    const msgElement = document.createElement('div');
    msgElement.className = isError ? 'text-danger' : '';
    msgElement.textContent = `[${timestamp}] ${message}`;
    debugDiv.appendChild(msgElement);
    
    // Scroll to bottom
    debugDiv.scrollTop = debugDiv.scrollHeight;
}

// Detect if Stripe.js loaded successfully
if (typeof Stripe === 'undefined') {
    debugLog('Stripe.js failed to load. This will prevent payment method addition.', true);
    
    // Add visible error message
    document.addEventListener('DOMContentLoaded', function() {
        const cardBody = document.querySelector('.card-body');
        if (cardBody) {
            const errorAlert = document.createElement('div');
            errorAlert.className = 'alert alert-danger mt-3';
            errorAlert.innerHTML = 'Error: Stripe.js failed to load. Please refresh the page or try again later.';
            cardBody.appendChild(errorAlert);
        }
    });
} else {
    document.addEventListener('DOMContentLoaded', function() {
        debugLog('DOM fully loaded, payment methods page ready');
        
        try {
            // Check if we have a valid public key
            const stripeKey = '<?php echo $stripePublishableKey; ?>';
            if (!stripeKey || stripeKey.trim() === '') {
                throw new Error('Missing Stripe publishable key');
            }
            debugLog(`Found publishable key: ${stripeKey.substring(0, 8)}...`);
            
            // Create a Stripe client
            const stripe = Stripe(stripeKey);
            debugLog('Stripe client initialized successfully');
            
            // Check for client secret
            const clientSecret = '<?php echo $setupIntent->client_secret ?? ''; ?>';
            if (!clientSecret) {
                throw new Error('Missing setupIntent client secret');
            }
            debugLog('Setup intent client secret available');
            
            // Create an instance of Elements
            const elements = stripe.elements();
            debugLog('Stripe Elements created successfully');
            
            // Create and mount the Card Element
            const cardElement = elements.create('card', {
                style: {
                    base: {
                        fontSize: '16px',
                        color: '#495057',
                        fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                        '::placeholder': {
                            color: '#6c757d',
                        },
                    },
                },
            });
            
            // Find the card element container
            const cardElementContainer = document.getElementById('card-element');
            if (!cardElementContainer) {
                throw new Error('Card element container not found in DOM');
            }
            
            cardElement.mount('#card-element');
            debugLog('Card element mounted to DOM successfully');
            
            // Handle real-time validation errors from the card Element
            cardElement.on('change', function(event) {
                const displayError = document.getElementById('card-errors');
                if (displayError) {
                    if (event.error) {
                        displayError.textContent = event.error.message;
                        debugLog(`Card validation error: ${event.error.message}`, true);
                    } else {
                        displayError.textContent = '';
                        if (event.complete) {
                            debugLog('Card details complete and valid');
                        }
                    }
                }
            });
            
            // Handle form submission
            const form = document.getElementById('payment-form');
            const submitButton = document.getElementById('submit-button');
            
            if (form && submitButton) {
                debugLog('Form and submit button found, adding click handler');
                
                // Force button to be clickable
                submitButton.disabled = false;
                submitButton.style.pointerEvents = 'auto';
                submitButton.style.cursor = 'pointer';
                
                // Add click event directly to the button to ensure it's clickable
                submitButton.addEventListener('click', function(event) {
                    debugLog('Submit button clicked');
                });
                
                form.addEventListener('submit', function(event) {
                    debugLog('Form submission started');
                    event.preventDefault();
                    
                    if (!form.checkValidity()) {
                        event.stopPropagation();
                        form.classList.add('was-validated');
                        debugLog('Form validation failed', true);
                        return;
                    }
                    
                    // Disable the submit button to prevent repeated clicks
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Processing...';
                    debugLog('Form is valid, proceeding with Stripe setup');
                    
                    const cardholderName = document.getElementById('cardholder-name').value;
                    
                    // Confirm the SetupIntent
                    debugLog('Confirming card setup with Stripe...');
                    stripe.confirmCardSetup(
                        clientSecret,
                        {
                            payment_method: {
                                card: cardElement,
                                billing_details: {
                                    name: cardholderName
                                }
                            }
                        }
                    ).then(function(result) {
                        if (result.error) {
                            // Show error to user
                            debugLog(`Stripe setup error: ${result.error.message}`, true);
                            const errorElement = document.getElementById('card-errors');
                            if (errorElement) {
                                errorElement.textContent = result.error.message;
                            }
                            
                            // Re-enable the submit button
                            submitButton.disabled = false;
                            submitButton.innerHTML = '<i class="fas fa-credit-card me-2"></i> Add Payment Method';
                        } else {
                            // The setup was successful, redirect to the same page to show the new payment method
                            debugLog('Payment method setup successful, redirecting...');
                            window.location.href = 'index.php?page=payment-methods&success=1';
                        }
                    }).catch(function(error) {
                        debugLog(`Unexpected error during Stripe setup: ${error.message}`, true);
                        const errorElement = document.getElementById('card-errors');
                        if (errorElement) {
                            errorElement.textContent = 'An unexpected error occurred. Please try again.';
                        }
                        
                        // Re-enable the submit button
                        submitButton.disabled = false;
                        submitButton.innerHTML = '<i class="fas fa-credit-card me-2"></i> Add Payment Method';
                    });
                });
            } else {
                debugLog('Form or submit button not found in the DOM!', true);
                // Add visible error message for debugging
                const cardBody = document.querySelector('.card-body');
                if (cardBody) {
                    const errorAlert = document.createElement('div');
                    errorAlert.className = 'alert alert-danger mt-3';
                    errorAlert.innerHTML = 'Error: Payment form elements not found. Please contact support.';
                    cardBody.appendChild(errorAlert);
                }
            }
        } catch (error) {
            debugLog(`Error initializing Stripe: ${error.message}`, true);
            // Add visible error message
            const cardBody = document.querySelector('.card-body');
            if (cardBody) {
                const errorAlert = document.createElement('div');
                errorAlert.className = 'alert alert-danger mt-3';
                errorAlert.innerHTML = 'Error initializing payment system: ' + error.message;
                cardBody.appendChild(errorAlert);
            }
        }
    });
}
</script>
<?php endif; ?> 