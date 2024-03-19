<?php

namespace Binance\Order;

use Binance\AbstractPayload;
use function Binance\truncate;

/**
 * @property string $status
 */
#[\AllowDynamicProperties]
class AbstractOrder extends AbstractPayload
{
    public string $symbol = 'BTCFDUSD';
    public string $side;
    public int $timestamp;

    public int $recvWindow;
    public string $isIsolated = 'TRUE';

    public static function fromApi(array $reply)
    {
        if (isset($reply['contingencyType'])) {
            return (new OcoOrder())->merge($reply);
        }
        else {
            return match ($reply['type']) {
                'LIMIT_MAKER', 'LIMIT'          => (new LimitOrder())->merge($reply),
                'STOP_LOSS', 'STOP_LOSS_LIMIT'  => (new StopOrder())->merge($reply),
                'MARKET'                        => (new MarketOrder())->merge($reply),
                default => throw new \InvalidArgumentException("Unknown type: {$reply['type']}")
            };
        }
    }

    public function oneline() : string
    {
        if (!$this instanceof OcoOrder)                     $type = $this->type;
        else if ($this->getFilled() instanceof StopOrder)   $type = 'STOP';
        else if ($this->getFilled() instanceof LimitOrder)  $type = 'LIMIT';
        else $type = 'OCO';

        $values = [
            $this->side,
            $type,
            match ($type) {
                'STOP'      => $this->stopPrice,
                'MARKET'    => $this->getExecutedPrice(),
                default     => $this->price
            }
        ];
        if ('SELL' == $this->side) {
            $tail = '%-8.5f -> %-8.2f';
            $values[] = $this->getExecutedQty();
            $values[] = $this->getExecutedAmount();
        }
        else {
            $tail = '%-8.2f -> %-8.5f';
            $values[] = $this->getExecutedAmount();
            $values[] = $this->getExecutedQty();
        }

        return vsprintf("%-4s %6s : %.2f : $tail", $values);
    }

    public function merge(array $reply) : static
    {
        foreach ($reply as $k => $v) $this->$k = $v;
        return $this;
    }

    public function fill() : static
    {
        $this->executedQty = $this->origQty;
        $this->cummulativeQuoteQty = truncate($this->executedQty * $this->price, 2);
        $this->status = 'FILLED';
        return $this;
    }

    public function isSell() : bool
    {
        return $this->side == 'SELL';
    }

    public function isBuy() : bool
    {
        return $this->side == 'BUY';
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function getDealId()
    {
        $parts = explode('-', $this->clientOrderId);
        return array_pop($parts);
    }

    public function getId()
    {
        return $this->orderId;
    }

    public function getClientOrderId() : string
    {
        return $this->clientOrderId;
    }

    public function getQty()
    {
        if (isset($this->quantity)) return $this->quantity;
        return floatval($this->origQty);
    }

    public function getExecutedQty()
    {
        return $this->executedQty;
    }

    /**
     * @return float Executed amount in fiat.
     */
    public function getAmount() : float
    {
        return (float) $this->cummulativeQuoteQty;
    }

    public function getExecutedAmount() : float
    {
        return $this->getAmount();
    }

    public function getAge()
    {
        return time() - $this->getDatetime()->getTimestamp();
    }

    public function isOco()
    {
        return @$this->contingencyType == 'OCO';
    }

    public function getDatetime() : \DateTime
    {
        if (isset($this->time)) {
            $time = (string) $this->time / 1000;
        }
        else if (isset($this->transactTime)) {
            $time = (string) $this->transactTime / 1000;
        }
        else if (isset($this->transactionTime)) {
            $time = (string) $this->transactionTime / 1000;
        }
        else {
            var_dump($this);
            throw new \RuntimeException('Could not find datetime.');
        }
        $time = sprintf('%.3F', $time);
        return \Datetime::createFromFormat('U.v', $time);
    }

    public function isFilled()
    {
        return 'FILLED' == $this->status;
    }

    public function isPartiallyFilled()
    {
        return isset($this->executedQty) && $this->executedQty > 0 && $this->executedQty != $this->origQty;
    }

    public function isNew()
    {
        return @$this->status == 'NEW';
    }

    public function isCanceled()
    {
        return @$this->status == 'CANCELED';
    }

    public function isStop()
    {
        return @$this->type == 'STOP_LOSS_LIMIT';
    }

    public function getPrice() : float
    {
        if (isset($this->price)) {
            return floatval($this->price);
        }
        else {
            xdebug_break();
            throw new \RuntimeException('Cannot find price.');
        }
    }

    public function getExecutedPrice()
    {
        return $this->price;
    }

    public function getStopPrice()
    {
        if ($this->isOco()) {
            foreach ($this->orderReports as $o) {
                if ($o['type'] == 'STOP_LOSS_LIMIT') {
                    return $o['price'];
                }
            }
            return false;
        }
        else {
            return $this->getPrice();
        }
    }

    public function setClientId(string $prefix, string $direction = 'in')
    {
        $this->newClientOrderId = $this->generateClientId($prefix, $direction);
    }

    protected function generateClientId(string $prefix, string $direction)
    {
        return join('-', [$prefix, uniqid(), $direction]);
    }
}
