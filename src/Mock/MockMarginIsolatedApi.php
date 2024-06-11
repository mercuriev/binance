<?php
namespace Binance;

use Binance\Account\Account;
use Binance\Event\Trade;
use Binance\Mock\MockSpotApi;
use Binance\Order\OcoOrder;

class MockMarginIsolatedApi extends MockSpotApi
{
    /**
     * @param float $fiat Starting balance
     * @param float $crypto Starting balance
     */
    public function __construct(float $fiat, float $crypto)
    {
        $this->account = new Account([
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
                $this->account['quoteAsset']['locked']  = bcsub($this->account['quoteAsset']['locked'], bcmul($filled->origQty, $trade['p']));
                $this->account['baseAsset']['free']     = bcadd($this->account['baseAsset']['free'], $filled->origQty);
            }
            else if ($order->isSell()) {
                if ($order instanceof OcoOrder && $trade['p'] <= $order->stopPrice) {
                    $filled = $order->getStopOrder();
                    $filled->status = 'FILLED';
                    $order->getLimitOrder()->status = 'EXPIRED';
                    $order->listOrderStatus = 'ALL_DONE';
                    $order->listStatusType = 'ALL_DONE';
                    $this->account['quoteAsset']['free']    = bcadd($this->account['quoteAsset']['free'], bcmul($filled->origQty, $trade['p']));
                    $this->account['baseAsset']['locked']   = bcsub($this->account['baseAsset']['locked'], $filled->origQty);
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
                    $this->account['quoteAsset']['free']    = bcadd($this->account['quoteAsset']['free'], bcmul($filled->origQty, $trade['p']));
                    $this->account['baseAsset']['locked']   = bcsub($this->account['baseAsset']['locked'], $filled->origQty);
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
        $this->account['indexPrice'] = isset($this->now) ? $this->now['p'] : 1000000;
        $this->account['quoteAsset']['netAssetOfBtc'] = round(
            ($this->account['quoteAsset']['free'] + $this->account['quoteAsset']['locked']) / $this->account['indexPrice'],
            5
        );
        $this->account['baseAsset']['netAssetOfBtc'] = round(
            $this->account['baseAsset']['free'] + $this->account['baseAsset']['locked'], 5
        );

        return ['assets' => [(array)$this->account]];
    }
}
