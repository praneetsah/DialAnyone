<?php
// Credits purchase view
?>
<div class="container py-4">
    <!-- Direct package selection script - at the top to ensure it's defined before use -->
    <script>
        // This function is called directly from the onclick attribute
        function selectPackage(packageItem) {
            console.log('Direct selection function called for package', packageItem);
            
            try {
                // Clear all selected packages
                var allPackages = document.querySelectorAll('.package-item');
                for (var i = 0; i < allPackages.length; i++) {
                    allPackages[i].classList.remove('selected-package');
                }
                
                // Add selected class to clicked package
                packageItem.classList.add('selected-package');
                
                // Get package data
                var packageId = packageItem.getAttribute('data-package-id');
                var packageName = packageItem.getAttribute('data-name');
                var credits = packageItem.getAttribute('data-credits');
                var price = packageItem.getAttribute('data-price');
                
                console.log('Package selection data:', { packageId, packageName, credits, price });
                
                // Update display elements
                var selectedPackageName = document.getElementById('selectedPackageName');
                var selectedPackageCredits = document.getElementById('selectedPackageCredits');
                var selectedPackagePrice = document.getElementById('selectedPackagePrice');
                var selectedPackageIdInput = document.getElementById('selectedPackageId');
                var totalPrice = document.getElementById('totalPrice');
                var packageDetails = document.getElementById('packageDetails');
                var noPackageSelected = document.getElementById('noPackageSelected');
                var paymentForm = document.getElementById('paymentForm');
                
                // Format price as currency
                function formatPrice(price) {
                    return '$' + parseFloat(price).toFixed(2);
                }
                
                // Update display
                if (selectedPackageName) selectedPackageName.textContent = packageName;
                if (selectedPackageCredits) selectedPackageCredits.textContent = credits + ' credits';
                if (selectedPackagePrice) selectedPackagePrice.textContent = formatPrice(price);
                if (selectedPackageIdInput) selectedPackageIdInput.value = packageId;
                
                // Calculate final price (including any discounts)
                var finalPrice = price; // Default to original price
                var discountElement = document.getElementById('discountAmount');
                
                if (discountElement) {
                    var discountType = discountElement.getAttribute('data-type');
                    var discountValue = parseFloat(discountElement.getAttribute('data-value'));
                    
                    if (discountType && !isNaN(discountValue)) {
                        if (discountType === 'percentage') {
                            var discount = price * (discountValue / 100);
                            discountElement.textContent = '-' + formatPrice(discount);
                            finalPrice = price - discount;
                        } else if (discountType === 'fixed') {
                            var discount = Math.min(discountValue, price);
                            discountElement.textContent = '-' + formatPrice(discount);
                            finalPrice = price - discount;
                        }
                    }
                }
                
                if (totalPrice) totalPrice.textContent = formatPrice(finalPrice);
                
                // Show package details and payment form
                if (packageDetails) packageDetails.classList.remove('d-none');
                if (noPackageSelected) noPackageSelected.classList.add('d-none');
                if (paymentForm) paymentForm.classList.remove('d-none');
                
                console.log('Package details updated successfully by direct selection function');
                
                // Return false to prevent any further event handling
                return false;
            } catch (error) {
                console.error('Error in selectPackage function', error);
            }
        }
    </script>
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <h3 class="card-title">Purchase Credits</h3>
                    <p class="card-text">
                        Credits are used to make outbound calls. Our credits never expire, and you can use them anytime.
                        <br>1000 Credits ~ 400 Minutes of Wordwide Calling.
                    </p>
                    <p class="mb-0">
                        Your current balance: <strong class="text-primary"><?php echo formatCredits($user['credits']); ?> credits</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Credit Packages -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Select a Credit Package</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush package-list">
                        <?php foreach ($creditPackages as $package): ?>
                            <div class="list-group-item package-item" 
                                 data-package-id="<?php echo htmlspecialchars($package['id']); ?>" 
                                 data-credits="<?php echo htmlspecialchars($package['credits']); ?>" 
                                 data-price="<?php echo htmlspecialchars($package['price']); ?>"
                                 data-name="<?php echo htmlspecialchars($package['name']); ?>"
                                 onclick="selectPackage(this)"
                                 role="button" 
                                 tabindex="0">
                                <div class="d-flex justify-content-between align-items-center package-content">
                                    <div class="package-details">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($package['name']); ?></h5>
                                        <p class="mb-0 text-muted"><?php echo htmlspecialchars($package['description']); ?></p>
                                    </div>
                                    <div class="package-pricing text-end">
                                        <div class="credit-amount mb-1"><?php echo formatCredits($package['credits']); ?> credits</div>
                                        <div class="package-price"><?php echo formatCurrency($package['price']); ?></div>
                                        <?php if (isset($package['is_popular']) && $package['is_popular']): ?>
                                            <span class="badge bg-success mt-2">Most Popular</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i> Click on a package to select it
                    </small>
                </div>
            </div>
        </div>

        <!-- Payment Section -->
        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Payment Summary</h5>
                </div>
                <div class="card-body">
                    <div id="packageDetails" class="mb-4 d-none">
                        <h6>Selected Package:</h6>
                        <p id="selectedPackageName" class="mb-1">-</p>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Credits:</span>
                            <span id="selectedPackageCredits">-</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Price:</span>
                            <span id="selectedPackagePrice">-</span>
                        </div>

                        <!-- Coupon Code -->
                        <div class="coupon-section mb-3">
                            <form id="couponForm" method="post" action="index.php?page=credits">
                                <?php $token = generateCsrfToken(); ?>
                                <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                                <input type="hidden" name="action" value="validate_coupon">
                                
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" id="couponCode" name="coupon_code" 
                                           placeholder="Coupon code" value="<?php echo htmlspecialchars($couponCode ?? ''); ?>">
                                    <button class="btn btn-outline-secondary" type="submit" id="applyCouponBtn">Apply</button>
                                </div>
                                
                                <?php if ($couponError): ?>
                                    <div class="text-danger small mb-2"><?php echo $couponError; ?></div>
                                <?php endif; ?>
                                
                                <?php if ($couponDiscount): ?>
                                    <div class="text-success small mb-2">
                                        <i class="fas fa-check-circle me-1"></i> 
                                        Coupon "<?php echo htmlspecialchars($couponDiscount['code']); ?>" applied!
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                        
                        <?php if ($couponDiscount): ?>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Discount:</span>
                                <span id="discountAmount" 
                                      data-type="<?php echo htmlspecialchars($couponDiscount['type']); ?>" 
                                      data-value="<?php echo htmlspecialchars($couponDiscount['value']); ?>">
                                    -
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="fw-bold">Total:</span>
                            <span id="totalPrice" class="fw-bold">-</span>
                        </div>
                    </div>

                    <div id="noPackageSelected" class="text-center py-4">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <p>Please select a credit package</p>
                    </div>

                    <!-- Stripe Payment Form -->
                    <div id="paymentForm" class="d-none">
                        <h6 class="mb-3">Payment Details</h6>
                        
                        <!-- Add Stripe script if not already included in the header -->
                        <script src="https://js.stripe.com/v3/"></script>
                        
                        <div id="cardElement" class="mb-3 p-3 border rounded">
                            <!-- Stripe Card Element will be inserted here -->
                        </div>
                        <div id="cardErrors" class="text-danger small mb-3" role="alert"></div>
                        
                        <input type="hidden" id="selectedPackageId" name="package_id" value="">
                        <input type="hidden" id="appliedCouponCode" name="coupon_code" value="<?php echo htmlspecialchars($couponCode ?? ''); ?>">
                        
                        <button id="payButton" type="button" class="btn btn-primary w-100" onclick="processPayment()">
                            <i class="fas fa-lock me-2"></i> Pay Now
                        </button>
                        
                        <div class="mt-3 small text-muted">
                            <p class="mb-0"><i class="fas fa-shield-alt me-1"></i> Your payment is secured with industry-standard encryption.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Safety & Security Info -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title"><i class="fas fa-shield-alt me-2"></i> Secure Payment</h6>
                    <p class="card-text small mb-0">
                        All payments are securely processed by Stripe. Your card details are never stored on our servers.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Stripe Public Key for JavaScript -->
