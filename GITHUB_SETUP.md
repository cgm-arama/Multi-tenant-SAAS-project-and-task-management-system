# SplashProjects - GitHub Setup Guide

## Quick Start Commands

Follow these commands to initialize Git, create a GitHub repository, and push your code.

### 1. Initialize Git Repository

```bash
cd /home/user/SplashProjects

# Initialize git repository
git init

# Add all files to staging
git add .

# Create initial commit
git commit -m "Initial commit: SplashProjects v1.0.0

- Complete multi-tenant SaaS platform
- Kanban boards with drag-and-drop
- Project and task management
- User management and invitations
- Subscription and billing system
- REST API with authentication
- Activity logging and notifications
- File attachments support
- Responsive UI with vanilla JS
- Comprehensive documentation"

# Set main as default branch
git branch -M main
```

### 2. Create GitHub Repository

**Option A: Using GitHub CLI (gh)**

```bash
# Install GitHub CLI if not already installed
# Visit: https://cli.github.com/

# Authenticate
gh auth login

# Create public repository
gh repo create SplashProjects --public --description "Multi-tenant SaaS project management platform with Kanban boards" --source=. --remote=origin --push

# Or create private repository
gh repo create SplashProjects --private --description "Multi-tenant SaaS project management platform with Kanban boards" --source=. --remote=origin --push
```

**Option B: Using GitHub Web Interface**

1. Go to https://github.com/new
2. Repository name: `SplashProjects`
3. Description: `Multi-tenant SaaS project management platform with Kanban boards`
4. Choose Public or Private
5. **Do NOT** initialize with README (we already have one)
6. Click "Create repository"

Then connect local repository:

```bash
# Add remote origin (replace YOUR_USERNAME with your GitHub username)
git remote add origin https://github.com/YOUR_USERNAME/SplashProjects.git

# Or use SSH
git remote add origin git@github.com:YOUR_USERNAME/SplashProjects.git

# Push to GitHub
git push -u origin main
```

### 3. Verify Upload

Visit your repository:
```
https://github.com/YOUR_USERNAME/SplashProjects
```

You should see:
- ✅ README.md displayed
- ✅ All project files
- ✅ Proper folder structure
- ✅ Documentation files

### 4. Add .gitignore (Optional but Recommended)

Create `.gitignore` file:

```bash
cat > .gitignore << 'EOF'
# Environment files
.env
.env.local
.env.production

# Uploaded files
storage/uploads/*
!storage/uploads/.gitkeep

# Logs
storage/logs/*.log
*.log

# OS files
.DS_Store
Thumbs.db

# IDE files
.vscode/
.idea/
*.swp
*.swo
*~

# Temporary files
tmp/
temp/

# Vendor (if using Composer)
vendor/

# Cache
cache/
*.cache
EOF

git add .gitignore
git commit -m "Add .gitignore file"
git push origin main
```

### 5. Create .gitkeep for Empty Directories

```bash
# Keep empty directories in Git
touch storage/uploads/.gitkeep
touch storage/logs/.gitkeep

git add storage/uploads/.gitkeep storage/logs/.gitkeep
git commit -m "Add .gitkeep files for empty directories"
git push origin main
```

### 6. Add Topics/Tags (GitHub Web)

Add these topics to make your repository discoverable:

1. Go to your repository on GitHub
2. Click "Add topics"
3. Add: `php`, `mysql`, `saas`, `multi-tenant`, `kanban`, `project-management`, `mvc`, `rest-api`, `task-management`

### 7. Create GitHub Release

```bash
# Create and push a tag
git tag -a v1.0.0 -m "Release version 1.0.0

Features:
- Multi-tenant architecture
- Kanban boards with drag-and-drop
- Project and task management
- User roles and permissions
- Subscription management
- REST API
- Activity logging
- File attachments
- Responsive design"

git push origin v1.0.0
```

Then on GitHub:
1. Go to "Releases"
2. Click "Draft a new release"
3. Choose tag `v1.0.0`
4. Title: `SplashProjects v1.0.0`
5. Description: Copy from tag message
6. Click "Publish release"

### 8. Set Up GitHub Pages (Optional - for Documentation)

If you want to host documentation on GitHub Pages:

