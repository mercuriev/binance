<?php

namespace Binance\Event;

class MiniTicker extends Event
{
    public \DateTime $time;
    public string    $symbol;
    public float     $closePrice;
    public float     $openPrice;
    public float     $highPrice;
    public float     $lowPrice;
    public float     $baseAssetVolume;
    public float     $quoteAssetVolume;

    public function __construct(array $payload)
    {
        $this->time = new \DateTime("@".intval($payload['E']/1000));
        $this->symbol = $payload['s'];
        $this->closePrice = floatval($payload['c']);
        $this->openPrice = floatval($payload['o']);
        $this->highPrice = floatval($payload['h']);
        $this->lowPrice = floatval($payload['l']);
        $this->baseAssetVolume = floatval($payload['v']);
        $this->quoteAssetVolume = floatval($payload['q']);
    }
}