<input type="hidden" id="stripePublicKey" value="<?php echo htmlspecialchars($stripePublicKey); ?>">
<input type="hidden" id="userId" value="<?php echo htmlspecialchars($user['id']); ?>">

<style>
    /* Package selection styles - Safari compatible */
    .package-item {
        cursor: pointer;
        transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease;
        border-left: 5px solid transparent;
        position: relative;
        margin-bottom: 1px;
        overflow: hidden;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        -webkit-tap-highlight-color: rgba(0,0,0,0); /* Remove mobile tap highlight */
    }
    
    .package-item:hover {
        background-color: #f8f9fa;
        -webkit-transform: translateY(-2px);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    /* Active state for Safari */
    .package-item:active {
        background-color: #e9ecef;
        -webkit-transform: translateY(0);
        transform: translateY(0);
    }
    
    .package-item.selected-package {
        background-color: #f0f9ff !important; /* Force the color even on Safari */
        border-left: 5px solid #0d6efd;
        box-shadow: 0 0 10px rgba(13, 110, 253, 0.15);
    }
    
    .package-item.selected-package::after {
        content: "✓";
        position: absolute;
        right: 15px;
        top: 15px;
        background-color: #0d6efd;
        color: white;
        width: 25px;
        height: 25px;
        line-height: 25px;
        text-align: center;
        border-radius: 50%;
        font-weight: bold;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        z-index: 1; /* Ensure the checkmark appears above other elements */
    }
    
    .package-content {
        padding: 15px 10px;
        /* Make entire content area clickable */
        width: 100%;
        display: block;
    }
    
    .credit-amount {
        font-weight: 600;
        color: #0d6efd;
    }
    
    .package-price {
        font-weight: 700;
        font-size: 1.2rem;
    }
    
    .coupon-section {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-top: 15px;
        border: 1px solid #e9ecef;
    }
    
    /* Stripe element styling - with Safari compatibility */
    #cardElement {
        background-color: white;
        padding: 12px 15px !important;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        -webkit-transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    #cardElement:focus-within {
        color: #212529;
        background-color: #fff;
        border-color: #86b7fe;
        outline: 0;
        -webkit-box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    
    /* Animation for selection - with webkit prefix for Safari */
    @-webkit-keyframes pulse {
        0% {
            -webkit-box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4);
            box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4);
        }
        70% {
            -webkit-box-shadow: 0 0 0 10px rgba(13, 110, 253, 0);
            box-shadow: 0 0 0 10px rgba(13, 110, 253, 0);
        }
        100% {
            -webkit-box-shadow: 0 0 0 0 rgba(13, 110, 253, 0);
            box-shadow: 0 0 0 0 rgba(13, 110, 253, 0);
        }
    }
    
    @keyframes pulse {
        0% {
            -webkit-box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4);
            box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4);
        }
        70% {
            -webkit-box-shadow: 0 0 0 10px rgba(13, 110, 253, 0);
            box-shadow: 0 0 0 10px rgba(13, 110, 253, 0);
        }
        100% {
            -webkit-box-shadow: 0 0 0 0 rgba(13, 110, 253, 0);
            box-shadow: 0 0 0 0 rgba(13, 110, 253, 0);
        }
    }
    
    .package-item.selected-package {
        -webkit-animation: pulse 1s 1;
        animation: pulse 1s 1;
    }
