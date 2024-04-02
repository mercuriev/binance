<?php

namespace Binance;

use Binance\Exception\BinanceException;
use Laminas\Http\Client;
use Laminas\Http\Request;
use Laminas\ServiceManager\ServiceManager;

/**
 * APIs are grouped similar to documentation.
 * Each API instance have its own keys and symbol.
 * That allows to separate keys by section.
 */
abstract class AbstractApi
{
    const API_URL = 'https://api.binance.com/';
    const API_PATH = 'api/v3/';

    const SEC_NONE          = 'NONE';           // neither key nor sign message
    const SEC_USER_STREAM   = 'USER_STREAM';    // valid API key in header
    const SEC_MARKET_DATA   = 'MARKET_DATA';    // valid API key in header
    const SEC_TRADE         = 'TRADE';          // API key and sign HTTP body
    const SEC_MARGIN        = 'MARGIN';         // API key and sign HTTP body
    const SEC_USER_DATA     = 'USER_DATA';      // API key and sign HTTP body
    const SEC_SIGNED        = 'SIGNED';         // group for signed sec types (3 above)

    private Client $client;
    private string $key;
    private string $secret;
    private int $timeout;
    private int $recvWidnow;

    /**
     * @param ServiceManager $sm    Typehint is omitted to allow unit tests to run the method.
     * @param $name
     * @param array $options
     * @return static
     */
    static public function factory($sm, $name) : static
    {
        $config = $sm->get('config');
        return new static($config[self::class] ?? []);
    }

    public function __construct(array $config)
    {
        $this->key          = $config['key'][0]     ?? '';
        $this->secret       = $config['key'][1]     ?? '';
        $this->timeout      = $config['timeout']    ??  5;
        $this->recvWidnow   = $config['recvWindow'] ?? 60000;

        $this->client = new Client(
            static::API_URL . static::API_PATH,
            ['maxredirects' => 0, 'timeout' => 10]
        );
    }

    static protected function buildRequest(
        string $method,
        string $endpoint,
        array  $params = []
    ) : Request
    {
        $req = new Request();
        $req->setMethod($method);
        $req->setUri(static::API_URL . static::API_PATH . $endpoint);
        if ($params) {
            switch (strtoupper($method)) {
                case 'GET':
                    $req->getQuery()->exchangeArray($params);
                    break;
                case 'POST':
                case 'PUT':
                case 'DELETE':
                    $req->getPost()->exchangeArray($params);
                    break;
            }
        }
        return $req;
    }

    /**
     * @throws BinanceException
     */
    public function request(Request $req, string $security = self::SEC_SIGNED) : array
    {
        if ($security != self::SEC_NONE) {
            $req->getHeaders()->addHeaderLine('X-MBX-APIKEY', $this->key);
        }

        // after signature body params MUST NOT be changed
        switch ($security) {
            case self::SEC_SIGNED:
            case self::SEC_MARGIN:
            case self::SEC_USER_DATA:
            case self::SEC_TRADE:
                $params = $req->isGet() ? $req->getQuery() : $req->getPost();
                $params['timestamp']  = time() * 1000;
                if (isset($this->recvWidnow)) {
                    $params['recvWindow'] = $this->timeout * 1000 + 500;
                }
                $params['signature'] = hash_hmac('sha256', http_build_query($params->getArrayCopy()), $this->secret);
        }

        //
        $res = $this->client->send($req);
        if ($res->getStatusCode() >= 200 && $res->getStatusCode() <= 299) {
            return json_decode($res->getBody(), true);
        }
        else throw new BinanceException($req, $res);
    }
}
