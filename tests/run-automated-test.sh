#!/bin/bash

# Automated multi-phase test runner (non-interactive)
# This script tests the phpunit-failed-runner script by verifying its actual output and behavior

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

cd "$PROJECT_ROOT"

echo "======================================"
echo "Automated Multi-Phase Test Runner"
echo "======================================"
echo ""

# Cleanup function to reset fixtures
cleanup() {
    echo "Resetting fixtures to passing state..."
    sed -i 's/return false;/return true;/g' tests/Fixtures/InitiallyFailingFixture.php 2>/dev/null || true
    sed -i 's/return false;/return true;/g' tests/Fixtures/AnotherFailingFixture.php 2>/dev/null || true
    rm -f junit.xml
}

# Ensure cleanup on exit
trap cleanup EXIT

# Ensure junit.xml is removed before starting
rm -f junit.xml

# Phase 1: Run tests with failures - should detect no logfile and run full suite
echo "PHASE 1: Initial test run (expect failures)"
echo "============================================"
echo ""

# Set fixtures to fail
sed -i 's/return true;/return false;/g' tests/Fixtures/InitiallyFailingFixture.php
sed -i 's/return true;/return false;/g' tests/Fixtures/AnotherFailingFixture.php

echo "Running: ./bin/phpunit-failed-runner"
output=$(./bin/phpunit-failed-runner 2>&1)
echo "$output"

# Verify script detected no logfile
if ! echo "$output" | grep -q "Logfile not found"; then
    echo "ERROR: Script should have detected no logfile"
    exit 1
fi

# Verify junit.xml was created
if [ ! -f "junit.xml" ]; then
    echo "ERROR: junit.xml should have been created"
    exit 1
fi

# Verify we have failures (PHPUnit should report failures)
if ! echo "$output" | grep -q "FAILURES!"; then
    echo "ERROR: Expected test failures in Phase 1"
    exit 1
fi

echo ""
echo "✓ Phase 1 complete: Script detected no logfile, ran full suite, tests failed as expected"
echo ""

# Phase 2: Fix the first fixture and re-run - script should detect logfile and filter
echo "PHASE 2: Fix InitiallyFailingFixture and re-run"
echo "================================================"
echo ""

echo "Fixing InitiallyFailingFixture..."
sed -i 's/return false;/return true;/g' tests/Fixtures/InitiallyFailingFixture.php

echo "Running: ./bin/phpunit-failed-runner"
output=$(./bin/phpunit-failed-runner 2>&1)
echo "$output"

# Verify script found the logfile
if ! echo "$output" | grep -q "Logfile found"; then
    echo "ERROR: Script should have detected existing logfile"
    exit 1
fi

# Verify script found previously failing tests
if ! echo "$output" | grep -q "Found .* previously failing tests"; then
    echo "ERROR: Script should have found previously failing tests"
    exit 1
fi

# Verify we still have 1 failure (AnotherFailingTest)
if ! echo "$output" | grep -q "FAILURES!"; then
    echo "ERROR: Expected AnotherFailingFixture to still fail in Phase 2"
    exit 1
fi

# Verify junit.xml still exists (because there are still failures)
if [ ! -f "junit.xml" ]; then
    echo "ERROR: junit.xml should still exist because tests failed"
    exit 1
fi

echo ""
echo "✓ Phase 2 complete: Script detected logfile, filtered to failed tests, 1 test still failing"
echo ""

# Phase 3: Fix the second fixture and re-run - script should show success and cleanup
echo "PHASE 3: Fix AnotherFailingFixture and re-run"
echo "=============================================="
echo ""

echo "Fixing AnotherFailingFixture..."
sed -i 's/return false;/return true;/g' tests/Fixtures/AnotherFailingFixture.php

echo "Running: ./bin/phpunit-failed-runner"
output=$(./bin/phpunit-failed-runner 2>&1)
echo "$output"

# Verify script found the logfile
if ! echo "$output" | grep -q "Logfile found"; then
    echo "ERROR: Script should have detected existing logfile"
    exit 1
fi

# Verify script shows success message
if ! echo "$output" | grep -q "No failed tests! Great job!"; then
    echo "ERROR: Script should have shown success message"
    exit 1
fi

# Verify junit.xml was deleted by the script
if [ -f "junit.xml" ]; then
    echo "ERROR: Script should have deleted junit.xml after all tests pass"
    exit 1
fi

echo ""
echo "✓ Phase 3 complete: Script detected all tests pass, cleaned up junit.xml"
echo ""

# Phase 4: Run script again - should detect no logfile and run full suite (all passing)
echo "PHASE 4: Verify clean state - no logfile exists"
echo "================================================"
echo ""

echo "Running: ./bin/phpunit-failed-runner"
output=$(./bin/phpunit-failed-runner 2>&1)
echo "$output"

# Verify script detected no logfile
if ! echo "$output" | grep -q "Logfile not found"; then
    echo "ERROR: Script should have detected no logfile in clean state"
    exit 1
fi

# Verify all tests pass
if ! echo "$output" | grep -q "OK"; then
    echo "ERROR: All tests should pass"
    exit 1
fi

# Verify script cleaned up junit.xml again
if [ -f "junit.xml" ]; then
    echo "ERROR: Script should have deleted junit.xml after successful run"
    exit 1
fi

echo ""
echo "✓ Phase 4 complete: Script handled clean state correctly, all tests pass"
echo ""
echo "======================================"
echo "✓ All phases completed successfully!"
echo "======================================"
echo ""
