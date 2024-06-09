<?php
namespace Binance;

use Binance\Account\Account;
use Binance\Exception\BinanceException;
use Binance\Order\AbstractOrder;
use Psr\Http\Message\ResponseInterface;

class SpotApi extends AbstractApi
{
    /**
     * @var string Mandatory to set before usage.
     */
    public string $symbol;

    /**
     * Returns current account information.
     *
     * @param array $params The data to send.
     *      @option int "timestamp"  A UNIX timestamp. (required)
     *      @option int "recvWindow" The number of milliseconds after timestamp the request is valid for.
     *
     * @return ResponseInterface
     *
     * @link https://www.binance.com/restapipub.html#account-information-signed
     */
    public function getAccount(array $params = null) : Account
    {
        $params = $params ?? ['timestamp' => time() * 1000];
        $req = self::buildRequest('GET', 'account', $params);
        $res = $this->request($req, self::SEC_SIGNED);
        return new Account($res);
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
