<?php
// FILE: /app/controllers/ProjectController.php

/**
 * Project Controller
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Manages projects: create, read, update, delete.
 */
class ProjectController extends Controller
{
    /**
     * List all projects
     */
    public function index()
    {
        $this->requireAuth();

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = ITEMS_PER_PAGE;

        $filters = [
            'status' => $_GET['status'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $projectModel = $this->model('Project');
        $projects = $projectModel->getProjects($filters, $perPage, ($page - 1) * $perPage);
        $total = $projectModel->countProjects($filters);

        $pagination = $this->paginate($total, $page, $perPage);

        $data = [
            'projects' => $projects,
            'pagination' => $pagination,
            'filters' => $filters
        ];

        $this->view->render('projects/index', $data);
    }

    /**
     * Show single project
     */
    public function view($projectId)
    {
        $this->requireAuth();

        $projectModel = $this->model('Project');
        $project = $projectModel->getProjectWithDetails($projectId);

        if (!$project) {
            $this->redirect('/projects?error=not_found');
        }

        // Check access
        if (!$projectModel->userHasAccess($projectId, $this->getUserId())) {
            $this->redirect('/projects?error=unauthorized');
        }

        // Get boards
        $boardModel = $this->model('Board');
        $boards = $boardModel->getBoardsByProject($projectId);

        // Get project members
        $memberModel = $this->model('ProjectMember');
        $members = $memberModel->getMembersByProject($projectId);

        // Get recent activities
        $activityModel = $this->model('Activity');
        $activities = $activityModel->getActivitiesByProject($projectId, 20);

        $data = [
            'project' => $project,
            'boards' => $boards,
            'members' => $members,
            'activities' => $activities
        ];

        $this->view->render('projects/view', $data);
    }

    /**
     * Show create project form
     */
    public function create()
    {
        $this->requireAuth();

        // Check project limit
        $subscriptionModel = $this->model('Subscription');
        $limitCheck = $subscriptionModel->checkLimit('projects');

        if (!$limitCheck['allowed']) {
            $this->redirect('/projects?error=limit_reached');
        }

        $data = [
            'csrf_token' => $this->getCSRF()
        ];

        $this->view->render('projects/create', $data);
    }

    /**
     * Store new project
     */
    public function store()
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/projects/create');
        }

        // Validate CSRF
        if (!$this->validateCSRF($_POST['csrf_token'] ?? '')) {
            $this->redirect('/projects/create?error=invalid_request');
        }

        // Check project limit
        $subscriptionModel = $this->model('Subscription');
        $limitCheck = $subscriptionModel->checkLimit('projects');

        if (!$limitCheck['allowed']) {
            $this->redirect('/projects?error=limit_reached');
        }

        // Get input
        $name = $this->sanitize($_POST['name'] ?? '');
        $description = $this->sanitize($_POST['description'] ?? '');
        $priority = $_POST['priority'] ?? 'medium';
        $color = $_POST['color'] ?? '#3498db';
        $startDate = $_POST['start_date'] ?? null;
        $dueDate = $_POST['due_date'] ?? null;

        // Validate
        $errors = $this->validate(['name' => $name], [
            'name' => 'required|min:3|max:255'
        ]);

        if (!empty($errors)) {
            $this->redirect('/projects/create?error=validation_failed');
        }

        try {
            $projectModel = $this->model('Project');
            $projectId = $projectModel->create([
                'tenant_id' => $this->getTenantId(),
                'name' => $name,
                'description' => $description,
                'owner_id' => $this->getUserId(),
                'status' => 'active',
                'priority' => $priority,
                'color' => $color,
                'start_date' => $startDate ?: null,
                'due_date' => $dueDate ?: null
            ]);

            // Update usage
            $usageModel = $this->model('Usage');
            $usageModel->increment('projects');

            // Log activity
            $activityModel = $this->model('Activity');
            $activityModel->log([
                'user_id' => $this->getUserId(),
                'project_id' => $projectId,
                'action_type' => 'project_created',
                'message' => Auth::user()['name'] . ' created project "' . $name . '"'
            ]);

            $this->redirect('/projects/' . $projectId . '?success=created');

        } catch (Exception $e) {
            $this->redirect('/projects/create?error=create_failed');
        }
    }

