<?php

namespace Binance;

use Binance\Exception\BinanceException;
use Psr\Http\Message\ResponseInterface;

class MarketDataApi extends AbstractApi
{
    protected function ping(): array
    {
        $req = self::buildRequest('GET', 'ping');
        return $this->request($req, self::SEC_NONE);
    }

    protected function getServerTime(): array
    {
        $req = self::buildRequest('GET', 'time');
        return $this->request($req, self::SEC_NONE);
    }

    protected function getOrderBook($params): array
    {
        $req = self::buildRequest('GET', 'depth', $params);
        return $this->request($req, self::SEC_NONE);
    }

    protected function getAggTrades($params): array
    {
        $req = self::buildRequest('GET', 'aggTrades', $params);
        return $this->request($req, self::SEC_NONE);
    }

    protected function getTwentyFourTickerPrice($params): array
    {
        $req = self::buildRequest('GET', 'ticker/24hr', $params);
        return $this->request($req, self::SEC_NONE);
    }

    protected function getTickers(): array
    {
        $req = self::buildRequest('GET', 'ticker/allPrices');
        return $this->request($req, self::SEC_NONE);
    }

    protected function getBookTickers(): array
    {
        $req = self::buildRequest('GET', 'ticker/allBookTickers');
        return $this->request($req, self::SEC_NONE);
    }

    protected function getPrice($params): array
    {
        $req = self::buildRequest('GET', 'ticker/price', $params);
        return $this->request($req, self::SEC_NONE);
    }

    protected function getKlines($params): array
    {
        $req = self::buildRequest('GET', 'klines', $params);
        return $this->request($req, self::SEC_NONE);
    }
}
