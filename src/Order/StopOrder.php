<?php

namespace Binance\Order;

class StopOrder extends AbstractOrder
{
    public float $stopPrice;
    public string $type = 'STOP_LOSS_LIMIT';
    public string $timeInForce = 'GTC';
}
