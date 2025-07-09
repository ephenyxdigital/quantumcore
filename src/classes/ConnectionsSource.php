<?php

/**
 * Class ConnectionsSource
 *
 * @since 1.9.1.0
 */
class ConnectionsSource extends PhenyxObjectModel {

    public $require_context = false;
    
    public static $uri_max_size = 255;
    public $id_connections;
    public $http_referer;
    public $request_uri;
    public $keywords;
    public $date_add;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'connections_source',
        'primary' => 'id_connections_source',
        'fields'  => [
            'id_connections' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'http_referer'   => ['type' => self::TYPE_STRING, 'validate' => 'isAbsoluteUrl'],
            'request_uri'    => ['type' => self::TYPE_STRING, 'validate' => 'isUrl'],
            'keywords'       => ['type' => self::TYPE_STRING, 'validate' => 'isMessage'],
            'date_add'       => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
        ],
    ];

    public static function logHttpReferer($cookie = null) {

        if (!$cookie) {
            $cookie = Context::getContext()->cookie;
        }

        if (!isset($cookie->id_connections) || !Validate::isUnsignedId($cookie->id_connections)) {
            return false;
        }

        if (isset($_SERVER['HTTP_REFERER']) && !Validate::isAbsoluteUrl($_SERVER['HTTP_REFERER'])) {
            return false;
        }

        if (!isset($_SERVER['HTTP_REFERER']) && !Context::getContext()->phenyxConfig->get('TRACKING_DIRECT_TRAFFIC')) {
            return false;
        }

        $source = new ConnectionsSource();

        if (isset($_SERVER['HTTP_REFERER'])) {
            // If the referrer is internal (i.e. from your own website), then we drop the connection
            $parsed = parse_url($_SERVER['HTTP_REFERER']);
            $parsedHost = parse_url('http://' . Tools::getHttpHost(false, false) . __EPH_BASE_URI__);

            if (!isset($parsed['host']) || (!isset($parsed['path']) || !isset($parsedHost['path']))) {
                return false;
            }

            if ((preg_replace('/^www./', '', $parsed['host']) == preg_replace('/^www./', '', Tools::getHttpHost(false, false))) && !strncmp($parsed['path'], $parsedHost['path'], strlen(__EPH_BASE_URI__))) {
                return false;
            }

            $source->http_referer = substr($_SERVER['HTTP_REFERER'], 0, ConnectionsSource::$uri_max_size);
            $source->keywords = substr(trim(SearchEngine::getKeywords($_SERVER['HTTP_REFERER'])), 0, ConnectionsSource::$uri_max_size);
        }

        $source->id_connections = (int) $cookie->id_connections;
        $source->request_uri = Tools::getHttpHost(false, false);

        if (isset($_SERVER['REQUEST_URI'])) {
            $source->request_uri .= $_SERVER['REQUEST_URI'];
        }

        if (!Validate::isUrl($source->request_uri)) {
            $source->request_uri = '';
        }

        $source->request_uri = substr($source->request_uri, 0, ConnectionsSource::$uri_max_size);

        return $source->add();
    }

    public function add($autoDate = true, $nullValues = false) {

        if ($result = parent::add($autoDate, $nullValues)) {
            $referrer = Referrer::getInstance();
            $referrer->cacheNewSource($this->id);
        }

        return $result;
    }

    public static function getOrderSources($idOrder) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            '
        SELECT cos.http_referer, cos.request_uri, cos.keywords, cos.date_add
        FROM ' . _DB_PREFIX_ . 'orders o
        INNER JOIN ' . _DB_PREFIX_ . 'guest g ON g.id_user = o.id_user
        INNER JOIN ' . _DB_PREFIX_ . 'connections co  ON co.id_guest = g.id_guest
        INNER JOIN ' . _DB_PREFIX_ . 'connections_source cos ON cos.id_connections = co.id_connections
        WHERE id_order = ' . (int) ($idOrder) . '
        ORDER BY cos.date_add DESC'
        );
    }

}
