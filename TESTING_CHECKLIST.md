# SplashProjects - Testing Checklist

## Pre-Testing Setup

- [ ] Database is created and seeded with demo data
- [ ] Environment variables configured correctly
- [ ] Web server is running
- [ ] All file permissions set correctly
- [ ] PHP extensions are installed
- [ ] Error reporting enabled for testing

## 1. Authentication Tests

### Registration
- [ ] User can register with valid credentials
- [ ] Registration requires: name, email, password, workspace name
- [ ] Password must be at least 6 characters
- [ ] Email validation works
- [ ] Duplicate email is rejected
- [ ] New workspace is created successfully
- [ ] User is logged in after registration
- [ ] Free plan is assigned by default
- [ ] Usage tracking initialized

### Login
- [ ] User can login with correct credentials
- [ ] Login fails with incorrect password
- [ ] Login fails with non-existent email
- [ ] Account status is checked (active only)
- [ ] Tenant status is checked (active only)
- [ ] Session is created on successful login
- [ ] Last login timestamp is updated
- [ ] CSRF token is validated

### Logout
- [ ] User can logout
- [ ] Session is destroyed
- [ ] User is redirected to login page
- [ ] Cannot access protected pages after logout

## 2. Project Management Tests

### Create Project
- [ ] Can create project with valid data
- [ ] Project name is required (min 3 chars)
- [ ] Project is created under correct tenant
- [ ] Owner is set to current user
- [ ] Default status is 'active'
- [ ] Color can be customized
- [ ] Start date and due date are optional
- [ ] Priority can be set
- [ ] Activity log is created
- [ ] Usage counter is incremented
- [ ] Subscription limit is enforced

### View Projects
- [ ] List page shows all tenant projects
- [ ] Projects are tenant-isolated
- [ ] Can filter by status
- [ ] Can search by name/description
- [ ] Pagination works correctly
- [ ] Project card shows correct stats
- [ ] Progress percentage is calculated correctly

### Edit Project
- [ ] Owner can edit project
- [ ] Tenant admin can edit any project
- [ ] Member cannot edit others' projects
- [ ] All fields can be updated
- [ ] Activity log is created on update
- [ ] CSRF protection works

### Delete Project
- [ ] Owner can delete project
- [ ] Tenant admin can delete any project
- [ ] Member cannot delete others' projects
- [ ] Cascading delete works (boards, tasks)
- [ ] Usage counter is decremented
- [ ] CSRF protection works

### Archive Project
- [ ] Can archive active project
- [ ] Can restore archived project
- [ ] Archived projects shown separately

## 3. Kanban Board Tests

### Create Board
- [ ] Can create board in project
- [ ] Board name is required
- [ ] Default columns are created (To Do, In Progress, Done)
- [ ] Columns have sequential positions
- [ ] Activity log is created

### View Board
- [ ] Board displays all columns
- [ ] Columns show task count
- [ ] WIP limit is displayed if set
- [ ] Tasks are sorted by position
- [ ] Task cards show metadata
- [ ] Project members can view board
- [ ] Guests with project access can view board

### Add Column
- [ ] Can add new column
- [ ] Column name is required
- [ ] WIP limit is optional
- [ ] Column position is set correctly

### Delete Column
- [ ] Can delete empty column
- [ ] Cannot delete column with tasks
- [ ] Proper error message shown

## 4. Task Management Tests

### Create Task
- [ ] Can create task via form
- [ ] Can create task via API
- [ ] Title is required
- [ ] Task is created in correct column
- [ ] Tenant ID is set automatically
- [ ] Project and board IDs are correct
- [ ] Creator is set to current user
- [ ] Assignee is optional
- [ ] Priority defaults to 'medium'
- [ ] Due date is optional
- [ ] Activity log is created
- [ ] Usage counter is incremented
- [ ] Subscription limit is enforced
- [ ] Notification sent if task assigned

### View Task
- [ ] Task details page loads
- [ ] All task fields displayed
- [ ] Comments are shown
- [ ] Checklists are shown
- [ ] Attachments are listed
- [ ] Activity history is shown
- [ ] Labels are displayed

