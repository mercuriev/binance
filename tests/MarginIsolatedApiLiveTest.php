<?php

use Binance\AbstractApi;
use Binance\AbstractPayload;
use Binance\Account\MarginIsolatedAccount;
use Binance\Entity\ExchangeInfo;
use Binance\Exception\InsuficcientBalance;
use Binance\MarginIsolatedApi;
use Binance\Order\AbstractOrder;
use Binance\Order\LimitOrder;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use function Binance\truncate;

class MarginIsolatedApiLiveTest extends TestCase
{
    protected static MarginIsolatedApi $api;

    const SYMBOL = 'BTCFDUSD';

    static private AbstractOrder $order;
    private static MarginIsolatedAccount $account;
    private static array $openOrders;

    static public function setUpBeforeClass() : void
    {
        // live api
        $key = getenv('API_KEY');
        if (!$key) throw new \RuntimeException('Expecting API_KEY in env.');
        $secret = getenv('API_SECRET');
        if (!$secret) throw new \RuntimeException('Expecting API_SECRET in env.');

        self::$api = new MarginIsolatedApi([
            'key'       => [$key, $secret],
            'symbol'    => self::SYMBOL
        ]);
    }

    public function testExchangeInfo()
    {
        $exchangeInfo = self::$api->exchangeInfo();
        $this->assertInstanceOf(ExchangeInfo::class, $exchangeInfo);
        $filter = $exchangeInfo->getFilter(self::SYMBOL, 'MAX_NUM_ALGO_ORDERS');
        $this->assertIsArray($filter);
    }

    public function testAccount()
    {
        self::$account = self::$api->getAccount(self::SYMBOL);
        $this->assertInstanceOf(MarginIsolatedAccount::class, self::$account);
    }

    #[Depends('testAccount')]
    public function testBorrow()
    {
        $api = self::$api;

        $max = $api->maxBorrowable('FDUSD');

        if ($max < 10) {
            if (self::$account->quoteAsset->borrowed > 10) { // already borrowed last run
                $max = self::$account->quoteAsset->borrowed;
            }
            else {
                $this->assertGreaterThan(10, $max, 'Unable to borrow on isolated '.self::SYMBOL);
            }
        }
        else {
            $id = $api->borrow('FDUSD', $max);
            $this->assertGreaterThan(0, $id);
        }
    }

    #[Depends('testAccount')]
    public function testOpenOrders()
    {
        self::$openOrders = self::$api->getOpenOrders();
        $this->assertContainsOnlyInstancesOf(LimitOrder::class, self::$openOrders);
    }

    #[Depends('testBorrow')]
    public function testPostLimit()
    {
        $order = new LimitOrder();
        $order->symbol = 'BTCFDUSD';
        $order->price = 40000;
        $order->side = 'BUY';
        $order->quantity = truncate(self::$account->quoteAsset->free / 40000, 5);

        try {
            self::$api->post($order);
            $this->assertGreaterThan(1, $order->getId());
        }
        catch (InsuficcientBalance $e) {
            echo $e->getMessage();
            // order exists
            if (self::$openOrders[0]) {
                $order = self::$api->cancel(self::$openOrders[0]['orderId']);
            }
        }

        self::$order = $order;
    }

    #[Depends('testPostLimit')]
    public function testRefresh()
    {
        $order = self::$api->get(self::$order);
        $this->assertIsNumeric($order->getId());
    }

    #[Depends('testRefresh')]
    public function testCancelAll()
    {
        self::$api->cancelAll();
        self::$order = self::$api->get(self::$order);
        $this->assertTrue(self::$order->isCanceled());
    }

    #[Depends('testCancelAll')]
    public function testRepay()
    {
        $repay = self::$api->repay('FDUSD', 10);
        $this->assertGreaterThan(0, $repay);
    }
}
