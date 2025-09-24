<?php

/**
 * Run All Tests by Including Files
 * 
 * This approach includes all test files in the same process
 * to avoid server startup/shutdown issues
 * 
 * Usage: php tests/run_all_2.php --env=testing
 */

class TestSuiteRunner
{
    private array $testFiles = [];
    private string $testsDir;
    private float $startTime;
    private string $envFlag = '';
    private bool $verbose = false;
    private $backgroundKillerProcess = null;
    public function __construct()
    {
        $this->testsDir = __DIR__;
        $this->startTime = microtime(true);
    }
    
    private function startBackgroundKiller(): void
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        if (!$isWindows) {
            echo "ℹ️  Background killer not needed on this platform\n";
            return;
        }

        $killerScript = $this->testsDir . '/kill_process_loop.php';
        $pidFile = $this->testsDir . '/pid.txt';
        
        if (!file_exists($killerScript)) {
            echo "⚠️  Background killer script not found: $killerScript\n";
            die();
        }
        
        // Clear any existing PID file
        if (file_exists($pidFile)) {
            file_put_contents($pidFile, '');
        }
        
        // Check if killer is already running
        exec('tasklist /FI "IMAGENAME eq php.exe" /FO CSV | findstr "kill_process_loop.php"', $output);
        if (!empty($output)) {
            echo "✅ Background killer already running\n";
            return;
        }
        
        echo "🚀 Starting background process killer monitor...\n";
        
