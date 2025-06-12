<?php

/**
 * Class ConnectionsPage
 *
 * @since 1.9.1.0
 */
class ConnectionsPage extends PhenyxObjectModel {

    public $require_context = false;
    // @codingStandardsIgnoreStart
    public static $uri_max_size = 255;
    public $id_connections;
    public $id_page;
    public $time_start;
    public $time_end;

    public $page;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'connections_page',
        'primary' => 'id_connections_page',
        'fields'  => [
            'id_connections' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_page'        => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'time_start'     => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'time_end'       => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    public function __construct($id = null, $idLang = null) {

        parent::__construct($id, $idLang);

        if ($this->id) {

            $this->page = $this->getPage();
        }

    }

    public function getPage() {

        return Db::getInstance()->getValue(
            (new DbQuery())
                ->select('pm.`name`')
                ->from('page', 'p')
                ->leftJoin('page_type', 'pm', 'pm.id_page_type = p.id_page_type')
                ->where('p.`id_page_type` = ' . (int) $this->id_page)
        );
    }

    public static function getByIdConnection($id_connections) {

        $id_connections_page = Db::getInstance()->getValue(
            (new DbQuery())
                ->select('id_connections_page')
                ->from('connections_page')
                ->where('`id_connections` = ' . (int) id_connections)
        );
    }

}
