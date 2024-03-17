<?php

namespace Binance\Event;

class MiniTicker extends Event
{
    protected \DateTime $eventTime;
    protected string    $symbol;
    protected float     $closePrice;
    protected float     $openPrice;
    protected float     $highPrice;
    protected float     $lowPrice;
    protected float     $baseAssetVolume;
    protected float     $quoteAssetVolume;

    public function __construct(array $payload)
    {
        $this->eventTime = new \DateTime("@".intval($payload['E']/1000));
        $this->symbol = $payload['s'];
        $this->closePrice = floatval($payload['c']);
        $this->openPrice = floatval($payload['o']);
        $this->highPrice = floatval($payload['h']);
        $this->lowPrice = floatval($payload['l']);
        $this->baseAssetVolume = floatval($payload['v']);
        $this->quoteAssetVolume = floatval($payload['q']);
    }
}
