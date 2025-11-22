<?php
// FILE: /app/views/layouts/landing.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SplashProjects - Multi-tenant Project Management Platform</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body class="landing-page">
    <nav class="landing-nav">
        <div class="container">
            <div class="nav-brand">SplashProjects</div>
            <div class="nav-links">
                <a href="<?php echo BASE_URL; ?>/auth/login">Login</a>
                <a href="<?php echo BASE_URL; ?>/auth/register" class="btn-primary">Get Started</a>
            </div>
        </div>
    </nav>

    <?php echo $content; ?>

    <footer class="landing-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> SplashProjects. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
