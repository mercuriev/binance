<?php

namespace Binance\Exception;

use Laminas\Http\Request;
use Laminas\Http\Response;

class BinanceException extends \Exception
{
    public array $data;

    public function __construct(public string|Request $req, public null|array|Response $res = null)
    {
        if (is_string($this->req)) return parent::__construct($this->req);

        $this->data = $body = json_decode($res->getBody(), true);
        parent::__construct($body['msg'], $body['code']);
    }
}
