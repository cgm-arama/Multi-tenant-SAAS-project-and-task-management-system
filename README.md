# SplashProjects

A complete multi-tenant SaaS project management platform built with PHP and MySQL. Features Kanban boards, team collaboration, task management, subscriptions, and REST API.

## Features

### Core Features
- **Multi-tenant Architecture** - Complete workspace isolation with tenant-based data segregation
- **Kanban Boards** - Drag-and-drop task management with customizable columns
- **Project Management** - Create, organize, and track multiple projects
- **Task Management** - Full task lifecycle with assignments, due dates, priorities, labels
- **Team Collaboration** - User invitations, role-based access control, project members
- **Activity Tracking** - Comprehensive activity logs for projects and tasks
- **Notifications** - In-app notifications for task assignments and updates
- **File Attachments** - Upload and attach files to tasks
- **Comments & Checklists** - Add comments and subtasks to tasks
- **Subscription Management** - Multiple plans with quota enforcement
- **Usage Tracking** - Monitor resource usage against subscription limits
- **REST API** - External integration via API keys

### User Roles
- **Platform Admin** - Global SaaS administrator
- **Tenant Admin** - Workspace owner with full permissions
- **Member** - Regular team member
- **Guest** - Limited access to specific projects

## Tech Stack

- **Backend**: PHP 7.0+ (compatible with PHP 8.x)
- **Database**: MySQL 5.7+ / MariaDB 10.2+
- **Frontend**: Vanilla JavaScript, HTML5, CSS3
- **Architecture**: Custom lightweight MVC framework

## Requirements

- PHP 7.0 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Apache/Nginx web server
- PHP Extensions:
  - PDO
  - pdo_mysql
  - mbstring
  - openssl
  - fileinfo

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/ahmedsaadawi13/SplashProjects.git
cd SplashProjects
```

### 2. Configure Environment

Copy the example environment file:

```bash
cp .env.example .env
```

Edit `.env` and update with your settings:

```env
ENVIRONMENT=development
BASE_URL=http://localhost/SplashProjects/public

DB_HOST=localhost
DB_NAME=splashprojects
DB_USER=root
DB_PASS=your_password
```

### 3. Create Database

Create a new MySQL database:

```sql
CREATE DATABASE splashprojects CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Import the database schema and seed data:

```bash
mysql -u root -p splashprojects < database.sql
```

### 4. Set Permissions

Ensure the web server has write permissions to the storage directory:

```bash
chmod -R 755 storage
chmod -R 755 storage/uploads
```

### 5. Configure Web Server

#### Apache

Create a virtual host or use `.htaccess` (already included in `/public`).

Example virtual host:

```apache
<VirtualHost *:80>
    ServerName splashprojects.local
    DocumentRoot /path/to/SplashProjects/public

    <Directory /path/to/SplashProjects/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/splashprojects-error.log
    CustomLog ${APACHE_LOG_DIR}/splashprojects-access.log combined
</VirtualHost>
```

Enable mod_rewrite:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Nginx

```nginx
server {
    listen 80;
    server_name splashprojects.local;
    root /path/to/SplashProjects/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?url=$uri&$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.env {
        deny all;
    }
}
```

### 6. Access the Application

Open your browser and navigate to:

```
http://localhost/SplashProjects/public
```

Or if using virtual host:

```
http://splashprojects.local
```

## Default Credentials

### Platform Admin
- Email: `admin@splashprojects.com`
- Password: `admin123`

### Demo Tenants

**Acme Corporation:**
- Email: `john@acme.com`
- Password: `password123`
- Role: Tenant Admin

**TechStart Inc:**
- Email: `alice@techstart.com`
- Password: `password123`
- Role: Tenant Admin

## API Documentation

### Authentication

All API requests require an API key in the header:

```
X-API-KEY: your_api_key_here
```

API keys can be found in the database `api_keys` table or generated via the admin panel.

### Endpoints

#### Projects API

**List Projects**
```http
GET /api/projects
```

**Get Project Details**
```http
GET /api/projects/{id}
```

**Get Project Boards**
```http
GET /api/projects/{id}/boards
```

