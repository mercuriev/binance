<?php

namespace Binance\Event;

class AggTrade extends Event
{
    public \DateTime $time;
    public string    $symbol;
    public int       $id;
    public float     $price;
    public float     $quantity;
    public int       $firstTrade;
    public int       $lastTrade;
    public \DateTime $tradeTime;
    public bool      $buyerIsMaker;

    public function __construct(array $payload)
    {
        parent::__construct($payload);
        $this->time         = new \DateTime("@".intval($payload['E']/1000));
        $this->symbol       = $payload['s'];
        $this->id           = $payload['a'];
        $this->price        = floatval($payload['p']);
        $this->quantity     = floatval($payload['q']);
        $this->firstTrade   = $payload['f'];
        $this->lastTrade    = $payload['l'];
        $this->tradeTime    = new \DateTime("@".intval($payload['T']/1000));
        $this->buyerIsMaker = $payload['m'];
    }
}
