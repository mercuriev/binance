<?php

use Binance\AbstractApi;
use Binance\Exception\InsuficcientBalance;
use Binance\Order\AbstractOrder;
use Binance\Order\LimitOrder;
use Binance\Order\MarketOrder;
use Binance\Order\OcoOrder;
use Binance\Order\StopOrder;
use Binance\Event\Trade;
use Binance\Account\Account;
use function Binance\truncate;

class MockBinance extends AbstractApi
{
    private Trade $now;
    private array $orders = [];
    private Account $balance;

    /**
     * @param float $fiat Starting balance
     * @param float $crypto Starting balance
     */
    public function __construct(float $fiat, float $crypto)
    {
        $this->balance = new Account([
            'quoteAsset' => [
                'asset'     => 'USDT',
                'borrowed'  => 0,
                'free'      => $fiat,
                'locked'    => 0,
            ],
            'baseAsset' => [
                'asset'     => 'BTC',
                'borrowed'  => 0,
                'free'      => $crypto,
                'locked'    => 0,
            ]
        ]);
        parent::__construct([
            AbstractApi::class => [
                'key' => ['test', 'test'],
                'timeout' => 1
            ]
        ]);
    }

    /**
     * Match internal binance orders if price match.
     */
    public function __invoke(Trade $trade) : Trade
    {
        $this->now = $trade;

        foreach ($this->orders as $k => $order)
        {
            if ($order->isFilled()) continue;

            if ($order->isBuy()) {
                if ($order instanceof OcoOrder && $trade['p'] >= $order->getStopPrice()) {
                    $filled = $order->getStopOrder();
                    $filled->status = 'FILLED';
                    $order->getLimitOrder()->status = 'EXPIRED';
                    $order->listOrderStatus = 'ALL_DONE';
                    $order->listStatusType = 'ALL_DONE';
                }
                elseif ($trade['p'] <= $order->price) {
                    if ($order instanceof OcoOrder) {
                        $filled = $order->getLimitOrder();
                        $order->getStopOrder()->status = 'EXPIRED';
                        $order->listOrderStatus = 'ALL_DONE';
                        $order->listStatusType = 'ALL_DONE';
                    }
                    else {
                        $filled = $order;
                    }
                    $filled->status = 'FILLED';
                }
                else continue;

                $filled->executedQty = $filled->origQty;
                $filled->cummulativeQuoteQty = $filled->origQty * $trade['p'];
                $trade['b'] = $filled->orderId;
                $trade['q'] = $filled->origQty;
                $this->balance['quoteAsset']['locked']  = bcsub($this->balance['quoteAsset']['locked'], bcmul($filled->origQty, $trade['p']));
                $this->balance['baseAsset']['free']     = bcadd($this->balance['baseAsset']['free'], $filled->origQty);
            }
            else if ($order->isSell()) {
                if ($order instanceof OcoOrder && $trade['p'] <= $order->stopPrice) {
                    $filled = $order->getStopOrder();
                    $filled->status = 'FILLED';
                    $order->getLimitOrder()->status = 'EXPIRED';
                    $order->listOrderStatus = 'ALL_DONE';
                    $order->listStatusType = 'ALL_DONE';
                    $this->balance['quoteAsset']['free']    = bcadd($this->balance['quoteAsset']['free'], bcmul($filled->origQty, $trade['p']));
                    $this->balance['baseAsset']['locked']   = bcsub($this->balance['baseAsset']['locked'], $filled->origQty);
                }
                elseif ($trade['p'] >= $order->price) {
                    if ($order instanceof OcoOrder) {
                        $filled = $order->getLimitOrder();
                        $order->getStopOrder()->status = 'EXPIRED';
                        $order->listOrderStatus = 'ALL_DONE';
                        $order->listStatusType = 'ALL_DONE';
                    }
                    else {
                        $filled = $order;
                    }
                    $filled->status = 'FILLED';
                    $this->balance['quoteAsset']['free']    = bcadd($this->balance['quoteAsset']['free'], bcmul($filled->origQty, $trade['p']));
                    $this->balance['baseAsset']['locked']   = bcsub($this->balance['baseAsset']['locked'], $filled->origQty);
                }
                else continue;

                $filled->executedQty = $filled->origQty;
                $filled->cummulativeQuoteQty = $filled->origQty * $trade['p'];
                $trade['a'] = $filled->orderId;
                $trade['q'] = $filled->origQty;
            }
        }
        return $trade;
    }

