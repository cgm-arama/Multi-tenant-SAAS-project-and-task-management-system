<?php
// FILE: /app/views/projects/view.php
?>
<div class="container">
    <div class="page-header">
        <h1><?php echo e($project['name'] ?? 'Project'); ?></h1>
        <a href="<?php echo BASE_URL; ?>/projects/edit/<?php echo $project['id']; ?>" class="btn btn-primary">Edit Project</a>
    </div>

    <div class="project-details">
        <p><strong>Description:</strong> <?php echo e($project['description'] ?? 'No description'); ?></p>
        <p><strong>Status:</strong> <?php echo e($project['status'] ?? 'unknown'); ?></p>
        <p><strong>Priority:</strong> <?php echo e($project['priority'] ?? 'medium'); ?></p>
        <p><strong>Owner:</strong> <?php echo e($project['owner_name'] ?? 'Unknown'); ?></p>
        <p><strong>Boards:</strong> <?php echo e($project['board_count'] ?? 0); ?></p>
        <p><strong>Tasks:</strong> <?php echo e($project['task_count'] ?? 0); ?></p>
        <p><strong>Members:</strong> <?php echo e($project['member_count'] ?? 0); ?></p>
<form method="POST" action="<?php echo BASE_URL; ?>/boards/create/<?php echo $project['id']; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">

    <input type="text" name="name" placeholder="Board name" required>
    <input type="text" name="description" placeholder="Description">

    <button type="submit" class="btn btn-primary">Create Board</button>
</form>
    <hr>

    <h2>Project Boards</h2>
    <?php if (!empty($boards)): ?>
        <ul>
            <?php foreach ($boards as $board): ?>
                <li>
                    <a href="<?php echo BASE_URL; ?>/boards/view/<?php echo $board['id']; ?>">
                        <?php echo e($board['name']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No boards yet.</p>
    <?php endif; ?>
        <hr>

<h2>Boards</h2>

<?php if (!empty($boards)): ?>
    <ul>
        <?php foreach ($boards as $board): ?>
            <li>
                <a href="<?php echo BASE_URL; ?>/boards/view/<?php echo $board['id']; ?>">
                    <?php echo e($board['name']); ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>No boards yet.</p>
<?php endif; ?>
    <hr>

    <h2>Recent Activity</h2>
    <?php if (!empty($activities)): ?>
        <ul>
            <?php foreach ($activities as $activity): ?>
                <li><?php echo e($activity['message'] ?? 'Activity'); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No recent activity.</p>
    <?php endif; ?>
</div>