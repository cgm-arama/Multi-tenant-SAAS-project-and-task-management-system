<?php
// FILE: /app/views/auth/login.php
?>
<div class="auth-card">
    <h2>Sign In</h2>

    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <?php
            $messages = [
                'invalid_credentials' => 'Invalid email or password',
                'account_inactive' => 'Your account is inactive',
                'workspace_suspended' => 'Your workspace has been suspended',
                'invalid_request' => 'Invalid request'
            ];
            echo $messages[$error] ?? 'An error occurred';
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo e($success); ?></div>
    <?php endif; ?>

    <form action="<?php echo BASE_URL; ?>/auth/doLogin" method="POST" class="auth-form">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
    </form>

    <div class="auth-footer">
        <p>Don't have an account? <a href="<?php echo BASE_URL; ?>/auth/register">Sign up</a></p>
    </div>

    <div class="demo-credentials">
        <p><strong>Demo Credentials:</strong></p>
        <p>Email: john@acme.com | Password: password123</p>
    </div>
</div>
