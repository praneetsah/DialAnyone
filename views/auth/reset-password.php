<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Reset Password</h4>
                </div>
                <div class="card-body">
                    <?php if ($resetPasswordError): ?>
                        <div class="alert alert-danger">
                            <?php echo $resetPasswordError; ?>
                        </div>
                        
                        <?php if (strpos($resetPasswordError, 'Invalid or expired') !== false || strpos($resetPasswordError, 'missing') !== false): ?>
                            <div class="text-center mt-3">
                                <a href="index.php?page=forgot-password" class="btn btn-primary">Request New Reset Link</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if ($resetPasswordSuccess): ?>
                        <div class="alert alert-success">
                            <p>Your password has been reset successfully!</p>
                            <p>You can now login with your new password.</p>
                        </div>
                        <div class="text-center mt-3">
                            <a href="index.php?page=login" class="btn btn-primary">Log In</a>
                        </div>
                    <?php elseif ($validToken): ?>
                        <p class="mb-4">Enter your new password below.</p>
                        
                        <form method="post" action="index.php?page=reset-password&token=<?php echo htmlspecialchars($token); ?>">
                            <?php $csrfToken = generateCsrfToken(); ?>
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="8">
                                <div class="form-text">
                                    Password must be at least 8 characters long
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">Reset Password</button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">
                        <a href="index.php?page=login">Back to Login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div> 