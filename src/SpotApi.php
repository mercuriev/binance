<?php

namespace Binance;

use Binance\AbstractApi;
use Psr\Http\Message\ResponseInterface;

class SpotApi extends AbstractApi
{
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
    public function getAccount(array $params = null)
    {
        $params = $params ?? ['timestamp' => time() * 1000];
        return $this->_makeApiRequest('GET', 'account', 'SIGNED', $params + ['timestamp' => time() * 1000]);
    }
}
