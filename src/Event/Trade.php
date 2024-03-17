<?php

namespace Binance\Event;

class Trade extends Event
{
    protected \DateTime $time;
    protected string    $symbol;
    protected int       $id;
    protected float     $price;
    protected float     $quantity;
    protected int       $buyerOrderId;
    protected int       $sellerOrderId;
    protected \DateTime $tradeTime;
    protected bool      $buyerIsMaker;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(array $payload)
    {
        $this->time = new \DateTime("@".intval($payload['E']/1000));
        $this->symbol = $payload['s'];
        $this->id = $payload['t'];
        $this->price = floatval($payload['p']);
        $this->quantity = floatval($payload['q']);
        $this->buyerOrderId = $payload['b'];
        $this->sellerOrderId = $payload['a'];
        $this->tradeTime = new \DateTime("@".intval($payload['T']/1000));
        $this->buyerIsMaker = $payload['m'];
    }
}
