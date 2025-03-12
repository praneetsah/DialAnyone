<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-LRBRX87E2P"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-LRBRX87E2P');
    </script>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-7773728151486066"
    crossorigin="anonymous"></script>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Favicons -->
    <link rel="icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/img/favicon/favicon-16x16.png">
    <link rel="manifest" href="assets/img/favicon/site.webmanifest">
    <meta name="msapplication-TileColor" content="#0d6efd">
    <meta name="theme-color" content="#0d6efd">
    
    <!-- SEO Meta Tags -->
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Dial Anyone</title>
    <meta name="description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Make cheap international calls to any phone number worldwide directly from your browser. No apps required. Pay as you go with Dial Anyone.'; ?>">
    <meta name="keywords" content="international calls, cheap calls, browser calls, voip calls, web calling, call any country, global calling, online phone calls, internet calling, cheap international calling">
    <meta name="author" content="Dial Anyone">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo 'https://dialanyone.com' . ($_SERVER['REQUEST_URI'] ?? ''); ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo 'https://dialanyone.com' . ($_SERVER['REQUEST_URI'] ?? ''); ?>">
    <meta property="og:title" content="<?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Dial Anyone">
    <meta property="og:description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Make cheap international calls to any phone number worldwide directly from your browser. No apps required. Pay as you go with Dial Anyone.'; ?>">
    <meta property="og:image" content="https://dialanyone.com/assets/img/og-image.jpg">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo 'https://dialanyone.com' . ($_SERVER['REQUEST_URI'] ?? ''); ?>">
    <meta property="twitter:title" content="<?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Dial Anyone">
    <meta property="twitter:description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Make cheap international calls to any phone number worldwide directly from your browser. No apps required. Pay as you go with Dial Anyone.'; ?>">
    <meta property="twitter:image" content="https://dialanyone.com/assets/img/twitter-image.jpg">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Twilio Client JS (only include on call page) -->
    <?php if (isset($includeTwilioJs) && $includeTwilioJs): ?>
    <script src="https://sdk.twilio.com/js/client/releases/1.14.0/twilio.js"></script>
    <?php endif; ?>
    
    <!-- Stripe JS (only include on payment page) -->
    <?php if (isset($includeStripeJs) && $includeStripeJs): ?>
    <script src="https://js.stripe.com/v3/"></script>
    <?php endif; ?>
</head>
<body>
    <!-- Beta Information Bar -->
    <div id="beta-info-bar" class="beta-bar" style="position: sticky; top: 0; background-color: #d4edda; color: #155724; text-align: center; padding: 8px 0; font-size: 14px; z-index: 1030; border-bottom: 1px solid #c3e6cb; width: 100%;">
        <div class="container">
            <strong>Beta Version:</strong> Everything works but just in case, the website is still in beta. For feedback or bug reports, please ping me at <a href="https://x.com/SahPraneet" target="_blank" style="color: #155724; text-decoration: underline;">@SahPraneet</a> or <a href="https://x.com/TryDialAnyone" target="_blank" style="color: #155724; text-decoration: underline;">@TryDialAnyone</a>.
            <button type="button" class="btn-close btn-close-white" aria-label="Close" style="font-size: 10px; float: right; cursor: pointer; opacity: 0.7;" onclick="document.getElementById('beta-info-bar').style.display='none';"></button>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-phone-alt"></i> Dial Anyone
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isAuthenticated()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=dashboard">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=call">
                                <i class="fas fa-phone"></i> Make a Call
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=call-history">
                                <i class="fas fa-history"></i> Call History
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=calling-rates">
                                <i class="fas fa-tags"></i> Calling Rates
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=credits">
                                <i class="fas fa-coins"></i> Credits: <?php 
                                // Get fresh credits from the database if user is logged in
                                try {
                                    if (isset($_SESSION['user_id'])) {
                                        $user = getUserById($_SESSION['user_id']);
                                        if ($user && isset($user['credits'])) {
                                            $_SESSION['user_credits'] = $user['credits'];
                                        }
                                    }
                                } catch (Exception $e) {
                                    // Silent fail - log the error but continue with session credits
                                    error_log("Error updating credits in header: " . $e->getMessage(), 0, 'logs/credits-debug.log');
                                }
                                echo formatCredits($_SESSION['user_credits'] ?? 0); 
                                ?>
                            </a>
                        </li>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-shield"></i> Admin
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                    <li><a class="dropdown-item" href="index.php?page=admin/dashboard">Admin Dashboard</a></li>
                                    <li><a class="dropdown-item" href="index.php?page=admin/users">Manage Users</a></li>
                                    <li><a class="dropdown-item" href="index.php?page=admin/calls">Call Records</a></li>
                                    <li><a class="dropdown-item" href="index.php?page=admin/payments">Payment History</a></li>
                                    <li><a class="dropdown-item" href="index.php?page=admin/coupons">Manage Coupons</a></li>
                                    <li><a class="dropdown-item" href="index.php?page=admin/settings">System Settings</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user"></i> <?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="index.php?page=profile">Profile</a></li>
                                <li><a class="dropdown-item" href="index.php?page=settings">Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="index.php?page=logout">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=home">
                                <i class="fas fa-home"></i> Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=calling-rates">
                                <i class="fas fa-tags"></i> Calling Rates
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=login">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=register">
                                <i class="fas fa-user-plus"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Flash messages -->
    <?php if ($flashMessage = getFlashMessage()): ?>
        <div class="container mt-3">
            <div class="alert alert-<?php echo $flashMessage['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $flashMessage['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Breadcrumbs (for SEO and navigation) -->
    <?php 
    // Get current page from URL
    $currentPage = $_GET['page'] ?? 'home';
    // Only show breadcrumbs if NOT on the homepage
    if ($currentPage !== 'home' && isset($pageTitle)): 
    ?>
    <div class="container mt-2">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=home">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo $pageTitle; ?></li>
            </ol>
        </nav>
    </div>
    <?php endif; ?>
    
    <!-- Main content -->
    <main class="py-4"><?php // Content will be inserted here ?> 