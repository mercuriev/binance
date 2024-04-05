<?php

namespace Binance\Event;

class Trade extends Event
{
    public \DateTime $time;
    public string    $symbol;
    public int       $id;
    public float     $price;
    public float     $quantity;
    public int       $buyerOrderId;
    public int       $sellerOrderId;
    public \DateTime $tradeTime;
    public bool      $buyerIsMaker;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(array $payload)
    {
        $this->time           = new \DateTime("@".intval($payload['E']/1000));
        $this->symbol         = $payload['s'];
        $this->id             = $payload['t'];
        $this->price          = floatval($payload['p']);
        $this->quantity       = floatval($payload['q']);
        $this->buyerOrderId   = $payload['b'];
        $this->sellerOrderId  = $payload['a'];
        $this->tradeTime      = new \DateTime("@".intval($payload['T']/1000));
        $this->buyerIsMaker   = $payload['m'];
    }
}
