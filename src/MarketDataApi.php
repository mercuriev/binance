<?php

namespace Binance;

use Binance\Entity\ExchangeInfo;
use Binance\Exception\BinanceException;
use Psr\Http\Message\ResponseInterface;

class MarketDataApi extends AbstractApi
{
    public function ping(): array
    {
        $req = self::buildRequest('GET', 'ping');
        return $this->request($req, self::SEC_NONE);
    }

    public function getExchangeInfo(): ExchangeInfo
    {
        $req = self::buildRequest('GET', 'exchangeInfo');
        $res = $this->request($req, self::SEC_NONE);
        return new ExchangeInfo($res);
    }

    public function getServerTime(): array
    {
        $req = self::buildRequest('GET', 'time');
        return $this->request($req, self::SEC_NONE);
    }

    public function getOrderBook($params): array
    {
        $req = self::buildRequest('GET', 'depth', $params);
        return $this->request($req, self::SEC_NONE);
    }

    public function getAggTrades($params): array
    {
        $req = self::buildRequest('GET', 'aggTrades', $params);
        return $this->request($req, self::SEC_NONE);
    }

    public function getTwentyFourTickerPrice($params): array
    {
        $req = self::buildRequest('GET', 'ticker/24hr', $params);
        return $this->request($req, self::SEC_NONE);
    }

    public function getTickers(): array
    {
        $req = self::buildRequest('GET', 'ticker/allPrices');
        return $this->request($req, self::SEC_NONE);
    }

    public function getBookTickers(): array
    {
        $req = self::buildRequest('GET', 'ticker/allBookTickers');
        return $this->request($req, self::SEC_NONE);
    }

    public function getPrice($params): array
    {
        $req = self::buildRequest('GET', 'ticker/price', $params);
        return $this->request($req, self::SEC_NONE);
    }

    public function getKlines($params): array
    {
        $req = self::buildRequest('GET', 'klines', $params);
        return $this->request($req, self::SEC_NONE);
    }
}
