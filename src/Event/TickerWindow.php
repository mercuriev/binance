<?php

namespace Binance\Event;

class TickerWindow extends MiniTicker
{
    protected float     $priceChange;
    protected float     $priceChangePercent;
    protected float     $weightedAvgPrice;
    protected \DateTime $statisticsOpenTime;
    protected \DateTime $statisticsCloseTime;
    protected int       $firstTradeId;
    protected int       $lastTradeId;
    protected int       $totalNumberOfTrades;

    public function __construct(array $payload)
    {
        parent::__construct($payload);

        $this->priceChange        = floatval($payload['p']);
        $this->priceChangePercent = floatval($payload['P']);
        $this->weightedAvgPrice   = floatval($payload['w']);
        $this->statisticsOpenTime = new \DateTime("@".intval($payload['O']/1000));
        $this->statisticsCloseTime= new \DateTime("@".intval($payload['C']/1000));
        $this->firstTradeId       = intval($payload['F']);
        $this->lastTradeId        = intval($payload['L']);
        $this->totalNumberOfTrades= intval($payload['n']);
    }
}
