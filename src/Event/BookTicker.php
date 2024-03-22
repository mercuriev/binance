<?php

namespace Binance\Event;

class BookTicker
{
    public int    $updateId;
    public string $symbol;
    public float  $bestBidPrice;
    public float  $bestBidQty;
    public float  $bestAskPrice;
    public float  $bestAskQty;

    public function __construct(array $payload)
    {
        $this->updateId = intval($payload['u']);
        $this->symbol = $payload['s'];
        $this->bestBidPrice = floatval($payload['b']);
        $this->bestBidQty = floatval($payload['B']);
        $this->bestAskPrice = floatval($payload['a']);
        $this->bestAskQty = floatval($payload['A']);
    }
}
