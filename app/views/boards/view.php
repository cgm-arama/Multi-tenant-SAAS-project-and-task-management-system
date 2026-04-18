<?php
// FILE: /app/views/boards/view.php
?>
<div class="board-view">
    <div class="board-header">
        <div class="container">
            <div class="breadcrumb">
                <a href="<?php echo BASE_URL; ?>/projects">Projects</a> /
                <a href="<?php echo BASE_URL; ?>/projects/view/<?php echo $project['id']; ?>">
                    <?php echo e($project['name']); ?>
                </a> /
                <?php echo e($board['name']); ?>
            </div>

            <h1><?php echo e($board['name']); ?></h1>
            <button class="btn btn-primary" onclick="document.getElementById('createTaskModal').style.display='block'">
                + Add Task
            </button>
        </div>
    </div>

    <div class="board-container">
        <div class="kanban-board">

            <?php foreach ($board['columns'] as $column): ?>
                <div class="kanban-column">

                    <div class="column-header">
                        <h3><?php echo e($column['name']); ?></h3>
                    </div>

                    <div class="column-tasks">

                        <?php if (!empty($column['tasks'])): ?>
                            <?php foreach ($column['tasks'] as $task): ?>
                                
                                <div class="task-card">

                                    <h4><?php echo e($task['title']); ?></h4>

                                    <?php if (!empty($task['description'])): ?>
                                        <p><?php echo e($task['description']); ?></p>
                                    <?php endif; ?>

                                    <div class="task-meta">

                                        <!-- Overdue logic FIXED -->
                                        <?php if (!empty($task['due_date'])): ?>
                                            <span class="task-due 
                                            <?php 
                                            if (strtotime($task['due_date']) < time() && $task['status'] !== 'completed') {
                                                echo 'overdue';
                                            }
                                            ?>">
                                                <?php echo date('M d', strtotime($task['due_date'])); ?>
                                            </span>
                                        <?php endif; ?>

                                        <span><?php echo ucfirst($task['priority']); ?></span>

                                    </div>

                                    <!-- ✔ DONE BUTTON -->
                                    <form method="POST" action="<?php echo BASE_URL; ?>/tasks/update/<?php echo $task['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="status" value="completed">

                                            <button type="submit" class="btn btn-success" style="margin-top:5px;">
                                              ✔ Done
                                             </button>
                                    </form>

                                </div>

                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </div>
</div>

<!-- TASK MODAL -->
<div id="createTaskModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span onclick="document.getElementById('createTaskModal').style.display='none'" style="cursor:pointer;">&times;</span>

        <h2>Create Task</h2>

        <form method="POST" action="<?php echo BASE_URL; ?>/tasks/create">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="board_id" value="<?php echo $board['id']; ?>">

            <div class="form-group">
                <label>Column</label>
                <select name="column_id" required>
                    <?php foreach ($board['columns'] as $column): ?>
                        <option value="<?php echo $column['id']; ?>">
                            <?php echo e($column['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description"></textarea>
            </div>

            <div class="form-group">
                <label>Assign To</label>
                <select name="assigned_to">
                    <option value="">Unassigned</option>
                    <?php foreach ($members as $member): ?>
                        <option value="<?php echo $member['id']; ?>">
                            <?php echo e($member['name']); ?>
                        </option>
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