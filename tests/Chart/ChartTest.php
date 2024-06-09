<?php
namespace Binance\Test;

use Binance\Chart\Chart;
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
}