    public function __call(string $name, array $arguments)
    {
        $allow = ['getKlines'];

        if (!in_array($name, $allow))
            throw new \LogicException("Tried to call API: $name");
        else
            return $this->$name(...$arguments);
    }

    public function getAccount(array $params = null)
    {
        $this->balance['indexPrice'] = isset($this->now) ? $this->now['p'] : 1000000;
        $this->balance['quoteAsset']['netAssetOfBtc'] = round(
            ($this->balance['quoteAsset']['free'] + $this->balance['quoteAsset']['locked']) / $this->balance['indexPrice'],
            5
        );
        $this->balance['baseAsset']['netAssetOfBtc'] = round(
            $this->balance['baseAsset']['free'] + $this->balance['baseAsset']['locked'], 5
        );

        return ['assets' => [(array)$this->balance]];
    }

    public function post(LimitOrder|MarketOrder|OcoOrder|StopOrder $order): LimitOrder|MarketOrder|OcoOrder|StopOrder
    {
        // filter MIN_NOTIONAL, check balance is enough
        if (isset($order->quantity)) {
            if (isset($order->price)) {
                $price = $order instanceof OcoOrder ? $order->stopPrice : $order->price;
            }
            else $price = $this->now['p'];
            $quote = bcmul($price, $order->quantity, 5);
            $qty   = $order->quantity;
        }
        else {
            $quote = $order->quoteOrderQty;
            $qty   = bcdiv($quote, $this->now['p'], 5);
        }
        if ($quote <= 10)
            throw new \LogicException('Too low quantity');
        if ($order->isBuy()         && $quote > $this->balance['quoteAsset']['free'] + 0.01)    // php think 0.9002 > 0.9002 :(
            throw new InsuficcientBalance("Tried to buy $quote");
        else if ($order->isSell()   && $qty   > $this->balance['baseAsset']['free'] + 0.00001)
            throw new InsuficcientBalance("Tried to sell $qty");

        // transform order to be like api reply
        if ($order instanceof MarketOrder) {
            $this->fill($order);
            $quote = truncate($order->cummulativeQuoteQty, 2);
            $qty = $order->getQty();
            if ('BUY' == $order->side) {
                $this->balance['quoteAsset']['free']    = bcsub($this->balance['quoteAsset']['free'], $quote);
                $this->balance['baseAsset']['free']     = bcadd($this->balance['baseAsset']['free'], $qty);
            }
            else if ('SELL' == $order->side) {
                $this->balance['baseAsset']['free']     = bcsub($this->balance['baseAsset']['free'], $qty);
                $this->balance['quoteAsset']['free']    = bcadd($this->balance['quoteAsset']['free'], $quote);
            }
            return $order;
        }
        else if ($order instanceof OcoOrder) {
            if (($order->isSell() && ($order->stopPrice >= $this->now['p'] || $order->stopPrice > $order->price))
                || ($order->isBuy()  && ($order->stopPrice <= $this->now['p'] || $order->stopPrice < $order->price))
                || ($order->isBuy() && $order->price < $this->now['p'])
                || ($order->isSell() && $order->price < $this->now['p'])
            )
                throw new \OutOfBoundsException('Stop price is higher than market.');

            $this->fillOco($order);
        }
        else {
            $order->status = 'NEW';
            $order->origQty = $order->quantity;
            $order->executedQty = $order->cummulativeQuoteQty = 0;
            $order->transactTime = $this->now['T']; // yes it's different
        }
        $order->orderId = intval(microtime(true) * 10000) + rand();

        // update balances
        if ('BUY' == $order->side) {
            $this->balance['quoteAsset']['free']    = bcsub($this->balance['quoteAsset']['free'], $quote);
            $this->balance['quoteAsset']['locked']  = bcadd($this->balance['quoteAsset']['locked'], $quote);
        }
        else if ('SELL' == $order->side) {
            $this->balance['baseAsset']['free']     = bcsub($this->balance['baseAsset']['free'], $qty);
            $this->balance['baseAsset']['locked']   = bcadd($this->balance['baseAsset']['locked'], $qty);
        }

        if (!$order instanceof MarketOrder)
            $this->orders[] = clone $order;

        return $order;
    }

