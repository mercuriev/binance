<?php
namespace Binance;

use Binance\Account\MarginIsolatedAccount;
use Binance\Entity\ExchangeInfo;
use Binance\Exception\BinanceException;
use Binance\Exception\ExceedBorrowable;
use Binance\Exception\InsuficcientBalance;
use Binance\Exception\InvalidPrices;
use Binance\Exception\StopPriceTrigger;
use Binance\Order\AbstractOrder;
use Binance\Order\LimitOrder;
use Binance\Order\MarketOrder;
use Binance\Order\OcoOrder;
use Binance\Order\StopOrder;

class MarginIsolatedApi extends AbstractApi
{
    const API_PATH = 'sapi/v1/margin/';

    const API_VERSION = 'sapi/v1/';

    public bool $isolated = true;

    /**
     * @var string Mandatory to set before usage.
     */
    public string $symbol;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->symbol = $config['symbol'] ?? '';
    }

    /**
     * @param string $asset
     * @param float $amount
     * @return int              Transaction ID if successful.
     * @throws ExceedBorrowable
     * @throws Exception\BinanceException
     */
    public function borrow(string $asset, float $amount) : int
    {
        $params = [];
        $params['symbol']       = $this->symbol;
        $params['isIsolated']   = $this->isolated ? 'TRUE' : 'FALSE';
        $params['asset']        = $asset;
        $params['amount']       = $amount;
        $params['type']         = 'BORROW';
        $req = static::buildRequest('POST', 'borrow-repay', $params);

        try {
            return $this->request($req, self::SEC_MARGIN)['tranId'];
        }
        catch (BinanceException $e) {
            if ($e->getCode() === -11008) {
                throw new ExceedBorrowable($e->req, $e->res);
            }
            else throw $e;
        }
    }

    /**
     * @param string $asset
     * @param float $amount
     * @return int              Transaction ID if successful.
     * @throws Exception\BinanceException
     */
    public function repay(string $asset, float $amount) : int
    {
        $params = [];
        $params['symbol']       = $this->symbol;
        $params['isIsolated']   = $this->isolated ? 'TRUE' : 'FALSE';
        $params['asset']        = $asset;
        $params['amount']       = $amount;
        $params['type']         = 'REPAY';
        $req = static::buildRequest('POST', 'borrow-repay', $params);
        return $this->request($req, self::SEC_MARGIN)['tranId'];
    }

    public function maxBorrowable(string $asset) : float
    {
        if (empty($asset)) throw new \InvalidArgumentException('Asset must be a token.');

        $params = ['asset' => $asset];
        if ($this->isolated) {
            if (empty($this->symbol)) throw new \RuntimeException('Symbol was not set.');
            $params['isolatedSymbol'] = $this->symbol;
        }
        $req = static::buildRequest('GET', 'maxBorrowable', $params);
        $res = $this->request($req, self::SEC_USER_DATA);

        return (float) $res['amount'];
    }

    /**
     * @param array|string|null $symbol Select up to 5 symbols or all if null
     *
     * @return MarginIsolatedAccount
     * @throws BinanceException|\InvalidArgumentException
     */
    public function getAccount(null|array|string $symbol = null) : MarginIsolatedAccount
    {
        if ($symbol && is_string($symbol)) $symbol = [$symbol];
        $params = $symbol ? ['symbols' => join(',', $symbol)] : [];
        $req = static::buildRequest('GET', 'isolated/account', $params);
        $res = $this->request($req, self::SEC_MARGIN);
        if ($res['assets']) {
            return new MarginIsolatedAccount($res['assets'][0]);
        } else {
            // empty response is only possible if filtered by non-existing symbol
            throw new \InvalidArgumentException('No such symbol ' . $params['symbols']);
        }
    }

    /**
     * @return AbstractOrder[]
     * @throws BinanceException
     */
    public function getOpenOrders() : array
    {
        $req = self::buildRequest('GET', 'openOrders', [
            'symbol' => $this->symbol, 'isIsolated' => $this->isolated ? 'TRUE' : 'FALSE'
        ]);
        $res = $this->request($req, self::SEC_USER_DATA);
        foreach ($res as &$o) {
            $o = AbstractOrder::fromApi($o);
        }

        return $res;
    }

    /**
     * @return LimitOrder|MarketOrder|OcoOrder|StopOrder
     */
    public function get(array|AbstractOrder $order): AbstractOrder
    {
        if ($order instanceof OcoOrder) {
            $endpoint = 'orderList';
            $params = ['symbol' => $order->symbol, 'orderListId' => $order->orderListId];
        }
        else {
            $endpoint = 'order';
            if (!is_array($order)) {
                $params = ['symbol' => $order->symbol,'orderId' => $order->orderId];
            } else $params = $order;
        }
        $req = self::buildRequest('GET', $endpoint, $params);
        $reply = $this->request($req, self::SEC_USER_DATA);

        is_array($order) ? $order = AbstractOrder::fromApi($reply) : $order->merge($reply);

        if ($order instanceof OcoOrder) {
            $order->orderReports = [];
            foreach ($order->orders as $o) {
                $order->orderReports[] = $this->get(['symbol' => $order->symbol, 'orderId' => $o['orderId']]);
            }
        }

        return $order;
    }

    public function post(LimitOrder|OcoOrder|StopOrder|MarketOrder $order) : LimitOrder|OcoOrder|StopOrder|MarketOrder
    {
        $endpoint = $order instanceof OcoOrder ? 'order/oco' : 'order';
        $params = (array) $order;
        try {
            $req = static::buildRequest('POST', $endpoint, $params);
            $res = $this->request($req, static::SEC_TRADE);
            $order->merge($res);
            return $order;
        }
        catch (BinanceException $e) {
            if (str_starts_with($e->getMessage(), 'Account has insufficient balance')) {
                throw new InsuficcientBalance($e->req, $e->res);
            }
            else if (str_starts_with($e->getMessage(), 'Stop price would trigger')) {
                throw new StopPriceTrigger($e->req, $e->res);
            }
            else if (str_starts_with($e->getMessage(), 'The relationship of the prices')) {
                throw new InvalidPrices($e->req, $e->res);
            }
            else throw $e;
        }
    }

    public function cancel(int|LimitOrder|OcoOrder|StopOrder $order): LimitOrder|OcoOrder|StopOrder
    {
        if (is_int($order)) {
            $order = new LimitOrder(['orderId' => $order]);
        }
        else {
            if ($order->isCanceled() || $order->isFilled()) return $order;
        }

        $params = ['isIsolated' => $this->isolated ? 'TRUE' : 'FALSE'];
        if ($order instanceof OcoOrder) {
            $endpoint = 'orderList';
            $params += ['symbol' => $order->symbol, 'orderListId' => $order->orderListId];
        }
        else {
            $endpoint = 'order';
            $params += ['symbol' => $order->symbol, 'orderId' => $order->orderId];
        }
        $req = self::buildRequest('DELETE', $endpoint, $params);
        $reply = $this->request($req, self::SEC_TRADE);
        $order->merge($reply);
        return $order;
    }

    /**
     * Prefix is used to cancel only orders placed by this software and let alone human orders from UI.
     *
     * @param string $clientIdPrefix
     * @param string $symbol
     * @return int
     * @throws BinanceException
     */
    public function cancelAll(string $clientIdPrefix = '') : int
    {
        $res = $this->getOpenOrders();

        $done = 0;
        foreach($res as $order) {
            if (str_starts_with($order->getClientOrderId(), $clientIdPrefix)) {
                /** @noinspection PhpParamsInspection */
                $this->cancel($order);
                $done++;
            }
        }
        return $done;
    }

    public function replace(LimitOrder|StopOrder|OcoOrder $cancel, LimitOrder|OcoOrder|StopOrder|MarketOrder $post) : AbstractOrder
    {
        if ($cancel->isFilled())        return $cancel;
        elseif (!$cancel->isCanceled()) $this->cancel($cancel);

        if ($cancel->isFilled()) {
            return $cancel;
        }

        if ($cancel->isPartiallyFilled()) {
            $post->quantity = truncate($post->quantity - $cancel->getExecutedQty(), 5);
        }
        return $this->post($post);
    }

    /**
     * Get /exchangeInfo for this SYMBOL on MARGIN
     *
     * @return array
     */
    public function exchangeInfo() : ExchangeInfo
    {
        $req = self::buildRequest('GET', 'exchangeInfo', [
            'symbol' => $this->symbol
        ]);
        $req->setUri(parent::API_URL . parent::API_PATH . 'exchangeInfo');
        $res = $this->request($req, self::SEC_NONE);
        return new ExchangeInfo($res);
    }
}
