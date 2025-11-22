<?php
// FILE: /app/controllers/TaskController.php

/**
 * Task Controller
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Manages tasks (Kanban cards): create, update, move, delete.
 */
class TaskController extends Controller
{
    /**
     * Show task details
     */
    public function view($taskId)
    {
        $this->requireAuth();

        $taskModel = $this->model('Task');
        $task = $taskModel->getTaskWithDetails($taskId);

        if (!$task) {
            $this->redirect('/dashboard?error=task_not_found');
        }

        // Check access
        $projectModel = $this->model('Project');
        if (!$projectModel->userHasAccess($task['project_id'], $this->getUserId())) {
            $this->redirect('/dashboard?error=unauthorized');
        }

        // Get available users for assignment
        $userModel = $this->model('User');
        $users = $userModel->getUsersByTenant();

        // Get available labels
        $labelModel = $this->model('Label');
        $labels = $labelModel->getLabels();

        $data = [
            'task' => $task,
            'users' => $users,
            'labels' => $labels,
            'csrf_token' => $this->getCSRF()
        ];

        $this->view->render('tasks/view', $data);
    }

    /**
     * Create new task
     */
    public function create()
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request method'], 405);
        }

        // Parse JSON body
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            $input = $_POST;
        }

        // Validate CSRF
        if (!$this->validateCSRF($input['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $columnId = (int)($input['column_id'] ?? 0);
        $title = $this->sanitize($input['title'] ?? '');
        $description = $this->sanitize($input['description'] ?? '');
        $assignedTo = !empty($input['assigned_to']) ? (int)$input['assigned_to'] : null;
        $priority = $input['priority'] ?? 'medium';
        $dueDate = $input['due_date'] ?? null;

        // Validate
        if (empty($title)) {
            $this->json(['error' => 'Title is required'], 400);
        }

        // Get column to find project and board
        $columnModel = $this->model('Column');
        $column = $columnModel->find($columnId);

        if (!$column) {
            $this->json(['error' => 'Column not found'], 404);
        }

        // Get board to find project
        $boardModel = $this->model('Board');
        $board = $boardModel->find($column['board_id']);

        if (!$board) {
            $this->json(['error' => 'Board not found'], 404);
        }

        // Check task limit
        $subscriptionModel = $this->model('Subscription');
        $limitCheck = $subscriptionModel->checkLimit('tasks');

        if (!$limitCheck['allowed']) {
            $this->json(['error' => 'Task limit reached'], 403);
        }

        try {
            $taskModel = $this->model('Task');
            $taskId = $taskModel->create([
                'tenant_id' => $this->getTenantId(),
                'project_id' => $board['project_id'],
                'board_id' => $board['id'],
                'column_id' => $columnId,
                'title' => $title,
                'description' => $description,
                'created_by' => $this->getUserId(),
                'assigned_to' => $assignedTo,
                'priority' => $priority,
                'due_date' => $dueDate ?: null,
                'status' => 'open',
                'position' => 0
            ]);

            // Update usage
            $usageModel = $this->model('Usage');
            $usageModel->increment('tasks');

            // Log activity
            $activityModel = $this->model('Activity');
            $activityModel->log([
                'user_id' => $this->getUserId(),
                'project_id' => $board['project_id'],
                'task_id' => $taskId,
                'action_type' => 'task_created',
                'message' => Auth::user()['name'] . ' created task "' . $title . '"'
            ]);

            // Create notification if assigned to someone
            if ($assignedTo && $assignedTo != $this->getUserId()) {
                $notificationModel = $this->model('Notification');
                $notificationModel->createNotification([
                    'user_id' => $assignedTo,
                    'type' => 'task_assigned',
                    'title' => 'New task assigned',
                    'message' => 'You have been assigned to "' . $title . '"',
                    'link' => '/tasks/' . $taskId
                ]);
            }

            // Get created task
            $task = $taskModel->getTaskWithDetails($taskId);

            $this->json(['success' => true, 'task' => $task]);

        } catch (Exception $e) {
            $this->json(['error' => 'Task creation failed'], 500);
        }
    }

    /**
     * Update task
     */
    public function update($taskId)
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request method'], 405);
        }

        // Parse JSON body
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            $input = $_POST;
        }

        // Validate CSRF
        if (!$this->validateCSRF($input['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $taskModel = $this->model('Task');
        $task = $taskModel->find($taskId);

        if (!$task) {
            $this->json(['error' => 'Task not found'], 404);
        }

        // Build update data
        $updateData = [];

        if (isset($input['title'])) {
            $updateData['title'] = $this->sanitize($input['title']);
        }

        if (isset($input['description'])) {
            $updateData['description'] = $this->sanitize($input['description']);
        }

        if (isset($input['assigned_to'])) {
            $updateData['assigned_to'] = !empty($input['assigned_to']) ? (int)$input['assigned_to'] : null;
        }

        if (isset($input['priority'])) {
            $updateData['priority'] = $input['priority'];
        }

        if (isset($input['status'])) {
            $updateData['status'] = $input['status'];
        }

        if (isset($input['due_date'])) {
            $updateData['due_date'] = $input['due_date'] ?: null;
        }

        try {
            $taskModel->update($taskId, $updateData);

            // Log activity
            $activityModel = $this->model('Activity');
            $activityModel->log([
                'user_id' => $this->getUserId(),
                'project_id' => $task['project_id'],
                'task_id' => $taskId,
                'action_type' => 'task_updated',
                'message' => Auth::user()['name'] . ' updated task "' . $task['title'] . '"'
            ]);

            // Create notification if assignee changed
            if (isset($updateData['assigned_to']) && $updateData['assigned_to'] != $task['assigned_to'] && $updateData['assigned_to']) {
                $notificationModel = $this->model('Notification');
                $notificationModel->createNotification([
                    'user_id' => $updateData['assigned_to'],
                    'type' => 'task_assigned',
                    'title' => 'Task assigned',
                    'message' => 'You have been assigned to "' . $task['title'] . '"',
                    'link' => '/tasks/' . $taskId
                ]);
            }

            $updatedTask = $taskModel->getTaskWithDetails($taskId);

            $this->json(['success' => true, 'task' => $updatedTask]);

        } catch (Exception $e) {
            $this->json(['error' => 'Update failed'], 500);
        }
    }

    /**
     * Move task to different column (drag and drop)
     */
    public function move($taskId)
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request method'], 405);
        }

        // Parse JSON body
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            $this->json(['error' => 'Invalid JSON'], 400);
        }

        $newColumnId = (int)($input['column_id'] ?? 0);
        $newPosition = isset($input['position']) ? (int)$input['position'] : null;

        $taskModel = $this->model('Task');
        $task = $taskModel->find($taskId);

        if (!$task) {
            $this->json(['error' => 'Task not found'], 404);
        }

        // Get new column
        $columnModel = $this->model('Column');
        $newColumn = $columnModel->find($newColumnId);

        if (!$newColumn) {
            $this->json(['error' => 'Column not found'], 404);
        }

        // Check WIP limit
        $wipCheck = $columnModel->checkWipLimit($newColumnId);
        if ($wipCheck['exceeded'] && $task['column_id'] != $newColumnId) {
            $this->json([
                'error' => 'WIP limit exceeded',
                'wip_limit' => $wipCheck['limit'],
                'current' => $wipCheck['current']
            ], 400);
        }

        try {
            $taskModel->moveTask($taskId, $newColumnId, $newPosition);

            // Log activity
            $activityModel = $this->model('Activity');
            $activityModel->log([
                'user_id' => $this->getUserId(),
                'project_id' => $task['project_id'],
                'task_id' => $taskId,
                'action_type' => 'task_moved',
                'message' => Auth::user()['name'] . ' moved task "' . $task['title'] . '" to ' . $newColumn['name']
            ]);

            $this->json(['success' => true, 'message' => 'Task moved successfully']);

        } catch (Exception $e) {
            $this->json(['error' => 'Move failed'], 500);
        }
    }

    /**
     * Delete task
     */
    public function delete($taskId)
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request method'], 405);
        }

        // Validate CSRF
        if (!$this->validateCSRF($_POST['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $taskModel = $this->model('Task');
        $task = $taskModel->find($taskId);

        if (!$task) {
            $this->json(['error' => 'Task not found'], 404);
        }

        try {
            $taskModel->delete($taskId);

            // Update usage
            $usageModel = $this->model('Usage');
            $usageModel->decrement('tasks');

            $this->json(['success' => true, 'message' => 'Task deleted']);

        } catch (Exception $e) {
            $this->json(['error' => 'Delete failed'], 500);
        }
    }

    /**
     * Add comment to task
     */
    public function addComment($taskId)
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request method'], 405);
        }

        // Validate CSRF
        if (!$this->validateCSRF($_POST['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $comment = $this->sanitize($_POST['comment'] ?? '');

        if (empty($comment)) {
            $this->json(['error' => 'Comment is required'], 400);
        }

        $taskModel = $this->model('Task');
        $task = $taskModel->find($taskId);

        if (!$task) {
            $this->json(['error' => 'Task not found'], 404);
        }

        try {
            $commentModel = $this->model('Comment');
            $commentId = $commentModel->create([
                'tenant_id' => $this->getTenantId(),
                'task_id' => $taskId,
                'user_id' => $this->getUserId(),
                'comment' => $comment
            ]);

            // Log activity
            $activityModel = $this->model('Activity');
            $activityModel->log([
                'user_id' => $this->getUserId(),
                'project_id' => $task['project_id'],
                'task_id' => $taskId,
                'action_type' => 'comment_added',
                'message' => Auth::user()['name'] . ' commented on "' . $task['title'] . '"'
            ]);

            // Notify task owner/assignee
            if ($task['assigned_to'] && $task['assigned_to'] != $this->getUserId()) {
                $notificationModel = $this->model('Notification');
                $notificationModel->createNotification([
                    'user_id' => $task['assigned_to'],
                    'type' => 'comment_added',
                    'title' => 'New comment',
                    'message' => Auth::user()['name'] . ' commented on "' . $task['title'] . '"',
                    'link' => '/tasks/' . $taskId
                ]);
            }

            $this->json(['success' => true, 'comment_id' => $commentId]);

        } catch (Exception $e) {
            $this->json(['error' => 'Failed to add comment'], 500);
        }
    }

    /**
     * Add checklist item
     */
    public function addChecklist($taskId)
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request method'], 405);
        }

        // Validate CSRF
        if (!$this->validateCSRF($_POST['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $name = $this->sanitize($_POST['name'] ?? '');

        if (empty($name)) {
            $this->json(['error' => 'Name is required'], 400);
        }

        try {
            $checklistModel = $this->model('Checklist');
            $checklistId = $checklistModel->create([
                'tenant_id' => $this->getTenantId(),
                'task_id' => $taskId,
                'name' => $name,
                'completed' => 0,
                'position' => 0
            ]);

            $this->json(['success' => true, 'checklist_id' => $checklistId]);

        } catch (Exception $e) {
            $this->json(['error' => 'Failed to add checklist item'], 500);
        }
    }

    /**
     * Toggle checklist item
     */
    public function toggleChecklist($checklistId)
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request method'], 405);
        }

        try {
            $checklistModel = $this->model('Checklist');
            $checklistModel->toggleCompleted($checklistId);

            $this->json(['success' => true]);

        } catch (Exception $e) {
            $this->json(['error' => 'Failed to toggle checklist'], 500);
        }
    }

    /**
     * Upload attachment
     */
    public function upload($taskId)
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request method'], 405);
        }

        // Validate CSRF
        if (!$this->validateCSRF($_POST['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        if (!isset($_FILES['file'])) {
            $this->json(['error' => 'No file uploaded'], 400);
        }

        $file = $_FILES['file'];

        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'Upload error'], 400);
        }

        // Check file size
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            $this->json(['error' => 'File too large'], 400);
        }

        // Check file type
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_FILE_TYPES)) {
            $this->json(['error' => 'File type not allowed'], 400);
        }

        // Generate unique filename
        $filename = time() . '_' . uniqid() . '.' . $ext;
        $filePath = UPLOAD_PATH . $filename;

        try {
            // Create upload directory if it doesn't exist
            if (!file_exists(UPLOAD_PATH)) {
                mkdir(UPLOAD_PATH, 0755, true);
            }

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                $this->json(['error' => 'Failed to save file'], 500);
            }

            // Save to database
            $attachmentModel = $this->model('Attachment');
            $attachmentId = $attachmentModel->create([
                'tenant_id' => $this->getTenantId(),
                'task_id' => $taskId,
                'user_id' => $this->getUserId(),
                'filename' => $filename,
                'original_name' => $file['name'],
                'file_size' => $file['size'],
                'mime_type' => $file['type'],
                'file_path' => 'storage/uploads/' . $filename
            ]);

            // Log activity
            $taskModel = $this->model('Task');
            $task = $taskModel->find($taskId);

            $activityModel = $this->model('Activity');
            $activityModel->log([
                'user_id' => $this->getUserId(),
                'project_id' => $task['project_id'],
                'task_id' => $taskId,
                'action_type' => 'file_attached',
                'message' => Auth::user()['name'] . ' attached a file to "' . $task['title'] . '"'
            ]);

            $this->json(['success' => true, 'attachment_id' => $attachmentId, 'filename' => $file['name']]);

        } catch (Exception $e) {
            $this->json(['error' => 'Upload failed'], 500);
        }
    }
}
