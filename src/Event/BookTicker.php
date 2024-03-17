<?php

namespace Binance\Event;

class BookTicker
{
    protected int    $updateId;
    protected string $symbol;
    protected float  $bestBidPrice;
    protected float  $bestBidQty;
    protected float  $bestAskPrice;
    protected float  $bestAskQty;

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
