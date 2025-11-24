<?php

namespace Tests\Fixtures;

/**
 * Fixture class that initially fails but can be "fixed"
 * Change the return value from false to true to simulate fixing the test
 */
class InitiallyFailingFixture
{
    public function success(): bool
    {
        // CHANGEME: false = failing test, true = passing test
        return true;
    }
}
