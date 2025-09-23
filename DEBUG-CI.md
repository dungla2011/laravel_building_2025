# 🐛 GitHub Actions Debug Guide

## Current Issue
Laravel server returns HTTP 500 error in GitHub Actions CI environment.

## 🔧 Fixes Applied

### 1. Enhanced Environment Setup
- ✅ Copy APP_KEY from main .env to .env.testing
- ✅ Use absolute database path for CI environment
- ✅ Clear and cache config properly

### 2. Debug Steps Added
- ✅ Show .env.testing content
- ✅ Check database connection
- ✅ Verify storage permissions
- ✅ Test migration status

### 3. Storage Directory Fix
- ✅ Create all required storage subdirectories
- ✅ Set proper permissions (777)
- ✅ Ensure logs directory exists

### 4. Server Startup Improvement  
- ✅ Better error handling
- ✅ Multiple connection attempts
- ✅ Display server logs on failure

## 🧪 Local Testing

```bash
# Test server startup locally
bash test-server-local.sh

# Result: SUCCESS (HTTP 200)
```

## 📋 Next Steps

1. **Commit & Push** updated workflow
2. **Monitor** GitHub Actions logs
3. **Check** debug output for issues
4. **Fix** any remaining problems

## 🔍 Debug Commands for CI

If still failing, check:
```bash
# In GitHub Actions debug step:
cat .env.testing
ls -la storage/
php artisan migrate:status --env=testing
tail -20 storage/logs/laravel.log
```

## 🎯 Expected Result

After fixes:
- ✅ Server should return HTTP 200/302
- ✅ All tests should pass
- ✅ CI pipeline should complete