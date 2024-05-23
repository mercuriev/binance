<?php
namespace Binance\Chart\Indicator;

use Laminas\Stdlib\ArrayObject;

class AbstractIndicator extends ArrayObject
{
    public function __toString(): string
    {
        return $this->now();
    }

    public function now()
    {
        return $this[0];
    }

    public function last() : float
    {
        return $this[count($this) - 1];
    }

    public function isAscending(int $period = null, float $ratio = 1) : bool
    {
        $period ??= count($this);
        $period = array_slice($this->storage, 0, $period + 1);

        $up = $down = 0;
        foreach ($period as $k => $price) {
            if (isset($period[$k+1])) {
                $last = $period[$k+1];
            } else break;

            if ($price > $last) $up++;
            else $down++;
        }

        return $ratio >= round($up / ($up + $down), 2);
    }

    public function isDescending(int $period, float $ratio = 0.75) : bool
    {
        return !$this->isAscending($period, $ratio);
    }

    public function min(int $period = null)
    {
        $period ??= count($this);
        $period = array_slice($this->storage, -$period);
        return min($period);
    }

    public function max(int $period = null)
    {
        $period ??= count($this);
        $period = array_slice($this->storage, -$period);
        return max($period);
    }

    public function above(AbstractIndicator $that, int $period) : float
    {
        $above = 0;
        for ($k = 0; $k < $period; $k++) {
            if ($this[$k] > $that[$k]) $above++;
        }
        return round($above / $period, 2);
    }

    public function below(AbstractIndicator $that, int $period) : float
    {
        $below = 0;
        for ($k = 0; $k < $period; $k++) {
            if ($this[$k] < $that[$k]) $below++;
        }
        return round($below / $period, 2);
    }
}
