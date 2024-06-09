<?php
namespace Binance\Chart;

use Binance\Event\Trade;
use Binance\MarketDataApi;

class Chart
{
    public readonly Seconds $s;
    public readonly Minutes $m;

    /**
     * Fetch market data from API and load historical prices.
     *
     * @param string $symbol
     * @param int|null $startTime timestamp in seconds (default is last 30 minutes)
     * @param int|null $endTime
     * @param MarketDataApi|null $api
     * @return static
     */
    static public function buildWithHistory(string $symbol, int $startTime = null, int $endTime = null, MarketDataApi $api = null): static
    {
        $startTime = $startTime ?? (time() - 1800);
        $endTime = $endTime ?? time();
        $api = $api ?? new MarketDataApi([]);

        $self = new static();
        do {
            $klines = $api->getKlines([
                'symbol' => $symbol,
                'interval' => '1s', // granularity
                'startTime' => $startTime * 1000,
                'endTime' => $endTime * 1000,
                'limit' => 1000 // maximum in API
            ]);
            foreach ($klines as $v) {
                $self->append(new Trade(['p' => $v[1], 'T' => $v[0]])); // open trade
                $self->append(new Trade(['p' => $v[4], 'T' => $v[6]])); // close trade
                $startTime = round($v[6] / 1000);
            }
        }
        while($startTime < $endTime);

        return $self;
    }

    public function __construct()
    {
        $this->s = new Seconds();
        $this->m = new Minutes();
    }

    public function append(Trade $trade) : self
    {
        $this->s->append($trade);
        $this->m->append($trade);
        return $this;
    }

    public function now() : float
    {
        return $this->s[0]->getPrice();
    }
}
