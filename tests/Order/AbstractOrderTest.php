<?php
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Binance\Event\Trade;
use Binance\Order\AbstractOrder;
use Binance\Order\LimitOrder;
use Binance\Order\StopOrder;

class AbstractOrderTest extends TestCase
{
    #[DataProvider('providerForTestMatch')]
    public function testMatch($side, $orderId, $expectedStatus, $trade, $orderClass)
    {
        /* Here we create an instance of Order class directly without mocking */
        $order = new $orderClass();
        $order->side = $side;
        $order->orderId = $orderId;
        $order->origQty = 5;
        $order->cummulativeQuoteQty = 10000;

        $result = $order->match($trade);

        if ($expectedStatus) {
            $this->assertSame($expectedStatus, $result->status);
        } else {
            $this->assertNull($result);
        }
    }

    static public function providerForTestMatch()
    {
        $trade1 = new Trade([
            'E' => time() * 1000,  // Event time.
            's' => 'BTCUSDT',      // Symbol.
            't' => 1,              // Trade ID.
            'p' => '3.0',          // Price.
            'q' => '2',            // Quantity.
            'b' => 1,              // Buyer order ID.
            'a' => 2,              // Seller order ID.
            'T' => time() * 1000,  // Trade time in milliseconds.
            'm' => false           // Buyer is maker flag.
        ]);

        $trade2 = new Trade([
            'E' => time() * 1000,  // Event time.
            's' => 'BTCUSDT',      // Symbol.
            't' => 3,              // Trade ID.
            'p' => '3.0',          // Price.
            'q' => '2',            // Quantity.
            'b' => 3,              // Buyer order ID.
            'a' => 4 ,             // Seller order ID.
            'T' => time() * 1000,  // Trade time in milliseconds.
            'm' => false           // Buyer is maker flag.
        ]);

        $trade3 = new Trade([
            'E' => time() * 1000,  // Event time.
            's' => 'BTCUSDT',      // Symbol.
            't' => 5,              // Trade ID.
            'p' => '3.0',          // Price.
            'q' => 5,              // Quantity.
            'b' => 5,              // Buyer order ID.
            'a' => 6,              // Seller order ID.
            'T' => time() * 1000,  // Trade time in milliseconds.
            'm' => false           // Buyer is maker flag.
        ]);

        $notMatchingTrade = new Trade([
            'E' => time() * 1000,  // Event time.
            's' => 'BTCUSDT',      // Symbol.
            't' => 7,              // Trade ID.
            'p' => '3.0',          // Price.
            'q' => '1',            // Quantity.
            'b' => 7,              // Buyer order ID.
            'a' => 8,              // Seller order ID.
            'T' => time() * 1000,  // Trade time in milliseconds.
            'm' => false           // Buyer is maker flag.
        ]);

        $anotherNotMatchingTrade = new Trade([
            'E' => time() * 1000,  // Event time.
            's' => 'BTCUSDT',      // Symbol.
            't' => 10,             // Trade ID.
            'p' => '3.0',          // Price.
            'q' => '1',            // Quantity.
            'b' => 10,             // Buyer order ID.
            'a' => 11,             // Seller order ID.
            'T' => time() * 1000,  // Trade time in milliseconds.
            'm' => false           // Buyer is maker flag.
        ]);

        return [
            ['SELL',   9, null,                  $trade1,                   LimitOrder::class],
            ['SELL',  10, null,                  $anotherNotMatchingTrade,  LimitOrder::class],
            ['BUY',    9, null,                  $trade3,                   StopOrder::class],
            ['BUY',   10, null,                  $notMatchingTrade,         StopOrder::class],
            ['SELL',   2, 'FILLED_PARTIALLY',    $trade1,                   LimitOrder::class],
            ['BUY',    3, 'FILLED_PARTIALLY',    $trade2,                   LimitOrder::class],
            ['SELL',   6, 'FILLED',              $trade3,                   LimitOrder::class],
            ['BUY',    1, 'FILLED_PARTIALLY',    $trade1,                   StopOrder::class],
            ['SELL',   4, 'FILLED_PARTIALLY',    $trade2,                   StopOrder::class],
            ['BUY',    5, 'FILLED',              $trade3,                   StopOrder::class]
        ];
    }
}
