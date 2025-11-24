# Tests

This directory contains the test suite for the phpunit-failed-runner package.

## Test Structure

The test suite uses fixture classes that simulate test failures and successes through a simple `success()` method:

### Fixture Classes (`tests/Fixtures/`)

- **AlwaysPassingFixture.php** - Always returns `true` (passing tests)
- **InitiallyFailingFixture.php** - Returns `false` initially (can be "fixed" to return `true`)
- **AnotherFailingFixture.php** - Returns `false` initially (can be "fixed" to return `true`)

### Test Cases

- **AlwaysPassingTest.php** - Tests that use the always-passing fixture
- **InitiallyFailingTest.php** - Tests that use InitiallyFailingFixture
- **AnotherFailingTest.php** - Tests that use AnotherFailingFixture

## Running Tests

### Run all tests normally

```bash
./vendor/bin/phpunit
```

or

```bash
composer test
```

### Multi-Phase Test Demonstration

The package includes scripts that demonstrate the incremental test-fixing workflow:

#### Interactive Demo

```bash
./tests/demo-multi-phase.sh
```

This script runs through the multi-phase workflow interactively, pausing between phases to show:
1. Initial test run with failures
2. Fixing the first set of failing tests
3. Fixing the remaining failing tests
4. Final verification that all tests pass

#### Automated Test

```bash
./tests/run-automated-test.sh
```

or

```bash
composer test:auto
```

This script automatically runs through all phases and validates:
- Phase 1: Initial run creates 3 failures
- Phase 2: After fixing InitiallyFailingFixture, only 1 failure remains
- Phase 3: After fixing AnotherFailingFixture, all tests pass and junit.xml is cleaned up
- Phase 4: Full test suite passes

## How It Works

The multi-phase tests use `sed` to modify the return values in fixture files inline:

```bash
# Simulate a failure
sed -i 's/return true;/return false;/g' tests/Fixtures/InitiallyFailingFixture.php

# Simulate a fix
sed -i 's/return false;/return true;/g' tests/Fixtures/InitiallyFailingFixture.php
```

This demonstrates the real-world workflow of:
1. Running tests and having some fail (junit.xml created)
2. Fixing code to make tests pass
3. Re-running only the previously failed tests
4. Continuing until all tests pass

## What the Automated Test Validates

The `run-automated-test.sh` script is an **integration test** that verifies the `phpunit-failed-runner` script's behavior by checking its actual output:

### Phase 1: Initial Run
- Verifies script detects "Logfile not found"
- Verifies script runs full test suite
- Verifies junit.xml is created
- Verifies failures are reported

### Phase 2: Incremental Re-run
- Verifies script detects "Logfile found"
- Verifies script finds "previously failing tests"
- Verifies script filters and runs only failed tests
- Verifies junit.xml persists (because tests still fail)

### Phase 3: All Tests Pass
- Verifies script shows "No failed tests! Great job!"
- Verifies script automatically deletes junit.xml
- Confirms cleanup behavior works correctly

### Phase 4: Clean State
- Verifies script correctly handles clean state (no junit.xml)
- Verifies script runs full suite when starting fresh
- Verifies all tests pass and junit.xml is cleaned up

The test validates the **actual script behavior** rather than duplicating its logic, ensuring the phpunit-failed-runner script works as intended.
