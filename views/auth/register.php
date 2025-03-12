<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Create New Account</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($registrationErrors['general'])): ?>
                        <div class="alert alert-danger">
                            <?php echo $registrationErrors['general']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($registrationErrors['csrf'])): ?>
                        <div class="alert alert-danger">
                            <?php echo $registrationErrors['csrf']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="index.php?page=register">
                        <?php $token = generateCsrfToken(); ?>
                        <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control <?php echo isset($registrationErrors['name']) ? 'is-invalid' : ''; ?>" 
                                   id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                            <?php if (isset($registrationErrors['name'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $registrationErrors['name']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control <?php echo isset($registrationErrors['email']) ? 'is-invalid' : ''; ?>" 
                                   id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                            <?php if (isset($registrationErrors['email'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $registrationErrors['email']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control <?php echo isset($registrationErrors['phone']) ? 'is-invalid' : ''; ?>" 
                                   id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                                   placeholder="+1234567890" required>
                            <?php if (isset($registrationErrors['phone'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $registrationErrors['phone']; ?>
                                </div>
                            <?php else: ?>
                                <div class="form-text">
                                    Please enter with country code (e.g., +1 for US)
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control <?php echo isset($registrationErrors['password']) ? 'is-invalid' : ''; ?>" 
                                   id="password" name="password" required>
                            <?php if (isset($registrationErrors['password'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $registrationErrors['password']; ?>
                                </div>
                            <?php else: ?>
                                <div class="form-text">
                                    Must be at least 8 characters long
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control <?php echo isset($registrationErrors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                   id="confirm_password" name="confirm_password" required>
                            <?php if (isset($registrationErrors['confirm_password'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $registrationErrors['confirm_password']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input <?php echo isset($registrationErrors['terms']) ? 'is-invalid' : ''; ?>" 
                                       type="checkbox" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="index.php?page=terms" target="_blank">Terms of Service</a> and <a href="index.php?page=privacy" target="_blank">Privacy Policy</a>
                                </label>
                                <?php if (isset($registrationErrors['terms'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo $registrationErrors['terms']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">Already have an account? <a href="index.php?page=login">Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div> 