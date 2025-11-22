<?php
// FILE: /app/views/layouts/default.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? e($title) . ' - ' : ''; ?>SplashProjects</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <a href="<?php echo BASE_URL; ?>/dashboard">SplashProjects</a>
            </div>
            <div class="navbar-menu">
                <a href="<?php echo BASE_URL; ?>/dashboard">Dashboard</a>
                <a href="<?php echo BASE_URL; ?>/projects">Projects</a>
                <a href="<?php echo BASE_URL; ?>/users">Team</a>
                <a href="<?php echo BASE_URL; ?>/notifications" class="notification-link">
                    Notifications
                    <?php if (isset($unread_notifications) && $unread_notifications > 0): ?>
                        <span class="badge"><?php echo $unread_notifications; ?></span>
                    <?php endif; ?>
                </a>
                <div class="navbar-user">
                    <span><?php echo e($_SESSION['name'] ?? 'User'); ?></span>
                    <a href="<?php echo BASE_URL; ?>/auth/logout">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <?php if (Session::hasFlash('success')): ?>
            <div class="alert alert-success"><?php echo e(Session::getFlash('success')); ?></div>
        <?php endif; ?>

        <?php if (Session::hasFlash('error')): ?>
            <div class="alert alert-error"><?php echo e(Session::getFlash('error')); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error"><?php echo e(str_replace('_', ' ', ucfirst($_GET['error']))); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo e(str_replace('_', ' ', ucfirst($_GET['success']))); ?></div>
        <?php endif; ?>

        <?php echo $content; ?>
    </main>

    <script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
</body>
</html>
