<?php

/**
 * Class ReferrerCore
 *
 * @since 1.9.1.0
 */
class ReferrerCompany extends PhenyxObjectModel {

    public $require_context = false;
    /** @var int $id_company */
    public $id_company;
    /** @var string $name */
    public $cache_visitors;
    /** @var string $passwd */
    public $cache_visits;
    public $cache_pages;
    public $cache_registrations;
    public $cache_orders;
    public $cache_sales;
    public $cache_reg_rate;
    public $cache_order_rate;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'referrer_company',
        'primary' => 'id_referrer_company',
        'fields'  => [
            'id_company'          => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'cache_visitors'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'cache_visits'        => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'cache_pages'         => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'cache_registrations' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'cache_orders'        => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'cache_sales'         => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'cache_reg_rate'      => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'cache_order_rate'    => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],

        ],
    ];

}
