<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests that actually execute the bin/phpunit-failed-runner script
 * These tests verify the script's end-to-end behavior by running it as a subprocess
 * 
 * @group integration
 */
class ScriptBehaviorTest extends TestCase
{
    private string $projectRoot;
    private string $junitPath;
    private string $scriptPath;
    private string $fixtureFilePath;
    private ?string $originalFixtureContent = null;
    
    protected function setUp(): void
    {
        $this->projectRoot = dirname(__DIR__, 2);
        $this->junitPath = $this->projectRoot . '/junit.xml';
        $this->scriptPath = $this->projectRoot . '/bin/phpunit-failed-runner';
        $this->fixtureFilePath = $this->projectRoot . '/tests/Fixtures/InitiallyFailingFixture.php';
        
        // Clean up any existing junit.xml before each test
        if (file_exists($this->junitPath)) {
            unlink($this->junitPath);
        }
        
        // Backup the fixture file
        $this->originalFixtureContent = file_get_contents($this->fixtureFilePath);
    }
    
    protected function tearDown(): void
    {
        // Restore the fixture file
        if ($this->originalFixtureContent !== null) {
            file_put_contents($this->fixtureFilePath, $this->originalFixtureContent);
        }
        
        // Clean up junit.xml after each test
        if (file_exists($this->junitPath)) {
            unlink($this->junitPath);
        }
    }
    
    /**
     * Execute the script and capture output
     * Uses phpunit-integration-fixtures.xml to only run fixture tests
     */
    private function runScript(?string &$output = null): int
    {
        $output = [];
        $exitCode = 0;
        
        // Use the fixtures config to only run fixture tests (not the full suite)
        exec("cd {$this->projectRoot} && {$this->scriptPath} -c phpunit-integration-fixtures.xml 2>&1", $output, $exitCode);
        $output = implode("\n", $output);
        
        return $exitCode;
    }
    
    /**
     * Make the fixture fail by changing return true to return false
     */
    private function makeFixtureFail(): void
    {
        $content = file_get_contents($this->fixtureFilePath);
        $content = str_replace('return true;', 'return false;', $content);
        file_put_contents($this->fixtureFilePath, $content);
    }
    
    /**
     * Make the fixture pass by changing return false to return true
     */
    private function makeFixturePass(): void
    {
        $content = file_get_contents($this->fixtureFilePath);
        $content = str_replace('return false;', 'return true;', $content);
        file_put_contents($this->fixtureFilePath, $content);
    }
    
    public function test_script_runs_full_suite_when_no_logfile_exists(): void
    {
        // Arrange: No junit.xml exists, make fixture fail so junit.xml persists
        $this->assertFileDoesNotExist($this->junitPath);
        $this->makeFixtureFail();
        
        // Act: Run the script
        $exitCode = $this->runScript($output);
        
        // Assert: Should run full suite
        $this->assertEquals(0, $exitCode, 'Script should exit successfully');
        $this->assertStringContainsString('Logfile not found', $output);
        $this->assertStringContainsString('Running the test suite', $output);
        
        // junit.xml should be created and persist (because test failed)
        $this->assertFileExists($this->junitPath);
    }
    
    public function test_script_removes_logfile_when_all_tests_pass(): void
    {
        // Arrange: Make fixture fail first, run script to create junit.xml
        $this->makeFixtureFail();
        $this->runScript($firstOutput);
        $this->assertFileExists($this->junitPath, 'junit.xml should exist after failed run');
        
        // Now fix the fixture
        $this->makeFixturePass();
        
        // Act: Run script again (it should find the logfile with no failures)
        $exitCode = $this->runScript($output);
        
        // Assert: Should clean up the logfile
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Logfile found', $output);
        $this->assertStringContainsString('No failed tests', $output);
        $this->assertStringContainsString('Great job', $output);
        
        // junit.xml should be removed
        $this->assertFileDoesNotExist($this->junitPath);
    }
    
    public function test_script_filters_and_reruns_only_failing_tests(): void
    {
        // Arrange: Make fixture fail, run script to create junit.xml with failures
        $this->makeFixtureFail();
        $this->runScript($firstOutput);
        
        $this->assertFileExists($this->junitPath);
        $this->assertStringContainsString('Logfile not found', $firstOutput);
        
        // Act: Run script again (should detect failures and rerun only those tests)
        $exitCode = $this->runScript($secondOutput);
        
        // Assert: Should find and filter failed tests
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Logfile found', $secondOutput);
        $this->assertStringContainsString('Searching for previously failing tests', $secondOutput);
        $this->assertStringContainsString('previously failing tests, filtering', $secondOutput);
        
        // junit.xml should still exist (tests still failing)
        $this->assertFileExists($this->junitPath);
    }
    