    /**
     * Show edit project form
     */
    public function edit($projectId)
    {
        $this->requireAuth();

        $projectModel = $this->model('Project');
        $project = $projectModel->find($projectId);

        if (!$project) {
            $this->redirect('/projects?error=not_found');
        }

        // Only owner or tenant admin can edit
        if ($project['owner_id'] != $this->getUserId() && !Auth::isTenantAdmin() && !Auth::isPlatformAdmin()) {
            $this->redirect('/projects?error=unauthorized');
        }

        $data = [
            'project' => $project,
            'csrf_token' => $this->getCSRF()
        ];

        $this->view->render('projects/edit', $data);
    }

    /**
     * Update project
     */
    public function update($projectId)
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/projects/' . $projectId . '/edit');
        }

        // Validate CSRF
        if (!$this->validateCSRF($_POST['csrf_token'] ?? '')) {
            $this->redirect('/projects/' . $projectId . '/edit?error=invalid_request');
        }

        $projectModel = $this->model('Project');
        $project = $projectModel->find($projectId);

        if (!$project) {
            $this->redirect('/projects?error=not_found');
        }

        // Check permission
        if ($project['owner_id'] != $this->getUserId() && !Auth::isTenantAdmin() && !Auth::isPlatformAdmin()) {
            $this->redirect('/projects?error=unauthorized');
        }

        // Get input
        $name = $this->sanitize($_POST['name'] ?? '');
        $description = $this->sanitize($_POST['description'] ?? '');
        $priority = $_POST['priority'] ?? 'medium';
        $status = $_POST['status'] ?? 'active';
        $color = $_POST['color'] ?? '#3498db';
        $startDate = $_POST['start_date'] ?? null;
        $dueDate = $_POST['due_date'] ?? null;

        // Validate
        $errors = $this->validate(['name' => $name], [
            'name' => 'required|min:3|max:255'
        ]);

        if (!empty($errors)) {
            $this->redirect('/projects/' . $projectId . '/edit?error=validation_failed');
        }

        try {
            $projectModel->update($projectId, [
                'name' => $name,
                'description' => $description,
                'priority' => $priority,
                'status' => $status,
                'color' => $color,
                'start_date' => $startDate ?: null,
                'due_date' => $dueDate ?: null
            ]);

            // Log activity
            $activityModel = $this->model('Activity');
            $activityModel->log([
                'user_id' => $this->getUserId(),
                'project_id' => $projectId,
                'action_type' => 'project_updated',
                'message' => Auth::user()['name'] . ' updated project "' . $name . '"'
            ]);

            $this->redirect('/projects/' . $projectId . '?success=updated');

        } catch (Exception $e) {
            $this->redirect('/projects/' . $projectId . '/edit?error=update_failed');
        }
    }

    /**
     * Delete project
     */
    public function delete($projectId)
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/projects');
        }

        // Validate CSRF
        if (!$this->validateCSRF($_POST['csrf_token'] ?? '')) {
            $this->redirect('/projects?error=invalid_request');
        }

        $projectModel = $this->model('Project');
        $project = $projectModel->find($projectId);

        if (!$project) {
            $this->redirect('/projects?error=not_found');
        }

        // Check permission
        if ($project['owner_id'] != $this->getUserId() && !Auth::isTenantAdmin() && !Auth::isPlatformAdmin()) {
            $this->redirect('/projects?error=unauthorized');
        }

        try {
            $projectModel->delete($projectId);

            // Update usage
            $usageModel = $this->model('Usage');
            $usageModel->decrement('projects');

            $this->redirect('/projects?success=deleted');

        } catch (Exception $e) {
            $this->redirect('/projects/' . $projectId . '?error=delete_failed');
        }
    }
}
