<?php

namespace Binance\Event;

class AvgPrice extends Event
{
    protected \DateTime $time;
    protected string    $symbol;
    protected string    $interval;
    protected float     $price;
    protected \DateTime $lastTradeTime;

    public function __construct(array $payload)
    {
        $this->time = new \DateTime("@".intval($payload['E']/1000));
        $this->symbol = $payload['s'];
        $this->interval = $payload['i'];
        $this->price = floatval($payload['w']);
        $this->lastTradeTime = new \DateTime("@".intval($payload['T']/1000));
    }
}
