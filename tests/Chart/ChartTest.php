<?php
namespace Binance\Test;

use Binance\Chart\Chart;
use Binance\MarketDataApi;
use DateTime;
use PHPUnit\Framework\TestCase;

class ChartTest extends TestCase
{
    public function testBuildWithHistory()
    {
        $sut = Chart::buildWithHistory('BTCFDUSD');
        $this->assertInstanceOf(Chart::class, $sut);
        $this->assertGreaterThan(0, count($sut->s));
        $this->assertGreaterThan(0, count($sut->m));
    }

    public function testAppend()
    {
        $sut = new Chart();
        $api = new MarketDataApi([]);
        $trades = $api->getHistoricalTrades('BNBFDUSD', new DateTime('10 minutes ago')); // last 1000 trades
        foreach ($trades as $trade) {
            $sut->append($trade);
        }
        $this->assertGreaterThanOrEqual($sut->s::SIZE, count($sut->s));
        $this->assertGreaterThanOrEqual(10, count($sut->m));
    }
}
