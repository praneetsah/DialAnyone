<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Login to Your Account</h4>
                </div>
                <div class="card-body">
                    <?php if ($registrationSuccess): ?>
                        <div class="alert alert-success">
                            Registration successful! Please login with your credentials.
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($loginError): ?>
                        <div class="alert alert-danger">
                            <?php echo $loginError; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="index.php?page=login">
                        <?php $token = generateCsrfToken(); ?>
                        <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                            <a href="index.php?page=forgot-password">Forgot Password?</a>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">Don't have an account? <a href="index.php?page=register">Register</a></p>
                </div>
            </div>
        </div>
    </div>
</div> 