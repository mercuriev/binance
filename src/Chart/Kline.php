<?php
namespace Binance\Chart;

use Binance\Event\Trade;
use Laminas\Stdlib\ArrayObject;
use function Binance\json_encode_pretty;

/**
 * Holds trades for CURRENT timeframe.
 */
class Kline extends ArrayObject
{
    public function __toString()
    {
        return json_encode_pretty($this);
    }

    public function high() : float
    {
        $prices = [];
        foreach ($this as $trade) $prices[] = $trade->price;
        return max($prices);
    }

    public function low() : float
    {
        $prices = [];
        foreach ($this as $trade) $prices[] = $trade->price;
        return min($prices);
    }

    public function getPrice() : float
    {
        return $this->last()->price;
    }

    public function getOpen() : float
    {
        return $this[0]->price;
    }

    public function getClose() : float
    {
        return $this->last()->price;
    }

    public function isGreen() : bool
    {
        return $this->getClose() > $this->getOpen();
    }

    public function isRed() : bool
    {
        return !$this->isGreen();
    }

    /**
     * Price move from second start in percents.
     *
     * @return float
     */
    public function getChange() : float
    {
        $diff  = $this->getClose() - $this->getOpen();
        return bcdiv($diff, $this->getOpen() * 100, 4);
    }

    public function last() : Trade
    {
        return $this->storage[array_key_last($this->storage)];
    }
}
