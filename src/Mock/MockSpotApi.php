<?php

namespace Binance\Mock;

use Binance\Account\Account;
use Binance\Event\Trade;
use Binance\Exception\BinanceException;
use Binance\Exception\InsuficcientBalance;
use Binance\Order\AbstractOrder;
use Binance\Order\LimitMakerOrder;
use Binance\Order\LimitOrder;
use Binance\Order\MarketOrder;
use Binance\Order\OcoOrder;
use Binance\Order\StopOrder;
use Binance\SpotApi;
use Laminas\Http\Request;
use function Binance\truncate;

class MockSpotApi extends SpotApi
{
    protected Account $account;
    protected Trade $trade;
    protected array $orders = [];

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct()
    {
        $this->account = new Account(array(
            'makerCommission' => 10,
            'takerCommission' => 10,
            'buyerCommission' => 0,
            'sellerCommission' => 0,
            'commissionRates' =>
                array (
                    'maker' => '0.00100000',
                    'taker' => '0.00100000',
                    'buyer' => '0.00000000',
                    'seller' => '0.00000000',
                ),
            'canTrade' => true,
            'canWithdraw' => true,
            'canDeposit' => true,
            'brokered' => false,
            'requireSelfTradePrevention' => false,
            'preventSor' => false,
            'updateTime' => 1717881230820,
            'accountType' => 'SPOT',
            'balances' =>
                array (
                    0 =>
                        array (
                            'asset' => 'BTC',
                            'free' => '0.00000000',
                            'locked' => '0.00000000',
                        ),
                    1 =>
                        array (
                            'asset' => 'FDUSD',
                            'free' => '10000.00000000',
                            'locked' => '0.00000000',
                        ),
                    2 =>
                        array (
                            'asset' => 'ETH',
                            'free' => '0',
                            'locked' => '0.00000000',
                        ),
                ),
            'permissions' =>
                array (
                    0 => 'SPOT',
                ),
            'uid' => rand(),
        ));
    }

