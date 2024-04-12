<?php

namespace Binance\Order;

class StopOrder extends LimitOrder
{
    public float $stopPrice;
    public string $type = 'STOP_LOSS_LIMIT';
    public string $newOrderRespType = 'FULL';

    public function setPrice(float $price): static
    {
        $this->stopPrice = $price;
        return parent::setPrice($price);
    }
}