### Edit Task
- [ ] Can update title
- [ ] Can update description
- [ ] Can change assignee
- [ ] Can change priority
- [ ] Can change status
- [ ] Can update due date
- [ ] Activity log is created
- [ ] Notification sent on assignee change

### Move Task (Drag & Drop)
- [ ] Can drag task to another column
- [ ] Task column_id is updated
- [ ] Activity log is created
- [ ] WIP limit is checked
- [ ] Move is blocked if WIP exceeded
- [ ] UI updates immediately
- [ ] Server state is updated

### Delete Task
- [ ] Can delete task
- [ ] Cascading delete works (comments, checklists, attachments)
- [ ] Files are deleted from filesystem
- [ ] Usage counter is decremented
- [ ] Activity log is created

### Comments
- [ ] Can add comment to task
- [ ] Comment text is required
- [ ] Comments show user name and timestamp
- [ ] Activity log is created
- [ ] Notification sent to task owner/assignee

### Checklists
- [ ] Can add checklist item
- [ ] Can toggle item completion
- [ ] Progress percentage calculated correctly
- [ ] Items ordered by position

### Attachments
- [ ] Can upload file to task
- [ ] File type validation works
- [ ] File size validation works
- [ ] Unique filename generated
- [ ] File stored in correct location
- [ ] Can download attachment
- [ ] Can delete attachment
- [ ] File deleted from filesystem on delete
- [ ] Storage usage updated

### Labels
- [ ] Can create label
- [ ] Can attach label to task
- [ ] Can detach label from task
- [ ] Tasks can be filtered by label

## 5. User Management Tests

### Invite User
- [ ] Tenant admin can invite users
- [ ] Email is required and validated
- [ ] Role can be specified
- [ ] Cannot invite existing user
- [ ] Cannot invite already invited email
- [ ] Invitation token is generated
- [ ] Invitation expires after 7 days
- [ ] Subscription user limit is enforced

### View Users
- [ ] List shows all tenant users
- [ ] Users are tenant-isolated
- [ ] Can see user role and status
- [ ] Pagination works

### User Roles
- [ ] Platform admin can access all tenants
- [ ] Tenant admin can manage workspace
- [ ] Member has limited access
- [ ] Guest can only access assigned projects

## 6. Subscription & Billing Tests

### Plans
- [ ] All plans are listed
- [ ] Plan limits are displayed
- [ ] Plan prices are shown

### Subscription Limits
- [ ] Project limit is enforced
- [ ] User limit is enforced
- [ ] Task limit is enforced
- [ ] Storage limit is enforced
- [ ] Clear error message when limit reached

### Usage Tracking
- [ ] Usage updates on resource creation
- [ ] Usage decrements on resource deletion
- [ ] Usage stats are accurate

### Invoices
- [ ] Invoices are generated
- [ ] Invoice numbers are unique
- [ ] Invoice totals include tax
- [ ] Invoice status is tracked

### Payments
- [ ] Payment can be simulated
- [ ] Invoice marked as paid on success
- [ ] Payment transaction ID is stored

## 7. Activity & Notifications Tests

### Activity Logging
- [ ] Task creation logged
- [ ] Task update logged
- [ ] Task move logged
- [ ] Comment logged
- [ ] File attachment logged
- [ ] Activity shows user name
- [ ] Activity shows timestamp
- [ ] Activity filtered by project
- [ ] Activity filtered by task

### Notifications
- [ ] Notification created on task assignment
- [ ] Notification created on comment
- [ ] Unread count is accurate
- [ ] Can mark notification as read
- [ ] Can mark all as read
- [ ] Notifications are tenant-isolated
- [ ] Notification badge updates

## 8. API Tests

### Authentication
- [ ] API requires X-API-KEY header
- [ ] Invalid key is rejected
- [ ] Valid key is accepted
- [ ] Inactive key is rejected
- [ ] Last used timestamp updated

### Projects API
- [ ] GET /api/projects returns tenant projects
- [ ] GET /api/projects/{id} returns project details
- [ ] GET /api/projects/{id}/boards returns boards with columns
- [ ] Data is tenant-isolated
- [ ] JSON format is correct

