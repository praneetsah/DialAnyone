    </main>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Dial Anyone</h5>
                    <p>Make calls to anywhere in the world using your browser.</p>
                </div>
                <div class="col-md-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <?php if (isAuthenticated()): ?>
                            <li><a href="index.php?page=dashboard" class="text-white">Dashboard</a></li>
                            <li><a href="index.php?page=call" class="text-white">Make a Call</a></li>
                            <li><a href="index.php?page=credits" class="text-white">Buy Credits</a></li>
                        <?php else: ?>
                            <li><a href="index.php?page=login" class="text-white">Login</a></li>
                            <li><a href="index.php?page=register" class="text-white">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contact Us</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope"></i> <a href="mailto:support@DialAnyone.com" class="text-white">support@DialAnyone.com</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; <?php echo date('Y'); ?> Dial Anyone. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>
                        <a href="index.php?page=terms" class="text-white">Terms of Service</a> |
                        <a href="index.php?page=privacy" class="text-white">Privacy Policy</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Custom JavaScript -->
    <script src="assets/js/script.js"></script>
</body>
</html> 