    /**
     * Match internal binance orders if price match.
     */
    public function __invoke(Trade $trade) : Trade
    {
        $this->trade = $trade;

        $trade->buyerOrderId  ??= 0;
        $trade->sellerOrderId ??= 0;

        foreach ($this->orders as $k => &$order)
        {
            if ($order->isFilled()) continue;

            if ($order->isBuy()) {
                if ($order instanceof OcoOrder && $trade->price >= $order->getStopPrice()) {
                    $filled = $order->getStopOrder();
                    $filled->status = 'FILLED';
                    $order->getLimitOrder()->status = 'EXPIRED';
                    $order->listOrderStatus = 'ALL_DONE';
                    $order->listStatusType = 'ALL_DONE';
                }
                elseif ($trade->price <= $order->price) {
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
                $filled->cummulativeQuoteQty = $filled->origQty * $trade->price;
                $trade->buyerOrderId = $filled->orderId;
                $trade->quantity = $filled->origQty;
                $this->account->quoteAsset->locked  = bcsub($this->account->quoteAsset->locked, bcmul($filled->origQty, $trade->price));
                $this->account->baseAsset->free     = bcadd($this->account->baseAsset->free, $filled->origQty);
            }
            else if ($order->isSell()) {
                if ($order instanceof OcoOrder && $trade->price <= $order->stopPrice) {
                    $order = $order->getStopOrder();
                    continue;

                    $filled = $order->getStopOrder();
                    $filled->status = 'FILLED';
                    $order->getLimitOrder()->status = 'EXPIRED';
                    $order->listOrderStatus = 'ALL_DONE';
                    $order->listStatusType = 'ALL_DONE';
                    $this->account->quoteAsset->free    = bcadd($this->account->quoteAsset->free, bcmul($filled->origQty, $trade->price));
                    $this->account->baseAsset->locked   = bcsub($this->account->baseAsset->locked, $filled->origQty);
                }
                elseif ($order instanceof StopOrder && $trade->price <= $order->stopPrice) {
                    $order = $this->stopToLimit($order);
                    continue;
                }
                elseif ($trade->price >= $order->price) {
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
                    $this->account->quoteAsset->free    = bcadd($this->account->quoteAsset->free, bcmul($filled->origQty, $trade->price));
                    $this->account->baseAsset->locked   = bcsub($this->account->baseAsset->locked, $filled->origQty);
                }
                else continue;

                $filled->executedQty = $filled->origQty;
                $filled->cummulativeQuoteQty = $filled->origQty * $trade->price;
                $trade->sellerOrderId = $filled->orderId;
                $trade->quantity = $filled->origQty;
            }
        }
        return $trade;
    }

    public function getAccount(array $params = null): Account
    {
        return $this->account;
    }

    public function getOpenOrders(): array
    {
        return $this->orders;
    }

    public function post(AbstractOrder $order): AbstractOrder
    {
        // filter MIN_NOTIONAL, check balance is enough
        if (isset($order->quantity)) {
            if (isset($order->price)) {
                $price = $order instanceof OcoOrder ? $order->stopPrice : $order->price;
            }
            else $price = $this->trade->price;
            $quote = bcmul($price, $order->quantity, 5);
            $qty   = $order->quantity;
        }
        else {
            $quote = $order->quoteOrderQty;
            $qty   = bcdiv($quote, $this->trade->price, 5);
        }
        if ($quote <= 10)
            throw new \LogicException('Too low quantity');
        if ($order->isBuy()         && $quote > $this->account->quoteAsset->free + 0.01)    // php think 0.9002 > 0.9002 :(
            throw new InsuficcientBalance("Tried to buy $quote");
        else if ($order->isSell()   && $qty   > $this->account->baseAsset->free + 0.00001)
            throw new InsuficcientBalance("Tried to sell $qty");

        // transform order to be like api reply
        if ($order instanceof MarketOrder) {
            $this->fill($order);
            $quote = truncate($order->cummulativeQuoteQty, 2);
            $qty = $order->getQty();
            if ('BUY' == $order->side) {
                $this->account->quoteAsset->free    = bcsub($this->account->quoteAsset->free, $quote);
                $this->account->baseAsset->free     = bcadd($this->account->baseAsset->free, $qty);
            }
            else if ('SELL' == $order->side) {
                $this->account->baseAsset->free     = bcsub($this->account->baseAsset->free, $qty);
                $this->account->quoteAsset->free    = bcadd($this->account->quoteAsset->free, $quote);
            }
            return $order;
        }
        else if ($order instanceof OcoOrder) {
            if (($order->isSell() && ($order->stopPrice >= $this->trade->price || $order->stopPrice > $order->price))
                || ($order->isBuy()  && ($order->stopPrice <= $this->trade->price || $order->stopPrice < $order->price))
                || ($order->isBuy() && $order->price < $this->trade->price)
                || ($order->isSell() && $order->price < $this->trade->price)
            )
                throw new BinanceException('Stop price is higher than market.');

            $this->buildOco($order);
        }
        else {
            $order->status = 'NEW';
            $order->origQty = $order->quantity;
            $order->executedQty = $order->cummulativeQuoteQty = 0;
            $order->transactTime = $this->trade->tradeTime->getTimestamp() * 1000;
        }
        $order->orderId = intval(microtime(true) * 10000) + rand();

        // update balances
        if ('BUY' == $order->side) {
            $this->account->quoteAsset->free    = bcsub($this->account->quoteAsset->free, $quote);
            $this->account->quoteAsset->locked  = bcadd($this->account->quoteAsset->locked, $quote);
        }
        else if ('SELL' == $order->side) {
            $this->account->baseAsset->free     = bcsub($this->account->baseAsset->free, $qty);
            $this->account->baseAsset->locked   = bcadd($this->account->baseAsset->locked, $qty);
        }

        if (!$order instanceof MarketOrder)
            $this->orders[] = clone $order;

        return $order;
    }

    public function buildOco(OcoOrder $order)
    {
        $order->orderListId = intval(microtime(true) * 10000) + rand();
        $order->contingencyType = 'OCO';
        $order->listStatusType = 'EXEC_STARTED';
        $order->listOrderStatus = 'EXECUTING';
        $order->transactionTime = $this->trade->time->getTimestamp() * 1000;
        $order->orders = [];

        for ($i = 0; $i < 2; $i++) {
            if ($i == 0) {
                $o = new StopOrder([
                    'symbol' => $order->symbol,
                    'side' => $order->side,
                    'quantity' => $order->quantity,
                    'stopPrice' => $order->stopPrice,
                    'price' => $order->stopLimitPrice
                ]);
            }
            else {
                $o = new LimitMakerOrder();
                $o->price = $order->price;
            }
            $o->side = $order->side;
            $o->orderListId = $order->orderListId;
            $o->orderId = $order->orderListId + $i + 1;
            $o->clientOrderId = 'mock';
            $o->transactTime = $order->transactionTime;
            $o->status = 'NEW';
            $o->origQty = $order->quantity;
            $o->executedQty = $o->cummulativeQuoteQty = 0;
            $order->orderReports[$i] = $o;
        }

        return $order;
    }

    public function cancel(int|AbstractOrder $order): AbstractOrder
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
            $this->account->quoteAsset->free    = bcadd($this->account->quoteAsset->free, $quote);
            $this->account->quoteAsset->locked  = bcsub($this->account->quoteAsset->locked, $quote);
        }
        if ($order->isSell()) {
            $this->account->baseAsset->free     = bcadd($this->account->baseAsset->free, $qty);
            $this->account->baseAsset->locked   = bcsub($this->account->baseAsset->locked, $qty);
        }

        return $order;
    }

    protected function fill(AbstractOrder $order)
    {
        if ($order->isSell()) {
            // sell orders always have quantity
            $quote  = $order->quantity * $this->trade->price;
            $qty    = $order->quantity;
        }
        else {
            $quote  = $order->quoteOrderQty;
            $qty    = round($quote / $this->trade->price, 5);
        }
        $order->origQty = $order->executedQty = $order->quantity = $qty;
        $order->cummulativeQuoteQty = min(
            $quote,
            bcmul( $order->origQty, $this->trade->price, 5)
        );
        $order->status = 'FILLED';
        $order->orderId = intval(microtime(true) * 10000) + rand();

        if ($order instanceof MarketOrder) {
            $order->fills = [
                ['price' => $this->trade->price, 'qty' => $qty]
            ];
        }
    }

    public function request(Request $req, string $security = self::SEC_SIGNED): array
    {
        throw new \LogicException('MockSpotApi is not supposed to make real requests.');
    }

    private function stopToLimit(StopOrder $stopOrder) : LimitOrder
    {
        $order = new LimitOrder([
            'symbol' => $stopOrder->symbol,
            'side' => $stopOrder->side,
            'origQty' => $stopOrder->origQty,
            'price' => $stopOrder->price,
            'newOrderRespType' => $stopOrder->newOrderRespType,
            'status' => $stopOrder->status,
            'orderId' => $stopOrder->orderId,
        ]);
        return $order;
    }
}
