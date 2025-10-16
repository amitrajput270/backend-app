<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CalculatorTest extends TestCase
{
    public function test_addition()
    {
        $result = 2 + 3;

        $this->assertEquals(5, $result); // Passes if 2 + 3 == 5
    }

    public function test_subtraction()
    {
        $result = 10 - 4;

        $this->assertEquals(6, $result); // Passes if 10 - 4 == 6
    }
}
