<?php

namespace Binance\Mock;

use Binance\Account\Account;
use Binance\Event\Trade;
use Binance\Order\OcoOrder;
use Binance\SpotApi;
use Laminas\Http\Request;

class MockSpotApi extends SpotApi
{
    private Account $account;
    private Trade $now;
    private array $orders = [];

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
        $this->now = $trade;

        foreach ($this->orders as $k => $order)
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
                $trade['b'] = $filled->orderId;
                $trade['q'] = $filled->origQty;
                $this->account->quoteAsset->locked  = bcsub($this->account->quoteAsset->locked, bcmul($filled->origQty, $trade->price));
                $this->account->baseAsset->free     = bcadd($this->account->baseAsset->free, $filled->origQty);
            }
            else if ($order->isSell()) {
                if ($order instanceof OcoOrder && $trade->price <= $order->stopPrice) {
                    $filled = $order->getStopOrder();
                    $filled->status = 'FILLED';
                    $order->getLimitOrder()->status = 'EXPIRED';
                    $order->listOrderStatus = 'ALL_DONE';
                    $order->listStatusType = 'ALL_DONE';
                    $this->account->quoteAsset->free    = bcadd($this->account->quoteAsset->free, bcmul($filled->origQty, $trade->price));
                    $this->account->baseAsset->locked   = bcsub($this->account->baseAsset->locked, $filled->origQty);
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
                $trade['a'] = $filled->orderId;
                $trade['q'] = $filled->origQty;
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

    public function request(Request $req, string $security = self::SEC_SIGNED): array
    {
        throw new \LogicException('MockSpotApi is not supposed to make real requests.');
    }
}
