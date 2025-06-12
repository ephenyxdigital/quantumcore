<?php

/**
 * Class Page
 *
 * @since 1.9.1.0
 */
class PageType extends PhenyxObjectModel {

    public $require_context = false;
    // @codingStandardsIgnoreStart

    public $id_page_type;
    public $id_object;
    // @codingStandardsIgnoreEnd
    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'page_type',
        'primary' => 'id_page_type',
        'fields'  => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
        ],
    ];

}
