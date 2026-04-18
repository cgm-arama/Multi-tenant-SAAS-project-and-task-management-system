<?php
// FILE: /app/views/projects/edit.php
?>
<div class="container">
    <div class="page-header">
        <h1>Edit Project</h1>
        <a href="<?php echo BASE_URL; ?>/projects/view/<?php echo $project['id']; ?>" class="btn">Back to Project</a>
    </div>

    <form method="POST" action="<?php echo BASE_URL; ?>/projects/update/<?php echo $project['id']; ?>" class="form-card">
        <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">

        <div class="form-group">
            <label for="name">Project Name</label>
            <input
                type="text"
                id="name"
                name="name"
                value="<?php echo e($project['name'] ?? ''); ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea
                id="description"
                name="description"
                rows="4"
            ><?php echo e($project['description'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label for="priority">Priority</label>
            <select id="priority" name="priority">
                <option value="low" <?php echo (($project['priority'] ?? '') === 'low') ? 'selected' : ''; ?>>Low</option>
                <option value="medium" <?php echo (($project['priority'] ?? '') === 'medium') ? 'selected' : ''; ?>>Medium</option>
                <option value="high" <?php echo (($project['priority'] ?? '') === 'high') ? 'selected' : ''; ?>>High</option>
            </select>
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="active" <?php echo (($project['status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active</option>
                <option value="completed" <?php echo (($project['status'] ?? '') === 'completed') ? 'selected' : ''; ?>>Completed</option>
                <option value="archived" <?php echo (($project['status'] ?? '') === 'archived') ? 'selected' : ''; ?>>Archived</option>
            </select>
        </div>

        <div class="form-group">
            <label for="color">Color</label>
            <input
                type="color"
                id="color"
                name="color"
                value="<?php echo e($project['color'] ?? '#3498db'); ?>"
            >
        </div>

        <div class="form-group">
            <label for="start_date">Start Date</label>
            <input
                type="date"
                id="start_date"
                name="start_date"
                value="<?php echo !empty($project['start_date']) ? e(date('Y-m-d', strtotime($project['start_date']))) : ''; ?>"
            >
        </div>

        <div class="form-group">
            <label for="due_date">Due Date</label>
            <input
                type="date"
                id="due_date"
                name="due_date"
                value="<?php echo !empty($project['due_date']) ? e(date('Y-m-d', strtotime($project['due_date']))) : ''; ?>"
            >
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="<?php echo BASE_URL; ?>/projects/view/<?php echo $project['id']; ?>" class="btn">Cancel</a>
        </div>
    </form>
</div>