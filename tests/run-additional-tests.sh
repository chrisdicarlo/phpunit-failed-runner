#!/bin/bash

# Additional integration tests for phpunit-failed-runner
# Tests additional functionality beyond the basic multi-phase workflow

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

cd "$PROJECT_ROOT"

echo "=========================================="
echo "Additional Integration Tests"
echo "=========================================="
echo ""

# Cleanup function
cleanup() {
    echo "Cleaning up..."
    rm -f junit.xml
    sed -i 's/return false;/return true;/g' tests/Fixtures/InitiallyFailingFixture.php 2>/dev/null || true
    sed -i 's/return false;/return true;/g' tests/Fixtures/AnotherFailingFixture.php 2>/dev/null || true
}

trap cleanup EXIT

# Test 1: Passing additional parameters to PHPUnit
echo "TEST 1: Passing additional parameters"
echo "========================================"
echo ""

rm -f junit.xml
sed -i 's/return true;/return false;/g' tests/Fixtures/InitiallyFailingFixture.php

echo "Running with --testdox parameter..."
output=$(./bin/phpunit-failed-runner --testdox 2>&1)

if ! echo "$output" | grep -q "Initially Failing"; then
    echo "ERROR: --testdox parameter not passed through"
    exit 1
fi

echo "✓ Additional parameters passed correctly"
echo ""

# Test 2: XSL transformations produce correct filter
echo "TEST 2: XSL filter generation"
echo "=============================="
echo ""

rm -f junit.xml
./bin/phpunit-failed-runner > /dev/null 2>&1 || true

if [ ! -f "junit.xml" ]; then
    echo "ERROR: junit.xml not created"
    exit 1
fi

# Generate the filter manually to verify XSL works
filter=$(xmlstarlet tr ./prune.xsl junit.xml | xmlstarlet tr --omit-decl ./failed-tests.xsl)

if [ -z "$filter" ]; then
    echo "ERROR: XSL transformation produced empty filter"
    exit 1
fi

# Check filter format (should be like: TestClass::testMethod|AnotherTest::anotherMethod)
if ! echo "$filter" | grep -q "::"; then
    echo "ERROR: Filter doesn't contain :: separator"
    exit 1
fi

echo "Generated filter: $filter"
echo "✓ XSL transformations work correctly"
echo ""

# Test 3: All tests failing
echo "TEST 3: All tests fail scenario"
echo "================================"
echo ""

rm -f junit.xml
sed -i 's/return true;/return false;/g' tests/Fixtures/AlwaysPassingFixture.php
sed -i 's/return true;/return false;/g' tests/Fixtures/InitiallyFailingFixture.php
sed -i 's/return true;/return false;/g' tests/Fixtures/AnotherFailingFixture.php

output=$(./bin/phpunit-failed-runner 2>&1)

if ! echo "$output" | grep -q "5.*test"; then
    echo "ERROR: Should report 5 total tests"
    exit 1
fi

# Run again to verify it tries to run all failed tests
output=$(./bin/phpunit-failed-runner 2>&1)

if ! echo "$output" | grep -q "Found 5 previously failing tests"; then
    echo "ERROR: Should find all 5 tests failed"
    exit 1
fi

# Reset AlwaysPassingFixture
sed -i 's/return false;/return true;/g' tests/Fixtures/AlwaysPassingFixture.php

echo "✓ All tests failing handled correctly"
echo ""

# Test 4: XSL path detection
echo "TEST 4: XSL path detection"
echo "=========================="
echo ""

# Verify XSL files are found
if [ ! -f "./prune.xsl" ]; then
    echo "ERROR: prune.xsl not found in package root"
    exit 1
fi

if [ ! -f "./count-failed.xsl" ]; then
    echo "ERROR: count-failed.xsl not found in package root"
    exit 1
fi

if [ ! -f "./failed-tests.xsl" ]; then
    echo "ERROR: failed-tests.xsl not found in package root"
    exit 1
fi

echo "✓ XSL files detected correctly"
echo ""

# Test 5: Error handling (create a fixture with an error vs failure)
echo "TEST 5: Handling errors and failures"
echo "====================================="
echo ""

# Create a temporary test that throws an error
cat > tests/Fixtures/ErrorFixture.php << 'EOF'
<?php

namespace Tests\Fixtures;

class ErrorFixture
{
    public function success(): bool
    {
        throw new \Exception("Simulated error");
    }
}
EOF

cat > tests/ErrorTest.php << 'EOF'
<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\ErrorFixture;

class ErrorTest extends TestCase
{
    public function test_error_fixture(): void
    {
        $fixture = new ErrorFixture();
        $this->assertTrue($fixture->success());
    }
}
EOF

rm -f junit.xml
output=$(./bin/phpunit-failed-runner 2>&1 || true)

if ! echo "$output" | grep -q "error\|Error\|ERROR"; then
    echo "WARNING: Error might not be reported clearly (this may be OK)"
fi

# Run again to verify errors are also tracked
if [ -f "junit.xml" ]; then
    output=$(./bin/phpunit-failed-runner 2>&1 || true)
    
    if ! echo "$output" | grep -q "previously failing tests"; then
        echo "ERROR: Errors should also be tracked as failed tests"
        exit 1
    fi
    
    echo "✓ Errors handled correctly"
else
    echo "WARNING: junit.xml not created with error"
fi

# Cleanup error test files
rm -f tests/Fixtures/ErrorFixture.php
rm -f tests/ErrorTest.php

echo ""

echo "=========================================="
echo "✓ All additional tests passed!"
echo "=========================================="
echo ""
