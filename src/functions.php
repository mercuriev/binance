<?php
namespace Binance;

function truncate(float|string $input, $decimals = 2) : float
{
    if (is_string($input)) {
        $dot = strpos($input, '.');
        if (false === $dot) return $input;
        return (float) substr($input, 0, $dot + $decimals + 1);
    }
    else {
        $power = pow(10, $decimals);
        if($input > 0) {
            return floor($input * $power) / $power;
        } else {
            return ceil($input * $power) / $power;
        }
    }
}

function json_encode_pretty(mixed $data)
{
    return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
