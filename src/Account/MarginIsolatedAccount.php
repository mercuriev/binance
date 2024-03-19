<?php
namespace Binance\Account;

/**
 * @property string $symbol
 * @property array $baseAsset
 * @property array $quoteAsset
 * @property float $indexPrice
 */
class MarginIsolatedAccount extends Account
{
    public Asset  $baseAsset;
    public Asset  $quoteAsset;
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

    public function getNetWorth() : float
    {
        $asset = $this->raw['quoteAsset']['netAssetOfBtc']
                + $this->raw['baseAsset']['netAssetOfBtc'];
        $asset *= $this->raw['indexPrice'];
        return $asset;
    }

    public function getQuoteName() : string
    {
        return $this->raw['quoteAsset']['asset'];
    }
    public function getBaseName() : string
    {
        return $this->raw['baseAsset']['asset'];
    }
    public function getQuoteSlice(int $parts = 2) : float
    {
        return self::truncate($this['quoteAsset']['free'] / $parts, 2);
    }
    public function getBaseSlice(int $parts = 2) : float
    {
        return self::truncate(($this['baseAsset']['free']) / $parts, 2);
    }

    public function getQuoteTotal() : float
    {
        return self::truncate($this->raw['quoteAsset']['free'] + $this->raw['quoteAsset']['locked'], 2);
    }
    public function getBaseTotal() : float
    {
        return self::truncate($this->raw['baseAsset']['free'] + $this->raw['baseAsset']['locked'], 5);
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
