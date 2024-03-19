<?php
namespace Binance\Order;

use Binance\Event\Trade;

/**
 * @property string $orderListId
 * @property string $limitClientOrderId
 * @property string $stopClientOrderId
 * @property string $contingencyType
 * @property string $listStatusType
 * @property string $listOrderStatus
 * @property string $transactionTime
 * @property array[] $orders
 * @property LimitOrder[]|StopOrder[] $orderReports
 */
class OcoOrder extends AbstractOrder
{
    public string $listClientOrderId;
    public float $quantity;
    public float $price = 0; // exchange will not accept 0
    public float $stopPrice = 0;
    public float $stopLimitPrice = 0;
    public string $stopLimitTimeInForce = 'GTC';

    public function getDealId()
    {
        $parts = explode('-', $this->listClientOrderId);
        return array_pop($parts);
    }

    public function merge(array|AbstractOrder $reply) : static
    {
        foreach ($reply as $k => $v) {
            if ('orderReports' == $k) {
                foreach ($v as &$order) {
                    if ($order instanceof AbstractOrder) continue;
                    $obj = 'LIMIT_MAKER' == $order['type'] ? new LimitOrder() : new StopOrder();
                    $obj->merge($order);
                    $order = $obj;
                }
            }
            $this->$k = $v;
        }
        return $this;
    }

    public function fill(?Trade $trade = null): static
    {
        throw new \LogicException('Which order to fill? I got two.');
    }

    public function getClientOrderId(): string
    {
        return $this->listClientOrderId;
    }

    public function getId()
    {
        return $this->orderListId;
    }

    public function getType() : string
    {
        return 'OCO';
    }

    public function getQty() : float
    {
        foreach ($this->orderReports as $o) {
            return $o['origQty'];
        }
    }

    public function getStopQty()
    {
        foreach ($this->orderReports as $order) {
            if ('STOP_LOSS_LIMIT' == $order['type']) {
                return $order['origQty'];
            }
        }
        throw new \RuntimeException('Unable to find stop loss order.');
    }

    public function getExecutedQty()
    {
        $qty = 0;
        foreach ($this->orderReports as $order) $qty += $order->executedQty;
        return $qty;
    }

    public function getExecutedPrice()
    {
        foreach ($this->orderReports as $o) if ('FILLED' == $o->status) {
            return 'STOP_LOSS_LIMIT' == $o->type ? $o->stopPrice : $o->price;
        }
        throw new \RuntimeException('No filled orders to find price.');
    }

    public function getExecutedAmount() : float
    {
        return $this->getFilled()->getAmount();
    }

    public function getLimitOrder()
    {
        foreach ($this->orderReports as $o)
            if ('LIMIT_MAKER' == $o->type) return $o;
        throw new \RuntimeException('Unable to find limit order.');
    }

    public function getStopOrder()
    {
        foreach ($this->orderReports as $o)
            if ('STOP_LOSS_LIMIT' == $o->type) return $o;
        throw new \RuntimeException('Unable to find stop loss order.');
    }

    public function getPrice() : float
    {
        return $this->getLimitOrder()->price;
    }

    public function setPrice(float $price)
    {
        $this->getLimitOrder()->price = $price;
        return $this;
    }

    public function getStopPrice() : float
    {
        return $this->getStopOrder()->stopPrice;
    }

    public function setStopPrice(float $price) : self
    {
        $this->stopPrice = $this->stopLimitPrice = $price;
        return $this;
    }

    public function isFilled() : bool
    {
        if (isset($this->orderReports))
            foreach ($this->orderReports as $o)
                if (10 >= ($o->origQty - $o->executedQty) * $o->price) {
                    $o->status = 'FILLED';
                    return true;
                }
        return false;
    }

    public function isPartiallyFilled() : bool
    {
        if (isset($this->orderReports))
            foreach ($this->orderReports as $o)
                if ($o->executedQty > 0 && $o->executedQty != $o->origQty) return true;
        else return false;
    }

    public function getFilled() : LimitOrder|MarketOrder|StopOrder
    {
        if (isset($this->orderReports))
            foreach ($this->orderReports as $order)
                if ('FILLED' == $order->status) return $order;
        throw new \RuntimeException('Not able to find filled order.');
    }

    public function isAllDone()
    {
        return $this->listOrderStatus == 'ALL_DONE';
    }

    public function isNew()
    {
        return @$this->listOrderStatus == 'EXECUTING';
    }

    public function isCanceled()
    {
        // partially filled orders do not have the other 'expired' order in reports
        foreach ($this->orderReports as $o) if (!in_array($o->status, ['CANCELED', 'EXPIRED'])) return false;
        return true;
    }

    public function isSamePrice(OcoOrder $order)
    {
        return abs($order->price     - $this->price)        <= 0.1
            && abs($order->stopPrice - $this->stopPrice)    <= 0.1;
    }

    public function setClientId(string $prefix, string $direction = 'in')
    {
        $id = $this->generateClientId($prefix, $direction);
        $this->listClientOrderId = $id;
        $this->limitClientOrderId = "$id-limit";
        $this->stopClientOrderId = "$id-stop";
    }
}
