<?php

namespace Binance\Event;

use Binance\Event\Event;

class Ticker extends MiniTicker
{
    public float     $priceChange;
    public float     $priceChangePercent;
    public float     $weightedAvgPrice;
    public float     $firstTradePrice;
    public float     $lastQuantity;
    public float     $bestBidPrice;
    public float     $bestBidQuantity;
    public float     $bestAskPrice;
    public float     $bestAskQuantity;
    public \DateTime $statisticsOpenTime;
    public \DateTime $statisticsCloseTime;
    public int       $firstTradeId;
    public int       $lastTradeId;
    public int       $totalNumberOfTrades;

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
