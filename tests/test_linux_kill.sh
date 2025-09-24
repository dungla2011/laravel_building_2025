#!/bin/bash

# Test script to verify Linux process killing logic
echo "🧪 Testing Linux process killing logic..."

# Start a dummy PHP server to test with
echo "🚀 Starting dummy PHP server on port 12368..."
php -S 127.0.0.1:12368 > /dev/null 2>&1 &
SERVER_PID=$!

echo "✅ Started server with PID: $SERVER_PID"

# Give it a moment to start
sleep 2

# Check if it's running
if kill -0 $SERVER_PID 2>/dev/null; then
    echo "✅ Server is running"
else
    echo "❌ Server failed to start"
    exit 1
fi

# Test kill -9
echo "🔫 Testing kill -9 $SERVER_PID..."
kill -9 $SERVER_PID 2>/dev/null
KILL_EXIT=$?

if [ $KILL_EXIT -eq 0 ]; then
    echo "✅ kill -9 command succeeded"
else
    echo "❌ kill -9 command failed with exit code: $KILL_EXIT"
fi

# Wait a moment and check if process is gone
sleep 1

if kill -0 $SERVER_PID 2>/dev/null; then
    echo "❌ Process still running after kill -9"
    # Try to clean up manually
    kill -9 $SERVER_PID 2>/dev/null || true
else
    echo "✅ Process successfully killed"
fi

echo "🎉 Linux kill test completed"