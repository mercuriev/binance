<?php
namespace Binance\Chart;

use Binance\Event\Trade;
use Laminas\ServiceManager\ServiceManager;
use X\Db\Adapter;
use X\Log\Logger;

final class Seconds extends AbstractChart
{
    static public function factory(ServiceManager $sm, $name, $options) : self
    {
        $options ??= [];
        $symbol   = @$options['symbol']   ? $options['symbol']   : 'BTCTUSD';
        $interval = @$options['interval'] ? $options['inverval'] : self::SIZE + 10;

        /** @var Adapter */
        $db = $sm->get(Adapter::class);
        /** @var Logger */
        $log = $sm->get(Logger::class);

        $sql = 'SELECT `data` FROM trade WHERE `symbol` = ? AND `timestamp` > NOW() - INTERVAL ? SECOND ORDER BY `timestamp`';
        $res = $db->query($sql)->execute([$symbol, $interval]);

        $seconds = new self();
        foreach($res as $row) {
            $trade = json_decode($row['data']);
            $trade = new Trade($trade);
            $seconds->append($trade);
        }

        $log->debug(sprintf('Loaded %u latest trades.', count($res)));

        return $seconds;
    }

    public function isNew(Trade $trade) : bool
    {
        if (empty($this->storage)) return true;
        return $trade->tradeTime != $this[0][0]->tradeTime;
    }
}
