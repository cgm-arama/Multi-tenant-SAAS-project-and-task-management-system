# SplashProjects - Production Deployment Guide

## Overview

This guide provides step-by-step instructions for deploying SplashProjects to a production environment with best practices for security, performance, and reliability.

## Server Requirements

### Minimum Specifications
- **CPU**: 2 cores
- **RAM**: 4GB
- **Storage**: 20GB SSD
- **OS**: Ubuntu 20.04 LTS or higher (CentOS 8+, Debian 10+ also supported)

### Recommended Specifications (100+ tenants)
- **CPU**: 4+ cores
- **RAM**: 8GB+
- **Storage**: 50GB+ SSD
- **OS**: Ubuntu 22.04 LTS

### Software Requirements
- PHP 7.4 or 8.0+ (recommended: 8.1)
- MySQL 8.0+ or MariaDB 10.6+
- Apache 2.4+ or Nginx 1.18+
- Git
- Composer (optional, for dependency management)
- SSL Certificate (Let's Encrypt recommended)

## Pre-Deployment Checklist

- [ ] Server provisioned and accessible via SSH
- [ ] Domain name configured (DNS A record pointing to server)
- [ ] SSL certificate ready
- [ ] Database backup strategy planned
- [ ] Monitoring tools selected
- [ ] Email service configured (SendGrid, Mailgun, etc.)

## Step-by-Step Deployment

### 1. Server Setup

#### Update System Packages

```bash
sudo apt update && sudo apt upgrade -y
```

#### Install Required Software

```bash
# Apache, PHP, and extensions
sudo apt install -y apache2 php php-cli php-fpm php-mysql php-pdo \
    php-mbstring php-zip php-xml php-curl php-gd php-fileinfo \
    mysql-server git unzip

# Enable Apache modules
sudo a2enmod rewrite
sudo a2enmod ssl
sudo a2enmod headers
```

### 2. MySQL Database Setup

#### Secure MySQL Installation

```bash
sudo mysql_secure_installation
```

Follow prompts:
- Set root password
- Remove anonymous users: Yes
- Disallow root login remotely: Yes
- Remove test database: Yes
- Reload privilege tables: Yes

#### Create Database and User

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE splashprojects CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'splashuser'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';

GRANT ALL PRIVILEGES ON splashprojects.* TO 'splashuser'@'localhost';

FLUSH PRIVILEGES;

EXIT;
```

### 3. Application Deployment

#### Clone Repository

```bash
cd /var/www
sudo git clone https://github.com/yourusername/SplashProjects.git
cd SplashProjects
```

#### Set Ownership and Permissions

```bash
sudo chown -R www-data:www-data /var/www/SplashProjects
sudo chmod -R 755 /var/www/SplashProjects

# Storage directory needs write permissions
sudo chmod -R 775 /var/www/SplashProjects/storage
sudo chmod -R 775 /var/www/SplashProjects/storage/uploads
```

#### Configure Environment

```bash
cp .env.example .env
sudo nano .env
```

Update `.env`:

```env
ENVIRONMENT=production
BASE_URL=https://yourdomain.com

DB_HOST=localhost
DB_NAME=splashprojects
DB_USER=splashuser
DB_PASS=STRONG_PASSWORD_HERE

# Email settings (example with SendGrid)
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USER=apikey
MAIL_PASS=YOUR_SENDGRID_API_KEY
MAIL_FROM=noreply@yourdomain.com
MAIL_FROM_NAME=SplashProjects
```

#### Import Database

```bash
mysql -u splashuser -p splashprojects < database.sql
```

#### Remove Demo Data (Production)

```bash
mysql -u splashuser -p splashprojects
```

```sql
-- Delete demo users (keep platform admin)
DELETE FROM users WHERE id > 1;

-- Delete demo tenants
DELETE FROM tenants;

-- Delete demo data
DELETE FROM projects;
DELETE FROM tasks;
DELETE FROM activities;
DELETE FROM notifications;

-- Reset usage
TRUNCATE usage;

-- Keep subscription plans
-- DELETE FROM plans; -- Don't run this
```

### 4. Web Server Configuration

#### Apache Configuration

Create virtual host:

```bash
sudo nano /etc/apache2/sites-available/splashprojects.conf
```

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com

    Redirect permanent / https://yourdomain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com

    DocumentRoot /var/www/SplashProjects/public

    <Directory /var/www/SplashProjects/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Logging
    ErrorLog ${APACHE_LOG_DIR}/splashprojects-error.log
    CustomLog ${APACHE_LOG_DIR}/splashprojects-access.log combined

    # SSL Configuration (after obtaining certificate)
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem

    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</VirtualHost>
```

Enable site and reload:

```bash
sudo a2ensite splashprojects
sudo a2dissite 000-default
sudo systemctl reload apache2
```

#### Nginx Configuration (Alternative)

```bash
sudo nano /etc/nginx/sites-available/splashprojects
```

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/SplashProjects/public;
    index index.php;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Logging
    access_log /var/log/nginx/splashprojects-access.log;
    error_log /var/log/nginx/splashprojects-error.log;

    # Increase upload size
    client_max_body_size 10M;

    location / {
        try_files $uri $uri/ /index.php?url=$uri&$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.env {
        deny all;
    }

    location ~ /\.git {
        deny all;
    }
}
```

Enable and reload:

```bash
sudo ln -s /etc/nginx/sites-available/splashprojects /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 5. SSL Certificate (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-apache

# For Apache
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# For Nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal test
sudo certbot renew --dry-run
```

### 6. PHP Production Configuration

Edit `php.ini`:

```bash
sudo nano /etc/php/8.1/apache2/php.ini
```

Update these settings:

```ini
; Error handling
display_errors = Off
display_startup_errors = Off
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
log_errors = On
error_log = /var/log/php/error.log

; Performance
memory_limit = 256M
max_execution_time = 60
max_input_time = 60

; File uploads
upload_max_filesize = 10M
post_max_size = 10M

; Security
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off

; OPcache (crucial for performance)
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2
opcache.fast_shutdown = 1
```

Create log directory:

```bash
sudo mkdir -p /var/log/php
sudo chown www-data:www-data /var/log/php
```

Restart Apache/PHP-FPM:

```bash
# Apache
sudo systemctl restart apache2

# Nginx with PHP-FPM
sudo systemctl restart php8.1-fpm
```

### 7. Database Optimization

Edit MySQL configuration:

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Add/update under `[mysqld]`:

```ini
# Performance
innodb_buffer_pool_size = 2G
innodb_log_file_size = 256M
max_connections = 200
query_cache_type = 1
query_cache_size = 64M

# Logging
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

Restart MySQL:

```bash
sudo systemctl restart mysql
```

### 8. Firewall Configuration

```bash
# Install UFW if not present
sudo apt install ufw

# Default policies
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Allow SSH, HTTP, HTTPS
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw enable
sudo ufw status
```

### 9. Automated Backups

#### Database Backup Script

```bash
sudo nano /usr/local/bin/backup-splashprojects.sh
```

```bash
#!/bin/bash

BACKUP_DIR="/var/backups/splashprojects"
DB_NAME="splashprojects"
DB_USER="splashuser"
DB_PASS="STRONG_PASSWORD_HERE"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Files backup
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/SplashProjects/storage/uploads

# Delete backups older than 30 days
find $BACKUP_DIR -type f -mtime +30 -delete

echo "Backup completed: $DATE"
```

Make executable:

```bash
sudo chmod +x /usr/local/bin/backup-splashprojects.sh
```

Schedule daily backups:

```bash
sudo crontab -e
```

Add:

```
0 2 * * * /usr/local/bin/backup-splashprojects.sh >> /var/log/splashprojects-backup.log 2>&1
```

### 10. Monitoring Setup

#### Log Rotation

```bash
sudo nano /etc/logrotate.d/splashprojects
```

```
/var/www/SplashProjects/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

#### Server Monitoring

Install monitoring tools:

```bash
# Basic monitoring
sudo apt install htop iotop nethogs

# Optional: Install New Relic, Datadog, or similar
```

#### Application Monitoring

Add health check endpoint to monitor uptime:

Create `/var/www/SplashProjects/public/health.php`:

```php
<?php
http_response_code(200);
echo json_encode(['status' => 'healthy', 'timestamp' => time()]);
```

Monitor with external service (UptimeRobot, Pingdom, etc.)

### 11. Performance Optimization

#### Enable Gzip Compression (Apache)

```bash
sudo a2enmod deflate
```

Add to virtual host or .htaccess:

```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>
```

#### Browser Caching

Add to .htaccess:

```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

#### CDN Setup (Optional)

Use Cloudflare or AWS CloudFront:
1. Create CDN distribution
2. Update asset URLs in templates
3. Configure caching rules

### 12. Security Hardening

#### Disable Directory Listing

Already configured in virtual host with `-Indexes`

#### Hide .env File

Add to .htaccess:

```apache
<FilesMatch "^\.env">
    Order allow,deny
    Deny from all
</FilesMatch>
```

#### Fail2Ban for Brute Force Protection

```bash
sudo apt install fail2ban

sudo nano /etc/fail2ban/jail.local
```

```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[apache-auth]
enabled = true
```

Start Fail2Ban:

```bash
sudo systemctl start fail2ban
sudo systemctl enable fail2ban
```

#### Regular Security Updates

```bash
# Auto-update security patches
sudo apt install unattended-upgrades
sudo dpkg-reconfigure --priority=low unattended-upgrades
```

### 13. Post-Deployment Verification

#### Checklist

- [ ] Application loads at https://yourdomain.com
- [ ] SSL certificate is valid (green padlock)
- [ ] Can register new user
- [ ] Can login successfully
- [ ] Can create project
- [ ] Can create board
- [ ] Can create task
- [ ] Drag and drop works
- [ ] File upload works
- [ ] API endpoints respond (test with Postman)
- [ ] Database backups running
- [ ] Logs are being written
- [ ] Monitoring is active
- [ ] Email notifications work (if configured)

#### Performance Test

```bash
# Install Apache Bench
sudo apt install apache2-utils

# Run load test
ab -n 1000 -c 10 https://yourdomain.com/

# Check results
# - Requests per second should be > 50
# - Time per request should be < 200ms
# - No failed requests
```

#### Security Scan

```bash
# Run Nikto security scanner
nikto -h https://yourdomain.com

# Check SSL configuration
# Visit: https://www.ssllabs.com/ssltest/analyze.html?d=yourdomain.com
```

## Maintenance Tasks

### Daily
- [ ] Check error logs: `tail -f /var/log/apache2/splashprojects-error.log`
- [ ] Monitor disk space: `df -h`
- [ ] Check backup completion

### Weekly
- [ ] Review slow query log
- [ ] Check database size: `SELECT table_schema, SUM(data_length + index_length) / 1024 / 1024 "Size (MB)" FROM information_schema.tables WHERE table_schema = "splashprojects";`
- [ ] Review security logs
- [ ] Test backup restoration

### Monthly
- [ ] Apply security updates: `sudo apt update && sudo apt upgrade`
- [ ] Optimize database: `mysqlcheck -u root -p --optimize --all-databases`
- [ ] Review user accounts and permissions
- [ ] Check SSL certificate expiration
- [ ] Review and archive old logs

## Scaling Considerations

### When to Scale

Scale up when:
- CPU usage consistently > 70%
- Memory usage > 80%
- Response time > 500ms
- Database connections maxed out
- Storage > 80% full

### Horizontal Scaling

1. **Load Balancer Setup**
   - Use Nginx or HAProxy
   - Multiple application servers
   - Session storage in Redis/Memcached

2. **Database Replication**
   - Master-slave setup
   - Read queries to slaves
   - Write queries to master

3. **File Storage**
   - Move uploads to S3/MinIO
   - Use CDN for static assets

4. **Caching Layer**
   - Implement Redis for sessions
   - Cache frequently accessed data
   - Use APCu for OPcache

## Troubleshooting

### Issue: White Screen (500 Error)

```bash
# Check error logs
sudo tail -f /var/log/apache2/splashprojects-error.log

# Check PHP logs
sudo tail -f /var/log/php/error.log

# Enable display_errors temporarily
sudo nano /etc/php/8.1/apache2/php.ini
# Set: display_errors = On
sudo systemctl restart apache2
```

### Issue: Database Connection Failed

```bash
# Check MySQL is running
sudo systemctl status mysql

# Test connection
mysql -u splashuser -p

# Check credentials in .env
cat /var/www/SplashProjects/.env
```

### Issue: File Upload Fails

```bash
# Check permissions
ls -la /var/www/SplashProjects/storage/uploads

# Fix permissions
sudo chown -R www-data:www-data /var/www/SplashProjects/storage
sudo chmod -R 775 /var/www/SplashProjects/storage/uploads
```

### Issue: Slow Performance

```bash
# Check PHP OPcache status
sudo php -i | grep opcache

# Check MySQL slow query log
sudo tail /var/log/mysql/slow.log

# Check server resources
htop
```

## Rollback Procedure

If deployment fails:

```bash
# Restore database
gunzip < /var/backups/splashprojects/db_TIMESTAMP.sql.gz | mysql -u splashuser -p splashprojects

# Restore files
tar -xzf /var/backups/splashprojects/files_TIMESTAMP.tar.gz -C /

# Revert code
cd /var/www/SplashProjects
git reset --hard PREVIOUS_COMMIT_HASH
```

## Conclusion

Your SplashProjects application is now deployed and running in production! Monitor the application regularly and follow the maintenance schedule to ensure optimal performance and security.

For support, refer to:
- README.md for application documentation
- CODE_REVIEW.md for architecture details
- TESTING_CHECKLIST.md for QA procedures

---

**Deployment Completed**: _____________
**Deployed By**: _____________
**Server**: _____________
**Version**: 1.0.0
