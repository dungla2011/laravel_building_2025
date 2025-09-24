<?php

/**
 * Background Process Killer Monitor
 * 
 * This script runs in the background and continuously monitors the pid.txt file.
 * When it finds a PID in the file, it kills that process and clears the file.
 * 
 * Usage:
 *   php kill_process.php
 * 
 * The script will run indefinitely, checking every 1 second.
 * Press Ctrl+C to stop.
 */

$pidFile = __DIR__ . '/pid.txt';
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

echo "🔄 Background Process Killer Monitor Started\n";
echo "📁 Monitoring file: $pidFile\n";
echo "💻 Platform: " . ($isWindows ? 'Windows' : 'Unix') . "\n";
echo "⏱️  Check interval: 1 second\n";
echo "🛑 Press Ctrl+C to stop\n";
echo str_repeat("-", 50) . "\n";

$checkCount = 0;

while (true) {
    $checkCount++;
    
    // Check if PID file exists and has content
    if (file_exists($pidFile)) {
        $content = trim(file_get_contents($pidFile));
        
        if ($content === 'STOP') {
            echo "[" . date('H:i:s') . "] 🛑 Received STOP command, exiting monitor...\n";
            break;
        } elseif (!empty($content)) {
            // Split content by lines to handle multiple PIDs
            $pids = array_filter(array_map('trim', explode("\n", $content)));
            $validPids = [];
            
            // Validate all PIDs are numeric
            foreach ($pids as $pidStr) {
                if (is_numeric($pidStr)) {
                    $validPids[] = (int) $pidStr;
                }
            }
            
            if (!empty($validPids)) {
                echo "[" . date('H:i:s') . "] 🎯 Found " . count($validPids) . " PID(s) in file: " . implode(', ', $validPids) . "\n";
                
                $killedCount = 0;
                
                // Kill each PID
                foreach ($validPids as $pid) {
                    echo "   🔄 Processing PID: $pid\n";
                    
                    $killed = false;
                    
                    if ($isWindows) {
                        // Windows approach
                        echo "      Using Windows taskkill method...\n";
                        
                        // First check if process exists
                        exec("tasklist /PID $pid /FO CSV 2>NUL", $checkOutput, $checkExitCode);
                        
                        if ($checkExitCode === 0 && count($checkOutput) > 1) {
                            echo "      Process $pid found, killing...\n";
                            
                            // Try graceful kill first
                            exec("taskkill /PID $pid 2>NUL", $killOutput, $killExitCode);
                            
                            if ($killExitCode === 0) {
                                echo "      ✅ Process $pid killed gracefully\n";
                                $killed = true;
                            } else {
                                echo "      Graceful kill failed, using force...\n";
                                exec("taskkill /PID $pid /F 2>NUL", $forceKillOutput, $forceExitCode);
                                
                                if ($forceExitCode === 0) {
                                    echo "      ✅ Process $pid force killed\n";
                                    $killed = true;
                                } else {
                                    echo "      ❌ Failed to kill process $pid\n";
                                }
                            }
                        } else {
                            echo "      ⚠️  Process $pid not found (may have already terminated)\n";
                            $killed = true; // Consider it handled
                        }
                    } else {
                        // Unix/Linux approach
                        echo "      Using Unix kill signals...\n";
                        
                        // Check if process exists
                        exec("kill -0 $pid 2>/dev/null", $checkOutput, $checkExitCode);
                        
                        if ($checkExitCode === 0) {
                            echo "      Process $pid found, sending TERM signal...\n";
                            
                            // Try TERM signal first (graceful)
                            exec("kill -TERM $pid 2>/dev/null", $termOutput, $termExitCode);
                            
                            if ($termExitCode === 0) {
                                echo "      TERM signal sent, waiting 2 seconds...\n";
                                sleep(2);
                                
                                // Check if still running
                                exec("kill -0 $pid 2>/dev/null", $checkOutput2, $checkExitCode2);
                                
                                if ($checkExitCode2 !== 0) {
                                    echo "      ✅ Process $pid terminated gracefully\n";
                                    $killed = true;
                                } else {
                                    echo "      Process still running, using KILL signal...\n";
                                    exec("kill -KILL $pid 2>/dev/null", $killOutput, $killExitCode);
                                    
                                    if ($killExitCode === 0) {
                                        echo "      ✅ Process $pid force killed\n";
                                        $killed = true;
                                    } else {
                                        echo "      ❌ Failed to kill process $pid\n";
                                    }
                                }
                            } else {
                                echo "      ❌ Failed to send TERM signal to process $pid\n";
                            }
                        } else {
                            echo "      ⚠️  Process $pid not found (may have already terminated)\n";
                            $killed = true; // Consider it handled
                        }
                    }
                    
                    if ($killed) {
                        $killedCount++;
                        echo "      ✅ PID $pid handled successfully\n";
                    }
                }
                
                // Clear the PID file after processing all PIDs
                file_put_contents($pidFile, '');
                echo "   🧹 Cleared PID file after processing " . count($validPids) . " processes\n";
                echo "   🎉 Batch kill completed: $killedCount/" . count($validPids) . " processes handled\n";
                echo "\n";
            }
        }
    }
    
    // Show periodic status (every 30 seconds)
    if ($checkCount % 30 === 0) {
        echo "[" . date('H:i:s') . "] 💓 Monitor active (check #$checkCount)\n";
    }
    
    // Sleep for 1 second
    sleep(1);
}