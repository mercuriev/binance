<?php

namespace Binance\Event;

#[\AllowDynamicProperties]
class Event
{
    public function __construct(array $payload = [])
    {
        foreach ($payload as $k => $v) $this->$k = $v;
    }

    public function __toString() : string
    {
        return json_encode($this, JSON_PRETTY_PRINT);
    }
}
