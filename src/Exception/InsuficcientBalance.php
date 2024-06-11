<?php
namespace Binance\Exception;

use Laminas\Http\Request;
use Laminas\Http\Response;

class InsuficcientBalance extends BinanceException
{
    public function __construct(string|Request $req, null|Response|array $res = null)
    {
        if (is_string($req)) return parent::__construct($req);

        parent::__construct($req, $res);

        $o = $req->getPost();
        $this->message = sprintf(
            'Account has insufficient balance to %s %s %.'.($o['quantity']?5:2).'f for %.2f',
            $o['side'],
            $o['symbol'],
            $o['quantity'] ?? $o['quoteOrderQty'],
            $o['price']
        );
    }
}
