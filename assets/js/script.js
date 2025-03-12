/**
 * Main JavaScript for Web Call Credit App
 */

// Wait for document to be ready
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize phone dialer if present
    initPhoneDialer();
    
    // Initialize call functionality if present
    initCallFunctionality();
    
    // Initialize payment form if present
    initPaymentForm();
    
    // Initialize credit packages if present
    initCreditPackages();
    
    // Initialize coupon form if present
    initCouponForm();
    
    // Initialize Bootstrap tabs
    initTabs();
    
    // Initialize settings page functionality
    initSettings();
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});

/**
 * Initialize phone dialer
 */
function initPhoneDialer() {
    const phoneInput = document.getElementById('phone-number');
    const dialerButtons = document.querySelectorAll('.dialer-btn');
    const clearButton = document.getElementById('btn-clear');
    const backspaceButton = document.getElementById('btn-backspace');
    const recentCallItems = document.querySelectorAll('.recent-call-item');
    
    if (!phoneInput) return;
    
    // Allow only numbers, +, *, # in the input field
    phoneInput.addEventListener('input', function(e) {
        // Replace any non-allowed characters
        this.value = this.value.replace(/[^0-9+*#]/g, '');
    });
    
    // Add click handlers for dialer buttons
    dialerButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const digit = button.getAttribute('data-digit');
            if (digit) {
                phoneInput.value += digit;
                // Focus on the input after adding a digit
                phoneInput.focus();
            }
        });
    });
    
    // Clear button
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            phoneInput.value = '';
            phoneInput.focus();
        });
    }
    
    // Backspace button
    if (backspaceButton) {
        backspaceButton.addEventListener('click', function() {
            phoneInput.value = phoneInput.value.slice(0, -1);
            phoneInput.focus();
        });
    }
    
    // Recent call items
    if (recentCallItems) {
        recentCallItems.forEach(function(item) {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const number = this.getAttribute('data-number');
                if (number && phoneInput) {
                    phoneInput.value = number;
                    phoneInput.focus();
                }
            });
        });
    }
    
    // Handle Enter key to start call
    phoneInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const callButton = document.getElementById('btn-call');
            if (callButton && !callButton.disabled) {
                callButton.click();
            }
        }
    });
}

/**
 * Initialize call functionality with Twilio Client
 */
