<?php
use App\Chart\Minutes;
use App\Trade;
use AppTest\AbstractTest;

final class MinutesTest extends AbstractTest
{
    public function setUp() : void
    {
    }

    public function testFactory()
    {
        $sut = $this->container->build(Minutes::class, []);
        $this->assertInstanceOf(Minutes::class, $sut);
        $this->assertGreaterThan(0, count($sut));
    }
}
