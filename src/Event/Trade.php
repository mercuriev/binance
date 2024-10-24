<?php
namespace Binance\Event;

class Trade extends Event
{
    public int       $id;
    public \DateTime $time;
    public string    $symbol;
    public float     $price;
    public string    $quantity;         // string to prevent exponential float that is not accepted by bcmath
    public int       $buyerOrderId;
    public int       $sellerOrderId;
    public \DateTime $tradeTime;
    public bool      $buyerIsMaker;

    public function __construct(array $payload)
    {
        parent::__construct($payload);
        if (isset($payload['E'])) $this->time           = new \DateTime("@" . intval($payload['E'] / 1000));
        if (isset($payload['s'])) $this->symbol         = $payload['s'];
        if (isset($payload['t'])) $this->id             = $payload['t'];
        if (isset($payload['p'])) $this->price          = floatval($payload['p']);
        if (isset($payload['q'])) $this->quantity       = $payload['q'];
        if (isset($payload['b'])) $this->buyerOrderId   = $payload['b'];
        if (isset($payload['a'])) $this->sellerOrderId  = $payload['a'];
        if (isset($payload['T'])) $this->tradeTime      = new \DateTime("@" . intval($payload['T'] / 1000));
        if (isset($payload['m'])) $this->buyerIsMaker   = $payload['m'];
    }

    static public function fromHistorical(array $payload) : static
    {
        // no order id in response
        $trade = new static([]);
        $trade->id = $payload['id'];
        $trade->price = $payload['price'];
        $trade->quantity = $payload['qty'];
        $trade->time = $trade->tradeTime = (new \DateTime("@" . intval($payload['time'] / 1000)))
            ->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $trade->buyerIsMaker = $payload['isBuyerMaker'];
        return $trade;
    }
}
