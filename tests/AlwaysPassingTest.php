<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\AlwaysPassingFixture;

class AlwaysPassingTest extends TestCase
{
    public function test_always_passing_fixture_returns_true(): void
    {
        $fixture = new AlwaysPassingFixture();
        
        $this->assertTrue(
            $fixture->success(),
            'AlwaysPassingFixture should always return true'
        );
    }
    
    public function test_another_always_passing_test(): void
    {
        $fixture = new AlwaysPassingFixture();
        
        $this->assertTrue($fixture->success());
    }
}
