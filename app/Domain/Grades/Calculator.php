<?php

namespace App\Domain\Grades;

use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

/** Pure domain service. Never uses PHP floats for academic arithmetic. */
final class Calculator
{
    public function number(string|int|BigRational $value): BigRational
    {
        return BigRational::of($value);
    }

    public function display(?BigRational $value, int $scale = 2): ?string
    {
        return $value === null ? null : (string) $value->toScale($scale, RoundingMode::HalfUp);
    }

    /** Missing positive-weight components are pending; zero weights are intentionally excluded. */
    public function weighted(array $values, array $weights): ?BigRational
    {
        $sum = $this->number(0);
        $total = $this->number(0);
        foreach ($weights as $key => $weight) {
            $w = $this->number((string) $weight);
            if ($w->isNegative()) {
                throw new InvalidArgumentException('Los pesos no pueden ser negativos.');
            }
            if ($w->isZero()) {
                continue;
            }
            if (! isset($values[$key])) {
                return null;
            }
            $sum = $sum->plus($this->number($values[$key])->multipliedBy($w));
            $total = $total->plus($w);
        }

        return $total->isZero() ? null : $sum->dividedBy($total);
    }

    public function mean(array $values): ?BigRational
    {
        return $this->weighted($values, array_fill_keys(array_keys($values), 1));
    }

    public function budget(BigRational $team): BigRational
    {
        return $this->number($this->display($team, 4));
    }

    public function allocationValid(BigRational $team, array $allocations): bool
    {
        if (count($allocations) < 2 || count($allocations) > 5) {
            return false;
        }
        $sum = $this->number(0);
        foreach ($allocations as $grade) {
            if ($grade === null) {
                return false;
            }
            $n = $this->number((string) $grade);
            if ($n->isLessThan(0) || $n->isGreaterThan(10)) {
                return false;
            }
            $sum = $sum->plus($n);
        }

        return $sum->isEqualTo($this->budget($team)->multipliedBy(count($allocations)));
    }

    public function challenge(?BigRational $base, array $defenses, bool $clamp): array
    {
        $sum = $this->number(0);
        foreach ($defenses as $defense) {
            if ($defense === null) {
                return ['defenses' => null, 'raw' => null, 'final' => null];
            }
            $sum = $sum->plus($this->number((string) $defense));
        }
        $raw = $base?->plus($sum);
        $final = $raw;
        if ($clamp && $raw !== null) {
            if ($raw->isLessThan(0)) {
                $final = $this->number(0);
            }
            if ($raw->isGreaterThan(10)) {
                $final = $this->number(10);
            }
        }

        return ['defenses' => $sum, 'raw' => $raw, 'final' => $final];
    }
}
