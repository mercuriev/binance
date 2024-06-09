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
