<?php

namespace Binance\Event;

class AggTrade extends Event
{
    protected \DateTime $time;
    protected string    $symbol;
    protected int       $id;
    protected float     $price;
    protected float     $quantity;
    protected int       $firstTrade;
    protected int       $lastTrade;
    protected \DateTime $tradeTime;
    protected bool      $buyerIsMaker;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(array $payload)
    {
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
