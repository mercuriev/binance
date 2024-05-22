<?php

namespace Binance\Order;

class LimitMakerOrder extends AbstractOrder
{
    public float $price;
    public string $type = 'LIMIT_MAKER';
}
