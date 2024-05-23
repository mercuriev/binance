<?php

namespace Binance\Order;

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
class LimitMakerOrder extends AbstractOrder
{
    public float $price;
    public string $type = 'LIMIT_MAKER';
}
