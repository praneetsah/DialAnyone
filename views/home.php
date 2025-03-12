<!-- Schema.org structured data for better SEO -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebApplication",
    "name": "Dial Anyone",
    "url": "https://dialanyone.com",
    "description": "Make cheap international calls to any phone number worldwide directly from your web browser. No apps required.",
    "applicationCategory": "CommunicationApplication",
    "operatingSystem": "All",
    "offers": {
        "@type": "Offer",
        "description": "1000 Credits for approximately 400 minutes of worldwide calling",
        "price": "5.99",
        "priceCurrency": "USD"
    },
    "author": {
        "@type": "Organization",
        "name": "Dial Anyone",
        "logo": "https://dialanyone.com/assets/img/logo.png"
    }
}
</script>

<!-- Hero Section -->
<div class="bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-4 fw-bold">Make Calls From Your Browser</h1>
                <p class="lead">Call anyone, anywhere in the world directly from your web browser. No apps to install.</p>
                <div class="mt-4">
                    <?php if (isAuthenticated()): ?>
                        <a href="index.php?page=call" class="btn btn-light btn-lg me-2">
                            <i class="fas fa-phone"></i> Make a Call
                        </a>
                        <a href="index.php?page=credits" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-coins"></i> Buy Credits
                        </a>
                    <?php else: ?>
                        <a href="index.php?page=register" class="btn btn-light btn-lg me-2">
                            <i class="fas fa-user-plus"></i> Sign Up Now
                        </a>
                        <a href="index.php?page=login" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6 d-none d-md-block">
                <img src="assets/img/hero-image.svg" style="max-height: 300px; width: auto; align-items: center;" alt="Browser Call" class="img-fluid" onerror="this.onerror=null; this.src='https://via.placeholder.com/600x400?text=Browser+Call'">
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">A Skype alternative to call international numbers worldwide</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-globe fa-4x text-primary mb-3"></i>
                        <h3 class="card-title">Global Calling</h3>
                        <p class="card-text">Call any phone number worldwide at competitive rates. Toll-free numbers are also supported.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-laptop fa-4x text-primary mb-3"></i>
                        <h3 class="card-title">Browser-Based</h3>
                        <p class="card-text">No apps or software to install. Call directly from your web browser.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-credit-card fa-4x text-primary mb-3"></i>
                        <h3 class="card-title">Pay As You Go</h3>
                        <p class="card-text">Purchase credits and use them as needed. No subscriptions required. Payments are processed securely by Stripe—we never store your card information.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- How It Works -->
<div class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-5">How It Works</h2>
        <div class="row">
            <div class="col-md-3 text-center">
                <div class="rounded-circle bg-primary text-white d-inline-flex justify-content-center align-items-center mb-3" style="width: 80px; height: 80px;">
                    <i class="fas fa-user-plus fa-2x"></i>
                </div>
                <h4>1. Sign Up</h4>
                <p>Create your account in just a few seconds.</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="rounded-circle bg-primary text-white d-inline-flex justify-content-center align-items-center mb-3" style="width: 80px; height: 80px;">
                    <i class="fas fa-coins fa-2x"></i>
                </div>
                <h4>2. Buy Credits</h4>
                <p>Purchase credit packages that fit your needs.</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="rounded-circle bg-primary text-white d-inline-flex justify-content-center align-items-center mb-3" style="width: 80px; height: 80px;">
                    <i class="fas fa-phone fa-2x"></i>
                </div>
                <h4>3. Start Calling</h4>
                <p>Enter any phone number and start your call.</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="rounded-circle bg-primary text-white d-inline-flex justify-content-center align-items-center mb-3" style="width: 80px; height: 80px;">
                    <i class="fas fa-headset fa-2x"></i>
                </div>
                <h4>4. Talk Anywhere</h4>
                <p>High-quality audio to anywhere in the world.</p>
            </div>
        </div>
    </div>
</div>

<!-- Pricing Section -->
<div class="py-5">
    <div class="container">
        <h2 class="text-center mb-3">Credit Packages</h2>
        <h3 class="text-center mb-5 text-muted fw-light" style="font-size: 1.2rem;"><a href="index.php?page=calling-rates" class="text-primary">Click here to see our comprehensive pricing list</a></h3>
        
        <div class="row g-4">
            <?php foreach ($packages as $package): ?>
                <div class="col-md-3">
                    <div class="card h-100 package-card">
                        <div class="card-header">
                            <?php echo htmlspecialchars($package['name']); ?>
                        </div>
                        <div class="card-body">
                            <div class="package-price">
                                $<?php echo number_format($package['price'], 2); ?>
                            </div>
                            <div class="package-credits">
                                <?php echo number_format($package['credits']); ?> Credits
                            </div>
                            <?php if (isAuthenticated()): ?>
                                <a href="index.php?page=credits&package=<?php echo $package['id']; ?>" class="btn btn-primary">
                                    Buy Now
                                </a>
                            <?php else: ?>
                                <a href="index.php?page=register" class="btn btn-primary">
                                    Sign Up to Purchase
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Call to Action -->
<div class="bg-primary text-white py-5">
    <div class="container text-center">
        <h2 class="mb-4">Ready to Start Making Calls?</h2>
        <p class="lead mb-4">Sign up now and get started with browser-based calling in minutes.</p>
        <?php if (!isAuthenticated()): ?>
            <a href="index.php?page=register" class="btn btn-light btn-lg me-2">
                <i class="fas fa-user-plus"></i> Create an Account
            </a>
        <?php else: ?>
            <a href="index.php?page=call" class="btn btn-light btn-lg me-2">
                <i class="fas fa-phone"></i> Make a Call
            </a>
            <a href="index.php?page=credits" class="btn btn-outline-light btn-lg">
                <i class="fas fa-coins"></i> Buy Credits
            </a>
        <?php endif; ?>
    </div>
</div> 