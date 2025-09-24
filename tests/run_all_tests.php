<?php

/**
 * Run All Standalone Tests
 * 
 * Automatically discovers and runs all test files with pattern: {number}-*.php
 * in the tests/ directory.
 * 
 * Usage:
 *   php tests/run_all_tests.php
 *   php tests/run_all_tests.php --env=testing
 *   php tests/run_all_tests.php --verbose
 */

class TestRunner
{
    private array $testFiles = [];
    private string $testsDir;
    private array $results = [];
    private bool $verbose = false;
    private string $envFlag = '';
    private float $startTime;
    
    public function __construct()
    {
        $this->testsDir = __DIR__;
        $this->startTime = microtime(true);
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
        
        if ($this->verbose) {
            echo "🔍 Discovered " . count($this->testFiles) . " test files:\n";
            foreach ($this->testFiles as $test) {
                echo "   - {$test['name']}\n";
            }
            echo "\n";
        }
    }
    
    /**
     * Run a single test file
     */
    private function runTestFile(array $testFile): array
    {
        $testName = $testFile['name'];
        $testPath = $testFile['path'];
        
        echo "🧪 Running: $testName\n";
        echo str_repeat("=", 80) . "\n";
        
        $startTime = microtime(true);
        
        // Build command with arguments
        $command = "php \"$testPath\"";
        if (!empty($this->envFlag)) {
            $command .= " {$this->envFlag}";
        }
        
        // Add role argument for permission tests
        if (strpos($testName, '01-test-permission') !== false) {
            $command .= " super-admin";
        }
        
        if ($this->verbose) {
            echo "📋 Command: $command\n";
        }
        
        // Execute test
        $output = [];
        $exitCode = 0;
        exec($command . ' 2>&1', $output, $exitCode);
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        // Parse results from output
        $testResult = $this->parseTestOutput($output, $exitCode);
        $testResult['file'] = $testName;
        $testResult['duration'] = $duration;
        $testResult['exit_code'] = $exitCode;
        
        // Display output
        if ($this->verbose || $exitCode !== 0) {
            foreach ($output as $line) {
                echo $line . "\n";
            }
        } else {
            // Show only summary lines for successful tests
            $summaryLines = array_filter($output, function($line) {
                return strpos($line, 'PHPUnit-Style') !== false ||
                       strpos($line, 'Time:') !== false ||
                       strpos($line, 'OK (') !== false ||
                       strpos($line, 'FAILURES!') !== false ||
                       strpos($line, 'Tests:') !== false;
            });
            foreach ($summaryLines as $line) {
                echo $line . "\n";
            }
        }
        
        echo "\n📊 Test Result: ";
        if ($exitCode === 0) {
            echo "✅ PASSED";
        } else {
            echo "❌ FAILED";
        }
        echo " (Duration: {$duration}s)\n";
        echo str_repeat("=", 80) . "\n\n";
        
        return $testResult;
    }
    
    /**
     * Parse test output to extract statistics
     */
    private function parseTestOutput(array $output, int $exitCode): array
    {
        $result = [
            'passed' => $exitCode === 0,
            'tests' => 0,
            'assertions' => 0,
            'failures' => 0,
            'errors' => 0
        ];
        
        // Look for PHPUnit-style summary
        foreach ($output as $line) {
            // Match: "OK (2 tests)"
            if (preg_match('/OK \((\d+) tests?\)/', $line, $matches)) {
                $result['tests'] = (int) $matches[1];
                break;
            }
            // Match: "Tests: 4, Assertions: 4, Failures: 2"
            if (preg_match('/Tests: (\d+), Assertions: (\d+), Failures: (\d+)/', $line, $matches)) {
                $result['tests'] = (int) $matches[1];
                $result['assertions'] = (int) $matches[2];
                $result['failures'] = (int) $matches[3];
                break;
            }
            // Match: "FAILURES!" or "Tests: X, ..."
            if (strpos($line, 'FAILURES!') !== false) {
                $result['failures'] = $result['failures'] > 0 ? $result['failures'] : 1;
            }
        }
        
        return $result;
    }
    
    /**
     * Display final summary
     */
    private function displaySummary(): void
    {
        $totalDuration = round(microtime(true) - $this->startTime, 2);
        $totalTests = count($this->results);
        $passedTests = count(array_filter($this->results, fn($r) => $r['passed']));
        $failedTests = $totalTests - $passedTests;
        
        $totalTestCases = array_sum(array_column($this->results, 'tests'));
        $totalAssertions = array_sum(array_column($this->results, 'assertions'));
        $totalFailures = array_sum(array_filter(array_column($this->results, 'failures')));
        
        echo str_repeat("=", 100) . "\n";
        echo "🏁 FINAL SUMMARY\n";
        echo str_repeat("=", 100) . "\n";
        
        echo "📁 Test Files: $passedTests/$totalTests passed\n";
        echo "🧪 Total Test Cases: $totalTestCases\n";
        if ($totalAssertions > 0) {
            echo "✅ Total Assertions: $totalAssertions\n";
        }
        if ($totalFailures > 0) {
            echo "❌ Total Failures: $totalFailures\n";
        }
        echo "⏱️  Total Duration: {$totalDuration} seconds\n";
        echo "💾 Peak Memory: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";
        
        echo "\n📋 Individual Results:\n";
        foreach ($this->results as $result) {
            $status = $result['passed'] ? '✅ PASS' : '❌ FAIL';
            $tests = $result['tests'] > 0 ? " ({$result['tests']} tests)" : '';
            echo "   $status - {$result['file']} - {$result['duration']}s$tests\n";
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
     * Parse command line arguments
     */
    private function parseArguments(array $argv): void
    {
        foreach ($argv as $arg) {
            if (strpos($arg, '--env=') === 0) {
                $this->envFlag = '--' . substr($arg, 2);
            } elseif ($arg === '--verbose' || $arg === '-v') {
                $this->verbose = true;
            }
        }
    }
    
    /**
     * Main run method
     */
    public function run(array $argv): int
    {
        echo "PHPUnit-Style Test Suite Runner\n";
        echo "Discovering and running all standalone tests...\n";
        echo str_repeat("=", 100) . "\n\n";
        
        $this->parseArguments($argv);
        $this->discoverTestFiles();
        
        if (empty($this->testFiles)) {
            echo "❌ No test files found matching pattern: {number}-*.php\n";
            return 1;
        }
        
        echo "🚀 Running " . count($this->testFiles) . " test files" . 
             ($this->envFlag ? " with environment: {$this->envFlag}" : "") . "\n\n";
        
        foreach ($this->testFiles as $testFile) {
            $result = $this->runTestFile($testFile);
            $this->results[] = $result;
        }
        
        $this->displaySummary();
        
        // Return appropriate exit code
        $failedCount = count(array_filter($this->results, fn($r) => !$r['passed']));
        return $failedCount === 0 ? 0 : 1;
    }
}

// Run the test suite
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $runner = new TestRunner();
    $exitCode = $runner->run($argv);
    exit($exitCode);
}