</style> 

<!-- Debug section (hidden in production) -->
<script>
    // Enable this for troubleshooting
    const debugMode = false;
    
    if (debugMode) {
        // Create debug panel
        document.addEventListener('DOMContentLoaded', function() {
            const debugPanel = document.createElement('div');
            debugPanel.style.position = 'fixed';
            debugPanel.style.bottom = '0';
            debugPanel.style.right = '0';
            debugPanel.style.width = '300px';
            debugPanel.style.height = '200px';
            debugPanel.style.backgroundColor = 'rgba(0,0,0,0.8)';
            debugPanel.style.color = '#00ff00';
            debugPanel.style.padding = '10px';
            debugPanel.style.fontFamily = 'monospace';
            debugPanel.style.fontSize = '12px';
            debugPanel.style.overflowY = 'scroll';
            debugPanel.style.zIndex = '9999';
            debugPanel.id = 'debugPanel';
            
            document.body.appendChild(debugPanel);
            
            // Override console.log
            const oldLog = console.log;
            console.log = function() {
                oldLog.apply(console, arguments);
                const debugPanel = document.getElementById('debugPanel');
                if (debugPanel) {
                    const args = Array.from(arguments);
                    const message = args.map(arg => {
                        if (typeof arg === 'object') {
                            return JSON.stringify(arg);
                        }
                        return arg;
                    }).join(' ');
                    
                    const logEntry = document.createElement('div');
                    logEntry.textContent = '> ' + message;
                    debugPanel.appendChild(logEntry);
                    debugPanel.scrollTop = debugPanel.scrollHeight;
                }
            };
            
            // Override console.error
            const oldError = console.error;
            console.error = function() {
                oldError.apply(console, arguments);
                const debugPanel = document.getElementById('debugPanel');
                if (debugPanel) {
                    const args = Array.from(arguments);
                    const message = args.map(arg => {
                        if (typeof arg === 'object') {
                            return JSON.stringify(arg);
                        }
                        return arg;
                    }).join(' ');
                    
                    const logEntry = document.createElement('div');
                    logEntry.textContent = '! ' + message;
                    logEntry.style.color = '#ff4444';
                    debugPanel.appendChild(logEntry);
                    debugPanel.scrollTop = debugPanel.scrollHeight;
                }
            };
            
            console.log('Debug panel initialized');
        });
    }
