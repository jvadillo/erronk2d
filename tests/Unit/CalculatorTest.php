<?php

namespace Tests\Unit;

use App\Domain\Grades\Calculator;
use PHPUnit\Framework\TestCase;

class CalculatorTest extends TestCase
{
    public function test_required_allocations_and_exact_decimals(): void
    {
        $c = new Calculator;
        $this->assertTrue($c->allocationValid($c->number(8), ['7', '8', '9']));
        $this->assertFalse($c->allocationValid($c->number(8), ['7', '8', '8']));
        $this->assertTrue($c->allocationValid($c->number('8.1'), ['8.0', '8.1', '8.2']));
        $this->assertFalse($c->allocationValid($c->number(8), ['7', '8', '9.0001']));
        $this->assertFalse($c->allocationValid($c->number(8), ['8']));
        $this->assertFalse($c->allocationValid($c->number(8), ['6', '7', '11']));
    }

    public function test_all_defenses_produce_one_shared_result(): void
    {
        $c = new Calculator;
        $a = $c->challenge($c->number(7), ['0.5', '-0.25'], true);
        $this->assertSame('7.25', $c->display($a['final']));
        $this->assertSame('8.00', $c->display($c->challenge($c->number(8), ['-0.5', '0.5'], true)['final']));
        $this->assertSame('9.00', $c->display($c->challenge($c->number(9), ['0'], true)['final']));
        $weights = ['transversal' => 30, 'challenge' => 40, 'exam' => 30];
        $this->assertSame('7.40', $c->display($c->weighted(['transversal' => '8', 'challenge' => $a['final'], 'exam' => '7'], $weights)));
        $this->assertSame('8.00', $c->display($c->weighted(['transversal' => '8', 'challenge' => $a['final'], 'exam' => '9'], $weights)));
    }

    public function test_pending_zero_optional_defenses_and_limits(): void
    {
        $c = new Calculator;
        $this->assertNull($c->challenge($c->number(7), [null], true)['final']);
        $this->assertSame('7.00', $c->display($c->challenge($c->number(7), ['0'], true)['final']));
        $this->assertSame('7.00', $c->display($c->challenge($c->number(7), [], true)['final']));
        $this->assertSame('10.00', $c->display($c->challenge($c->number(9), ['2'], true)['final']));
        $this->assertSame('11.00', $c->display($c->challenge($c->number(9), ['2'], true)['raw']));
        $this->assertSame('11.00', $c->display($c->challenge($c->number(9), ['2'], false)['final']));
        $this->assertSame('0.00', $c->display($c->challenge($c->number(1), ['-2'], true)['final']));
    }

    public function test_periods_weights_and_no_premature_rounding(): void
    {
        $c = new Calculator;
        $this->assertSame('7.50', $c->display($c->weighted(['a' => '6', 'b' => '8'], ['a' => 1, 'b' => 3])));
        $this->assertNull($c->weighted(['a' => '6', 'b' => null], ['a' => 1, 'b' => 3]));
        $this->assertSame('6.00', $c->display($c->weighted(['a' => '6', 'b' => null], ['a' => 1, 'b' => 0])));
        $this->assertSame('7.00', $c->display($c->mean(['6', '8'])));
        $this->assertSame('8.00', $c->display($c->mean(['6', '8', '10'])));
        $this->assertNull($c->mean(['6', null, '10']));
        $third = $c->number(1)->dividedBy(3);
        $this->assertSame('1.00', $c->display($third->multipliedBy(3)));
        $this->assertSame('1.01', $c->display($c->number('1.005')));
        $this->assertSame('0.6667', $c->display($c->budget($c->number(2)->dividedBy(3)), 4));
    }
}