function initCallFunctionality() {
    const callButton = document.getElementById('btn-call');
    const hangupButton = document.getElementById('btn-hangup');
    const muteButton = document.getElementById('btn-mute');
    const phoneNumberInput = document.getElementById('phone-number');
    const callStatus = document.getElementById('call-status');
    const tokenInput = document.getElementById('twilio-token');
    
    // If call elements don't exist, return
    if (!callButton || !phoneNumberInput || !tokenInput) return;
    
    let device;
    let currentConnection = null;
    let callStartTime;
    let isMuted = false;
    
    // Setup Twilio device
    function setupDevice() {
        const token = tokenInput.value;
        
        // Create Device
        device = new Twilio.Device(token, {
            enableRingingSound: true,
            debug: true
        });
        
        // Device ready
        device.on('ready', function() {
            updateCallStatus('Ready to make calls');
            callButton.disabled = false;
        });
        
        // Error handler
        device.on('error', function(error) {
            updateCallStatus('Error: ' + error.message);
            console.error(error);
        });
        
        // Connect handler (outgoing call connected)
        device.on('connect', function(conn) {
            currentConnection = conn;
            callStartTime = new Date();
            updateCallStatus('Call in progress');
            
            // Update UI
            callButton.style.display = 'none';
            hangupButton.style.display = 'inline-block';
            if (muteButton) muteButton.style.display = 'inline-block';
            
            // Listen for call end
            conn.on('disconnect', callDisconnected);
        });
        
        // Disconnect handler
        device.on('disconnect', callDisconnected);
    }
    
    // Handle call disconnection
    function callDisconnected() {
        // Calculate call duration
        let duration = 0;
        if (callStartTime) {
            const endTime = new Date();
            duration = Math.floor((endTime - callStartTime) / 1000);
        }
        
        // Update UI
        updateCallStatus('Call ended (' + formatDuration(duration) + ')');
        callButton.style.display = 'inline-block';
        hangupButton.style.display = 'none';
        if (muteButton) {
            muteButton.style.display = 'none';
            muteButton.innerHTML = '<i class="fas fa-microphone-slash"></i>';
            isMuted = false;
        }
        
        // Record call if needed
        if (duration > 0) {
            recordCall(currentConnection.parameters.CallSid, duration);
        }
        
        currentConnection = null;
        callStartTime = null;
    }
    
    // Format duration in seconds to MM:SS
    function formatDuration(seconds) {
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return minutes.toString().padStart(2, '0') + ':' + secs.toString().padStart(2, '0');
    }
    
    // Update call status message
    function updateCallStatus(message) {
        callStatus.textContent = message;
    }
    
    // Record call in database
    function recordCall(callSid, duration) {
        // Only if we have a call SID
        if (!callSid) return;
        
        console.log('Recording call with SID:', callSid, 'and duration:', duration);
        
        const userIdElement = document.getElementById('user-id');
        const userId = userIdElement ? userIdElement.value : '';
        const phoneNumber = document.getElementById('phone-number').value;
        
        // Log the values we're sending
        console.log('Sending userId:', userId);
        console.log('Sending phoneNumber:', phoneNumber);
        
        if (!userId) {
            console.error('No user ID found - user may not be logged in');
        }
        
        // Send AJAX request to record call
        fetch('index.php?page=api/call/complete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                call_sid: callSid,
                duration: duration,
                userId: userId,
                To: phoneNumber // Include the destination number
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Call recorded successfully');
                // Refresh credits display if needed
                if (data.credits) {
                    const creditsDisplay = document.querySelector('.credits-display');
                    if (creditsDisplay) {
                        creditsDisplay.textContent = data.credits;
                    }
                }
            } else {
                console.error('Failed to record call:', data.message);
            }
        })
        .catch(error => {
            console.error('Error recording call:', error);
        });
    }
    
    // Initialize Twilio device
    setupDevice();
    
    // Call button click handler
    callButton.addEventListener('click', function() {
        const phoneNumber = phoneNumberInput.value.trim();
        const userId = document.getElementById('user-id').value;
        
        // Check if phone number is empty
        if (!phoneNumber) {
            updateCallStatus('Please enter a phone number');
            return;
        }
        
        // Check if user ID is missing
        if (!userId) {
            console.error('Missing user ID - call may not be properly tracked');
            updateCallStatus('Error: User ID not found. Please reload the page.');
            return;
        }
        
        // Log that we're initiating a call (for debugging)
        console.log('Initiating call to:', phoneNumber, 'with user ID:', userId);
        
        // Disable call button and update status
        callButton.disabled = true;
        updateCallStatus('Initiating call...');
        
        // Make the call
        const params = {
            To: phoneNumber,
            userId: userId  // Make sure we're sending the user ID with every call
        };
        
        console.log('Making call with params:', params);  // Log the params for debugging
        
        // Connect to the phone number
        device.connect(params)
            .then(function(conn) {
                // When the connection is established, save it
                currentConnection = conn;
                
                // Store the connection start time
                callStartTime = new Date();
                
                // Setup disconnect handler
                conn.on('disconnect', callDisconnected);
            })
            .catch(function(error) {
                console.error('Error connecting call:', error);
                updateCallStatus('Error: ' + (error.message || 'Could not connect call'));
                
                // Re-enable the call button so user can try again
                callButton.disabled = false;
                
                // Reset UI
                callButton.style.display = 'inline-block';
                hangupButton.style.display = 'none';
                if (muteButton) muteButton.style.display = 'none';
            });
    });
    
    // Hangup button click handler
    hangupButton.addEventListener('click', function() {
        if (currentConnection) {
            currentConnection.disconnect();
        }
    });
    
    // Mute button click handler
    if (muteButton) {
        muteButton.addEventListener('click', function() {
            if (!currentConnection) return;
            
            if (isMuted) {
                currentConnection.mute(false);
                muteButton.innerHTML = '<i class="fas fa-microphone-slash"></i>';
                isMuted = false;
            } else {
                currentConnection.mute(true);
                muteButton.innerHTML = '<i class="fas fa-microphone"></i>';
                isMuted = true;
            }
        });
    }
}

