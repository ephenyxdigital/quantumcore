<?php

/**
 * Class Theme
 *
 * @since 1.9.1.0
 */
class ThemeMeta extends PhenyxObjectModel {

    
    public static $definition = [
        'table'   => 'theme_meta',
        'primary' => 'id_theme_meta',
        'fields'  => [
            'id_theme'  => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true],
            'id_meta'  => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true],         
            'left_column'  => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'right_column' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
        ],
    ];
    /** @var string $name */
    public $id_theme;
    /** @var string $directory */
    public $id_meta;
    /** @var int $default_left_column */
    public $left_column;
    /** @var int $default_right_column */
    public $right_column;
   

}
