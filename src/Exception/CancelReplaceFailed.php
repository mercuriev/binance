<?php
namespace Binance\Exception;

class CancelReplaceFailed extends BinanceException
{
    const CODES = [
        -2010 => 'Account has insufficient balance for requested action.'
    ];

    public function __construct ($message = null, $code = null, $previous = null)
    {
        if (isset($previous->req))  $this->req  = $previous->request;
        if (isset($previous->res))  $this->res  = $previous->response;
        parent::__construct($message, $code, $previous);
    }

    public function getCancelFail()
    {
        return @$this->data['cancelResponse']['msg'];
    }

    public function getOrderFail()
    {
        return @$this->data['newResponse']['msg'];
    }
}
