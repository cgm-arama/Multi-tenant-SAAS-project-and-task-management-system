<?php
// FILE: /tests/run_tests.php

/**
 * Simple Test Runner for SplashProjects
 * Run basic functional tests
 */

// Include configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Model.php';

echo "===========================================\n";
echo "SplashProjects - Functional Tests\n";
echo "===========================================\n\n";

$passed = 0;
$failed = 0;


echo "Test 1: Database Connection... ";
try {
    $db = Database::getInstance()->getConnection();
    if ($db) {
        echo "PASSED\n";
        $passed++;
    } else {
        echo "FAILED\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    $failed++;
}


echo "Test 2: Tenant Isolation... ";
try {
    require_once __DIR__ . '/../app/models/User.php';
    $userModel = new User();
    $userModel->setTenantId(1);
    $users = $userModel->all();

    // Check that all users belong to tenant 1
    $isolated = true;
    foreach ($users as $user) {
        if ($user['tenant_id'] != 1) {
            $isolated = false;
            break;
        }
    }

    if ($isolated) {
        echo "PASSED\n";
        $passed++;
    } else {
        echo "FAILED: Tenant isolation broken\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 3: Password Hashing
echo "Test 3: Password Hashing... ";
try {
    require_once __DIR__ . '/../app/models/User.php';
    $userModel = new User();
    $user = $userModel->findByEmail('john@acme.com');

    if ($user && password_verify('password123', $user['password'])) {
        echo "PASSED\n";
        $passed++;
    } else {
        echo "FAILED: Password verification failed\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 4: Project CRUD
echo "Test 4: Project Model... ";
try {
    require_once __DIR__ . '/../app/models/Project.php';
    $projectModel = new Project();
    $projectModel->setTenantId(1);
    $projects = $projectModel->all();

    if (is_array($projects) && count($projects) > 0) {
        echo "PASSED\n";
        $passed++;
    } else {
        echo "FAILED: No projects found\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 5: Task Model
echo "Test 5: Task Model... ";
try {
    require_once __DIR__ . '/../app/models/Task.php';
    $taskModel = new Task();
    $taskModel->setTenantId(1);
    $tasks = $taskModel->all();

    if (is_array($tasks)) {
        echo "PASSED\n";
        $passed++;
    } else {
        echo "FAILED: Task query failed\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 6: Subscription Limits
echo "Test 6: Subscription Limits... ";
try {
    require_once __DIR__ . '/../app/models/Subscription.php';
    $subscriptionModel = new Subscription();
    $subscriptionModel->setTenantId(1);
    $limitCheck = $subscriptionModel->checkLimit('projects');

    if (isset($limitCheck['allowed']) && isset($limitCheck['current']) && isset($limitCheck['limit'])) {
        echo "PASSED\n";
        $passed++;
    } else {
        echo "FAILED: Limit check structure invalid\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 7: Activity Logging
echo "Test 7: Activity Logging... ";
try {
    require_once __DIR__ . '/../app/models/Activity.php';
    $activityModel = new Activity();
    $activityModel->setTenantId(1);
    $activities = $activityModel->getRecentActivities(5);

    if (is_array($activities)) {
        echo "PASSED\n";
        $passed++;
    } else {
        echo "FAILED: Activity query failed\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 8: API Key Authentication
echo "Test 8: API Key Model... ";
try {
    require_once __DIR__ . '/../app/models/ApiKey.php';
    $apiKeyModel = new ApiKey();
    $keys = $apiKeyModel->getKeysByTenant(1);

    if (is_array($keys) && count($keys) > 0) {
        echo "PASSED\n";
        $passed++;
    } else {
        echo "FAILED: No API keys found\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    $failed++;
}

// Summary
echo "\n===========================================\n";
echo "Test Summary\n";
echo "===========================================\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "Total: " . ($passed + $failed) . "\n";

if ($failed === 0) {
    echo "\n✓ All tests passed!\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed!\n";
    exit(1);
}
