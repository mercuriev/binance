<?php

namespace Binance;

abstract class AbstractPayload
{
    public function __construct(array $payload = [])
    {
        foreach ($payload as $k => $v) $this->$k = $v;
    }

    public function __toString()
    {
        return $this->toJson();
    }

    public function toJson()
    {
        return json_encode($this, true, JSON_PRETTY_PRINT);
    }
}