/**
 * Initialize credit packages
 */
function initCreditPackages() {
    console.log('Initializing credit packages...');
    
    // Get elements
    const packageItems = document.querySelectorAll('.package-item');
    const packageDetails = document.getElementById('packageDetails');
    const noPackageSelected = document.getElementById('noPackageSelected');
    const paymentForm = document.getElementById('paymentForm');
    const payButton = document.getElementById('payButton');
    const stripePublicKeyInput = document.getElementById('stripePublicKey');
    
    // Check if we're on the credits page by confirming required elements exist
    if (!packageItems.length) {
        console.log('Not on credits page or no package items found');
        return;
    }
    
    console.log(`Found ${packageItems.length} package items`);
    
    // Package selection elements
    const selectedPackageName = document.getElementById('selectedPackageName');
    const selectedPackageCredits = document.getElementById('selectedPackageCredits');
    const selectedPackagePrice = document.getElementById('selectedPackagePrice');
    const selectedPackageIdInput = document.getElementById('selectedPackageId');
    const totalPrice = document.getElementById('totalPrice');
    
    if (!selectedPackageName || !selectedPackageCredits || !selectedPackagePrice || !totalPrice) {
        console.error('Missing required elements for package selection:', {
            selectedPackageName, selectedPackageCredits, selectedPackagePrice, totalPrice
        });
        return;
    }
    
    /**
     * Format price as currency
     */
    function formatPrice(price) {
        return '$' + parseFloat(price).toFixed(2);
    }
    
    /**
     * Calculate discount if coupon is applied
     */
    function calculateDiscount(price) {
        const discountElement = document.getElementById('discountAmount');
        if (!discountElement) {
            console.log('No discount element found');
            return price;
        }
        
        const discountType = discountElement.dataset.type;
        const discountValue = parseFloat(discountElement.dataset.value);
        
        if (!discountType || isNaN(discountValue)) {
            console.log('Invalid discount data', { discountType, discountValue });
            return price;
        }
        
        console.log('Calculating discount', { type: discountType, value: discountValue, price });
        
        let finalPrice = price;
        let discountAmount = 0;
        
        if (discountType === 'percentage') {
            discountAmount = price * (discountValue / 100);
            finalPrice = price - discountAmount;
        } else if (discountType === 'fixed') {
            discountAmount = Math.min(discountValue, price);
            finalPrice = price - discountAmount;
        }
        
        discountElement.textContent = '-' + formatPrice(discountAmount);
        console.log('Discount calculated', { original: price, discount: discountAmount, final: finalPrice });
        
        return finalPrice;
    }
    
    /**
     * Update package details display when a package is selected
     */
    function updatePackageDetails(packageItem) {
        try {
            // Get package data
            const packageId = packageItem.getAttribute('data-package-id');
            const packageNameElement = packageItem.querySelector('.package-details h5');
            const packageName = packageNameElement ? packageNameElement.textContent : 'Selected Package';
            const credits = packageItem.getAttribute('data-credits');
            const price = packageItem.getAttribute('data-price');
            
            console.log('Updating package details', { packageId, packageName, credits, price });
            
            if (!packageId || !credits || !price) {
                console.error('Missing required package data', { packageId, credits, price });
                return;
            }
            
            // Update display
            selectedPackageName.textContent = packageName;
            selectedPackageCredits.textContent = credits + ' credits';
            selectedPackagePrice.textContent = formatPrice(price);
            
            if (selectedPackageIdInput) {
                selectedPackageIdInput.value = packageId;
            }
            
            // Calculate final price with discount
            const finalPrice = calculateDiscount(parseFloat(price));
            totalPrice.textContent = formatPrice(finalPrice);
            
            // Show package details and payment form
            if (packageDetails) packageDetails.classList.remove('d-none');
            if (noPackageSelected) noPackageSelected.classList.add('d-none');
            if (paymentForm) paymentForm.classList.remove('d-none');
            
            console.log('Package details updated successfully');
        } catch (error) {
            console.error('Error updating package details', error);
        }
    }
    
    // Add click event to package items - using more direct approach for Safari compatibility
    for (let i = 0; i < packageItems.length; i++) {
        const packageItem = packageItems[i];
        // Safari-compatible event binding
        packageItem.onclick = function(event) {
            console.log(`Package item ${i + 1} clicked`);
            
            // Remove selected class from all packages
            for (let j = 0; j < packageItems.length; j++) {
                packageItems[j].classList.remove('selected-package');
            }
            
            // Add selected class to clicked package
            this.classList.add('selected-package');
            
            // Update package details
            updatePackageDetails(this);
            
            // Prevent any default behavior or bubbling that might interfere
            event.preventDefault();
            event.stopPropagation();
            
            return false; // Extra safety for older browsers
        };
    }
    
    // Initialize Stripe payment if needed
    if (stripePublicKeyInput && payButton) {
        const stripeKey = stripePublicKeyInput.value;
        
        if (!stripeKey) {
            console.error('Missing Stripe public key');
            return;
        }
        
        console.log('Initializing Stripe payment');
        
        try {
            // Check if Stripe is loaded
            if (typeof Stripe === 'undefined') {
                console.error('Stripe.js is not loaded');
                
                // Add a message to the user
                const paymentError = document.createElement('div');
                paymentError.className = 'alert alert-danger mt-3';
                paymentError.textContent = 'Payment processing is currently unavailable. Please try again later.';
                
                if (paymentForm) {
                    paymentForm.prepend(paymentError);
                }
                return;
            }
            
            const stripe = Stripe(stripeKey);
            const cardElement = document.getElementById('cardElement');
            
            if (!cardElement) {
                console.error('Card element not found');
                return;
            }
            
            // Create card element
            const elements = stripe.elements();
            const card = elements.create('card', {
                style: {
                    base: {
                        fontSize: '16px',
                        color: '#32325d',
                    },
                }
            });
            
            // Mount card element
            card.mount(cardElement);
            console.log('Card element mounted successfully');
            
            // Handle validation errors
            card.addEventListener('change', function(event) {
                const displayError = document.getElementById('cardErrors');
                if (displayError) {
                    if (event.error) {
                        displayError.textContent = event.error.message;
                    } else {
                        displayError.textContent = '';
                    }
                }
            });
            
            // Pay button click event handler
            payButton.onclick = function(event) {
                console.log('Pay button clicked');
                
                // Get payment data
                const packageId = selectedPackageIdInput ? selectedPackageIdInput.value : '';
                const couponCode = document.getElementById('appliedCouponCode') ? 
                    document.getElementById('appliedCouponCode').value : '';
                
                if (!packageId) {
                    alert('Please select a package first');
                    return;
                }
                
                // Disable button to prevent multiple submissions
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
                
                console.log('Creating payment method', { packageId, couponCode });
                
                // Create payment method
                stripe.createPaymentMethod({
                    type: 'card',
                    card: card,
                }).then(function(result) {
                    if (result.error) {
                        console.error('Stripe payment method error:', result.error);
                        
                        // Show error
                        const errorElement = document.getElementById('cardErrors');
                        if (errorElement) errorElement.textContent = result.error.message;
                        
                        payButton.disabled = false;
                        payButton.innerHTML = '<i class="fas fa-lock me-2"></i> Pay Now';
                    } else {
                        console.log('Payment method created, sending to server');
                        console.log('Payment method ID:', result.paymentMethod.id);
                        
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
                        .then(response => {
                            console.log('Server response status:', response.status);
                            console.log('Response headers:', [...response.headers].map(h => h.join(': ')).join(', '));
                            
                            if (!response.ok) {
                                return response.text().then(text => {
                                    console.error('Network response error:', text);
                                    throw new Error('Network response was not ok: ' + response.status);
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('Server response data:', data);
                            
                            if (data.success) {
                                // Payment succeeded, redirect to success page
                                console.log('Payment succeeded, redirecting to:', data.redirect);
                                window.location.href = data.redirect;
                            } else if (data.requires_action) {
                                // Payment requires additional authentication
                                console.log('Payment requires additional authentication');
                                
                                // Use Stripe.js to handle the additional authentication
                                stripe.handleCardAction(data.payment_intent_client_secret)
                                    .then(function(result) {
                                        if (result.error) {
                                            // Show error in payment form
                                            console.error('Authentication error:', result.error);
                                            const errorElement = document.getElementById('cardErrors');
                                            if (errorElement) {
                                                errorElement.textContent = result.error.message;
                                            }
                                            
                                            payButton.disabled = false;
                                            payButton.innerHTML = '<i class="fas fa-lock me-2"></i> Pay Now';
                                        } else {
                                            // The card action has been handled
                                            // The PaymentIntent can be confirmed again on the server
                                            console.log('Authentication successful, confirming payment');
                                            
                                            fetch('index.php?page=api/payment/confirm', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json'
                                                },
                                                body: JSON.stringify({
                                                    payment_intent_id: result.paymentIntent.id
                                                })
                                            })
                                            .then(response => {
                                                console.log('Confirmation response status:', response.status);
                                                if (!response.ok) {
                                                    return response.text().then(text => {
                                                        console.error('Confirmation error text:', text);
                                                        throw new Error('Confirmation response was not ok: ' + response.status);
                                                    });
                                                }
                                                return response.json();
                                            })
                                            .then(confirmResult => {
                                                console.log('Confirmation response data:', confirmResult);
                                                // Check if payment was actually successful despite error messages
                                                if (confirmResult.success || (confirmResult.payment_status === 'succeeded' && confirmResult.credits_updated)) {
                                                    // Payment and credit update succeeded, redirect to success page
                                                    window.location.href = confirmResult.redirect || 'index.php?page=dashboard';
                                                } else {
                                                    // Payment failed
                                                    const errorElement = document.getElementById('cardErrors');
                                                    if (errorElement) {
                                                        errorElement.textContent = confirmResult.message || 'Payment failed';
                                                    }
                                                    
                                                    payButton.disabled = false;
                                                    payButton.innerHTML = '<i class="fas fa-lock me-2"></i> Pay Now';
                                                }
                                            })
                                            .catch(error => {
                                                console.error('Error confirming payment:', error);
                                                
                                                const errorElement = document.getElementById('cardErrors');
                                                if (errorElement) {
                                                    errorElement.textContent = 'An error occurred. Please try again.';
                                                }
                                                
                                                payButton.disabled = false;
                                                payButton.innerHTML = '<i class="fas fa-lock me-2"></i> Pay Now';
                                            });
                                        }
                                    });
                            } else {
                                // Payment failed
                                console.error('Payment failed:', data.message);
                                const errorElement = document.getElementById('cardErrors');
                                if (errorElement) {
                                    errorElement.textContent = data.message || 'Payment failed';
                                }
                                
                                payButton.disabled = false;
                                payButton.innerHTML = '<i class="fas fa-lock me-2"></i> Pay Now';
                            }
                        })
                        .catch(error => {
                            console.error('Error processing payment:', error);
                            console.error('Error stack:', error.stack);
                            
                            const errorElement = document.getElementById('cardErrors');
                            if (errorElement) {
                                errorElement.textContent = 'An error occurred. Please try again.';
                            }
                            
                            payButton.disabled = false;
                            payButton.innerHTML = '<i class="fas fa-lock me-2"></i> Pay Now';
                        });
                    }
                });
                
                // Prevent default form behavior
                if (event && event.preventDefault) {
                    event.preventDefault();
                }
                return false;
            };
            
            console.log('Stripe payment initialization complete');
        } catch (error) {
            console.error('Error initializing Stripe', error);
        }
    }
    
    console.log('Credit packages initialization complete');
}

/**
 * Initialize payment form with Stripe
 */
function initPaymentForm() {
    const paymentForm = document.getElementById('payment-form');
    const stripePublicKeyInput = document.getElementById('stripe-public-key');
    
    if (!paymentForm || !stripePublicKeyInput) return;
    
    const stripePublicKey = stripePublicKeyInput.value;
    const stripe = Stripe(stripePublicKey);
    const elements = stripe.elements();
    
    // Create card element
    const card = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#32325d',
            },
        }
    });
    
    // Mount card element
    card.mount('#card-element');
    
    // Handle validation errors
    card.addEventListener('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });
    
    // Handle form submission
    paymentForm.addEventListener('submit', function(event) {
        event.preventDefault();
        
        const submitButton = paymentForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
        
        // Create payment method
        stripe.createPaymentMethod({
            type: 'card',
            card: card,
            billing_details: {
                name: document.getElementById('cardholder-name').value
            }
        }).then(function(result) {
            if (result.error) {
                // Show error
                const errorElement = document.getElementById('card-errors');
                errorElement.textContent = result.error.message;
                submitButton.disabled = false;
                submitButton.textContent = 'Pay Now';
            } else {
                // Send to server
                const paymentMethodId = result.paymentMethod.id;
                const packageId = document.getElementById('package-id').value;
                const couponCode = document.getElementById('coupon-code') ? document.getElementById('coupon-code').value : '';
                
                fetch('index.php?page=api/payment/process', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        payment_method_id: paymentMethodId,
                        package_id: packageId,
                        coupon_code: couponCode
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.redirect;
                    } else {
                        const errorElement = document.getElementById('card-errors');
                        errorElement.textContent = data.message;
                        submitButton.disabled = false;
                        submitButton.textContent = 'Pay Now';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const errorElement = document.getElementById('card-errors');
                    errorElement.textContent = 'An error occurred. Please try again.';
                    submitButton.disabled = false;
                    submitButton.textContent = 'Pay Now';
                });
            }
        });
    });
}

