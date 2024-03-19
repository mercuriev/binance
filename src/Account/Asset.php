<?php

namespace Binance\Account;

class Asset
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

    public function __construct(array $data = array())
    {
        foreach ($data as $k => $v) {
            $this->$k = $v;
        }
    }
}
