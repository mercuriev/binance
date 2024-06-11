<?php
namespace Binance\Chart;

use Binance\Event\Trade;
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

        $now   = intval($this->storage[0][0]->tradeTime->getTimestamp() / 60);
        $trade = intval($trade->tradeTime->getTimestamp() / 60);

        return $now != $trade;
    }

    /**
     * Minutes klines hold only open/close prices.
     */
    public function append(mixed $trade): void
    {
        if (!$trade instanceof Trade) throw new \InvalidArgumentException('Must be a Trade.');
        if (array_key_last($this->storage) >= self::SIZE) array_pop($this->storage);
        if (array_key_last($this->trader) >= self::SIZE) array_shift($this->trader);


        if ($this->isNew($trade)) {
            array_unshift($this->storage, new Kline([$trade]));
        }
        else {
            $this->storage[0][1] = $trade;
            if ($this->trader) array_pop($this->trader);
        }
        $this->trader[] = $trade->price;
    }
}
