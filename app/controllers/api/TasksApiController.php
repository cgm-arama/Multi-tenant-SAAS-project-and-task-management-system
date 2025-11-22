<?php
// FILE: /app/controllers/api/TasksApiController.php

/**
 * Tasks API Controller
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * REST API for task management.
 * Authentication: X-API-KEY header
 */
class TasksApiController extends Controller
{
    private $apiTenant;

    public function __construct()
    {
        parent::__construct();
        $this->authenticateApi();
    }

    private function authenticateApi()
    {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';

        if (empty($apiKey)) {
            $this->json(['error' => 'API key required'], 401);
        }

        $apiKeyModel = $this->model('ApiKey');
        $key = $apiKeyModel->findByKey($apiKey);

        if (!$key || $key['status'] !== 'active') {
            $this->json(['error' => 'Invalid API key'], 401);
        }

        $this->apiTenant = $key;
        $apiKeyModel->updateLastUsed($key['id']);
    }

    /**
     * POST /api/tasks - Create new task
     */
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Method not allowed'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            $this->json(['error' => 'Invalid JSON'], 400);
        }

        $required = ['column_id', 'title'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                $this->json(['error' => ucfirst($field) . ' is required'], 400);
            }
        }

        $taskModel = $this->model('Task');
        $taskModel->setTenantId($this->apiTenant['tenant_id']);

        // Get column to find board and project
        $columnModel = $this->model('Column');
        $columnModel->setTenantId($this->apiTenant['tenant_id']);
        $column = $columnModel->find($input['column_id']);

        if (!$column) {
            $this->json(['error' => 'Column not found'], 404);
        }

        $boardModel = $this->model('Board');
        $boardModel->setTenantId($this->apiTenant['tenant_id']);
        $board = $boardModel->find($column['board_id']);

        if (!$board) {
            $this->json(['error' => 'Board not found'], 404);
        }

        try {
            $taskId = $taskModel->create([
                'tenant_id' => $this->apiTenant['tenant_id'],
                'project_id' => $board['project_id'],
                'board_id' => $board['id'],
                'column_id' => $input['column_id'],
                'title' => $input['title'],
                'description' => $input['description'] ?? '',
                'created_by' => 1, // API user
                'assigned_to' => $input['assigned_to'] ?? null,
                'priority' => $input['priority'] ?? 'medium',
                'due_date' => $input['due_date'] ?? null,
                'status' => 'open',
                'position' => 0
            ]);

            $task = $taskModel->find($taskId);

            $this->json([
                'success' => true,
                'data' => $task
            ], 201);

        } catch (Exception $e) {
            $this->json(['error' => 'Task creation failed'], 500);
        }
    }

    /**
     * PATCH /api/tasks/{id} - Update task
     */
    public function update($taskId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PATCH' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->json(['error' => 'Method not allowed'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            $this->json(['error' => 'Invalid JSON'], 400);
        }

        $taskModel = $this->model('Task');
        $taskModel->setTenantId($this->apiTenant['tenant_id']);

        $task = $taskModel->find($taskId);

        if (!$task) {
            $this->json(['error' => 'Task not found'], 404);
        }

        $updateData = [];

        if (isset($input['title'])) $updateData['title'] = $input['title'];
        if (isset($input['description'])) $updateData['description'] = $input['description'];
        if (isset($input['column_id'])) $updateData['column_id'] = $input['column_id'];
        if (isset($input['assigned_to'])) $updateData['assigned_to'] = $input['assigned_to'];
        if (isset($input['priority'])) $updateData['priority'] = $input['priority'];
        if (isset($input['status'])) $updateData['status'] = $input['status'];
        if (isset($input['due_date'])) $updateData['due_date'] = $input['due_date'];

        try {
            $taskModel->update($taskId, $updateData);
            $updatedTask = $taskModel->find($taskId);

            $this->json([
                'success' => true,
                'data' => $updatedTask
            ]);

        } catch (Exception $e) {
            $this->json(['error' => 'Update failed'], 500);
        }
    }

    /**
     * GET /api/tasks/{id} - Get task details
     */
    public function show($taskId)
    {
        $taskModel = $this->model('Task');
        $taskModel->setTenantId($this->apiTenant['tenant_id']);

        $task = $taskModel->getTaskWithDetails($taskId);

        if (!$task) {
            $this->json(['error' => 'Task not found'], 404);
        }

        $this->json([
            'success' => true,
            'data' => $task
        ]);
    }
}
