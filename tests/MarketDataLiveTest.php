<?php

use Binance\Entity\ExchangeInfo;
use Binance\MarginIsolatedApi;
use Binance\MarketDataApi;
use PHPUnit\Framework\TestCase;

class MarketDataLiveTest extends TestCase
{
    protected static MarketDataApi $api;

    static public function setUpBeforeClass() : void
    {
        // live api
        $key = getenv('API_KEY');
        if (!$key) throw new \RuntimeException('Expecting API_KEY in env.');
        $secret = getenv('API_SECRET');
        if (!$secret) throw new \RuntimeException('Expecting API_SECRET in env.');

        self::$api = new MarketDataApi([
            'key'       => [$key, $secret]
        ]);
    }

    public function testExchangeInfo()
    {
        $foo = self::$api->getExchangeInfo();
        $this->assertInstanceOf(ExchangeInfo::class, $foo);
    }
}
