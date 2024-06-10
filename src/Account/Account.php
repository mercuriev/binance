<?php

namespace Binance\Account;

use Binance\AbstractPayload;

/**
 * @property int makerCommission
 * @property int takerCommission
 * @property int buyerCommission
 * @property int sellerCommission
 * @property array commissionRates
 * @property bool canTrade
 * @property bool canWithdraw
 * @property bool canDeposit
 * @property bool brokered
 * @property bool requireSelfTradePrevention
 * @property bool preventSor
 * @property int updateTime
 * @property string accountType
 * @property array balances
 * @property array permissions
 * @property int uid
 */
// FIXME separate class for SpotAccount, Account interface?
class Account extends AbstractPayload
{
    public Asset  $baseAsset;
    public Asset  $quoteAsset;

    /**
     * For spot account symbol pair.
     *
     * @param string $symbol
     * @return $this
     */
    public function selectAssetsFor(string $symbol) : self
    {
        foreach ($this->balances as $balance) {
            if (str_starts_with($symbol, $balance['asset'])) {
                $this->baseAsset = new Asset($balance);
            }
            if (str_ends_with($symbol, $balance['asset'])) {
                $this->quoteAsset = new Asset($balance);
            }
        }
        return $this;
    }
}
