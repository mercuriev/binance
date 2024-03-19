<?php

namespace Binance\Event;

use Binance\Event\Event;

class Ticker extends MiniTicker
{
    protected float     $priceChange;
    protected float     $priceChangePercent;
    protected float     $weightedAvgPrice;
    protected float     $firstTradePrice;
    protected float     $lastQuantity;
    protected float     $bestBidPrice;
    protected float     $bestBidQuantity;
    protected float     $bestAskPrice;
    protected float     $bestAskQuantity;
    protected \DateTime $statisticsOpenTime;
    protected \DateTime $statisticsCloseTime;
    protected int       $firstTradeId;
    protected int       $lastTradeId;
    protected int       $totalNumberOfTrades;

    public function __construct(array $payload)
    {
        parent::__construct($payload);
        $this->priceChangePercent     = floatval($payload['P']);
        $this->weightedAvgPrice       = floatval($payload['w']);
        $this->firstTradePrice        = floatval($payload['x']);
        $this->lastQuantity           = floatval($payload['Q']);
        $this->bestBidPrice           = floatval($payload['b']);
        $this->bestBidQuantity        = floatval($payload['B']);
        $this->bestAskPrice           = floatval($payload['a']);
        $this->bestAskQuantity        = floatval($payload['A']);
        $this->statisticsOpenTime     = new \DateTime("@".intval($payload['O']/1000));
        $this->statisticsCloseTime    = new \DateTime("@".intval($payload['C']/1000));
        $this->firstTradeId           = $payload['F'];
        $this->lastTradeId            = $payload['L'];
        $this->totalNumberOfTrades    = $payload['n'];
    }
}
