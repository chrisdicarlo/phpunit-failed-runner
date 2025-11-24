#!/bin/bash

# Multi-phase test runner demonstration script
# This script simulates the full workflow of:
# 1. Running tests with failures
# 2. Fixing the failing tests
# 3. Re-running only the failed tests
# 4. Verifying all tests pass

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

cd "$PROJECT_ROOT"

echo "======================================"
echo "Multi-Phase Test Runner Demonstration"
echo "======================================"
echo ""

# Ensure junit.xml is removed before starting
if [ -f "junit.xml" ]; then
    echo "Cleaning up previous junit.xml..."
    rm junit.xml
fi

# Phase 1: Run tests with failures
echo "======================================"
echo "PHASE 1: Initial test run (expect failures)"
echo "======================================"
echo ""

# Ensure fixtures are set to fail
sed -i 's/return true;/return false;/g' tests/Fixtures/InitiallyFailingFixture.php
sed -i 's/return true;/return false;/g' tests/Fixtures/AnotherFailingFixture.php

echo "Running: ./bin/phpunit-failed-runner"
./bin/phpunit-failed-runner || true

echo ""
echo "Press Enter to continue to Phase 2..."
read -r

# Phase 2: Fix the first fixture
echo ""
echo "======================================"
echo "PHASE 2: Fix InitiallyFailingFixture"
echo "======================================"
echo ""

echo "Fixing InitiallyFailingFixture (changing return false to return true)..."
sed -i 's/return false;/return true;/g' tests/Fixtures/InitiallyFailingFixture.php

echo "Running: ./bin/phpunit-failed-runner (should only run previously failed tests)"
./bin/phpunit-failed-runner || true

echo ""
echo "Press Enter to continue to Phase 3..."
read -r

# Phase 3: Fix the second fixture
echo ""
echo "======================================"
echo "PHASE 3: Fix AnotherFailingFixture"
echo "======================================"
echo ""

echo "Fixing AnotherFailingFixture (changing return false to return true)..."
sed -i 's/return false;/return true;/g' tests/Fixtures/AnotherFailingFixture.php

echo "Running: ./bin/phpunit-failed-runner (should only run remaining failed test)"
./bin/phpunit-failed-runner || true

echo ""
echo "======================================"
echo "PHASE 4: Verify all tests pass"
echo "======================================"
echo ""

echo "Running full test suite to verify everything passes..."
./vendor/bin/phpunit

echo ""
echo "======================================"
echo "Demo Complete!"
echo "======================================"
echo ""
echo "To reset fixtures for another demo run:"
echo "  sed -i 's/return true;/return false;/g' tests/Fixtures/InitiallyFailingFixture.php"
echo "  sed -i 's/return true;/return false;/g' tests/Fixtures/AnotherFailingFixture.php"
echo ""
