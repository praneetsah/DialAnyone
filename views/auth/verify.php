<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Verify Your Email Address</h4>
                </div>
                <div class="card-body">
                    <p>To protect your account and enable calling features, we need to verify your email address. We have sent a verification code to <strong><?php echo htmlspecialchars($user['email']); ?></strong>.</p>
                    
                    <?php if ($verificationError): ?>
                        <div class="alert alert-danger">
                            <?php echo $verificationError; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($resendSuccess): ?>
                        <div class="alert alert-success">
                            A new verification code has been sent to your email address.
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="index.php?page=verify">
                        <?php $token = generateCsrfToken(); ?>
                        <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                        <input type="hidden" name="action" value="verify">
                        
                        <div class="mb-3">
                            <label for="verification_code" class="form-label">Verification Code</label>
                            <input type="text" class="form-control <?php echo $verificationError ? 'is-invalid' : ''; ?>" 
                                   id="verification_code" name="verification_code" required>
                            <div class="form-text">
                                Enter the 6-digit code sent to your email
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">Verify Email Address</button>
                    </form>
                    
                    <hr>
                    
                    <div class="text-center">
                        <p>Didn't receive the code or need a new one?</p>
                        <form method="post" action="index.php?page=verify">
                            <?php $token = generateCsrfToken(); ?>
                            <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                            <input type="hidden" name="action" value="resend">
                            <button type="submit" class="btn btn-outline-secondary">Resend Verification Code</button>
                        </form>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <p class="small text-muted mb-0">
                        Please check your spam folder if you don't see the verification email in your inbox.
                    </p>
                    
                    <?php if ($isActuallyVerified): ?>
                    <hr>
                    <div class="mt-3">
                        <p class="small"><strong>Troubleshooting:</strong> Our system shows your email is verified in our database, but your session might be out of sync.</p>
                        <form method="post" action="index.php?page=verify">
                            <?php $token = generateCsrfToken(); ?>
                            <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                            <input type="hidden" name="action" value="force_update">
                            <button type="submit" class="btn btn-sm btn-warning">Update Session Status</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div> 