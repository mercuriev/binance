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
class Account extends AbstractPayload
{
}
