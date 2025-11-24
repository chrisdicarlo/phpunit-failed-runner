<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\InitiallyFailingFixture;

class InitiallyFailingTest extends TestCase
{
    public function test_initially_failing_fixture(): void
    {
        $fixture = new InitiallyFailingFixture();
        
        $this->assertTrue(
            $fixture->success(),
            'This test will fail initially but pass after the fixture is fixed'
        );
    }
    
    public function test_another_initially_failing_test(): void
    {
        $fixture = new InitiallyFailingFixture();
        
        $this->assertTrue(
            $fixture->success(),
            'Another test that depends on the same fixture'
        );
    }
}
