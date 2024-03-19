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
use WebSocket\TimeoutException;

/**
 * This extends AbstractApi to enable factory only.
 * Websockets use another HTTP client.
 */
class WebsocketsApi extends AbstractApi
{
    const API_URL = 'wss://stream.binance.com:9443';
    const API_PATH = '/ws/bookTicker';

    const INTERVAL_1S = '1s';
    const INTERVAL_1M = '1m';
    const INTERVAL_3M = '3m';
    const INTERVAL_5M = '5m';
    const INTERVAL_15M = '15m';
    const INTERVAL_30M = '30m';
    const INTERVAL_1H = '1h';
    const INTERVAL_2H = '2h';
    const INTERVAL_4H = '4h';
    const INTERVAL_6H = '6h';
    const INTERVAL_8H = '8h';
    const INTERVAL_12H = '12h';
    const INTERVAL_1D = '1d';
    const INTERVAL_3D = '3d';
    const INTERVAL_1W = '1w';
    const INTERVAL_ONE_MONTH = '1M';

    protected Client $ws;

    public function __construct()
    {
        $this->ws = new Client(static::API_URL . static::API_PATH);
    }

    /**
     * Fetch the next incoming message from stream.
     */
    public function __invoke(int $timeout = 5) : null|bool|Event
    {
        $this->ws->setTimeout($timeout);
        try {
            $res = $this->ws->receive();
        }
        catch (TimeoutException) {
            return null;
        }

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

    public function receive() : bool|Event
    {
        return ($this)();
    }

    /**
     * Generic raw access to topics. See other methods for clear interface.
     *
     * @param string|array $topics
     * @return bool
     * @throws \RuntimeException|\WebSocket\BadOpcodeException
     */
    public function subscribe(string|array $topics) : bool
    {
        $topics = is_array($topics) ? $topics : [$topics];
        $topics = array_map('strtolower', $topics);
        $payload = [
            'id' => null,
            'method' => 'SUBSCRIBE',
            'params' => $topics
        ];
        $this->ws->send(json_encode($payload));

        if (true === $this()) {
            return true; // API acked subscription
        } else {
            throw new \RuntimeException("Failed to subscribe");
        }
    }

    public function kline(string $symbol, string $interval)
    {
        return $this->subscribe("$symbol@kline_$interval");
    }
}