</script> 

<!-- Safari compatibility script -->
<script>
    // This is a direct fallback script in case the main JavaScript doesn't handle the clicks correctly in Safari
    document.addEventListener('DOMContentLoaded', function() {
        // Get all package items
        var packageItems = document.querySelectorAll('.package-item');
        
        // Safari has issues with event delegation sometimes, so add direct handlers
        for (var i = 0; i < packageItems.length; i++) {
            var item = packageItems[i];
            
            // Add multiple event types for broader compatibility
            item.addEventListener('click', handlePackageClick);
            item.addEventListener('touchend', handlePackageClick);
            
            // Add keyboard accessibility
            item.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    handlePackageClick.call(this, e);
                }
            });
        }
        
        function handlePackageClick(e) {
            console.log('Safari fallback handler: Package clicked', this);
            
            // First try the normal flow - this will attempt to use the initCreditPackages handler
            // If it doesn't work (e.g., if initCreditPackages hasn't run yet), we'll handle it directly
            
            // Check if already selected a few ms later
            setTimeout(function() {
                var isSelected = document.querySelector('.package-item.selected-package');
                if (!isSelected && e.currentTarget) {
                    console.log('Safari fallback: No selection detected, forcing selection');
                    
                    // Clear any existing selections
                    for (var j = 0; j < packageItems.length; j++) {
                        packageItems[j].classList.remove('selected-package');
                    }
                    
                    // Add selected class
                    e.currentTarget.classList.add('selected-package');
                    
                    // Manual update of the payment details
                    var packageId = e.currentTarget.getAttribute('data-package-id');
                    var packageNameEl = e.currentTarget.querySelector('.package-details h5');
                    var packageName = packageNameEl ? packageNameEl.textContent : 'Selected Package';
                    var credits = e.currentTarget.getAttribute('data-credits');
                    var price = e.currentTarget.getAttribute('data-price');
                    
                    // Update the payment form
                    var selectedPackageName = document.getElementById('selectedPackageName');
                    var selectedPackageCredits = document.getElementById('selectedPackageCredits');
                    var selectedPackagePrice = document.getElementById('selectedPackagePrice');
                    var selectedPackageIdInput = document.getElementById('selectedPackageId');
                    var totalPrice = document.getElementById('totalPrice');
                    var packageDetails = document.getElementById('packageDetails');
                    var noPackageSelected = document.getElementById('noPackageSelected');
                    var paymentForm = document.getElementById('paymentForm');
                    
                    if (selectedPackageName) selectedPackageName.textContent = packageName;
                    if (selectedPackageCredits) selectedPackageCredits.textContent = credits + ' credits';
                    if (selectedPackagePrice) selectedPackagePrice.textContent = '$' + parseFloat(price).toFixed(2);
                    if (selectedPackageIdInput) selectedPackageIdInput.value = packageId;
                    if (totalPrice) totalPrice.textContent = '$' + parseFloat(price).toFixed(2);
                    
                    // Show the details and payment form
                    if (packageDetails) packageDetails.classList.remove('d-none');
                    if (noPackageSelected) noPackageSelected.classList.add('d-none');
                    if (paymentForm) paymentForm.classList.remove('d-none');
                    
                    console.log('Safari fallback: Package selection updated manually');
                }
            }, 100);
        }
    });
</script> 

