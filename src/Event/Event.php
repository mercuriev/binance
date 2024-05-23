<?php

namespace Binance\Event;

#[\AllowDynamicProperties]
class Event extends \ArrayObject
{
    public function __toString() : string
    {
        return json_encode($this, JSON_PRETTY_PRINT);
    }
}