### Tasks API
- [ ] POST /api/tasks creates task
- [ ] Required fields validated
- [ ] Task created in correct tenant
- [ ] PATCH /api/tasks/{id} updates task
- [ ] GET /api/tasks/{id} returns task details
- [ ] Proper HTTP status codes returned
- [ ] Error messages are clear

## 9. Security Tests

### CSRF Protection
- [ ] All forms have CSRF token
- [ ] POST without token is rejected
- [ ] Invalid token is rejected
- [ ] Token is regenerated properly

### SQL Injection
- [ ] Single quotes in input don't break queries
- [ ] SQL keywords in input are escaped
- [ ] Prepared statements used everywhere

### XSS Prevention
- [ ] HTML tags in input are escaped
- [ ] JavaScript in input is escaped
- [ ] Output is properly escaped in views

### Session Security
- [ ] Session ID changes on login
- [ ] Session expires after inactivity
- [ ] HttpOnly cookies are set
- [ ] Secure cookies in production

### File Upload Security
- [ ] PHP files cannot be uploaded
- [ ] Executable files rejected
- [ ] File size limit enforced
- [ ] File type validated

### Tenant Isolation
- [ ] Cannot access other tenant's projects
- [ ] Cannot access other tenant's tasks
- [ ] Cannot access other tenant's users
- [ ] API enforces tenant isolation

## 10. UI/UX Tests

### Navigation
- [ ] All menu links work
- [ ] Breadcrumbs are accurate
- [ ] Back button works correctly

### Forms
- [ ] Required fields marked with *
- [ ] Validation errors shown clearly
- [ ] Success messages displayed
- [ ] Forms can be canceled

### Kanban Board
- [ ] Drag and drop works smoothly
- [ ] Cards display correctly
- [ ] Columns are scrollable
- [ ] Modal forms work
- [ ] Task creation is smooth

### Responsive Design
- [ ] Works on desktop (1920x1080)
- [ ] Works on tablet (768x1024)
- [ ] Works on mobile (375x667)
- [ ] Kanban board adapts to mobile

### Performance
- [ ] Pages load within 2 seconds
- [ ] No JavaScript errors in console
- [ ] No broken images
- [ ] CSS loads correctly

## 11. Edge Cases & Error Handling

### Error Pages
- [ ] 404 page for invalid routes
- [ ] Graceful error messages
- [ ] No sensitive data in errors

### Data Validation
- [ ] Empty string handling
- [ ] Very long strings (>1000 chars)
- [ ] Special characters (@#$%^&*)
- [ ] Unicode characters (emoji, Chinese)
- [ ] Null values
- [ ] Negative numbers where not allowed

### Boundary Conditions
- [ ] Maximum file size upload
- [ ] Maximum tasks in column
- [ ] Maximum users in tenant
- [ ] Date in past/future validation

### Concurrent Access
- [ ] Two users editing same task
- [ ] Moving task while another user views board
- [ ] Deleting resource another user is viewing

## 12. Database Tests

### Data Integrity
- [ ] Foreign keys enforced
- [ ] Cascading deletes work
- [ ] Unique constraints enforced
- [ ] Required fields enforced

### Transactions
- [ ] Multi-step operations are atomic
- [ ] Rollback on error works

### Performance
- [ ] Queries execute within 100ms
- [ ] Indexes are used (check EXPLAIN)
- [ ] No N+1 query problems

## Test Summary Template

```
Total Tests: ___
Passed: ___
Failed: ___
Skipped: ___

Critical Issues: ___
High Priority Issues: ___
Medium Priority Issues: ___
Low Priority Issues: ___

Testing Date: ___________
Tester: ___________
Environment: Development / Staging / Production
```

## Automated Testing

Run the automated test suite:

```bash
php tests/run_tests.php
```

Expected output: All 8 tests should pass.

## Performance Testing

Use Apache Bench for basic load testing:

```bash
ab -n 1000 -c 10 http://localhost/SplashProjects/public/
```

Monitor:
- Response time < 200ms
- No memory leaks
- Database connections closed properly

## Conclusion

This checklist covers comprehensive testing for SplashProjects. All items should be verified before production deployment.

For CI/CD integration, automate as many tests as possible using PHPUnit and integration testing frameworks.
