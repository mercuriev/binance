<?php

namespace Binance\Event;

class Depth extends Event
{
    protected \DateTime $time;
    protected string $symbol;
    protected int $firstUpdateId;
    protected int $finalUpdateId;
    protected array $bids;
    protected array $asks;

    public function __construct(array $payload)
    {
        $this->time = new \DateTime("@".intval($payload['E']/1000));
        $this->symbol = $payload['s'];
        $this->firstUpdateId = intval($payload['U']);
        $this->finalUpdateId = intval($payload['u']);
        $this->bids = array_map(function($bid) {
            return ['price' => floatval($bid[0]), 'qty' => floatval($bid[1])];
        }, $payload['b']);
        $this->asks = array_map(function($ask) {
            return ['price' => floatval($ask[0]), 'qty' => floatval($ask[1])];
        }, $payload['a']);
    }
}
