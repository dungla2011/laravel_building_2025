#!/bin/bash

echo "🧪 Testing CI Pipeline Locally..."
echo "================================"

# Check if Laravel server can start without errors
echo "🌐 Testing Laravel Server Startup..."

# Start server in background
php artisan serve --port=8001 --env=testing &
SERVER_PID=$!

# Wait and test
sleep 5

echo "📡 Testing server response..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8001)

echo "HTTP Response Code: $HTTP_CODE"

if [[ "$HTTP_CODE" == "200" || "$HTTP_CODE" == "302" ]]; then
    echo "✅ Server is responding correctly!"
    RESULT="SUCCESS"
else
    echo "❌ Server returned HTTP $HTTP_CODE"
    echo "📋 Checking Laravel logs..."
    tail -10 storage/logs/laravel.log || echo "No log file found"
    RESULT="FAILED"
fi

# Cleanup
kill $SERVER_PID 2>/dev/null

echo "================================"
echo "🏁 Local CI Test Result: $RESULT"

if [ "$RESULT" == "SUCCESS" ]; then
    exit 0
else
    exit 1
fi