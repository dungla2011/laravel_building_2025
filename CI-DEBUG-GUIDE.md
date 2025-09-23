# 🔍 CI Debug Monitoring Guide

## 🎯 **Issue Under Investigation**
Permission toggle failing in CI: `✅ Successfully enabled 0/7 permissions`
vs Local success: `✅ Successfully enabled 7/7 permissions`

## 📊 **Debug Information Added** 

### 1. Permission Toggle Logging:
```
   Role ID: {roleId}
   Enabling: {permissionName} (ID: {permissionId})... ✅/❌
```

### 2. HTTP Error Details:
```
⚠️  Permission toggle failed: HTTP {code}
   Message: {errorMessage}
   Response: {responseBody}
```

### 3. Expected CI Output:
Look for these patterns in GitHub Actions logs:

#### Success Pattern (Local):
```
🔓 Step 1: Enabling all permissions for Viewer...
   Role ID: 3
   Enabling: View All Users (ID: 2)... ✅
   Enabling: View Users (ID: 7)... ✅
   ...
✅ Successfully enabled 7/7 permissions
```

#### Failure Pattern (CI - Current):
```
🔓 Step 1: Enabling all permissions for Viewer...
   Role ID: ?
   Enabling: View All Users (ID: 2)... ❌
      ⚠️  Permission toggle failed: HTTP {code}
   ...
✅ Successfully enabled 0/7 permissions
```

## 🔍 **What to Monitor**

### In GitHub Actions Logs:

1. **Role ID Check**: 
   - Local shows `Role ID: 3`
   - CI should show similar role ID
   - If different, indicates database seeding issue

2. **Permission Toggle HTTP Codes**:
   - Look for `⚠️  Permission toggle failed: HTTP {code}`
   - Common issues:
     - `HTTP 403`: CSRF token invalid
     - `HTTP 404`: Route not found  
     - `HTTP 422`: Validation failed
     - `HTTP 500`: Server error

3. **CSRF Token**:
   - Should see `✅ CSRF token obtained`
   - If missing, session initialization failed

4. **Database State**:
   - Look for role/permission ID mismatches
   - Compare seeding between local vs CI

## 🎯 **Next Steps Based on CI Logs**

### If HTTP 403 (CSRF Issues):
- Fix session/cookie handling in CI
- Check CSRF token extraction

### If HTTP 404 (Route Issues):  
- Verify admin routes are available in testing
- Check route caching in CI

### If HTTP 422 (Validation):
- Check request data format
- Verify role/permission ID validity

### If Role ID Different:
- Database seeding inconsistency
- Fix seeder or migration order

## 📋 **Monitor At**

GitHub Repository → Actions → Latest Workflow → View Logs

Look for the detailed debug output in the test execution step.

**Expected: Debug logs will reveal the exact reason for permission toggle failures in CI environment.** 🔍