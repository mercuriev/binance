<?php

namespace Binance\Order;

/**
 * @property int $orderId
 * @property array[] $fills
 * @property float $quoteOrderQty
 * @property float $cummulativeQuoteQty
 * @property float $quantity
 */
class MarketOrder extends AbstractOrder
{
    public string $type = 'MARKET';
    public string $newClientOrderId;

    public function getExecutedPrice()
    {
        if (!$this->fills) throw new \RuntimeException('Order is not filled.');
        $prices = [];
        foreach ($this->fills as $fill) $prices[] = $fill['price'];
        return round(array_sum($prices) / count($prices), 2);
    }

    public function getExecutedAmount() : float
    {
        return $this->cummulativeQuoteQty;
    }

    public function getPrice(): float
    {
        return $this->getExecutedPrice();
    }

    public function getQty()
    {
        if (isset($this->quantity)) return $this->quantity;
        else {
            $qty = 0;
            foreach ($this->fills as $trade) $qty += $trade['qty'];
            return $qty;
        }
    }

    public function getExecutedQty()
    {
        return $this->getQty();
    }

    public function isMarket() : bool
    {
        return true;
    }
}
