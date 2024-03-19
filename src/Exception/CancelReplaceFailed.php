<?php
namespace Binance\Exception;

class CancelReplaceFailed extends BinanceException
{
    const CODES = [
        -2010 => 'Account has insufficient balance for requested action.'
    ];

    public function getCancelFail()
    {
        return @$this->data['cancelResponse']['msg'];
    }

    public function getOrderFail()
    {
        return @$this->data['newResponse']['msg'];
    }
}
