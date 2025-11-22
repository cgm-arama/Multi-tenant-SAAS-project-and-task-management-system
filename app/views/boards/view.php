<?php
// FILE: /app/views/boards/view.php
?>
<div class="board-view">
    <div class="board-header">
        <div class="container">
            <div class="breadcrumb">
                <a href="<?php echo BASE_URL; ?>/projects">Projects</a> /
                <a href="<?php echo BASE_URL; ?>/projects/<?php echo $project['id']; ?>"><?php echo e($project['name']); ?></a> /
                <?php echo e($board['name']); ?>
            </div>
            <h1><?php echo e($board['name']); ?></h1>
            <button class="btn btn-primary" onclick="showCreateTaskModal()">+ Add Task</button>
        </div>
    </div>

    <div class="board-container">
        <div class="kanban-board">
            <?php foreach ($board['columns'] as $column): ?>
                <div class="kanban-column" data-column-id="<?php echo $column['id']; ?>">
                    <div class="column-header">
                        <h3><?php echo e($column['name']); ?></h3>
                        <span class="task-count"><?php echo count($column['tasks']); ?></span>
                        <?php if ($column['wip_limit']): ?>
                            <span class="wip-limit">WIP: <?php echo $column['wip_limit']; ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="column-tasks" data-column-id="<?php echo $column['id']; ?>">
                        <?php foreach ($column['tasks'] as $task): ?>
                            <div class="task-card" data-task-id="<?php echo $task['id']; ?>" draggable="true">
                                <h4>
                                    <a href="<?php echo BASE_URL; ?>/tasks/<?php echo $task['id']; ?>">
                                        <?php echo e($task['title']); ?>
                                    </a>
                                </h4>
                                <?php if ($task['description']): ?>
                                    <p class="task-description"><?php echo e(substr($task['description'], 0, 100)); ?></p>
                                <?php endif; ?>

                                <div class="task-meta">
                                    <?php if ($task['due_date']): ?>
                                        <span class="task-due <?php echo strtotime($task['due_date']) < time() ? 'overdue' : ''; ?>">
                                            <?php echo date('M d', strtotime($task['due_date'])); ?>
                                        </span>
                                    <?php endif; ?>

                                    <span class="priority-<?php echo $task['priority']; ?>">
                                        <?php echo ucfirst($task['priority']); ?>
                                    </span>

                                    <?php if ($task['assignee_name']): ?>
                                        <span class="assignee" title="<?php echo e($task['assignee_name']); ?>">
                                            <?php echo substr($task['assignee_name'], 0, 1); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="task-indicators">
                                    <?php if ($task['comment_count'] > 0): ?>
                                        <span title="Comments">💬 <?php echo $task['comment_count']; ?></span>
                                    <?php endif; ?>

                                    <?php if ($task['attachment_count'] > 0): ?>
                                        <span title="Attachments">📎 <?php echo $task['attachment_count']; ?></span>
                                    <?php endif; ?>

                                    <?php if ($task['checklist_total'] > 0): ?>
                                        <span title="Checklist">
                                            ✓ <?php echo $task['checklist_completed']; ?>/<?php echo $task['checklist_total']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="kanban-column add-column">
                <button class="btn-add-column" onclick="showAddColumnModal()">+ Add Column</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Task Modal -->
<div id="createTaskModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal('createTaskModal')">&times;</span>
        <h2>Create Task</h2>
        <form id="createTaskForm" onsubmit="createTask(event)">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="form-group">
                <label>Column</label>
                <select name="column_id" required>
                    <?php foreach ($board['columns'] as $column): ?>
                        <option value="<?php echo $column['id']; ?>"><?php echo e($column['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label>Assign To</label>
                <select name="assigned_to">
                    <option value="">Unassigned</option>
                    <?php foreach ($members as $member): ?>
                        <option value="<?php echo $member['id']; ?>"><?php echo e($member['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Priority</label>
                <select name="priority">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </div>

            <div class="form-group">
                <label>Due Date</label>
                <input type="date" name="due_date">
            </div>

            <button type="submit" class="btn btn-primary">Create Task</button>
        </form>
    </div>
</div>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
const BOARD_ID = <?php echo $board['id']; ?>;
</script>
<script src="<?php echo BASE_URL; ?>/assets/js/kanban.js"></script>
