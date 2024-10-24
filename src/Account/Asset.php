<?php

namespace Binance\Account;

use Binance\AbstractPayload;

class Asset extends AbstractPayload
{
    public string $asset;
    public bool   $borrowEnabled;
    public string  $borrowed;
    public string  $free;
    public string  $interest;
    public string  $locked;
    public string  $netAsset;
    public string  $netAssetOfBtc;
    public bool   $repayEnabled;
    public string  $totalAsset;
}