<!-- Direct Safari-compatible payment processing script -->
<script>
    // Set up global variables for Stripe
    var stripeInstance = null;
    var cardElement = null;
    
    // Initialize Stripe when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Get Stripe public key
        var stripeKey = document.getElementById('stripePublicKey');
        
        if (stripeKey && stripeKey.value) {
            try {
                // Create Stripe instance
                stripeInstance = Stripe(stripeKey.value);
                
                // Create card element
                var cardContainer = document.getElementById('cardElement');
                if (cardContainer && stripeInstance) {
                    var elements = stripeInstance.elements();
                    cardElement = elements.create('card', {
                        style: {
                            base: {
                                fontSize: '16px',
                                color: '#32325d',
                            },
                        }
                    });
                    
                    // Mount card element
                    cardElement.mount(cardContainer);
                    
                    // Handle validation errors
                    cardElement.addEventListener('change', function(event) {
                        var displayError = document.getElementById('cardErrors');
                        if (displayError) {
                            if (event.error) {
                                displayError.textContent = event.error.message;
                            } else {
                                displayError.textContent = '';
                            }
                        }
                    });
                    
                    console.log('Stripe initialized successfully for Safari compatibility');
                }
            } catch (error) {
                console.error('Error initializing Stripe', error);
            }
        }
    });
    
    // Function to process payment (called directly from button onclick)
    function processPayment() {
        console.log('Processing payment with direct handler');
        
        try {
            // Get payment button and disable it
            var payButton = document.getElementById('payButton');
            if (payButton) {
                payButton.disabled = true;
                payButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            }
            
            // Check if we have Stripe and card element
            if (!stripeInstance || !cardElement) {
                console.error('Stripe or card element not initialized');
                alert('Payment processing is not available. Please try again later.');
                
                if (payButton) {
                    payButton.disabled = false;
                    payButton.innerHTML = '<i class="fas fa-lock me-2"></i> Pay Now';
                }
                
                return;
            }
            
            // Get package ID and coupon code
            var packageId = document.getElementById('selectedPackageId').value;
            var couponCode = document.getElementById('appliedCouponCode') ? 
                document.getElementById('appliedCouponCode').value : '';
            
            if (!packageId) {
                alert('Please select a package first');
                
                if (payButton) {
                    payButton.disabled = false;
                    payButton.innerHTML = '<i class="fas fa-lock me-2"></i> Pay Now';
                }
                
                return;
            }
            
            // Create payment method
            stripeInstance.createPaymentMethod({
                type: 'card',
                card: cardElement,
            }).then(function(result) {
                if (result.error) {
                    // Show error
                    var errorElement = document.getElementById('cardErrors');
                    if (errorElement) errorElement.textContent = result.error.message;
                    
                    if (payButton) {
                        payButton.disabled = false;
                        payButton.innerHTML = '<i class="fas fa-lock me-2"></i> Pay Now';
                    }
                } else {
                    // Send to server
                    fetch('index.php?page=api/payment/process', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            payment_method_id: result.paymentMethod.id,
                            package_id: packageId,
                            coupon_code: couponCode
                        })
                    })
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(function(data) {
                        if (data.success) {
                            // Check if the payment method was saved
                            console.log('Payment successful', data);
                            
                            // If user has no saved payment methods yet, show a message and redirect to payment methods
                            if (data.payment_method_saved) {
                                // Add a small delay to ensure the payment is fully processed
                                setTimeout(function() {
                                    window.location.href = data.redirect;
                                }, 1000);
                            } else {
                                window.location.href = data.redirect;
                            }
                        } else {
                            // Show error and re-enable button
                            console.error('Payment failed', data);
                            
                            let errorMessage = data.message || 'An error occurred during payment processing. Please try again.';
                            
                            // Handle specific error codes
                            if (data.error_code === 'db_connection_error') {
                                errorMessage = 'The payment system is experiencing high traffic. Please try again in a moment.';
                            }
                            
                            var errorElement = document.getElementById('cardErrors');
                            if (errorElement) errorElement.textContent = errorMessage;
                            
                            // Create a more detailed error alert
                            var detailedError = document.createElement('div');
                            detailedError.className = 'alert alert-danger mt-3';
                            detailedError.innerHTML = '<strong>Payment Error:</strong> ' + errorMessage;
                            
                            // Include suggestion based on the error code
                            if (data.error_code === 'db_connection_error') {
                                detailedError.innerHTML += '<p class="mt-2 mb-0">Our system is currently experiencing high demand. We recommend trying again in a few minutes or adding a payment method first from your <a href="index.php?page=payment-methods">payment methods page</a>.</p>';
                            }
                            
                            // Append the error message to the card form
                            var formElement = document.querySelector('.card-form');
                            if (formElement && !document.querySelector('.card-form .alert-danger')) {
                                formElement.appendChild(detailedError);
                            }
                            
                            if (payButton) {
                                payButton.disabled = false;
                                payButton.innerHTML = '<i class="fas fa-lock me-2"></i> Try Again';
                            }
                        }
                    })
                    .catch(function(error) {
                        // Show error and re-enable button
                        console.error('Payment processing error', error);
                        
                        var errorElement = document.getElementById('cardErrors');
                        if (errorElement) {
                            errorElement.textContent = 'Payment processing error: ' + error.message;
                            errorElement.classList.add('alert', 'alert-danger', 'mt-3', 'p-2');
                        }
                        
                        // Show a more user-friendly error message
                        var paymentErrorAlert = document.createElement('div');
                        paymentErrorAlert.className = 'alert alert-danger mt-3';
                        paymentErrorAlert.innerHTML = '<strong>Payment Error:</strong> We couldn\'t process your payment. You may want to <a href="index.php?page=payment-methods">add a payment method</a> first, or try a different card.';
                        
                        var cardContainer = document.querySelector('.card-form');
                        if (cardContainer) {
                            cardContainer.appendChild(paymentErrorAlert);
                        }
                        
                        if (payButton) {
                            payButton.disabled = false;
                            payButton.innerHTML = '<i class="fas fa-lock me-2"></i> Try Again';
                        }
                    });
                }
            });
        } catch (error) {
            console.error('Error in processPayment function', error);
            
            var payButton = document.getElementById('payButton');
            if (payButton) {
                payButton.disabled = false;
                payButton.innerHTML = '<i class="fas fa-lock me-2"></i> Pay Now';
            }
        }
    }
