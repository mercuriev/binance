<?php

namespace Binance\Exception;

use Laminas\Http\Request;
use Laminas\Http\Response;

class BinanceException extends \Exception
{
    public array $data;

    public function __construct(public Request $req, public array|Response $res)
    {
        $this->data = $body = json_decode($res->getBody(), true);
        parent::__construct($body['msg'], $body['code']);
    }
}