/**
 * Initialize coupon form
 */
function initCouponForm() {
    const couponForm = document.getElementById('coupon-form');
    const couponCode = document.getElementById('coupon-code');
    const couponButton = document.getElementById('apply-coupon');
    const couponStatus = document.getElementById('coupon-status');
    const originalAmount = document.getElementById('original-amount');
    const discountAmount = document.getElementById('discount-amount');
    const finalAmount = document.getElementById('final-amount');
    
    if (!couponForm || !couponCode || !couponButton) return;
    
    couponForm.addEventListener('submit', function(event) {
        event.preventDefault();
        
        const code = couponCode.value.trim();
        if (!code) return;
        
        couponButton.disabled = true;
        couponButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        
        // Get package ID
        const packageId = document.getElementById('package-id').value;
        
        // Verify coupon
        fetch('index.php?page=api/coupon/verify', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                code: code,
                package_id: packageId
            })
        })
        .then(response => response.json())
        .then(data => {
            couponButton.disabled = false;
            couponButton.textContent = 'Apply';
            
            if (data.success) {
                couponStatus.textContent = 'Coupon applied: ' + data.coupon.code;
                couponStatus.className = 'text-success';
                
                // Update amounts
                if (originalAmount && discountAmount && finalAmount) {
                    originalAmount.textContent = '$' + parseFloat(data.original).toFixed(2);
                    discountAmount.textContent = '$' + parseFloat(data.discount).toFixed(2);
                    finalAmount.textContent = '$' + parseFloat(data.final).toFixed(2);
                    
                    // Show discount row
                    const discountRow = document.getElementById('discount-row');
                    if (discountRow) {
                        discountRow.style.display = 'table-row';
                    }
                }
            } else {
                couponStatus.textContent = data.message;
                couponStatus.className = 'text-danger';
                
                // Reset amounts
                if (originalAmount && finalAmount) {
                    const original = parseFloat(originalAmount.getAttribute('data-original'));
                    originalAmount.textContent = '$' + original.toFixed(2);
                    finalAmount.textContent = '$' + original.toFixed(2);
                    
                    // Hide discount row
                    const discountRow = document.getElementById('discount-row');
                    if (discountRow) {
                        discountRow.style.display = 'none';
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            couponButton.disabled = false;
            couponButton.textContent = 'Apply';
            couponStatus.textContent = 'An error occurred. Please try again.';
            couponStatus.className = 'text-danger';
        });
    });
}

