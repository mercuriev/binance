<?php

namespace Binance\Event;

use Binance\Event\Event;

class TickerWindow extends Event
{
    protected \DateTime $time;
    protected string    $symbol;
    protected float     $priceChange;
    protected float     $priceChangePercent;
    protected float     $openPrice;
    protected float     $highPrice;
    protected float     $lowPrice;
    protected float     $lastPrice;
    protected float     $weightedAvgPrice;
    protected float     $baseAssetVolume;
    protected float     $quoteAssetVolume;
    protected \DateTime $statisticsOpenTime;
    protected \DateTime $statisticsCloseTime;
    protected int       $firstTradeId;
    protected int       $lastTradeId;
    protected int       $totalNumberOfTrades;

    public function __construct(array $payload)
    {
        $this->time = new \DateTime("@".intval($payload['E']/1000));
        $this->symbol = $payload['s'];
        $this->priceChange = floatval($payload['p']);
        $this->priceChangePercent = floatval($payload['P']);
        $this->openPrice = floatval($payload['o']);
        $this->highPrice = floatval($payload['h']);
        $this->lowPrice = floatval($payload['l']);
        $this->lastPrice = floatval($payload['c']);
        $this->weightedAvgPrice = floatval($payload['w']);
        $this->baseAssetVolume = floatval($payload['v']);
        $this->quoteAssetVolume = floatval($payload['q']);
        $this->statisticsOpenTime = new \DateTime("@".intval($payload['O']/1000));
        $this->statisticsCloseTime = new \DateTime("@".intval($payload['C']/1000));
        $this->firstTradeId = intval($payload['F']);
        $this->lastTradeId = intval($payload['L']);
        $this->totalNumberOfTrades = intval($payload['n']);
    }
}
