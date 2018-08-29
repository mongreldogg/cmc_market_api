<?php

namespace Bundle;

class MarketPrice extends DBObject implements DBTableObject
{
    public function getId()
    {
        return $this->getField('market_id');
    }

    public function getMarketName()
    {
        return $this->getField('market_name');
    }

    public function setMarketName($marketName)
    {
        $this->setField('market_name', $marketName);
    }

    public function getMarketPrice()
    {
        return $this->getField('market_price');
    }

    public function setMarketPrice($price)
    {
        $this->setField('market_price', $price);
    }

    private static $fields = [
        'market_id' => TYPE_DB_NUMERIC | TYPE_DB_PRIMARY | TYPE_DB_NON_SERIALIZABLE,
        'market_name' => TYPE_DB_TEXT,
        'market_price' => TYPE_DB_TEXT,
    ];

    public function __construct($obj = null)
    {
        parent::__construct(self::$fields, 'market', $obj);
    }

    public static function Init()
    {
        parent::__init(self::$fields, 'market');
    }

    public static function Select($rules, $count = null, $start = 0)
    {
        return parent::__select($rules, 'market', MarketPrice::class, $count, $start);
    }

    public static function Count($rules = [])
    {
        return parent::__selectCount($rules, 'market');
    }

    public static function Delete($rules)
    {
        return parent::__delete($rules, 'market');
    }
}