/**
 * Initialize Bootstrap tabs
 */
function initTabs() {
    console.log('Initializing tabs with Bootstrap...');
    
    // Get all tab links using the Bootstrap selector
    const tabLinks = document.querySelectorAll('[data-bs-toggle="list"], [data-bs-toggle="tab"]');
    
    if (tabLinks.length > 0) {
        console.log(`Found ${tabLinks.length} tab links`);
        
        // Check if Bootstrap is available
        if (typeof bootstrap !== 'undefined') {
            console.log('Bootstrap is available, using native Tab implementation');
            
            // Add click handlers to tab links that use Bootstrap's Tab functionality
            tabLinks.forEach(tabLink => {
                // Initialize the tab using Bootstrap's Tab
                new bootstrap.Tab(tabLink);
                
                // Add click event to update URL hash
                tabLink.addEventListener('shown.bs.tab', function(event) {
                    const targetId = this.getAttribute('href');
                    if (history.pushState) {
                        history.pushState(null, null, targetId);
                    } else {
                        location.hash = targetId;
                    }
                });
            });
            
            // Check if URL has hash and select corresponding tab
            if (location.hash) {
                const hash = location.hash;
                const tabLink = document.querySelector(`[href="${hash}"]`);
                if (tabLink) {
                    console.log(`Activating tab with hash: ${hash}`);
                    const tab = new bootstrap.Tab(tabLink);
                    tab.show();
                }
            }
            
            console.log('Bootstrap tabs initialization complete');
        } else {
            console.warn('Bootstrap not found - falling back to manual tab implementation');
            
            // Manual fallback implementation
            tabLinks.forEach(tabLink => {
                tabLink.addEventListener('click', function(event) {
                    event.preventDefault();
                    
                    // Get the target tab id from href
                    const targetId = this.getAttribute('href');
                    
                    // Remove active class from all tab links
                    tabLinks.forEach(link => {
                        link.classList.remove('active');
                    });
                    
                    // Add active class to clicked tab link
                    this.classList.add('active');
                    
                    // Hide all tab panes
                    const tabPanes = document.querySelectorAll('.tab-pane');
                    tabPanes.forEach(pane => {
                        pane.classList.remove('show');
                        pane.classList.remove('active');
                    });
                    
                    // Show the target tab pane
                    const targetPane = document.querySelector(targetId);
                    if (targetPane) {
                        targetPane.classList.add('show');
                        targetPane.classList.add('active');
                    }
                    
                    // Update URL hash without scrolling
                    if (history.pushState) {
                        history.pushState(null, null, targetId);
                    } else {
                        location.hash = targetId;
                    }
                });
            });
            
            // Check if URL has hash and select corresponding tab for manual implementation
            if (location.hash) {
                const hash = location.hash;
                const tabLink = document.querySelector(`[href="${hash}"]`);
                if (tabLink) {
                    // Trigger click on tab link
                    tabLink.click();
                }
            }
        }
    } else {
        console.log('No tab links found');
    }
}

/**
 * Initialize settings page functionality
 */
function initSettings() {
    // Auto Top-up toggle
    const autoTopupCheckbox = document.getElementById('enable_auto_topup');
    const topupPackageContainer = document.getElementById('topupPackageContainer');
    
    if (autoTopupCheckbox && topupPackageContainer) {
        console.log('Initializing auto top-up settings...');
        
        // Handle checkbox toggle
        autoTopupCheckbox.addEventListener('change', function() {
            if (this.checked) {
                topupPackageContainer.classList.remove('d-none');
            } else {
                topupPackageContainer.classList.add('d-none');
            }
        });
        
        console.log('Auto top-up settings initialized');
    }
    
    // Payment method toggle (to be implemented in the future)
} 