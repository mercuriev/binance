<?php
namespace Binance\Chart;

use Binance\Binance;
use Binance\Trade;
use Laminas\ServiceManager\ServiceManager;

final class Minutes extends AbstractChart
{
    static public function factory(ServiceManager $sm, $name, $options) : self
    {
        $options ??= [];
        $symbol   = @$options['symbol']   ? $options['symbol']   : 'BTCTUSD';
        $endTime  = @$options['endTime'] ?? time();
        $startTime = $endTime - 60 * self::SIZE - 60;

        /** @var Binance */
        $api = $sm->get(Binance::class);

        $minutes = new self;
        $klines = $api->getKlines([
            'symbol' => $symbol,
            'interval' => '1m',
            'startTime' => $startTime * 1000,
            'endTime' => $endTime * 1000
        ]);
        if (!$klines) {
            throw new \RuntimeException('Failed to load minutes klines.');
        }

        return $minutes->withKlines($klines);
    }

    public function isNew(Trade $trade) : bool
    {
        if (empty($this->storage)) return true;

        $now   = intval($this[0]->last()['T'] / 1000 / 60);
        $trade = intval($trade['T'] / 1000 / 60);

        return $now != $trade;
    }

    /**
     * Minutes klines hold only open/close prices.
     */
    public function append(mixed $trade)
    {
        if (!$trade instanceof Trade) throw new \InvalidArgumentException('Must be a Trade.');
        if (array_key_last($this->storage) >= self::SIZE) array_pop($this->storage);
        if (array_key_last($this->trader) >= self::SIZE) array_shift($this->trader);


        if ($this->isNew($trade)) {
            array_unshift($this->storage, new Kline([$trade]));
        }
        else {
            $this[0][1] = $trade;
            if ($this->trader) array_pop($this->trader);
        }
        $this->trader[] = (float) $trade['p'];
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
}
