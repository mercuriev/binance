<?php

namespace Binance;

use Binance\Entity\ExchangeInfo;
use Binance\Event\Trade;
use Binance\Exception\BinanceException;
use DateTime;

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

    /**
     * Returns an array of historical trades for a given symbol starting from a specified date.
     *
     * @param string $symbol The symbol to retrieve historical trades for.
     * @param ?DateTime $since The starting date from which to retrieve trades.
     * @return array An array of historical trade objects.
     * @throws \InvalidArgumentException If the specified "since" date is in the future.
     * @throws BinanceException
     *
     */
    public function getHistoricalTrades(string $symbol, \DateTime $since = null): array
    {
        if ($since && $since > (new DateTime('now'))) {
            throw new \InvalidArgumentException('Since date must be in the past.');
        }

        $params = [
            'symbol' => $symbol,
            'limit' => 1000,
        ];
        $trades = [];
        do {
            $req = self::buildRequest('GET', 'historicalTrades', $params);
            $res = $this->request($req, self::SEC_NONE);
            $res = array_reverse($res);
            foreach ($res as $v) {
                $trade = new Trade([]);
                $trade->id = $v['id'];
                $trade->symbol = $symbol;
                $trade->price = $v['price'];
                $trade->quantity = $v['qty'];
                $trade->time = $trade->tradeTime = new \DateTime("@" . intval($v['time'] / 1000));
                $trade->buyerIsMaker = $v['isBuyerMaker'];
                // no order id in response
                array_unshift($trades, $trade);

                if (!$since || $since > $trade->tradeTime) {
                    return $trades;
                }
            }
            /** @noinspection PhpUndefinedVariableInspection */
            $params['fromId'] = $v['id'];
        }
        while(true);
    }
}
