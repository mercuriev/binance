<?php
namespace Binance\Account;

use function Binance\truncate;

/**
 * @property string $symbol
 * @property float $indexPrice
 */
class MarginIsolatedAccount extends Account
{
    public string $symbol;
    public bool   $isolatedCreated;
    public bool   $enabled;
    public float  $marginLevel;
    public string $marginLevelStatus;
    public float  $marginRatio;
    public float  $indexPrice;
    public float  $liquidatePrice;
    public float  $liquidateRate;
    public bool   $tradeEnabled;

    public function __construct(object|array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                if ($key === 'baseAsset' || $key === 'quoteAsset') {
                    $this->{$key} = new Asset($value);
                } else {
                    $this->{$key} = $value;
                }
            }
        }
    }

    public function __toString()
    {
        return vsprintf('%s Net: %.2f %s (%.2f / %.5f)', [
            $this->symbol,
            $this->getNetWorth(),
            $this->getQuoteName(),
            $this->getQuoteTotal(),
            $this->getBaseTotal(),
        ]);
    }

    public function oneline() : string
    {
        return (string) $this;
    }

    public function getNetWorth(): float
    {
        $asset = $this->quoteAsset->netAssetOfBtc
            + $this->baseAsset->netAssetOfBtc;
        $asset *= $this->indexPrice;
        return $asset;
    }

    public function getQuoteName(): string
    {
        return $this->quoteAsset->asset;
    }

    public function getBaseName(): string
    {
        return $this->baseAsset->asset;
    }

    public function getQuoteSlice(int $parts = 2): float
    {
        return truncate($this->quoteAsset->free / $parts, 2);
    }

    public function getBaseSlice(int $parts = 2): float
    {
        return truncate($this->baseAsset->free / $parts, 2);
    }

    public function getQuoteTotal(): float
    {
        return truncate($this->quoteAsset->free + $this->quoteAsset->locked, 2);
    }

    public function getBaseTotal(): float
    {
        return truncate($this->baseAsset->free + $this->baseAsset->locked, 5);
    }

    public function hasFiat() : bool
    {
        return $this['quoteAsset']['free'] > 10;
    }

    public function hasCrypto() : bool
    {
        return $this['baseAsset']['free'] > 0;
    }
}