Response:
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Development Board",
            "columns": [
                {
                    "id": 1,
                    "name": "To Do",
                    "task_count": 5
                }
            ]
        }
    ]
}
```

#### Tasks API

**Create Task**
```http
POST /api/tasks
Content-Type: application/json

{
    "column_id": 1,
    "title": "Implement feature X",
    "description": "Detailed description",
    "assigned_to": 2,
    "priority": "high",
    "due_date": "2025-12-31"
}
```

**Update Task**
```http
PATCH /api/tasks/{id}
Content-Type: application/json

{
    "title": "Updated title",
    "status": "completed",
    "column_id": 3
}
```

**Get Task Details**
```http
GET /api/tasks/{id}
```

## File Structure

```
SplashProjects/
├── app/
│   ├── controllers/       # Application controllers
│   │   ├── api/          # API controllers
│   │   └── *.php
│   ├── models/           # Database models
│   ├── views/            # View templates
│   │   ├── layouts/      # Layout templates
│   │   └── */            # Feature views
│   └── core/             # Core framework classes
├── config/               # Configuration files
├── public/               # Public web root
│   ├── assets/
│   │   ├── css/
│   │   └── js/
│   └── index.php         # Entry point
├── storage/              # Storage directory
│   ├── uploads/          # File uploads
│   └── logs/             # Log files
├── tests/                # Test files
├── database.sql          # Database schema
├── .env.example          # Example environment file
└── README.md
```

## Subscription Plans

The system includes 4 default plans:

1. **Free** - $0/month
   - 3 projects, 3 users, 100 tasks, 500MB storage

2. **Starter** - $19/month
   - 10 projects, 10 users, 1000 tasks, 5GB storage

3. **Professional** - $49/month
   - 50 projects, 50 users, 10000 tasks, 20GB storage

4. **Enterprise** - $199/month
   - Unlimited resources, 100GB storage

## Development

### Running Tests

Basic functional tests are included:

```bash
php tests/run_tests.php
```

### Code Standards

- PSR-1 and PSR-2 coding standards
- All code and comments in English
- Comprehensive inline documentation
- Security-first approach

### Security Features

- CSRF protection on all forms
- Password hashing with `password_hash()`
- SQL injection prevention via prepared statements
- XSS prevention with output escaping
- File upload validation
- Session security (httponly, secure cookies)
- Tenant data isolation

## Deployment

### Production Checklist

1. Update `.env`:
   ```env
   ENVIRONMENT=production
   BASE_URL=https://yourdomain.com
   ```

2. Disable error display:
   ```php
   error_reporting(0);
   ini_set('display_errors', 0);
   ```

3. Enable HTTPS and secure cookies

4. Set proper file permissions:
   ```bash
   chmod -R 755 storage
   chown -R www-data:www-data storage
   ```

5. Configure database backups

6. Set up log rotation for `/storage/logs`

7. Enable opcache for PHP

8. Configure CDN for static assets (optional)

### Performance Optimization

- Enable opcache in `php.ini`
- Use MySQL query caching
- Implement Redis/Memcached for sessions
- Enable gzip compression
- Optimize images before upload
- Use CDN for static assets

## Troubleshooting

### Database Connection Failed
- Check MySQL is running: `sudo systemctl status mysql`
- Verify credentials in `.env`
- Ensure database exists

### 404 Errors on All Pages
- Check Apache mod_rewrite is enabled
- Verify `.htaccess` exists in `/public`
- Check virtual host DocumentRoot points to `/public`

### File Upload Errors
- Check `storage/uploads` permissions: `chmod 755 storage/uploads`
- Verify PHP upload settings in `php.ini`:
  ```ini
  upload_max_filesize = 10M
  post_max_size = 10M
  ```

### Blank Pages
- Enable error display in development
- Check PHP error logs
- Verify all required PHP extensions are installed

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch
3. Follow existing code style
4. Write clear commit messages
5. Test your changes
6. Submit a pull request

## License

This project is open-source software licensed under the MIT License.

## Support

For issues, questions, or contributions:
- GitHub Issues: https://github.com/ahmedsaadawi13/SplashProjects/issues
- Documentation: See `/docs` folder

## Credits

Developed by the SplashProjects team.

Built with ❤️ using PHP and MySQL.

---

**Version**: 1.0.0
**Last Updated**: January 2025
