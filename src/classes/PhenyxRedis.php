<?php

/**
 * @since 1.9.1.0
 */
class PhenyxRedis extends PhenyxObjectModel {

    public $require_context = false;
    // @codingStandardsIgnoreStart
    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'redis_servers',
        'primary' => 'id_redis_servers',
        'fields'  => [
            'ip'   => ['type' => self::TYPE_STRING, 'copy_post' => false],
            'port' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false],
            'auth' => ['type' => self::TYPE_STRING, 'copy_post' => false],
            'rdb'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false],
            'main' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false],

        ],
    ];
    public $ip;
    public $port;
    public $auth;
    public $rdb;
    public $main

    /**
     * GenderCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idCompany
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct($id = null) {

        parent::__construct($id);
    }

    public static function getSeverbyId($idServer) {

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(bqSQL(static::$definition['table']));
        $sql->where('`id_redis_servers` = ' . $idServer);

        $server = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow($sql);

        return Tools::jsonDecode(Tools::jsonEncode($server));

    }

}
