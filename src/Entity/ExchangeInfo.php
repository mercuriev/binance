<?php

namespace Binance\Entity;

use Binance\AbstractPayload;

/**
 * @property array $symbols
 */
class ExchangeInfo extends AbstractPayload
{
    public function getFilter(string $symbol, string $filter) : ?array
    {
        foreach ($this->symbols as $s) {
            if (0 === strcasecmp($symbol, $s['symbol'])) {
                foreach ($s['filters'] as $f) {
                    if (0 === strcasecmp($f['filterType'], $filter)) {
                        return $f;
                    }
                }
            }
        }
        return null;
    }
}
