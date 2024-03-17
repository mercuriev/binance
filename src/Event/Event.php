<?php

namespace Binance\Event;

#[\AllowDynamicProperties]
class Event
{
    public function __construct(array $payload)
    {
        foreach ($payload as $k => $v) $this->$k = $v;
    }
}
