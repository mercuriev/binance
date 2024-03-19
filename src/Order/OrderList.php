<?php
namespace Binance\Order;

use Binance\Db\Deal;
use Binance\Trade;
use function Binance\truncate;

class OrderList extends \Laminas\Stdlib\ArrayObject
{
    public function flip() : self
    {
        while(count($this->storage) < 2) $this->storage[] = null;
        $this->storage = array_flip($this->storage);
        return $this;
    }

    /**
     * Syntax to support more than 2 orders. Now only 2 is supported.
     *
     * @return $this
     */
    public function rotate()
    {
        $this->storage = array_reverse($this->storage);
        return $this;
    }

    public function match(Trade $trade)
    {
        $matched = 0;
        $fill = function(AbstractOrder $o) use ($trade) : bool {
            if (('SELL' == $o->side && $trade['a'] == $o->orderId)
                || ('BUY' == $o->side && $trade['b'] == $o->orderId)) {
                $o->executedQty = bcadd($o->executedQty, $trade['q'], 5);
                $o->cummulativeQuoteQty = truncate(bcadd($o->cummulativeQuoteQty, bcmul($trade['p'], $trade['q'], 8), 8), 5);
                $o->status = $o->executedQty >= $o->origQty ? 'FILLED' : 'FILLED_PARTIALLY';
                return true;
            }
            return false;
        };

        foreach ($this as $order) {
            if (!$order || $order->isFilled()) continue;
            if ($order instanceof OcoOrder) {
                foreach ($order->orderReports as $o) {
                    $fill($o) && ($order->listOrderStatus = 'ALL_DONE') && ++$matched;
                }
            } else if (!$order instanceof MarketOrder) {
                $fill($order) && ++$matched;
            }
        }
        return $matched;
    }

    public function isEmpty() : bool
    {
        return count($this->storage) == 0;
    }

    public function isFull()
    {
        return 2 == $this->count();
    }

    /**
     * Returns TRUE is list has orders and all are filled.
     *
     * @return bool
     */
    public function isAllFilled() : bool
    {
        foreach ($this as $o) {
            if (!$o->isFilled()) return false;
        }
        return count($this) > 0;
    }

    public function isAnyFilled() : bool
    {
        foreach ($this as $order) {
            if ($order->isFilled()) return true;
        }
        return false;
    }

    public function getExecutedQty()
    {
        $qty = 0;
        foreach ($this->storage as $o) $qty += $o->getExecutedQty();
        return $qty;
    }
}
