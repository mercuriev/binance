<?php

namespace Binance;

use Binance\Event\AggTrade;
use Binance\Event\AvgPrice;
use Binance\Event\Depth;
use Binance\Event\Event;
use Binance\Event\Kline;
use Binance\Event\MiniTicker;
use Binance\Event\Ticker;
use Binance\Event\Trade;
use WebSocket\Client;

/**
 * This extends AbstractApi to enable factory only.
 * Websockets use another HTTP client.
 */
class WebsocketsApi extends AbstractApi
{
    const API_URL = 'wss://stream.binance.com:9443';
    const API_PATH = '/ws/bookTicker';

    protected Client $ws;

    public function __construct()
    {
        $this->ws = new Client(static::API_URL . static::API_PATH);
    }

    /**
     * Fetch the next incoming message from stream.
     */
    public function __invoke() : bool|Event
    {
        if (!$this->ws->isConnected()) throw new \RuntimeException('Lost connection to server.');

        $res = $this->ws->receive();

        if (is_numeric($res)) xdebug_break();

        $res = json_decode($res, true);

        // API acknowledged last request
        if (array_key_exists('result', $res) && $res['result'] === null) {
            return true;
        }

        return match (@$res['e']) {
            'aggTrade'          => new AggTrade($res),
            'trade'             => new Trade($res),
            'kline'             => new Kline($res),
            '24hrMiniTicker'    => new MiniTicker($res),
            'avgPrice'          => new AvgPrice($res),
            '1hTicker', '4hTicker', '24hrTicker', '1dTicker' => new Ticker($res),
            'depthUpdate'       => new Depth($res),
            null                => new Event($res)
        };
    }

    /**
     * @param string|array $topics
     * @return bool
     * @throws \RuntimeException|\WebSocket\BadOpcodeException
     */
    public function subscribe(string|array $topics) : bool
    {
        $payload = [
            'id' => null,
            'method' => 'SUBSCRIBE',
            'params' => is_array($topics) ? $topics : [$topics]
        ];
        $this->ws->send(json_encode($payload));

        if (true === $this()) {
            return true; // API acked subscription
        } else {
            throw new \RuntimeException("Failed to subscribe");
        }
    }
}
