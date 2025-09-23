# Local CI Test Script for Windows PowerShell
# This script simulates the GitHub Actions CI pipeline locally

Write-Host "🚀 Starting Local CI Test Simulation..." -ForegroundColor Green
Write-Host "========================================"

# 1. Install dependencies
Write-Host "📦 Installing dependencies..." -ForegroundColor Yellow
composer install --prefer-dist --no-progress --no-interaction
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Composer install failed" -ForegroundColor Red
    exit 1
}

# 2. Setup environment
Write-Host "🔧 Setting up environment..." -ForegroundColor Yellow
if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
}
php artisan key:generate --force

# 3. Create database directory and file
Write-Host "🗄️  Creating testing database..." -ForegroundColor Yellow
if (-not (Test-Path "database")) {
    New-Item -ItemType Directory -Path "database" -Force
}
if (-not (Test-Path "database/testing.sqlite")) {
    New-Item -ItemType File -Path "database/testing.sqlite" -Force
}

# 4. Run migrations and seeders
Write-Host "🔄 Running migrations and seeders..." -ForegroundColor Yellow
php artisan migrate --env=testing --force
php artisan db:seed --env=testing --force

# 5. Start Laravel server in background
Write-Host "🌐 Starting Laravel server..." -ForegroundColor Yellow
$currentDir = Get-Location
$serverJob = Start-Job -ScriptBlock {
    Set-Location $args[0]
    php artisan serve --port=8000 --env=testing
} -ArgumentList $currentDir

# Wait for server to start
Write-Host "⏳ Waiting for server to be ready..." -ForegroundColor Yellow
Start-Sleep -Seconds 5

# Check if server is responding
$serverReady = $false
for ($i = 1; $i -le 10; $i++) {
    try {
        $response = Invoke-WebRequest -Uri "http://localhost:8000" -TimeoutSec 3 -ErrorAction Stop
        if ($response.StatusCode -eq 200) {
            Write-Host "✅ Server is ready!" -ForegroundColor Green
            $serverReady = $true
            break
        }
    }
    catch {
        # Server not ready yet
    }
    
    if ($i -eq 10) {
        Write-Host "❌ Server failed to start" -ForegroundColor Red
        Stop-Job $serverJob -ErrorAction SilentlyContinue
        Remove-Job $serverJob -ErrorAction SilentlyContinue
        exit 1
    }
    Start-Sleep -Seconds 2
}

# 6. Run tests
Write-Host "🧪 Running PHPUnit tests..." -ForegroundColor Yellow
php artisan test --env=testing
$testResult = $LASTEXITCODE

Write-Host "🧪 Running standalone permission test..." -ForegroundColor Yellow
php tests/01-test-permission-standalone.php
$standaloneResult = $LASTEXITCODE

# 7. Cleanup
Write-Host "🧹 Cleaning up..." -ForegroundColor Yellow
Stop-Job $serverJob -ErrorAction SilentlyContinue
Remove-Job $serverJob -ErrorAction SilentlyContinue

# 8. Results
Write-Host "========================================"
Write-Host "📊 CI Test Results:" -ForegroundColor Cyan
if ($testResult -eq 0) {
    Write-Host "✅ PHPUnit tests: PASSED" -ForegroundColor Green
} else {
    Write-Host "❌ PHPUnit tests: FAILED" -ForegroundColor Red
}

if ($standaloneResult -eq 0) {
    Write-Host "✅ Standalone test: PASSED" -ForegroundColor Green
} else {
    Write-Host "⚠️  Standalone test: FAILED (but continuing)" -ForegroundColor Yellow
}

Write-Host "========================================"

if ($testResult -eq 0) {
    Write-Host "🎉 Local CI simulation completed successfully!" -ForegroundColor Green
    exit 0
} else {
    Write-Host "💥 Local CI simulation failed!" -ForegroundColor Red
    exit 1
}