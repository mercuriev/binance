<?php

namespace Binance;

use Binance\Exception\BinanceException;
use Psr\Http\Message\ResponseInterface;

class MarketDataApi extends AbstractApi
{
    protected function ping()
    {
        $req = self::buildRequest('GET', 'ping');
        return $this->request($req, self::SEC_NONE);
    }

    protected function getServerTime()
    {
        $req = self::buildRequest('GET', 'time');
        return $this->request($req, self::SEC_NONE);
    }

    protected function getOrderBook($params)
    {
        $req = self::buildRequest('GET', 'depth', $params);
        return $this->request($req, self::SEC_NONE);
    }

    protected function getAggTrades($params)
    {
        $req = self::buildRequest('GET', 'aggTrades', $params);
        return $this->request($req, self::SEC_NONE);
    }

    protected function getTwentyFourTickerPrice($params)
    {
        $req = self::buildRequest('GET', 'ticker/24hr', $params);
        return $this->request($req, self::SEC_NONE);
    }

    protected function getTickers()
    {
        $req = self::buildRequest('GET', 'ticker/allPrices');
        return $this->request($req, self::SEC_NONE);
    }

    protected function getBookTickers()
    {
        $req = self::buildRequest('GET', 'ticker/allBookTickers');
        return $this->request($req, self::SEC_NONE);
    }

    protected function getPrice($params)
    {
        $req = self::buildRequest('GET', 'ticker/price', $params);
        return $this->request($req, self::SEC_NONE);
    }

    protected function getKlines($params)
    {
        $req = self::buildRequest('GET', 'klines', $params);
        return $this->request($req, self::SEC_NONE);
    }
}
