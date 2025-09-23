# GitHub Actions CI/CD Setup

This repository is configured with GitHub Actions for continuous integration and testing.

## 🚀 CI/CD Pipeline Features

### ✅ Automated Testing
- **PHPUnit Tests**: Full Laravel Feature and Unit tests
- **Permission Testing**: Complete role-based permission validation
- **Database Testing**: SQLite with migrations and seeding
- **API Testing**: HTTP endpoint validation with CSRF protection

### 🔧 Matrix Testing
- **PHP Versions**: 8.2, 8.3
- **Database**: SQLite (in-memory and file-based)
- **Environment**: Ubuntu Latest

### 📁 Workflow File
```
.github/workflows/laravel.yml
```

## 🏃‍♂️ Local CI Testing

### Option 1: PowerShell (Windows)
```powershell
powershell -ExecutionPolicy Bypass -File ci-test-local.ps1
```

### Option 2: Bash (Linux/macOS)
```bash
chmod +x ci-test-local.sh
./ci-test-local.sh
```

### Option 3: Manual Laravel Testing
```bash
# Install dependencies
composer install

# Setup environment
cp .env.testing .env
php artisan key:generate

# Create database
mkdir -p database
touch database/testing.sqlite

# Run migrations and seeders
php artisan migrate --env=testing --force
php artisan db:seed --env=testing --force

# Start server (background)
php artisan serve --port=8000 --env=testing &

# Run tests
php artisan test --env=testing
```

## 📊 Test Coverage

### Feature Tests
- ✅ **RolePermissionIntegrationTest**: Comprehensive permission system testing
  - Viewer role with permissions enabled/disabled
  - Super Admin role functionality
  - Server health checks
  - Database seeding verification

### Integration Tests
- ✅ **HTTP API Testing**: All CRUD operations
- ✅ **CSRF Protection**: Token validation
- ✅ **Session Management**: Cookie jar persistence
- ✅ **Permission Matrix**: Dynamic enable/disable testing

## 🔗 GitHub Actions Triggers

### Push Events
- `main` branch
- `develop` branch

### Pull Request Events
- Target: `main` branch
- Target: `develop` branch

## 📝 Environment Configuration

### Required Environment Variables
```bash
DB_CONNECTION=sqlite
DB_DATABASE=database/testing.sqlite
CACHE_DRIVER=array
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
MAIL_MAILER=array
TELESCOPE_ENABLED=false
PERMISSION_VALIDATION_ENABLED=true
```

### Server Configuration
- **Port**: 8000 (testing)
- **Timeout**: 30 seconds for server startup
- **Background**: Laravel artisan serve

## 🛠️ CI Pipeline Steps

1. **Environment Setup**
   - PHP installation with extensions
   - Composer dependency caching
   - Laravel key generation

2. **Database Preparation**
   - SQLite database creation
   - Fresh migrations
   - Database seeding

3. **Server Management**
   - Laravel development server startup
   - Health check validation
   - Background process management

4. **Test Execution**
   - PHPUnit Feature tests
   - Standalone permission tests
   - Error logging on failure

## 🎯 Success Criteria

### All tests must pass:
- ✅ **5 Feature Tests**: 31 assertions
- ✅ **Permission Matrix**: 100% API coverage
- ✅ **Database Integrity**: Role/permission seeding
- ✅ **Server Health**: HTTP response validation

### Performance Benchmarks:
- **Total Duration**: ~30-40 seconds
- **Server Startup**: < 5 seconds  
- **Test Execution**: ~35 seconds
- **Database Operations**: < 3 seconds per migration

## 🔧 Troubleshooting

### Common Issues

1. **Server Not Starting**
   ```bash
   # Check port availability
   netstat -tulpn | grep :8000
   
   # Manual server start
   php artisan serve --port=8000 --env=testing
   ```

2. **Database Issues**
   ```bash
   # Recreate database
   rm database/testing.sqlite
   touch database/testing.sqlite
   php artisan migrate --env=testing --force
   ```

3. **Permission Errors**
   ```bash
   # Fix storage permissions
   chmod -R 777 storage bootstrap/cache
   ```

### Log Analysis
- Laravel logs: `storage/logs/laravel.log`
- GitHub Actions logs: Available in Actions tab
- Local test output: Console with colored status indicators

## 📈 Future Enhancements

- [ ] Code coverage reporting
- [ ] Performance monitoring
- [ ] Security vulnerability scanning
- [ ] Multi-database testing (MySQL, PostgreSQL)
- [ ] Frontend asset testing
- [ ] Deployment automation