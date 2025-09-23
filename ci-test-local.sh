#!/bin/bash

# Local CI Test Script
# This script simulates the GitHub Actions CI pipeline locally

echo "🚀 Starting Local CI Test Simulation..."
echo "========================================"

# 1. Install dependencies
echo "📦 Installing dependencies..."
composer install --prefer-dist --no-progress --no-interaction
if [ $? -ne 0 ]; then
    echo "❌ Composer install failed"
    exit 1
fi

# 2. Setup environment
echo "🔧 Setting up environment..."
cp .env.example .env 2>/dev/null || echo ".env already exists"
php artisan key:generate --force

# 3. Directory permissions
echo "📁 Setting directory permissions..."
chmod -R 777 storage bootstrap/cache

# 4. Create database
echo "🗄️  Creating testing database..."
mkdir -p database
touch database/testing.sqlite

# 5. Run migrations and seeders
echo "🔄 Running migrations and seeders..."
php artisan migrate --env=testing --force
php artisan db:seed --env=testing --force

# 6. Start Laravel server in background
echo "🌐 Starting Laravel server..."
php artisan serve --port=8000 --env=testing &
SERVER_PID=$!

# Wait for server to start
echo "⏳ Waiting for server to be ready..."
sleep 5

# Check if server is responding
for i in {1..10}; do
    if curl -f http://localhost:8000 >/dev/null 2>&1; then
        echo "✅ Server is ready!"
        break
    fi
    if [ $i -eq 10 ]; then
        echo "❌ Server failed to start"
        kill $SERVER_PID 2>/dev/null
        exit 1
    fi
    sleep 2
done

# 7. Run tests
echo "🧪 Running PHPUnit tests..."
php artisan test --env=testing
TEST_RESULT=$?

echo "🧪 Running standalone permission test..."
php tests/01-test-permission-standalone.php
STANDALONE_RESULT=$?

# 8. Cleanup
echo "🧹 Cleaning up..."
kill $SERVER_PID 2>/dev/null

# 9. Results
echo "========================================"
echo "📊 CI Test Results:"
if [ $TEST_RESULT -eq 0 ]; then
    echo "✅ PHPUnit tests: PASSED"
else
    echo "❌ PHPUnit tests: FAILED"
fi

if [ $STANDALONE_RESULT -eq 0 ]; then
    echo "✅ Standalone test: PASSED"
else
    echo "⚠️  Standalone test: FAILED (but continuing)"
fi

echo "========================================"

if [ $TEST_RESULT -eq 0 ]; then
    echo "🎉 Local CI simulation completed successfully!"
    exit 0
else
    echo "💥 Local CI simulation failed!"
    exit 1
fi