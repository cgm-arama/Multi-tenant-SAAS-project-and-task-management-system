

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Create database
CREATE DATABASE IF NOT EXISTS `splashprojects` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `splashprojects`;

-- =====================================================
-- TENANTS AND USERS
-- =====================================================

-- Tenants (Companies/Teams)
CREATE TABLE `tenants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` text,
  `timezone` varchar(50) DEFAULT 'UTC',
  `status` enum('active','suspended','canceled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `role` enum('platform_admin','tenant_admin','member','guest') DEFAULT 'member',
  `avatar` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','pending') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `tenant_id` (`tenant_id`),
  KEY `role` (`role`),
  KEY `status` (`status`),
  CONSTRAINT `users_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Invitations
CREATE TABLE `invitations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` enum('tenant_admin','member','guest') DEFAULT 'member',
  `token` varchar(255) NOT NULL,
  `invited_by` int(11) NOT NULL,
  `status` enum('pending','accepted','expired') DEFAULT 'pending',
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `tenant_id` (`tenant_id`),
  KEY `email` (`email`),
  KEY `invited_by` (`invited_by`),
  CONSTRAINT `invitations_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invitations_user_fk` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Keys for tenant API access
CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_used` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_key` (`api_key`),
  KEY `tenant_id` (`tenant_id`),
  CONSTRAINT `api_keys_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SUBSCRIPTION AND BILLING
-- =====================================================

-- Subscription Plans
CREATE TABLE `plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `billing_cycle` enum('monthly','yearly') DEFAULT 'monthly',
  `max_projects` int(11) DEFAULT 10,
  `max_users` int(11) DEFAULT 5,
  `max_tasks` int(11) DEFAULT 1000,
  `max_storage_mb` int(11) DEFAULT 1024,
  `features` text,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tenant Subscriptions
CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `status` enum('trialing','active','past_due','canceled','suspended') DEFAULT 'trialing',
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `current_period_start` timestamp NOT NULL,
  `current_period_end` timestamp NOT NULL,
  `canceled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `plan_id` (`plan_id`),
  KEY `status` (`status`),
  CONSTRAINT `subscriptions_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscriptions_plan_fk` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usage Tracking
CREATE TABLE `usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `projects_count` int(11) DEFAULT 0,
  `users_count` int(11) DEFAULT 0,
  `tasks_count` int(11) DEFAULT 0,
  `storage_used_mb` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_id` (`tenant_id`),
  CONSTRAINT `usage_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoices
CREATE TABLE `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `subscription_id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `status` enum('draft','pending','paid','failed','refunded') DEFAULT 'pending',
  `due_date` date NOT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `tenant_id` (`tenant_id`),
  KEY `subscription_id` (`subscription_id`),
  CONSTRAINT `invoices_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_subscription_fk` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'card',
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','success','failed','refunded') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `invoice_id` (`invoice_id`),
  CONSTRAINT `payments_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_invoice_fk` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PROJECTS AND BOARDS
-- =====================================================

-- Projects
CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `owner_id` int(11) NOT NULL,
  `status` enum('active','archived','on_hold') DEFAULT 'active',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `color` varchar(7) DEFAULT '#3498db',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `owner_id` (`owner_id`),
  KEY `status` (`status`),
  CONSTRAINT `projects_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `projects_owner_fk` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project Members (for guest access control)
CREATE TABLE `project_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('owner','member','viewer') DEFAULT 'member',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_user` (`project_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `project_members_project_fk` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_members_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Boards
CREATE TABLE `boards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `position` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `boards_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `boards_project_fk` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Board Columns/Lists
CREATE TABLE `columns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `board_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` int(11) DEFAULT 0,
  `wip_limit` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `board_id` (`board_id`),
  CONSTRAINT `columns_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `columns_board_fk` FOREIGN KEY (`board_id`) REFERENCES `boards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TASKS AND RELATED DATA
-- =====================================================

-- Tasks (Cards)
CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `board_id` int(11) NOT NULL,
  `column_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `created_by` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `status` enum('open','in_progress','completed','archived') DEFAULT 'open',
  `due_date` date DEFAULT NULL,
  `position` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `project_id` (`project_id`),
  KEY `board_id` (`board_id`),
  KEY `column_id` (`column_id`),
  KEY `created_by` (`created_by`),
  KEY `assigned_to` (`assigned_to`),
  KEY `status` (`status`),
  CONSTRAINT `tasks_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_project_fk` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_board_fk` FOREIGN KEY (`board_id`) REFERENCES `boards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_column_fk` FOREIGN KEY (`column_id`) REFERENCES `columns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_creator_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `tasks_assignee_fk` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Labels/Tags
CREATE TABLE `labels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `color` varchar(7) DEFAULT '#95a5a6',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  CONSTRAINT `labels_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Task Labels (Many-to-Many)
CREATE TABLE `task_labels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `label_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_label` (`task_id`,`label_id`),
  KEY `label_id` (`label_id`),
  CONSTRAINT `task_labels_task_fk` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_labels_label_fk` FOREIGN KEY (`label_id`) REFERENCES `labels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Checklists (Subtasks)
CREATE TABLE `checklists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `position` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `task_id` (`task_id`),
  CONSTRAINT `checklists_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `checklists_task_fk` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comments
CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `task_id` (`task_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `comments_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_task_fk` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attachments
CREATE TABLE `attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `task_id` (`task_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `attachments_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attachments_task_fk` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attachments_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ACTIVITY AND NOTIFICATIONS
-- =====================================================

-- Activity Log
CREATE TABLE `activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `task_id` int(11) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `metadata` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `user_id` (`user_id`),
  KEY `project_id` (`project_id`),
  KEY `task_id` (`task_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `activities_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `activities_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `activities_project_fk` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `activities_task_fk` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `user_id` (`user_id`),
  KEY `is_read` (`is_read`),
  CONSTRAINT `notifications_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SEED DATA
-- =====================================================

-- Insert Subscription Plans
INSERT INTO `plans` (`name`, `slug`, `description`, `price`, `billing_cycle`, `max_projects`, `max_users`, `max_tasks`, `max_storage_mb`, `features`, `status`) VALUES
('Free', 'free', 'Perfect for individuals and small teams getting started', 0.00, 'monthly', 3, 3, 100, 500, 'Basic features,3 projects,3 users,100 tasks,500MB storage', 'active'),
('Starter', 'starter', 'Great for growing teams', 19.00, 'monthly', 10, 10, 1000, 5120, 'All Free features,10 projects,10 users,1000 tasks,5GB storage,Priority support', 'active'),
('Professional', 'professional', 'For established teams', 49.00, 'monthly', 50, 50, 10000, 20480, 'All Starter features,50 projects,50 users,10000 tasks,20GB storage,Custom fields,Advanced analytics', 'active'),
('Enterprise', 'enterprise', 'For large organizations', 199.00, 'monthly', 999, 999, 999999, 102400, 'All Professional features,Unlimited projects,Unlimited users,Unlimited tasks,100GB storage,Dedicated support,Custom integrations', 'active');

-- Insert Demo Tenants
INSERT INTO `tenants` (`name`, `slug`, `description`, `timezone`, `status`) VALUES
('Acme Corporation', 'acme-corp', 'Leading provider of innovative solutions', 'America/New_York', 'active'),
('TechStart Inc', 'techstart-inc', 'Cutting-edge technology startup', 'America/Los_Angeles', 'active');

-- Insert Platform Admin User (password: admin123)
INSERT INTO `users` (`tenant_id`, `email`, `password`, `name`, `role`, `status`) VALUES
(NULL, 'admin@splashprojects.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Platform Admin', 'platform_admin', 'active');

-- Insert Users for Acme Corporation (password: password123)
INSERT INTO `users` (`tenant_id`, `email`, `password`, `name`, `role`, `status`) VALUES
(1, 'john@acme.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Smith', 'tenant_admin', 'active'),
(1, 'sarah@acme.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Johnson', 'member', 'active'),
(1, 'mike@acme.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike Williams', 'member', 'active'),
(1, 'emma@acme.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emma Davis', 'guest', 'active');

-- Insert Users for TechStart Inc (password: password123)
INSERT INTO `users` (`tenant_id`, `email`, `password`, `name`, `role`, `status`) VALUES
(2, 'alice@techstart.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice Brown', 'tenant_admin', 'active'),
(2, 'bob@techstart.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob Wilson', 'member', 'active'),
(2, 'carol@techstart.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carol Martinez', 'member', 'active');

-- Insert API Keys
INSERT INTO `api_keys` (`tenant_id`, `api_key`, `name`, `status`) VALUES
(1, 'sk_live_acme_1234567890abcdef', 'Acme API Key', 'active'),
(2, 'sk_live_techstart_abcdef1234567890', 'TechStart API Key', 'active');

-- Insert Subscriptions
INSERT INTO `subscriptions` (`tenant_id`, `plan_id`, `status`, `trial_ends_at`, `current_period_start`, `current_period_end`) VALUES
(1, 3, 'active', NULL, NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH)),
(2, 2, 'trialing', DATE_ADD(NOW(), INTERVAL 14 DAY), NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH));

-- Insert Usage Data
INSERT INTO `usage` (`tenant_id`, `projects_count`, `users_count`, `tasks_count`, `storage_used_mb`) VALUES
(1, 3, 4, 12, 45),
(2, 2, 3, 8, 23);

-- Insert Invoices
INSERT INTO `invoices` (`tenant_id`, `subscription_id`, `invoice_number`, `amount`, `tax`, `total`, `status`, `due_date`, `paid_at`) VALUES
(1, 1, 'INV-2025-001', 49.00, 4.90, 53.90, 'paid', CURDATE(), NOW()),
(1, 1, 'INV-2025-002', 49.00, 4.90, 53.90, 'pending', DATE_ADD(CURDATE(), INTERVAL 30 DAY), NULL);

-- Insert Payments
INSERT INTO `payments` (`tenant_id`, `invoice_id`, `amount`, `payment_method`, `transaction_id`, `status`) VALUES
(1, 1, 53.90, 'card', 'txn_1234567890', 'success');

-- Insert Projects for Acme Corporation
INSERT INTO `projects` (`tenant_id`, `name`, `description`, `owner_id`, `status`, `priority`, `start_date`, `due_date`, `color`) VALUES
(1, 'Website Redesign', 'Complete overhaul of company website', 2, 'active', 'high', '2025-01-01', '2025-03-31', '#e74c3c'),
(1, 'Mobile App Development', 'Native mobile app for iOS and Android', 2, 'active', 'critical', '2025-01-15', '2025-06-30', '#3498db'),
(1, 'Marketing Campaign Q1', 'Q1 2025 marketing initiatives', 2, 'active', 'medium', '2025-01-01', '2025-03-31', '#2ecc71');

-- Insert Projects for TechStart Inc
INSERT INTO `projects` (`tenant_id`, `name`, `description`, `owner_id`, `status`, `priority`, `due_date`, `color`) VALUES
(2, 'Product Launch', 'Launch new SaaS product', 6, 'active', 'critical', '2025-04-01', '#9b59b6'),
(2, 'Customer Onboarding', 'Improve onboarding process', 6, 'active', 'high', '2025-02-28', '#f39c12');

-- Insert Project Members
INSERT INTO `project_members` (`project_id`, `user_id`, `role`) VALUES
(1, 2, 'owner'), (1, 3, 'member'), (1, 4, 'member'),
(2, 2, 'owner'), (2, 3, 'member'),
(3, 2, 'owner'), (3, 5, 'viewer'),
(4, 6, 'owner'), (4, 7, 'member'), (4, 8, 'member'),
(5, 6, 'owner'), (5, 7, 'member');

-- Insert Boards
INSERT INTO `boards` (`tenant_id`, `project_id`, `name`, `description`, `position`) VALUES
(1, 1, 'Website Board', 'Main board for website redesign', 0),
(1, 2, 'App Development', 'Mobile app tasks', 0),
(1, 3, 'Marketing Tasks', 'Marketing campaign board', 0),
(2, 4, 'Launch Board', 'Product launch tasks', 0),
(2, 5, 'Onboarding Board', 'Onboarding improvements', 0);

-- Insert Columns for each Board
INSERT INTO `columns` (`tenant_id`, `board_id`, `name`, `position`, `wip_limit`) VALUES
-- Board 1 (Website Redesign)
(1, 1, 'To Do', 0, NULL),
(1, 1, 'In Progress', 1, 3),
(1, 1, 'Review', 2, 2),
(1, 1, 'Done', 3, NULL),
-- Board 2 (Mobile App)
(1, 2, 'Backlog', 0, NULL),
(1, 2, 'In Development', 1, 3),
(1, 2, 'Testing', 2, 2),
(1, 2, 'Completed', 3, NULL),
-- Board 3 (Marketing)
(1, 3, 'Ideas', 0, NULL),
(1, 3, 'Planned', 1, NULL),
(1, 3, 'In Progress', 2, 3),
(1, 3, 'Done', 3, NULL),
-- Board 4 (Product Launch)
(2, 4, 'To Do', 0, NULL),
(2, 4, 'In Progress', 1, 3),
(2, 4, 'Done', 2, NULL),
-- Board 5 (Onboarding)
(2, 5, 'To Do', 0, NULL),
(2, 5, 'In Progress', 1, 2),
(2, 5, 'Done', 2, NULL);

-- Insert Labels
INSERT INTO `labels` (`tenant_id`, `name`, `color`) VALUES
(1, 'Bug', '#e74c3c'),
(1, 'Feature', '#3498db'),
(1, 'Enhancement', '#2ecc71'),
(1, 'Documentation', '#f39c12'),
(1, 'Urgent', '#c0392b'),
(2, 'Bug', '#e74c3c'),
(2, 'Feature', '#3498db'),
(2, 'High Priority', '#e67e22');

-- Insert Tasks
INSERT INTO `tasks` (`tenant_id`, `project_id`, `board_id`, `column_id`, `title`, `description`, `created_by`, `assigned_to`, `priority`, `status`, `due_date`, `position`) VALUES
-- Website Redesign Tasks
(1, 1, 1, 1, 'Design homepage mockup', 'Create initial homepage design in Figma', 2, 3, 'high', 'open', '2025-01-20', 0),
(1, 1, 1, 2, 'Develop header component', 'Build responsive header with navigation', 2, 3, 'medium', 'in_progress', '2025-01-25', 0),
(1, 1, 1, 2, 'Setup color palette', 'Define brand colors and create CSS variables', 2, 4, 'medium', 'in_progress', '2025-01-22', 1),
(1, 1, 1, 4, 'Research competitors', 'Analyze top 5 competitor websites', 2, 3, 'low', 'completed', '2025-01-15', 0),
-- Mobile App Tasks
(1, 2, 2, 5, 'Setup React Native project', 'Initialize new RN project with required dependencies', 2, 3, 'critical', 'open', '2025-01-25', 0),
(1, 2, 2, 6, 'Design authentication flow', 'Create login and signup screens', 2, 4, 'high', 'in_progress', '2025-02-01', 0),
(1, 2, 2, 6, 'Implement API integration', 'Connect app to backend API', 2, 3, 'high', 'in_progress', '2025-02-05', 1),
-- Marketing Campaign Tasks
(1, 3, 3, 9, 'Q1 content calendar', 'Plan blog posts and social media content', 2, 5, 'medium', 'open', '2025-01-30', 0),
(1, 3, 3, 11, 'Email campaign design', 'Design newsletter template', 2, 5, 'medium', 'in_progress', '2025-02-10', 0),
-- Product Launch Tasks
(2, 4, 4, 13, 'Prepare press release', 'Draft and finalize press release', 6, 7, 'high', 'open', '2025-02-15', 0),
(2, 4, 4, 14, 'Setup landing page', 'Create product landing page', 6, 8, 'critical', 'in_progress', '2025-02-20', 0),
(2, 4, 4, 15, 'Beta testing', 'Conduct beta tests with early users', 6, 7, 'high', 'completed', '2025-01-18', 0),
-- Onboarding Tasks
(2, 5, 5, 16, 'Create tutorial videos', 'Record product walkthrough videos', 6, 8, 'medium', 'open', '2025-02-28', 0),
(2, 5, 5, 17, 'Update documentation', 'Revise user documentation', 6, 7, 'medium', 'in_progress', '2025-02-25', 0);

-- Insert Task Labels
INSERT INTO `task_labels` (`task_id`, `label_id`) VALUES
(1, 2), (2, 2), (3, 3), (4, 4),
(5, 2), (5, 5), (6, 2), (7, 2), (7, 5),
(8, 2), (9, 3),
(10, 5), (11, 2), (11, 8), (12, 2),
(13, 2), (14, 4);

-- Insert Checklists
INSERT INTO `checklists` (`tenant_id`, `task_id`, `name`, `completed`, `position`) VALUES
(1, 1, 'Gather brand assets', 1, 0),
(1, 1, 'Create wireframe', 1, 1),
(1, 1, 'Design mockup in Figma', 0, 2),
(1, 1, 'Get feedback from team', 0, 3),
(1, 2, 'Create HTML structure', 1, 0),
(1, 2, 'Add CSS styling', 1, 1),
(1, 2, 'Make it responsive', 0, 2),
(1, 2, 'Add mobile menu', 0, 3),
(2, 11, 'Setup project structure', 1, 0),
(2, 11, 'Design hero section', 1, 1),
(2, 11, 'Add pricing table', 0, 2),
(2, 11, 'Implement contact form', 0, 3);

-- Insert Comments
INSERT INTO `comments` (`tenant_id`, `task_id`, `user_id`, `comment`) VALUES
(1, 2, 3, 'Started working on the header component. Using flexbox for layout.'),
(1, 2, 2, 'Great! Make sure it works well on mobile devices.'),
(1, 2, 3, 'Will do! Planning to test on iPhone and Android.'),
(1, 6, 4, 'Working on the design. Should we use Material Design or custom components?'),
(1, 6, 2, 'Let\'s go with custom components to match our brand.'),
(2, 11, 8, 'Landing page is looking good! Added hero section and features.'),
(2, 11, 6, 'Awesome! Don\'t forget to add testimonials section.'),
(2, 14, 7, 'Documentation has been updated with new screenshots.'),
(2, 14, 6, 'Perfect! Let\'s review it tomorrow.');

-- Insert Attachments (dummy file paths)
INSERT INTO `attachments` (`tenant_id`, `task_id`, `user_id`, `filename`, `original_name`, `file_size`, `mime_type`, `file_path`) VALUES
(1, 1, 3, '1705932847_homepage_mockup.pdf', 'homepage_mockup.pdf', 2048576, 'application/pdf', '/storage/uploads/1705932847_homepage_mockup.pdf'),
(1, 1, 3, '1705933124_wireframe.png', 'wireframe.png', 512000, 'image/png', '/storage/uploads/1705933124_wireframe.png'),
(1, 6, 4, '1705934521_auth_flow.pdf', 'auth_flow.pdf', 1024000, 'application/pdf', '/storage/uploads/1705934521_auth_flow.pdf'),
(2, 11, 8, '1705935789_landing_design.png', 'landing_design.png', 3072000, 'image/png', '/storage/uploads/1705935789_landing_design.png');

-- Insert Activities
INSERT INTO `activities` (`tenant_id`, `user_id`, `project_id`, `task_id`, `action_type`, `message`) VALUES
(1, 2, 1, 1, 'task_created', 'John Smith created task "Design homepage mockup"'),
(1, 3, 1, 2, 'task_updated', 'Sarah Johnson moved task "Develop header component" to In Progress'),
(1, 3, 1, 2, 'comment_added', 'Sarah Johnson added a comment on "Develop header component"'),
(1, 2, 1, 2, 'comment_added', 'John Smith replied to a comment on "Develop header component"'),
(1, 4, 1, 6, 'task_updated', 'Emma Davis was assigned to task "Design authentication flow"'),
(1, 3, 1, 1, 'file_attached', 'Sarah Johnson attached a file to "Design homepage mockup"'),
(2, 6, 4, 11, 'task_created', 'Alice Brown created task "Setup landing page"'),
(2, 8, 4, 11, 'task_updated', 'Carol Martinez moved task "Setup landing page" to In Progress'),
(2, 7, 4, 12, 'task_completed', 'Bob Wilson completed task "Beta testing"'),
(2, 6, 5, 14, 'comment_added', 'Alice Brown added a comment on "Update documentation"');

-- Insert Notifications
INSERT INTO `notifications` (`tenant_id`, `user_id`, `type`, `title`, `message`, `link`, `is_read`) VALUES
(1, 3, 'task_assigned', 'New task assigned', 'You have been assigned to "Develop header component"', '/tasks/2', 1),
(1, 4, 'task_assigned', 'New task assigned', 'You have been assigned to "Setup color palette"', '/tasks/3', 0),
(1, 3, 'comment_mention', 'New comment', 'John Smith replied to your comment', '/tasks/2', 0),
(1, 5, 'task_assigned', 'New task assigned', 'You have been assigned to "Q1 content calendar"', '/tasks/8', 0),
(2, 7, 'task_assigned', 'New task assigned', 'You have been assigned to "Prepare press release"', '/tasks/10', 1),
(2, 8, 'task_assigned', 'New task assigned', 'You have been assigned to "Setup landing page"', '/tasks/11', 0),
(2, 7, 'comment_mention', 'New comment', 'Alice Brown mentioned you in a comment', '/tasks/14', 0);

-- =====================================================
-- END OF SEED DATA
-- =====================================================
