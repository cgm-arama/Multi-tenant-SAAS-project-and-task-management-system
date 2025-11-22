<?php
// FILE: /app/controllers/api/ProjectsApiController.php

/**
 * Projects API Controller
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * REST API for projects and boards.
 * Authentication: X-API-KEY header
 */
class ProjectsApiController extends Controller
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

        if (!$key || $key['status'] !== 'active' || $key['tenant_status'] !== 'active') {
            $this->json(['error' => 'Invalid API key'], 401);
        }

        $this->apiTenant = $key;
        $apiKeyModel->updateLastUsed($key['id']);
    }

    /**
     * GET /api/projects - List all projects
     */
    public function index()
    {
        $projectModel = $this->model('Project');
        $projectModel->setTenantId($this->apiTenant['tenant_id']);

        $projects = $projectModel->getProjects([], 100, 0);

        $this->json([
            'success' => true,
            'data' => $projects
        ]);
    }

    /**
     * GET /api/projects/{id} - Get project details
     */
    public function show($projectId)
    {
        $projectModel = $this->model('Project');
        $projectModel->setTenantId($this->apiTenant['tenant_id']);

        $project = $projectModel->getProjectWithDetails($projectId);

        if (!$project) {
            $this->json(['error' => 'Project not found'], 404);
        }

        $this->json([
            'success' => true,
            'data' => $project
        ]);
    }

    /**
     * GET /api/projects/{id}/boards - Get project boards with columns
     */
    public function boards($projectId)
    {
        $boardModel = $this->model('Board');
        $boardModel->setTenantId($this->apiTenant['tenant_id']);

        $boards = $boardModel->getBoardsByProject($projectId);

        // Get columns for each board
        $columnModel = $this->model('Column');
        $columnModel->setTenantId($this->apiTenant['tenant_id']);

        foreach ($boards as &$board) {
            $board['columns'] = $columnModel->getColumnsByBoard($board['id']);
        }

        $this->json([
            'success' => true,
            'data' => $boards
        ]);
    }
}
