<?php

namespace Binance\Event;

use Binance\Event\Event;

class Ticker extends Event
{
    protected \DateTime $eventTime;
    protected string    $symbol;
    protected float     $priceChange;
    protected float     $priceChangePercent;
    protected float     $weightedAvgPrice;
    protected float     $firstTradePrice;
    protected float     $lastPrice;
    protected float     $lastQuantity;
    protected float     $bestBidPrice;
    protected float     $bestBidQuantity;
    protected float     $bestAskPrice;
    protected float     $bestAskQuantity;
    protected float     $openPrice;
    protected float     $highPrice;
    protected float     $lowPrice;
    protected float     $baseAssetVolume;
    protected float     $quoteAssetVolume;
    protected \DateTime $statisticsOpenTime;
    protected \DateTime $statisticsCloseTime;
    protected int       $firstTradeId;
    protected int       $lastTradeId;
    protected int       $totalNumberOfTrades;

    public function __construct(array $payload)
    {
        $this->eventTime = new \DateTime("@".intval($payload['E']/1000));
        $this->symbol = $payload['s'];
        $this->priceChange = floatval($payload['p']);
        $this->priceChangePercent = floatval($payload['P']);
        $this->weightedAvgPrice = floatval($payload['w']);
        $this->firstTradePrice = floatval($payload['x']);
        $this->lastPrice = floatval($payload['c']);
        $this->lastQuantity = floatval($payload['Q']);
        $this->bestBidPrice = floatval($payload['b']);
        $this->bestBidQuantity = floatval($payload['B']);
        $this->bestAskPrice = floatval($payload['a']);
        $this->bestAskQuantity = floatval($payload['A']);
        $this->openPrice = floatval($payload['o']);
        $this->highPrice = floatval($payload['h']);
        $this->lowPrice = floatval($payload['l']);
        $this->baseAssetVolume = floatval($payload['v']);
        $this->quoteAssetVolume = floatval($payload['q']);
        $this->statisticsOpenTime = new \DateTime("@".intval($payload['O']/1000));
        $this->statisticsCloseTime = new \DateTime("@".intval($payload['C']/1000));
        $this->firstTradeId = $payload['F'];
        $this->lastTradeId = $payload['L'];
        $this->totalNumberOfTrades = $payload['n'];
    }
}
