<?php
// FILE: /app/views/home/index.php
?>
<section class="hero">
    <div class="container">
        <h1>Manage Projects Like a Pro</h1>
        <p class="lead">SplashProjects is a powerful multi-tenant project management platform with Kanban boards, team collaboration, and real-time updates.</p>
        <div class="hero-actions">
            <a href="<?php echo BASE_URL; ?>/auth/register" class="btn btn-primary btn-lg">Get Started Free</a>
            <a href="<?php echo BASE_URL; ?>/auth/login" class="btn btn-secondary btn-lg">Sign In</a>
        </div>
    </div>
</section>

<section class="features">
    <div class="container">
        <h2>Features</h2>
        <div class="feature-grid">
            <div class="feature">
                <h3>Kanban Boards</h3>
                <p>Visualize your workflow with customizable Kanban boards</p>
            </div>
            <div class="feature">
                <h3>Team Collaboration</h3>
                <p>Work together with your team in real-time</p>
            </div>
            <div class="feature">
                <h3>Task Management</h3>
                <p>Create, assign, and track tasks with ease</p>
            </div>
            <div class="feature">
                <h3>File Attachments</h3>
                <p>Attach files to tasks and keep everything organized</p>
            </div>
        </div>
    </div>
</section>

<section class="pricing">
    <div class="container">
        <h2>Simple Pricing</h2>
        <div class="pricing-grid">
            <?php foreach ($plans as $plan): ?>
                <div class="pricing-card">
                    <h3><?php echo e($plan['name']); ?></h3>
                    <div class="price">$<?php echo number_format($plan['price'], 0); ?><span>/month</span></div>
                    <p><?php echo e($plan['description']); ?></p>
                    <ul class="feature-list">
                        <li><?php echo $plan['max_projects']; ?> Projects</li>
                        <li><?php echo $plan['max_users']; ?> Users</li>
                        <li><?php echo number_format($plan['max_tasks']); ?> Tasks</li>
                        <li><?php echo $plan['max_storage_mb']; ?>MB Storage</li>
                    </ul>
                    <a href="<?php echo BASE_URL; ?>/auth/register" class="btn btn-outline">Get Started</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
