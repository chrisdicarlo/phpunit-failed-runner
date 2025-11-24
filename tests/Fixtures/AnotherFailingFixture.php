<?php

namespace Tests\Fixtures;

/**
 * Another fixture class that initially fails
 * Change the return value from false to true to simulate fixing the test
 */
class AnotherFailingFixture
{
    public function success(): bool
    {
        // CHANGEME: false = failing test, true = passing test
        return true;
    }
}
