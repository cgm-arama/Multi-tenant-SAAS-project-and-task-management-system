<?php
// FILE: /app/controllers/BoardController.php

/**
 * Board Controller
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Manages Kanban boards within projects.
 */
class BoardController extends Controller
{
    /**
     * Show board with Kanban view
     */
    public function view($boardId)
    {
        $this->requireAuth();

        $boardModel = $this->model('Board');
        $board = $boardModel->getBoardWithColumns($boardId);

        if (!$board) {
            $this->redirect('/dashboard?error=board_not_found');
        }

        // Check project access
        $projectModel = $this->model('Project');
        if (!$projectModel->userHasAccess($board['project_id'], $this->getUserId())) {
            $this->redirect('/dashboard?error=unauthorized');
        }

        // Get tasks for each column
        $taskModel = $this->model('Task');
        foreach ($board['columns'] as &$column) {
            $column['tasks'] = $taskModel->getTasksByColumn($column['id']);
        }

        // Get project details
        $project = $projectModel->find($board['project_id']);

        // Get labels for filtering
        $labelModel = $this->model('Label');
        $labels = $labelModel->getLabels();

        // Get team members for assignment
        $userModel = $this->model('User');
        $members = $userModel->getUsersByTenant();

        $data = [
            'board' => $board,
            'project' => $project,
            'labels' => $labels,
            'members' => $members,
            'csrf_token' => $this->getCSRF()
        ];

        $this->view->render('boards/view', $data);
    }

    /**
     * Create new board
     */
    public function create($projectId)
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/projects/' . $projectId);
        }

        // Validate CSRF
        if (!$this->validateCSRF($_POST['csrf_token'] ?? '')) {
            $this->redirect('/projects/' . $projectId . '?error=invalid_request');
        }

        // Check project access
        $projectModel = $this->model('Project');
        if (!$projectModel->userHasAccess($projectId, $this->getUserId())) {
            $this->redirect('/projects?error=unauthorized');
        }

        $name = $this->sanitize($_POST['name'] ?? '');
        $description = $this->sanitize($_POST['description'] ?? '');

        $errors = $this->validate(['name' => $name], ['name' => 'required|min:2']);

        if (!empty($errors)) {
            $this->redirect('/projects/' . $projectId . '?error=validation_failed');
        }

        try {
            $boardModel = $this->model('Board');
            $boardId = $boardModel->createBoardWithColumns([
                'tenant_id' => $this->getTenantId(),
                'project_id' => $projectId,
                'name' => $name,
                'description' => $description,
                'position' => 0
            ]);

            // Log activity
            $activityModel = $this->model('Activity');
            $activityModel->log([
                'user_id' => $this->getUserId(),
                'project_id' => $projectId,
                'action_type' => 'board_created',
                'message' => Auth::user()['name'] . ' created board "' . $name . '"'
            ]);

            $this->redirect('/boards/' . $boardId);

        } catch (Exception $e) {
            $this->redirect('/projects/' . $projectId . '?error=create_failed');
        }
    }

    /**
     * Add column to board
     */
    public function addColumn($boardId)
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/boards/' . $boardId);
        }

        // Validate CSRF
        if (!$this->validateCSRF($_POST['csrf_token'] ?? '')) {
            $this->redirect('/boards/' . $boardId . '?error=invalid_request');
        }

        $boardModel = $this->model('Board');
        $board = $boardModel->find($boardId);

        if (!$board) {
            $this->redirect('/dashboard?error=board_not_found');
        }

        // Check access
        $projectModel = $this->model('Project');
        if (!$projectModel->userHasAccess($board['project_id'], $this->getUserId())) {
            $this->redirect('/dashboard?error=unauthorized');
        }

        $name = $this->sanitize($_POST['name'] ?? '');
        $wipLimit = $_POST['wip_limit'] ?? null;

        try {
            $columnModel = $this->model('Column');
            $columnModel->create([
                'tenant_id' => $this->getTenantId(),
                'board_id' => $boardId,
                'name' => $name,
                'wip_limit' => $wipLimit ?: null,
                'position' => 999
            ]);

            $this->redirect('/boards/' . $boardId . '?success=column_added');

        } catch (Exception $e) {
            $this->redirect('/boards/' . $boardId . '?error=add_column_failed');
        }
    }

    /**
     * Delete column
     */
    public function deleteColumn($columnId)
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request method'], 405);
        }

        // Validate CSRF
        if (!$this->validateCSRF($_POST['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $columnModel = $this->model('Column');
        $column = $columnModel->find($columnId);

        if (!$column) {
            $this->json(['error' => 'Column not found'], 404);
        }

        // Check if column has tasks
        $taskModel = $this->model('Task');
        $taskCount = $taskModel->countTasks(['column_id' => $columnId]);

        if ($taskCount > 0) {
            $this->json(['error' => 'Cannot delete column with tasks'], 400);
        }

        try {
            $columnModel->delete($columnId);
            $this->json(['success' => true, 'message' => 'Column deleted']);

        } catch (Exception $e) {
            $this->json(['error' => 'Delete failed'], 500);
        }
    }
}
