<?php
namespace Binance;

use Binance\Account\Account;
use Binance\Exception\BinanceException;
use Binance\Exception\InsuficcientBalance;
use Binance\Exception\InvalidPrices;
use Binance\Exception\StopPriceTrigger;
use Binance\Order\AbstractOrder;
use Binance\Order\LimitOrder;
use Binance\Order\OcoOrder;

class SpotApi extends AbstractApi
{
    /**
     * @var string Mandatory to set before usage.
     */
    public string $symbol;

    public function __construct(array $config)
    {
        parent::__construct($config);
        if (isset($config['symbol'])) {
            $this->symbol = $config['symbol'];
        }
    }

    /**
     * Returns current account information.
     *
     * @param array|null $params The data to send.
     * @return Account
     * @throws BinanceException
     * @link https://binance-docs.github.io/apidocs/spot/en/#account-information-user_data
     */
    public function getAccount(array $params = null) : Account
    {
        $params = array_merge([
            'omitZeroBalances' => 'true',
            'timestamp' => time() * 1000
        ], $params ?? []);
        $req = self::buildRequest('GET', 'account', $params);
        $res = $this->request($req, self::SEC_SIGNED);
        $account = new Account($res);
        if (isset($this->symbol)) {
            $account->selectAssetsFor($this->symbol);
        }
        return $account;
    }

    public function getOpenOrders() : array
    {
        $req = self::buildRequest('GET', 'openOrders', [
            'symbol' => $this->symbol
        ]);
        $res = $this->request($req, self::SEC_USER_DATA);
        foreach ($res as &$o) {
            $o = AbstractOrder::fromApi($o);
        }

        return $res;
    }

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

    public function post(AbstractOrder $order) : AbstractOrder
    {
        if ($order->quantity == 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

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

    public function cancel(int|AbstractOrder $order): AbstractOrder
    {
        if (is_int($order)) {
            $order = new LimitOrder(['orderId' => $order]);
        }
        else {
            if ($order->isCanceled() || $order->isFilled()) return $order;
        }

        $params = [];
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
     * @return int
     * @throws BinanceException
     */
    public function cancelAll(string $clientIdPrefix = '') : int
    {
        $res = $this->getOpenOrders();

        $done = 0;
        foreach($res as $order) {
            if (str_starts_with($order->getClientOrderId(), $clientIdPrefix)) {
                $this->cancel($order);
                $done++;
            }
        }
        return $done;
    }
}
