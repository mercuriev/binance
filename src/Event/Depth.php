<?php

namespace Binance\Event;

class Depth extends Event
{
    public \DateTime $time;
    public string $symbol;
    public int $firstUpdateId;
    public int $finalUpdateId;
    public array $bids;
    public array $asks;

    public function __construct(array $payload)
    {
        parent::__construct($payload);
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
