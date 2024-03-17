<?php

namespace Binance\Event;

use Binance\Event\Event;

class Kline extends Event
{
    protected \DateTime $time;
    protected \DateTime $startTime;
    protected \DateTime $closeTime;
    protected string $symbol;
    protected string $interval;
    protected int $firstTradeId;
    protected int $lastTradeId;
    protected float $openPrice;
    protected float $closePrice;
    protected float $highPrice;
    protected float $lowPrice;
    protected float $baseAssetVolume;
    protected int $numberOfTrades;
    protected bool $isKlineClosed;
    protected float $quoteAssetVolume;
    protected float $takerBuyBaseAssetVolume;
    protected float $takerBuyQuoteAssetVolume;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(array $payload)
    {
        $this->time = new \DateTime("@".intval($payload['E']/1000));
        $this->symbol = $payload['s'];
        $payload = $payload['k'];

        $this->startTime = new \DateTime("@".intval($payload['t']/1000));
        $this->closeTime = new \DateTime("@".intval($payload['T']/1000));
        $this->interval = $payload['i'];
        $this->firstTradeId = $payload['f'];
        $this->lastTradeId = $payload['L'];
        $this->openPrice = floatval($payload['o']);
        $this->closePrice = floatval($payload['c']);
        $this->highPrice = floatval($payload['h']);
        $this->lowPrice = floatval($payload['l']);
        $this->baseAssetVolume = floatval($payload['v']);
        $this->numberOfTrades = $payload['n'];
        $this->isKlineClosed = $payload['x'];
        $this->quoteAssetVolume = floatval($payload['q']);
        $this->takerBuyBaseAssetVolume = floatval($payload['V']);
        $this->takerBuyQuoteAssetVolume = floatval($payload['Q']);
    }
}
