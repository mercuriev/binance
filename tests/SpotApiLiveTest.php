<?php
namespace Binance;

use Binance\Account\Account;
use Binance\Exception\InsuficcientBalance;
use Binance\Order\AbstractOrder;
use Binance\Order\LimitMakerOrder;
use Binance\Order\LimitOrder;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class SpotApiLiveTest extends TestCase
{
    protected static SpotApi $api;

    private const string TESTNET_KEY = '5117T7BqRsAyDkspJSvydMeP6XmvB9BExk1U0QKTit8eGJEH4uOF44Za7g1abTj1';
    private const string TESTNET_SECRET = 'HLa3dMGXbr9IXUBsdZiIG3ZNuNPTByTJRomCA2JbMxcxdH2z0NdTGSYmde6MuPl6';

    const string SYMBOL = 'BTCFDUSD';

    static private AbstractOrder $order;
    private static Account $account;
    private static array $openOrders;

    static public function setUpBeforeClass(): void
    {
        self::$api = new class([
            'key' => [
                self::TESTNET_KEY,
                self::TESTNET_SECRET
            ],
            'symbol' => self::SYMBOL
        ]) extends SpotApi {
            const API_URL = 'https://testnet.binance.vision/';
        };
    }

    public function testAccount()
    {
        self::$account = self::$api->getAccount();
        $this->assertInstanceOf(Account::class, self::$account);
        self::$account->selectAssetsFor(self::SYMBOL);
    }

    #[Depends('testAccount')]
    public function testOpenOrders()
    {
        self::$openOrders = self::$api->getOpenOrders();
        $this->assertContainsOnlyInstancesOf(LimitOrder::class, self::$openOrders);
    }

    #[Depends('testOpenOrders')]
    public function testCancelAll()
    {
        self::$api->cancelAll();
        $this->assertTrue(true);
    }

    #[Depends('testCancelAll')]
    public function testPostLimit()
    {
        $order = new LimitMakerOrder();
        $order->symbol = 'BTCFDUSD';
        if (self::$account->baseAsset->free > 0) {
            $order->side = 'SELL';
            $order->price = 100000;
            $order->quantity = truncate(self::$account->baseAsset->free, 5);
        }
        else if (self::$account->quoteAsset->free > 0) {
            $order->side = 'BUY';
            $order->price = 40000;
            $order->quantity = truncate(self::$account->quoteAsset->free / $order->price, 5);
        }
        else {
            // test fail
            $this->assertTrue(false);
        }

        try {
            self::$api->post($order);
            $this->assertGreaterThan(1, $order->getId());
        } catch (InsuficcientBalance $e) {
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
}
