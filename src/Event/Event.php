<?php

namespace Binance\Event;

#[\AllowDynamicProperties]
class Event extends \ArrayObject
{
    public function __construct(array $payload = [])
    {
        parent::__construct($payload);
        foreach ($payload as $k => $v) $this->$k = $v;
    }

    public function __toString() : string
    {
        return json_encode($this, JSON_PRETTY_PRINT);
    }
}
