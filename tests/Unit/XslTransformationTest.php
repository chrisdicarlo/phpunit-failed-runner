<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for XSL transformations
 * These tests verify the XSL files work correctly with various junit.xml formats
 */
class XslTransformationTest extends TestCase
{
    private string $projectRoot;
    
    protected function setUp(): void
    {
        $this->projectRoot = dirname(__DIR__, 1);
    }
    
    public function test_prune_xsl_extracts_failed_tests_from_junit_xml(): void
    {
        $junitXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
  <testsuite name="Test Suite" tests="3" assertions="3" errors="0" failures="2" skipped="0" time="0.001">
    <testcase name="test_passing" class="Tests\PassingTest" assertions="1" time="0.000">
    </testcase>
    <testcase name="test_failing_one" class="Tests\FailingTest" assertions="1" time="0.000">
      <failure type="PHPUnit\Framework\ExpectationFailedException">Failed</failure>
    </testcase>
    <testcase name="test_failing_two" class="Tests\AnotherTest" assertions="1" time="0.000">
      <failure type="PHPUnit\Framework\ExpectationFailedException">Failed</failure>
    </testcase>
  </testsuite>
</testsuites>
XML;

        $tempFile = tempnam(sys_get_temp_dir(), 'junit');
        file_put_contents($tempFile, $junitXml);
        
        $output = shell_exec("xmlstarlet tr {$this->projectRoot}/../prune.xsl {$tempFile}");
        
        $this->assertStringContainsString('<tests>', $output);
        $this->assertStringContainsString('<test>Tests\FailingTest::test_failing_one</test>', $output);
        $this->assertStringContainsString('<test>Tests\AnotherTest::test_failing_two</test>', $output);
        $this->assertStringNotContainsString('PassingTest', $output);
        
        unlink($tempFile);
    }
    
    public function test_prune_xsl_handles_errors_as_failures(): void
    {
        $junitXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
  <testsuite name="Test Suite" tests="2" assertions="2" errors="1" failures="1" skipped="0" time="0.001">
    <testcase name="test_with_error" class="Tests\ErrorTest" assertions="1" time="0.000">
      <error type="Exception">Error occurred</error>
    </testcase>
    <testcase name="test_with_failure" class="Tests\FailureTest" assertions="1" time="0.000">
      <failure type="PHPUnit\Framework\ExpectationFailedException">Failed</failure>
    </testcase>
  </testsuite>
</testsuites>
XML;

        $tempFile = tempnam(sys_get_temp_dir(), 'junit');
        file_put_contents($tempFile, $junitXml);
        
        $output = shell_exec("xmlstarlet tr {$this->projectRoot}/../prune.xsl {$tempFile}");
        
        $this->assertStringContainsString('ErrorTest::test_with_error', $output);
        $this->assertStringContainsString('FailureTest::test_with_failure', $output);
        
        unlink($tempFile);
    }
    
    public function test_count_failed_xsl_counts_tests_correctly(): void
    {
        $prunedXml = <<<XML
<?xml version="1.0"?>
<tests>
  <test>Tests\FailingTest::test_one</test>
  <test>Tests\FailingTest::test_two</test>
  <test>Tests\AnotherTest::test_three</test>
</tests>
XML;

        $tempFile = tempnam(sys_get_temp_dir(), 'pruned');
        file_put_contents($tempFile, $prunedXml);
        
        $count = trim(shell_exec("xmlstarlet tr --omit-decl {$this->projectRoot}/../count-failed.xsl {$tempFile}"));
        
        $this->assertEquals('3', $count);
        
        unlink($tempFile);
    }
    
    public function test_count_failed_xsl_returns_zero_for_no_failures(): void
    {
        $prunedXml = <<<XML
<?xml version="1.0"?>
<tests>
</tests>
XML;

        $tempFile = tempnam(sys_get_temp_dir(), 'pruned');
        file_put_contents($tempFile, $prunedXml);
        
        $count = trim(shell_exec("xmlstarlet tr --omit-decl {$this->projectRoot}/../count-failed.xsl {$tempFile}"));
        
        $this->assertEquals('0', $count);
        
        unlink($tempFile);
    }
    
