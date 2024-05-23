<?php

namespace Binance\Event;

use Binance\Event\Event;

class Kline extends Event
{
    public \DateTime $time;
    public \DateTime $startTime;
    public \DateTime $closeTime;
    public string $symbol;
    public string $interval;
    public int $firstTradeId;
    public int $lastTradeId;
    public float $open;
    public float $close;
    public float $high;
    public float $low;
    public float $volumeBase;
    public int $numberOfTrades;
    public bool $isClosed;
    public float $volumeQuote;
    public float $takerBuyBaseAssetVolume;
    public float $takerBuyQuoteAssetVolume;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(array $payload)
    {
        parent::__construct($payload);
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
