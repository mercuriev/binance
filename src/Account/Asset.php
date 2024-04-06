<?php

namespace Binance\Account;

use Binance\AbstractPayload;

class Asset extends AbstractPayload
{
    public string $asset;
    public bool   $borrowEnabled;
    public float  $borrowed;
    public float  $free;
    public float  $interest;
    public float  $locked;
    public float  $netAsset;
    public float  $netAssetOfBtc;
    public bool   $repayEnabled;
    public float  $totalAsset;
}
