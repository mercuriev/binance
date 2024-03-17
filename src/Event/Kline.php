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
    protected float $open;
    protected float $close;
    protected float $high;
    protected float $low;
    protected float $volumeBase;
    protected int $numberOfTrades;
    protected bool $isClosed;
    protected float $volumeQuote;
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
        $this->open = floatval($payload['o']);
        $this->close = floatval($payload['c']);
        $this->high = floatval($payload['h']);
        $this->low = floatval($payload['l']);
        $this->volumeBase = floatval($payload['v']);
        $this->volumeQuote = floatval($payload['q']);
        $this->numberOfTrades = $payload['n'];
        $this->isClosed = $payload['x'];
        $this->takerBuyBaseAssetVolume = floatval($payload['V']);
        $this->takerBuyQuoteAssetVolume = floatval($payload['Q']);
    }

    public function getChange(): float
    {
        $diff = $this->close - $this->open;
        return round($diff / ($this->open * 100), 4);
    }

    public function isGreen() : bool
    {
        return $this->getChange() > 0;
    }

    public function isRed() : bool
    {
        return !$this->isGreen();
    }
}
