<?php
// FILE: /app/views/projects/index.php
?>
<div class="container">
    <div class="page-header">
        <h1>Projects</h1>
        <a href="<?php echo BASE_URL; ?>/projects/create" class="btn btn-primary">New Project</a>
    </div>

    <div class="filters">
        <form method="GET" class="filter-form">
            <input type="search" name="search" placeholder="Search projects..." value="<?php echo e($filters['search'] ?? ''); ?>">
            <select name="status">
                <option value="">All Status</option>
                <option value="active" <?php echo ($filters['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="archived" <?php echo ($filters['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archived</option>
            </select>
            <button type="submit" class="btn">Filter</button>
        </form>
    </div>

    <?php if (empty($projects)): ?>
        <div class="empty-state">
            <h2>No projects yet</h2>
            <p>Create your first project to get started</p>
            <a href="<?php echo BASE_URL; ?>/projects/create" class="btn btn-primary">Create Project</a>
        </div>
    <?php else: ?>
        <div class="project-grid">
            <?php foreach ($projects as $project): ?>
                <div class="project-card">
                    <div class="project-color" style="background-color: <?php echo e($project['color']); ?>"></div>
                    <h3>
                        <a href="<?php echo BASE_URL; ?>/projects/<?php echo $project['id']; ?>">
                            <?php echo e($project['name']); ?>
                        </a>
                    </h3>
                    <p class="project-description"><?php echo e($project['description'] ?? ''); ?></p>
                    <div class="project-meta">
                        <span>Owner: <?php echo e($project['owner_name']); ?></span>
                        <span><?php echo $project['task_count']; ?> tasks</span>
                        <span class="status-<?php echo $project['status']; ?>"><?php echo ucfirst($project['status']); ?></span>
                    </div>
                    <div class="project-progress">
                        <?php
                        $progress = $project['task_count'] > 0
                            ? round(($project['completed_count'] / $project['task_count']) * 100)
                            : 0;
                        ?>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <span class="progress-text"><?php echo $progress; ?>% complete</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="pagination">
                <?php if ($pagination['has_prev']): ?>
                    <a href="?page=<?php echo $pagination['page'] - 1; ?>" class="btn">Previous</a>
                <?php endif; ?>

                <span>Page <?php echo $pagination['page']; ?> of <?php echo $pagination['total_pages']; ?></span>

                <?php if ($pagination['has_next']): ?>
                    <a href="?page=<?php echo $pagination['page'] + 1; ?>" class="btn">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