        // Use proc_open to start background process (works in PowerShell)
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['file', 'nul', 'w'],  // Redirect stdout to null
            2 => ['file', 'nul', 'w']   // Redirect stderr to null
        ];
        
        $command = "php \"$killerScript\"";
        $process = proc_open($command, $descriptorspec, $pipes);
        
        if (is_resource($process)) {
            fclose($pipes[0]);
            echo "   ✅ Background killer started successfully\n";
            
            // Store process handle for cleanup later
            $this->backgroundKillerProcess = $process;
        } else {
            echo "   ⚠️  Failed to start background killer\n";
        }
        
        // Give it a moment to start
        sleep(1);
        
        // Verify it started
        exec('tasklist /FI "IMAGENAME eq php.exe" /FI "WINDOWTITLE eq kill_process_loop.php" /FO CSV', $verifyOutput);
        if (!empty($verifyOutput) && count($verifyOutput) > 1) {
            echo "✅ Background killer started successfully\n";
        } else {
            echo "⚠️  Could not verify background killer startup (continuing anyway)\n";
        }
        
        echo "\n";
    }

    /**
     * Parse command line arguments
     */
    private function parseArguments(array $argv): void
    {
        for ($i = 1; $i < count($argv); $i++) {
            $arg = $argv[$i];
            
            if (strpos($arg, '--env=') === 0) {
                $this->envFlag = $arg;
            } elseif ($arg === '--verbose' || $arg === '-v') {
                $this->verbose = true;
            }
        }
    }
    
    /**
     * Discover test files with pattern: {number}-*.php
     */
    private function discoverTestFiles(): void
    {
        $files = glob($this->testsDir . '/[0-9]*-*.php');
        
        // Sort files numerically by prefix
        usort($files, function($a, $b) {
            $numA = (int) basename($a);
            $numB = (int) basename($b);
            return $numA <=> $numB;
        });
        
        foreach ($files as $file) {
            if (is_file($file) && is_readable($file)) {
                $this->testFiles[] = [
                    'path' => $file,
                    'name' => basename($file),
                    'number' => (int) basename($file)
                ];
            }
        }
    }
    
    /**
     * Get role for test based on test number
     */
    private function getRoleForTest(int $testNumber): string
    {
        switch ($testNumber) {
            case 1: return 'super-admin';
            case 2: return 'admin';
            case 3: return 'editor';
            case 4: return 'viewer';
            default: return 'super-admin';
        }
    }
    
    /**
     * Run a single test file by including it
     */
    private function runTestFile(array $testFile): array
    {
        $testName = $testFile['name'];
        $testPath = $testFile['path'];
        $testNumber = $testFile['number'];
        $role = $this->getRoleForTest($testNumber);
        
        echo "\n🧪 Running: $testName (role: $role)\n";
        echo str_repeat("=", 80) . "\n";
        
        $startTime = microtime(true);
        
        // Set up global variables that test files expect
        global $argc, $argv;
        $originalArgc = $argc ?? 0;
        $originalArgv = $argv ?? [];
        
        // Simulate command line arguments for the test
        $argc = 3;
        $argv = [
            $testPath,
            $role,
            $this->envFlag
        ];
        
        // Don't buffer output - show realtime
        $exitCode = 0;
        $error = null;
        
        // Ensure no output buffering is active for realtime display
        while (ob_get_level()) {
            ob_end_flush();
        }
        
        // Force immediate output flushing
        ob_implicit_flush(true);
        
        try {
            // Include the test file - this will execute it with realtime output
            include $testPath;
        } catch (Exception $e) {
            $error = $e;
            $exitCode = 1;
        } catch (Error $e) {
            $error = $e;
            $exitCode = 1;
        }
        
        // Restore output buffering state
        ob_implicit_flush(false);
        
        // Restore original argc/argv
        $argc = $originalArgc;
        $argv = $originalArgv;
        
        $duration = round(microtime(true) - $startTime, 2);
        
        if ($error) {
            echo "\n❌ ERROR: " . $error->getMessage() . "\n";
            echo "   File: " . $error->getFile() . ":" . $error->getLine() . "\n";
        }
        
        echo "\n📊 Test Result: ";
        if ($exitCode === 0) {
            echo "✅ PASSED";
        } else {
            echo "❌ FAILED";
        }
        echo " (Duration: {$duration}s)\n";
        echo str_repeat("=", 80) . "\n";
        
        return [
            'file' => $testName,
            'duration' => $duration,
            'passed' => $exitCode === 0,
            'error' => $error ? $error->getMessage() : null
        ];
    }
    
    /**
     * Display final summary
     */
    private function displaySummary(array $results): void
    {
        $totalDuration = round(microtime(true) - $this->startTime, 2);
        $totalTests = count($results);
        $passedTests = count(array_filter($results, fn($r) => $r['passed']));
        $failedTests = $totalTests - $passedTests;
        
        echo str_repeat("=", 100) . "\n";
        echo "🏁 FINAL SUMMARY\n";
        echo str_repeat("=", 100) . "\n";
        
        echo "📁 Test Files: $passedTests/$totalTests passed\n";
        echo "⏱️  Total Duration: {$totalDuration} seconds\n";
        echo "💾 Peak Memory: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";
        
        echo "\n📋 Individual Results:\n";
        foreach ($results as $result) {
            $status = $result['passed'] ? '✅ PASS' : '❌ FAIL';
            $error = $result['error'] ? " - " . substr($result['error'], 0, 50) . "..." : '';
            echo "   $status - {$result['file']} - {$result['duration']}s$error\n";
        }
        
        echo "\n";
        if ($failedTests === 0) {
            echo "🎉 ALL TESTS PASSED!\n";
            echo str_repeat("=", 100) . "\n";
        } else {
            echo "💥 $failedTests TEST FILE(S) FAILED!\n";
            echo str_repeat("=", 100) . "\n";
        }
    }
    
    /**
     * Main run method
     */
    public function run(array $argv): int
    {
        $this->parseArguments($argv);
        
        $this->startBackgroundKiller();

        $this->discoverTestFiles();
        
        if (empty($this->testFiles)) {
            echo "❌ No test files found matching pattern: [0-9]*-*.php\n";
            return 1;
        }
        
        echo "PHPUnit-Style Test Suite Runner (Include Mode)\n";
        echo "Discovered and including " . count($this->testFiles) . " test files...\n";
        echo str_repeat("=", 100) . "\n";
        
        if (!empty($this->envFlag)) {
            echo "🌍 Environment: {$this->envFlag}\n";
        }
        
        if ($this->verbose) {
            echo "🔍 Verbose mode enabled\n";
        }
        
        echo "\n🚀 Running " . count($this->testFiles) . " test files in include mode...\n";
        
        $results = [];
        
        // Initialize the testing environment once
        echo "\n🔧 Initializing test environment...\n";
        
        // Set up common test utilities if available
        if (class_exists('TestUtils')) {
            echo "   ✅ TestUtils class available\n";
        }
        
        // Run each test file
        foreach ($this->testFiles as $testFile) {
            $result = $this->runTestFile($testFile);
            $results[] = $result;
            
            // Small delay between tests
            usleep(500000); // 0.5 second
        }
        
        $this->displaySummary($results);
        
        // Return exit code based on results
        $failedCount = count(array_filter($results, fn($r) => !$r['passed']));
        return $failedCount > 0 ? 1 : 0;
    }
}

// Run the test suite
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $runner = new TestSuiteRunner();
    $exitCode = $runner->run($argv);
    exit($exitCode);
}