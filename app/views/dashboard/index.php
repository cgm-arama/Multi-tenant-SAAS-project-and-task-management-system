<?php
// FILE: /app/views/dashboard/index.php
?>
<div class="container dashboard">
    <?php if (isset($welcome) && $welcome): ?>
        <div class="alert alert-success">
            <h3>Welcome to SplashProjects! 🎉</h3>
            <p>Your workspace has been created successfully. Start by creating your first project!</p>
        </div>
    <?php endif; ?>

    <div class="dashboard-header">
        <h1>Dashboard</h1>
        <a href="<?php echo BASE_URL; ?>/projects/create" class="btn btn-primary">New Project</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Projects</h3>
            <div class="stat-value"><?php echo $total_projects; ?></div>
            <div class="stat-label"><?php echo $active_projects; ?> active</div>
        </div>
        <div class="stat-card">
            <h3>Tasks</h3>
            <div class="stat-value"><?php echo $total_tasks; ?></div>
            <div class="stat-label"><?php echo $completed_tasks; ?> completed</div>
        </div>
        <div class="stat-card">
            <h3>In Progress</h3>
            <div class="stat-value"><?php echo $in_progress_tasks; ?></div>
            <div class="stat-label">tasks</div>
        </div>
        <div class="stat-card">
            <h3>Overdue</h3>
            <div class="stat-value"><?php echo count($overdue_tasks); ?></div>
            <div class="stat-label">tasks</div>
        </div>
    </div>

    <div class="dashboard-content">
        <div class="dashboard-main">
            <section class="card">
                <h2>My Tasks</h2>
                <?php if (empty($my_tasks)): ?>
                    <p class="empty-state">No tasks assigned to you</p>
                <?php else: ?>
                    <div class="task-list">
                        <?php foreach ($my_tasks as $task): ?>
                            <div class="task-item">
                                <a href="<?php echo BASE_URL; ?>/tasks/<?php echo $task['id']; ?>">
                                    <h4><?php echo e($task['title']); ?></h4>
                                    <p><?php echo e($task['project_name']); ?> • <?php echo e($task['board_name']); ?></p>
                                    <?php if ($task['due_date']): ?>
                                        <span class="task-due <?php echo strtotime($task['due_date']) < time() ? 'overdue' : ''; ?>">
                                            Due: <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="card">
                <h2>Recent Projects</h2>
                <?php if (empty($recent_projects)): ?>
                    <p class="empty-state">No projects yet. <a href="<?php echo BASE_URL; ?>/projects/create">Create one now</a></p>
                <?php else: ?>
                    <div class="project-list">
                        <?php foreach ($recent_projects as $project): ?>
                            <div class="project-item">
                                <a href="<?php echo BASE_URL; ?>/projects/<?php echo $project['id']; ?>">
                                    <h4><?php echo e($project['name']); ?></h4>
                                    <p><?php echo $project['task_count']; ?> tasks • <?php echo $project['completed_count']; ?> completed</p>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <div class="dashboard-sidebar">
            <section class="card">
                <h2>Activity</h2>
                <?php if (empty($recent_activities)): ?>
                    <p class="empty-state">No recent activity</p>
                <?php else: ?>
                    <div class="activity-list">
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <p><?php echo e($activity['message']); ?></p>
                                <small><?php echo date('M d, H:i', strtotime($activity['created_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <?php if ($subscription): ?>
                <section class="card">
                    <h2>Subscription</h2>
                    <p><strong><?php echo e($subscription['plan_name']); ?></strong></p>
                    <p class="small">Status: <?php echo ucfirst($subscription['status']); ?></p>
                    <p class="small">Renews: <?php echo date('M d, Y', strtotime($subscription['current_period_end'])); ?></p>
                </section>
            <?php endif; ?>
        </div>
    </div>
</div>
