<?php
namespace Binance\Order;

use function Binance\truncate;

/**
 * @property int $orderId
 * @property int $orderListId
 * @property string $newClientOrderId
 * @property string $clientOrderId
 * @property float $quantity
 * @property int $transactTime
 * @property int $origQty
 * @property int $executedQty
 * @property int $cummulativeQuoteQty
 * @property float $quoteOrderQty
 * @property string $newOrderRespType
 */
class LimitOrder extends AbstractOrder
{
    public float $price;
    public string $type = 'LIMIT';
    public string $timeInForce = 'GTC';

    public function setQuoteOrderQty(float $qty) : static
    {
        $this->quoteOrderQty = truncate($qty, 2);
        return $this;
    }

    public function isLimit() : bool
    {
        return true;
    }

    public function setPrice(float $price) : static
    {
        $this->price = $price;
        return $this;
    }

    public function convertToMarket() : MarketOrder
    {
        $market = new MarketOrder();
        $market->symbol     = $this->symbol;
        $market->side       = $this->side;
        $market->quantity   = truncate($this->quantity) - $this->getExecutedQty();
        return $market;
    }
}
