<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\AnotherFailingFixture;

class AnotherFailingTest extends TestCase
{
    public function test_another_failing_fixture(): void
    {
        $fixture = new AnotherFailingFixture();
        
        $this->assertTrue(
            $fixture->success(),
            'This test will also fail initially'
        );
    }
}
