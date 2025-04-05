<?php

declare(strict_types=1);

class SalesBoosterDiscount extends ObjectModel
{
    public int $id_product;
    public float $discount_percentage;
    public bool $is_selected;
    public string $date_add;
    public string $date_upd;

    public static $definition = [
        'table' => 'salesbooster_discount',
        'primary' => 'id_salesbooster_discount',
        'fields' => [
            'id_product' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'discount_percentage' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true, 'size' => '5,2'],
            'is_selected' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
        ],
    ];

    public static function getDiscountSuggestions(?bool $selected = null): PrestaShopCollection
    {
        $collection = new PrestaShopCollection('SalesBoosterDiscount');
        if ($selected !== null) {
            $collection->where('is_selected', '=', (int)$selected);
        }
        return $collection;
    }

    public static function findByProductId(int $id_product)
    {
        $query = new DbQuery();
        $query->select(self::$definition['primary']);
        $query->from(self::$definition['table']);
        $query->where('id_product = ' . (int)$id_product);

        $id = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);

        if (!$id) {
            return false;
        }

        return new self((int)$id);
    }

    public static function clearAllSuggestions(): bool
    {
        $sql = 'DELETE FROM `' . _DB_PREFIX_ . self::$definition['table'] . '`';
        return Db::getInstance()->execute($sql);
    }

    public static function deselectAllSuggestions(): bool
    {
        $sql = 'UPDATE `' . _DB_PREFIX_ . self::$definition['table'] . '` SET `is_selected` = 0';
        return Db::getInstance()->execute($sql);
    }
}