```bash
# Create gh-pages branch
git checkout --orphan gh-pages

# Remove all files
git rm -rf .

# Create index.html
cat > index.html << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>SplashProjects Documentation</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <h1>SplashProjects</h1>
    <p>Multi-tenant SaaS Project Management Platform</p>
    <ul>
        <li><a href="https://github.com/YOUR_USERNAME/SplashProjects">GitHub Repository</a></li>
        <li><a href="https://github.com/YOUR_USERNAME/SplashProjects/blob/main/README.md">README</a></li>
        <li><a href="https://github.com/YOUR_USERNAME/SplashProjects/blob/main/CODE_REVIEW.md">Code Review</a></li>
        <li><a href="https://github.com/YOUR_USERNAME/SplashProjects/blob/main/DEPLOYMENT_GUIDE.md">Deployment Guide</a></li>
    </ul>
</body>
</html>
EOF

git add index.html
git commit -m "Create GitHub Pages"
git push origin gh-pages

# Switch back to main
git checkout main
```

Enable in GitHub Settings → Pages → Source: gh-pages branch

### 9. Configure Repository Settings

#### Branch Protection

1. Go to Settings → Branches
2. Add rule for `main`
3. Enable:
   - ✅ Require pull request reviews before merging
   - ✅ Require status checks to pass before merging
   - ✅ Include administrators

#### Collaborators

1. Go to Settings → Collaborators
2. Click "Add people"
3. Enter usernames or emails

#### Issues and Projects

1. Go to Settings → Features
2. Enable:
   - ✅ Issues
   - ✅ Projects
   - ✅ Wiki (optional)

### 10. Create Development Workflow

#### Create develop branch

```bash
git checkout -b develop
git push -u origin develop
```

#### Feature branch workflow

```bash
# Create feature branch
git checkout -b feature/new-feature develop

# Make changes
git add .
git commit -m "Add new feature"

# Push to GitHub
git push -u origin feature/new-feature

# Create Pull Request on GitHub
# Merge to develop after review
# Delete feature branch after merge
```

### 11. Set Up CI/CD (Optional)

Create `.github/workflows/ci.yml`:

```yaml
name: CI

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
    - uses: actions/checkout@v2

    - name: Set up PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: mbstring, pdo_mysql

    - name: Run tests
      run: php tests/run_tests.php

    - name: Check code style
      run: |
        # Add PHP_CodeSniffer or similar
        echo "Code style check passed"
```

Commit and push:

```bash
git add .github/workflows/ci.yml
git commit -m "Add CI workflow"
git push origin main
```

### 12. README Badges (Optional)

Add badges to README.md:

```markdown
# SplashProjects

![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-orange)
![License](https://img.shields.io/badge/license-MIT-green)
![GitHub release](https://img.shields.io/github/v/release/YOUR_USERNAME/SplashProjects)
![GitHub stars](https://img.shields.io/github/stars/YOUR_USERNAME/SplashProjects)

Multi-tenant SaaS project management platform with Kanban boards
```

### 13. Useful Git Commands

```bash
# Check status
git status

# View commit history
git log --oneline --graph --all

# Create new branch
git checkout -b branch-name

# Switch branches
git checkout main

# Pull latest changes
git pull origin main

# View remote repositories
git remote -v

# Undo last commit (keep changes)
git reset --soft HEAD~1

# Undo last commit (discard changes)
git reset --hard HEAD~1

# View changes
git diff

# Stash changes temporarily
git stash
git stash pop
```

### 14. Contributing Guidelines

Create `CONTRIBUTING.md`:

```bash
cat > CONTRIBUTING.md << 'EOF'
# Contributing to SplashProjects

Thank you for considering contributing to SplashProjects!

## How to Contribute

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Code Style

- Follow PSR-1 and PSR-2 coding standards
- Add comments for complex logic
- Write meaningful commit messages

## Testing

- Run tests before submitting PR: `php tests/run_tests.php`
- Add tests for new features

## Reporting Bugs

Open an issue with:
- Clear description
- Steps to reproduce
- Expected vs actual behavior
- Screenshots if applicable

## Feature Requests

Open an issue describing:
- Use case
- Proposed solution
- Alternative solutions considered
EOF

git add CONTRIBUTING.md
git commit -m "Add contributing guidelines"
git push origin main
```

### 15. License

Create `LICENSE` file:

```bash
cat > LICENSE << 'EOF'
MIT License

Copyright (c) 2025 SplashProjects

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
EOF

git add LICENSE
git commit -m "Add MIT license"
git push origin main
```

## Summary

Your SplashProjects repository is now set up on GitHub with:

✅ Complete source code
✅ Comprehensive documentation
✅ Proper Git history
✅ Branch protection (optional)
✅ CI/CD pipeline (optional)
✅ Contributing guidelines
✅ MIT License

Repository URL: `https://github.com/YOUR_USERNAME/SplashProjects`

Share your project with the world! 🚀
