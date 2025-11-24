<?php

namespace Tests\Fixtures;

/**
 * Fixture class that always passes
 */
class AlwaysPassingFixture
{
    public function success(): bool
    {
        return true;
    }
}