    public function test_full_workflow_fail_then_fix_then_cleanup(): void
    {
        // Phase 1: Run with failing test
        $this->makeFixtureFail();
        $exitCode1 = $this->runScript($output1);
        
        $this->assertEquals(0, $exitCode1, 'Script should exit successfully even with failures');
        $this->assertStringContainsString('Logfile not found', $output1);
        $this->assertFileExists($this->junitPath);
        
        // Phase 2: Run again (should detect and rerun failures)
        $exitCode2 = $this->runScript($output2);
        
        $this->assertEquals(0, $exitCode2);
        $this->assertStringContainsString('Logfile found', $output2);
        $this->assertStringContainsString('previously failing tests', $output2);
        $this->assertFileExists($this->junitPath);
        
        // Phase 3: Fix the test and run again
        $this->makeFixturePass();
        $exitCode3 = $this->runScript($output3);
        
        // Assert: After fixing, should clean up
        $this->assertEquals(0, $exitCode3);
        $this->assertStringContainsString('Logfile found', $output3);
        $this->assertStringContainsString('No failed tests', $output3);
        $this->assertFileDoesNotExist($this->junitPath, 'junit.xml should be cleaned up after all tests pass');
    }
    
    public function test_script_counts_failed_tests_correctly(): void
    {
        // Arrange: Make fixture fail
        $this->makeFixtureFail();
        $this->runScript();
        
        // Act: Run again and check the count in output
        $this->runScript($output);
        
        // Assert: Output should mention the count of failed tests
        $this->assertStringContainsString('Logfile found', $output);
        
        // The script should report finding failed tests
        $this->assertMatchesRegularExpression(
            '/Found \d+ previously failing tests/',
            $output,
            'Should report count of failed tests'
        );
    }
    
    public function test_script_exits_with_zero_on_success(): void
    {
        // Arrange: All tests passing
        $this->makeFixturePass();
        
        // Act: Run script
        $exitCode = $this->runScript();
        
        // Assert: Should exit with 0
        $this->assertEquals(0, $exitCode, 'Script should always exit with code 0');
    }
    
    public function test_script_creates_valid_junit_xml(): void
    {
        // Arrange: Make fixture fail so junit.xml persists
        $this->makeFixtureFail();
        
        // Act: Run script
        $this->runScript();
        
        // Assert: junit.xml should be valid XML
        $this->assertFileExists($this->junitPath);
        
        $xml = @simplexml_load_file($this->junitPath);
        $this->assertNotFalse($xml, 'junit.xml should be valid XML');
        $this->assertEquals('testsuites', $xml->getName());
    }
    
    public function test_script_handles_no_failures_on_first_run(): void
    {
        // Arrange: All tests passing
        $this->makeFixturePass();
        
        // Act: Run script (first run, no logfile)
        $exitCode = $this->runScript($output);
        
        // Assert: Should run full suite and clean up immediately if all pass
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Logfile not found', $output);
        
        // The logfile might be cleaned up immediately after first run if all tests pass
        // Check if we see success message
        if (!file_exists($this->junitPath)) {
            $this->assertStringContainsString('No failed tests', $output);
        }
    }
    
    public function test_script_output_contains_expected_messages(): void
    {
        // Test that the script outputs the expected emoji/messages
        $this->makeFixtureFail();
        $this->runScript($firstOutput);
        
        // First run: should see "Logfile not found"
        $this->assertStringContainsString('Logfile not found', $firstOutput);
        $this->assertStringContainsString('Running the test suite', $firstOutput);
        
        // Second run: should see "Logfile found"
        $this->runScript($secondOutput);
        $this->assertStringContainsString('Logfile found', $secondOutput);
        $this->assertStringContainsString('Searching for previously failing tests', $secondOutput);
    }
    
    public function test_script_persists_junit_xml_when_tests_fail(): void
    {
        // Arrange: Make fixture fail
        $this->makeFixtureFail();
        
        // Act: Run script
        $this->runScript();
        
        // Assert: junit.xml should persist (not be deleted)
        $this->assertFileExists($this->junitPath, 'junit.xml should not be deleted when tests fail');
    }
    
    public function test_script_uses_xmlstarlet_for_transformations(): void
    {
        // This test verifies xmlstarlet is available (required dependency)
        // Act: Run script
        $exitCode = $this->runScript($output);
        
        // Assert: Should not contain errors about xmlstarlet
        $this->assertStringNotContainsString('xmlstarlet: command not found', $output);
        $this->assertStringNotContainsString('xmlstarlet: not found', $output);
    }
    
    public function test_script_detects_xsl_files_in_project_root(): void
    {
        // Act: Run script
        $exitCode = $this->runScript($output);
        
        // Assert: Should not contain XSL path detection errors
        $this->assertStringNotContainsString('Cannot find XSL transformation files', $output);
        $this->assertEquals(0, $exitCode);
    }
}
