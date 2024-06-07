<?php
namespace Binance\Chart;

use Binance\Chart\Indicator\AbstractIndicator;
use Binance\Chart\Indicator\BOLL;
use Binance\Chart\Indicator\EMA;
use Binance\Chart\Indicator\MA;
use Binance\Chart\Indicator\MACD;
use Binance\Chart\Indicator\RSI;
use Binance\Event\Trade;
use Laminas\Stdlib\ArrayObject;
use function Binance\json_encode_pretty;


/**
 * Laminas array object is used so that this object can array_shift() $storage
 * which is private in SPL. (SPL->exchangeArray() is very slow.)
 */
abstract class AbstractChart extends ArrayObject
{
    const SIZE = 180;

    /** Kline close price index for trader_*() functions */
    protected array $trader = [];

    /**
     * @return True if new trade is NOT in current kline.
     */
    abstract public function isNew(Trade $trade) : bool;

    public function __construct ($input = null)
    {
        parent::__construct([], self::STD_PROP_LIST, 'ArrayIterator');
        if (null !== $input) {
            foreach ($input as $v) {
                $this->append($v);
            }
        }
    }

    public function __toString()
    {
        return json_encode_pretty($this->storage);
    }

    /**
     * @param Trade $value
     * @return void
     */
    public function append(mixed $value): void
    {
        if (!$value instanceof Trade) throw new \InvalidArgumentException('Must be a Trade.');
        if (array_key_last($this->storage) >= self::SIZE) array_pop($this->storage);
        if (array_key_last($this->trader) >= self::SIZE) array_shift($this->trader);

        if ($this->isNew($value)) {
            array_unshift($this->storage, new Kline([]));
        } else {
            array_pop($this->trader);
        }
        $this[0]->append($value);
        $this->trader[] = (float)$value['p'];
    }

    public function isReady(): bool
    {
        return isset($this[self::SIZE - 1]);
    }

    public function last() : Kline
    {
        return $this->storage[array_key_last($this->storage)];
    }

    public function now() : float
    {
        return $this[0]->getPrice();
    }

    public function min(int $period = self::SIZE)
    {
        $period = array_slice($this->trader, -$period);
        return min($period);
    }

    public function max(int $period = self::SIZE)
    {
        $period = array_slice($this->trader, -$period);
        return max($period);
    }

    public function isBelow(AbstractIndicator|float $ind, int $period) : bool
    {
        $key = 0;
        while ($key < $period) {
            $level = is_float($ind) ? $ind : $ind[$key];
            if ($this[$key]->high() > $level) return false;
            $key++;
        }
        return true;
    }

    public function isAbove(AbstractIndicator|float $ind, int $period) : bool
    {
        $key = 0;
        while ($key < $period) {
            $level = is_float($ind) ? $ind : $ind[$key];
            if ($this[$key]->low() < $level) return false;
            $key++;
        }
        return true;
    }

    /**
     * Fill storage with API candlestick data response
     */
    public function withKlines(array $klines) : self
    {
        foreach($klines as $v) {
            $this->append(new Trade(['p' => $v[1], 'T' => $v[0]])); // open trade
            $this->append(new Trade(['p' => $v[4], 'T' => $v[6]])); // close trade
        }
        return $this;
    }

    public function getUpRatio(int $period = self::SIZE) : float
    {
        $up = $down = 0;

        /** @var Kline $kline */
        foreach ($this as $k => $kline) {
            $last = isset($this[$k-1]) ? $this[$k-1]->getClose() : $kline->getOpen();
            if ($kline->getClose() > $last) $up++;
            else $down++;
            if (!$period--) break;
        }

        return round($up / ($up + $down), 2);
    }

    /**
     * @return float Ratio of closes when price is higher than in indicator.
     */
    public function getPriceRatio(AbstractIndicator $ind, int $period = self::SIZE) : float
    {
        $more = $less = 0;
        for ($i = 0; $i < $period; $i++) {
            if ($this[$i]->getClose() > $ind[$i]) $more++;
            else $less++;
        }
        return round($more / ($more + $less), 2);
    }

    public function ma(int $period): MA
    {
        /** @noinspection PhpUndefinedFunctionInspection, PhpUndefinedConstantInspection */
        $res = trader_ma($this->trader, $period, TRADER_MA_TYPE_SMA);
        if (!$res) throw new \UnderflowException('Failed to find MA.');

        $res = array_reverse($res);
        return new MA($res);
    }

    public function ema(int $period): EMA
    {
        /** @noinspection PhpUndefinedFunctionInspection, PhpUndefinedConstantInspection */
        $res = trader_ma($this->trader, $period, TRADER_MA_TYPE_EMA);
        if (!$res) throw new \UnderflowException('Failed to find EMA.');

        $res = array_reverse($res);
        return new EMA($res);
    }

    /**
     * @return AbstractIndicator DEA, DIF, MACD
     */
    public function macd(int $fast = 12, int $slow = 26, int $signal = 9): MACD
    {
        /** @noinspection PhpUndefinedFunctionInspection, PhpUndefinedConstantInspection */
        $res = trader_macd($this->trader, $fast, $slow, $signal);
        if (!$res) throw new \UnderflowException('Failed to find MACD.');

        // reset keys
        $fast = array_reverse($res[0]);
        $slow = array_reverse($res[1]);
        $signal = array_reverse($res[2]);

        $macd = [];
        for ($i = 0; $i < array_key_last($fast); $i++) {
            $macd[] = [
                round($slow[$i], 2),
                round($fast[$i], 2),
                round($signal[$i], 2)
            ];
        }

        return new MACD($macd);
    }

    public function rsi(int $period): RSI
    {
        /** @noinspection PhpUndefinedFunctionInspection, PhpUndefinedConstantInspection */
        $res = trader_rsi($this->trader, $period);
        if (!$res) throw new \UnderflowException('Failed to find RSI.');

        $res = array_reverse($res);
        return new RSI($res);
    }

    public function boll(int $period = 90, float $multiplier = 2): BOLL
    {
        /** @noinspection PhpUndefinedFunctionInspection, PhpUndefinedConstantInspection */
        $res = trader_bbands($this->trader, $period, $multiplier, $multiplier);
        if (!$res) throw new \UnderflowException('Failed to find BOLL.');

        // reset keys
        $up = array_reverse($res[0]);
        $mid = array_reverse($res[1]);
        $down = array_reverse($res[2]);

        $bbands = [];
        for ($i = 0; $i < array_key_last($up); $i++) {
            $bbands[] = [
                round($up[$i], 2),
                round($mid[$i], 2),
                round($down[$i], 2)
            ];
        }

        return new BOLL($bbands);
    }
}