</script> 

<!-- Safari-specific enhancements -->
<script>
    // Detect Safari
    function isSafari() {
        var ua = navigator.userAgent.toLowerCase();
        return (ua.indexOf('safari') != -1 && ua.indexOf('chrome') == -1);
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        var browser = isSafari() ? 'Safari' : 'Other';
        console.log('Browser detected:', browser);
        
        // If Safari, apply additional enhancements
        if (isSafari()) {
            console.log('Applying Safari-specific enhancements');
            
            // Make package items more interactive for Safari
            var packageItems = document.querySelectorAll('.package-item');
            
            for (var i = 0; i < packageItems.length; i++) {
                var item = packageItems[i];
                
                // Add special Safari-specific click handling
                item.style.cursor = 'pointer';
                item.style.webkitTapHighlightColor = 'rgba(0,0,0,0)';
                
                // Add manual touch event listeners
                item.addEventListener('touchstart', function(e) {
                    // Add a visual cue that the item is being touched
                    this.style.backgroundColor = '#e9ecef';
                });
                
                item.addEventListener('touchend', function(e) {
                    // Reset styles
                    this.style.backgroundColor = '';
                    
                    // Force selection
                    selectPackage(this);
                    
                    // Prevent default behavior
                    e.preventDefault();
                });
                
                // Create a visible "Select" button for Safari users
                var selectBtn = document.createElement('button');
                selectBtn.textContent = 'Select';
                selectBtn.className = 'btn btn-sm btn-outline-primary mt-2 safari-select-btn';
                selectBtn.style.display = 'none'; // Initially hidden, shown only in Safari
                selectBtn.setAttribute('type', 'button');
                
                // Use a closure to capture the current item
                (function(packageItem) {
                    selectBtn.onclick = function(e) {
                        selectPackage(packageItem);
                        e.stopPropagation();
                        return false;
                    };
                })(item);
                
                // Add the button to the item
                var pricingDiv = item.querySelector('.package-pricing');
                if (pricingDiv) {
                    pricingDiv.appendChild(selectBtn);
                    // Make the button visible in Safari
                    selectBtn.style.display = 'inline-block';
                }
            }
            
            // Add click handler to entire document to catch any clicks on package items
            document.addEventListener('click', function(e) {
                var target = e.target;
                
                // Walk up the DOM tree to find if a parent is a package item
                while (target != null) {
                    if (target.classList && target.classList.contains('package-item')) {
                        console.log('Document-level click handler caught package item click');
                        selectPackage(target);
                        break;
                    }
                    target = target.parentElement;
                }
            });
        }
    });
</script> 