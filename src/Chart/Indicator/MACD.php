<?php

namespace Binance\Chart\Indicator;

class MACD extends AbstractIndicator
{
    public function isAscending(int $period = null, float $ratio = 1, int $key = 2) : bool
    {
        return $ratio <= $this->getUpRatio($period, $key);
    }

    public function isDescending(int $period = null, float $ratio = 1, int $key = 2) : bool
    {
        return $ratio <= $this->getDownRatio($period, $key);
    }

    public function getUpRatio(int $period = null, int $key = 2) : float
    {
        $period ??= count($this);
        $macd = [];
        for ($i = 0; $i < $period + 1; $i++) $macd[] = $this->storage[$i][$key];

        $up = $down = 0;
        foreach ($macd as $k => $value) {
            if (isset($macd[$k+1])) {
                $last = $macd[$k+1];
            }
            else {
                return round($up / ($up + $down), 2);
            }

            if ($value > $last) $up++;
            else $down++;
        }
        throw new \LogicException('Not enough data.');
    }

    public function getDownRatio(int $period = null, int $key = 2) : float
    {
        $period ??= count($this);
        $macd = [];
        for ($i = 0; $i < $period + 1; $i++) $macd[] = $this->storage[$i][$key];

        $up = $down = 0;
        foreach ($macd as $k => $value) {
            if (isset($macd[$k+1])) {
                $last = $macd[$k+1];
            }
            else {
                return round($down / ($up + $down), 2);
            }

            if ($value > $last) $up++;
            else $down++;
        }
        throw new \LogicException('Not enough data.');
    }
}
