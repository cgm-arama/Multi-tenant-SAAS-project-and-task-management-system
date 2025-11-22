<?php
// FILE: /app/views/auth/register.php
?>
<div class="auth-card">
    <h2>Create Your Account</h2>

    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <?php
            $messages = [
                'validation_failed' => 'Please check your input',
                'email_exists' => 'Email already registered',
                'registration_failed' => 'Registration failed. Please try again',
                'invalid_request' => 'Invalid request'
            ];
            echo $messages[$error] ?? 'An error occurred';
            ?>
        </div>
    <?php endif; ?>

    <form action="<?php echo BASE_URL; ?>/auth/doRegister" method="POST" class="auth-form">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

        <div class="form-group">
            <label for="name">Your Name</label>
            <input type="text" id="name" name="name" required autofocus>
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required minlength="6">
        </div>

        <div class="form-group">
            <label for="workspace_name">Workspace Name</label>
            <input type="text" id="workspace_name" name="workspace_name" required placeholder="e.g., Acme Corporation">
        </div>

        <button type="submit" class="btn btn-primary btn-block">Create Account</button>
    </form>

    <div class="auth-footer">
        <p>Already have an account? <a href="<?php echo BASE_URL; ?>/auth/login">Sign in</a></p>
    </div>
</div>