    public function fillOco(OcoOrder $order)
    {
        $order->orderListId = intval(microtime(true) * 10000) + rand();
        $order->contingencyType = 'OCO';
        $order->listStatusType = 'EXEC_STARTED';
        $order->listOrderStatus = 'EXECUTING';
        $order->transactionTime = $this->now['T'];
        $order->orders = [];

        for ($i = 0; $i < 2; $i++) {
            if ($i == 0) {
                $o = new StopOrder();
                $o->stopPrice = $order->stopLimitPrice;
            }
            else {
                $o = new LimitOrder();
                $o->type = 'LIMIT_MAKER'; // it differs for OCO
            }
            $o->side = $order->side;
            $o->orderListId = $order->orderListId;
            $o->orderId = $order->orderListId + $i + 1;
            $o->clientOrderId = 'mock';
            $o->transactTime = $order->transactionTime;
            $o->price = $order->price;
            $o->status = 'NEW';
            $o->origQty = $order->quantity;
            $o->executedQty = $o->cummulativeQuoteQty = 0;
            $order->orderReports[$i] = $o;
        }

        return $order;
    }

    /**
     * @param LimitOrder|StopOrder $order
     */
    public function cancel(LimitOrder|OcoOrder|StopOrder $order): LimitOrder|OcoOrder|StopOrder
    {
        $price = $order instanceof OcoOrder ? $order->stopPrice : $order->price;
        $quote = (float) bcmul($price, $order->quantity, 5);
        $qty   = $order->quantity;

        if ($order instanceof OcoOrder) {
            foreach ($this->orders as $k => $our) {
                if ($order->getId() == $our->getId()) {
                    if ($our->isNew()) {
                        $order->listOrderStatus = 'ALL_DONE';
                        $order->listStatusType = 'ALL_DONE';
                        foreach ($order->orderReports as $o) $o->status = 'EXPIRED';
                        unset($this->orders[$k]); // cancel it
                    }
                    else {
                        unset($this->orders[$k]);
                        return $order->merge($our);
                    }
                }
            }
        }
        else {
            foreach ($this->orders as $k => $our) {
                if ($order->orderId == $our->orderId) {
                    if ('NEW' == $our->status) {
                        $order->status = 'CANCELED';
                        unset($this->orders[$k]); // cancel it
                    }
                    else {
                        unset($this->orders[$k]);
                        return $order->merge($our);
                    }
                }
            }
        }

        if ($order->isBuy()) {
            $this->balance['quoteAsset']['free']    = bcadd($this->balance['quoteAsset']['free'], $quote);
            $this->balance['quoteAsset']['locked']  = bcsub($this->balance['quoteAsset']['locked'], $quote);
        }
        if ($order->isSell()) {
            $this->balance['baseAsset']['free']     = bcadd($this->balance['baseAsset']['free'], $qty);
            $this->balance['baseAsset']['locked']   = bcsub($this->balance['baseAsset']['locked'], $qty);
        }

        return $order;
    }

    private function fill(AbstractOrder $order)
    {
        if ($order->isSell()) {
            // sell orders always have quantity
            $quote  = $order->quantity * $this->now['p'];
            $qty    = $order->quantity;
        }
        else {
            $quote  = $order->quoteOrderQty;
            $qty    = round($quote / $this->now['p'], 5);
        }
        $order->origQty = $order->executedQty = $order->quantity = $qty;
        $order->cummulativeQuoteQty = min(
            $quote,
            bcmul( $order->origQty, $this->now['p'], 5)
        );
        $order->status = 'FILLED';
        $order->orderId = intval(microtime(true) * 10000) + rand();

        if ($order instanceof MarketOrder) {
            $order->fills = [
                ['price' => $this->now['p'], 'qty' => $qty]
            ];
        }
    }
}
