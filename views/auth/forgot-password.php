<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Forgot Password</h4>
                </div>
                <div class="card-body">
                    <?php if ($forgotPasswordError): ?>
                        <div class="alert alert-danger">
                            <?php echo $forgotPasswordError; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($forgotPasswordSuccess): ?>
                        <div class="alert alert-success">
                            <p>If an account exists with the email you provided, we've sent a password reset link to that address.</p>
                            <p>Please check your email (including spam folder) and follow the instructions to reset your password.</p>
                        </div>
                        <div class="text-center mt-3">
                            <a href="index.php?page=login" class="btn btn-primary">Return to Login</a>
                        </div>
                    <?php else: ?>
                        <p class="mb-4">Enter your email address below and we'll send you a link to reset your password.</p>
                        
                        <form method="post" action="index.php?page=forgot-password">
                            <?php $token = generateCsrfToken(); ?>
                            <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
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