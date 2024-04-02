<?php

use Binance\AbstractApi;
use Binance\WebsocketsApi;
use PHPUnit\Framework\TestCase;

class AbstractApiTest extends TestCase
{
    public function testFactoryForServiceManager()
    {
        $sm = new class () {
            public function get() {
                return [AbstractApi::class => [
                ]];
            }
        };

        /** @noinspection PhpParamsInspection */
        $api = WebsocketsApi::factory($sm, AbstractApi::class);
        $this->assertInstanceOf(AbstractApi::class, $api);
    }
}
