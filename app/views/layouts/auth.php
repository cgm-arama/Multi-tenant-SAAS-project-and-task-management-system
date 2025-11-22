<?php
// FILE: /app/views/layouts/auth.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? e($title) . ' - ' : ''; ?>SplashProjects</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-header">
            <h1>SplashProjects</h1>
            <p>Project Management Made Simple</p>
        </div>
        <?php echo $content; ?>
    </div>
</body>
</html>
