<?php

use Binance\Event\Event;
use Binance\WebsocketsApi;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WebSocket\Client;

class WebsocketsApiTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('invokeProvider')]
    public function testInvoke($eventType, $data)
    {
        // mock websockets
        $wsStub = $this->createStub(Client::class);
        $wsStub->method('isConnected')->willReturn(true);
        $wsStub->method('receive')->willReturn($data);
        $api = new class($wsStub) extends WebsocketsApi {
            public function __construct(protected Client $ws) {}
        };

        // hydrate payloads
        $result = ($api)();
        $this->assertInstanceOf(Event::class, $result);
    }

    public function testSubscribe()
    {
        $api = new WebsocketsApi();
        $res = $api->subscribe('btcusdt@aggTrade');
        $this->assertTrue($res);
    }

    static public function invokeProvider() : array
    {
        return [
            [
                'aggTrade',
                '{
                    "e":"aggTrade",
                    "E": 1672515782136,
                    "s": "BNBBTC",
                    "a": "1234", 
                    "p": "0.001", 
                    "q": "100", 
                    "f": "123456", 
                    "l": "123459", 
                    "T": "1498793709153", 
                    "m": true
                }'
            ],
            [
                'trade',
                '{
                    "e":"trade", 
                    "E": 1672515782136,
                    "s": "BNBBTC",
                    "t": "1234", 
                    "p": "0.001", 
                    "q": "100", 
                    "b": "123456", 
                    "a": "123459", 
                    "T": "1498793709153", 
                    "m": true
                }'
            ],
            [
                'kline',
                '{
                    "e":"kline", 
                    "E": 1672515782136,
                    "s": "BNBBTC",
                    "k":{
                        "t":1498793709153,
                        "T":1498793709153,
                        "s":"BNBBTC",
                        "i":"1m",
                        "f":123456,
                        "L":123459,
                        "o":"0.001", 
                        "h":"0.001", 
                        "l":"0.0009", 
                        "c":"0.001", 
                        "v":"100", 
                        "n":10,
                        "x":false,
                        "q":"0.1",
                        "V":"50",
                        "Q":"0.05",
                        "B":"0"
                    }
                }'
            ],
            [
                '24hrMiniTicker',
                '{
                    "e":"24hrMiniTicker",
                    "E": 1672515782136,
                    "s": "BNBBTC", 
                    "u":400900217, 
                    "s":"BNBBTC", 
                    "c":"0.0025", 
                    "o":"0.001", 
                    "h":"0.0025", 
                    "l":"0.001", 
                    "v":"10000", 
                    "q":"10"
                }'
            ],
            [
                'avgPrice',
                '{
                    "E": 1672515782136,
                    "s": "BNBBTC",
                    "mins":5, 
                    "price":"0.001"
                }'
            ],
            [
                '1hTicker',
                '{
                    "e": "24hrTicker",  
                    "E": 1672515782136,     
                    "s": "BNBBTC",      
                    "p": "0.0015",      
                    "P": "250.00",      
                    "w": "0.0018",
                    "x": "0.0009",  
                    "c": "0.0025",
                    "Q": "10",        
                    "b": "0.0024",      
                    "B": "10",        
                    "a": "0.0026",      
                    "A": "100",        
                    "o": "0.0010",
                    "h": "0.0025",      
                    "l": "0.0010",      
                    "v": "10000",      
                    "q": "18",
                    "O": 0,   
                    "C": 86400000,
                    "F": 0,
                    "L": 18150,
                    "n": 18151
                }'
            ],
            [
                'depthUpdate',
                '{
                    "e": "depthUpdate", 
                    "E": 1672515782136, 
                    "s": "BNBBTC", 
                    "U": 157, 
                    "u": 160, 
                    "b": [ ["0.0024", "10"] ], 
                    "a": [ ["0.0026", "100"] ]
                }'
            ],
            [
                null,
                '{}'
            ]
        ];
    }
}