    public function test_failed_tests_xsl_creates_phpunit_filter(): void
    {
        $prunedXml = <<<XML
<?xml version="1.0"?>
<tests>
  <test>Tests\FailingTest::test_one</test>
  <test>Tests\FailingTest::test_two</test>
  <test>Tests\AnotherTest::test_three</test>
</tests>
XML;

        $tempFile = tempnam(sys_get_temp_dir(), 'pruned');
        file_put_contents($tempFile, $prunedXml);
        
        $filter = trim(shell_exec("xmlstarlet tr --omit-decl {$this->projectRoot}/../failed-tests.xsl {$tempFile}"));
        
        $this->assertStringContainsString('Tests\\\\FailingTest::test_one', $filter);
        $this->assertStringContainsString('Tests\\\\FailingTest::test_two', $filter);
        $this->assertStringContainsString('Tests\\\\AnotherTest::test_three', $filter);
        $this->assertStringContainsString('|', $filter); // Tests are separated by |
        
        unlink($tempFile);
    }
    
    public function test_failed_tests_xsl_escapes_backslashes(): void
    {
        $prunedXml = <<<XML
<?xml version="1.0"?>
<tests>
  <test>Tests\Namespace\ClassName::testMethod</test>
</tests>
XML;

        $tempFile = tempnam(sys_get_temp_dir(), 'pruned');
        file_put_contents($tempFile, $prunedXml);
        
        $filter = trim(shell_exec("xmlstarlet tr --omit-decl {$this->projectRoot}/../failed-tests.xsl {$tempFile}"));
        
        // Backslashes should be escaped for PHPUnit filter
        $this->assertStringContainsString('Tests\\\\Namespace\\\\ClassName::testMethod', $filter);
        
        unlink($tempFile);
    }
    
    public function test_full_transformation_pipeline(): void
    {
        $junitXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
  <testsuite name="Test Suite" tests="5" assertions="5" errors="0" failures="2" skipped="0" time="0.001">
    <testcase name="test_pass_1" class="Tests\PassingTest" assertions="1" time="0.000"></testcase>
    <testcase name="test_pass_2" class="Tests\PassingTest" assertions="1" time="0.000"></testcase>
    <testcase name="test_fail_1" class="Tests\FailingTest" assertions="1" time="0.000">
      <failure type="PHPUnit\Framework\ExpectationFailedException">Failed</failure>
    </testcase>
    <testcase name="test_pass_3" class="Tests\PassingTest" assertions="1" time="0.000"></testcase>
    <testcase name="test_fail_2" class="Tests\FailingTest" assertions="1" time="0.000">
      <failure type="PHPUnit\Framework\ExpectationFailedException">Failed</failure>
    </testcase>
  </testsuite>
</testsuites>
XML;

        $tempFile = tempnam(sys_get_temp_dir(), 'junit');
        file_put_contents($tempFile, $junitXml);
        
        // Run full pipeline: prune -> count
        $count = trim(shell_exec("xmlstarlet tr {$this->projectRoot}/../prune.xsl {$tempFile} | xmlstarlet tr --omit-decl {$this->projectRoot}/../count-failed.xsl"));
        $this->assertEquals('2', $count);
        
        // Run full pipeline: prune -> failed-tests
        $filter = trim(shell_exec("xmlstarlet tr {$this->projectRoot}/../prune.xsl {$tempFile} | xmlstarlet tr --omit-decl {$this->projectRoot}/../failed-tests.xsl"));
        $this->assertStringContainsString('Tests\\\\FailingTest::test_fail_1', $filter);
        $this->assertStringContainsString('Tests\\\\FailingTest::test_fail_2', $filter);
        $this->assertStringContainsString('|', $filter);
        
        unlink($tempFile);
    }
}
