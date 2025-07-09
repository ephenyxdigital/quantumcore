<?php

/**
 * Class ReferrerCore
 *
 * @since 1.9.1.0
 */
class Referrer extends PhenyxObjectModel {
    
    protected static $instance;
    
    public $require_context = false;
    
    public $phenyxConfig;
    
    public $_tools;
    // @codingStandardsIgnoreStart
   public $_join = '(r.http_referer_like IS NULL OR r.http_referer_like = \'\' OR cs.http_referer LIKE r.http_referer_like)
            AND (r.request_uri_like IS NULL OR r.request_uri_like = \'\' OR cs.request_uri LIKE r.request_uri_like)
            AND (r.http_referer_like_not IS NULL OR r.http_referer_like_not = \'\' OR cs.http_referer NOT LIKE r.http_referer_like_not)
            AND (r.request_uri_like_not IS NULL OR r.request_uri_like_not = \'\' OR cs.request_uri NOT LIKE r.request_uri_like_not)
            AND (r.http_referer_regexp IS NULL OR r.http_referer_regexp = \'\' OR cs.http_referer REGEXP r.http_referer_regexp)
            AND (r.request_uri_regexp IS NULL OR r.request_uri_regexp = \'\' OR cs.request_uri REGEXP r.request_uri_regexp)
            AND (r.http_referer_regexp_not IS NULL OR r.http_referer_regexp_not = \'\' OR cs.http_referer NOT REGEXP r.http_referer_regexp_not)
            AND (r.request_uri_regexp_not IS NULL OR r.request_uri_regexp_not = \'\' OR cs.request_uri NOT REGEXP r.request_uri_regexp_not)';
    /** @var int $id_company */
    public $id_company;
    /** @var string $name */
    public $name;
    /** @var string $passwd */
    public $passwd;
    public $http_referer_regexp;
    public $http_referer_like;
    public $request_uri_regexp;
    public $request_uri_like;
    public $http_referer_regexp_not;
    public $http_referer_like_not;
    public $request_uri_regexp_not;
    public $request_uri_like_not;
    public $base_fee;
    public $percent_fee;
    public $click_fee;
    public $date_add;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'referrer',
        'primary' => 'id_referrer',
        'fields'  => [
            'name'                    => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'passwd'                  => ['type' => self::TYPE_STRING, 'validate' => 'isPasswd', 'size' => 60],
            'http_referer_regexp'     => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 64],
            'request_uri_regexp'      => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 64],
            'http_referer_like'       => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 64],
            'request_uri_like'        => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 64],
            'http_referer_regexp_not' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'request_uri_regexp_not'  => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'http_referer_like_not'   => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'request_uri_like_not'    => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'base_fee'                => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'percent_fee'             => ['type' => self::TYPE_FLOAT, 'validate' => 'isPercentage'],
            'click_fee'               => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'date_add'                => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
    
    public function __construct($id = null, $id_lang = null) {

        parent::__construct($id, $id_lang);
        $this->_join = '(r.http_referer_like IS NULL OR r.http_referer_like = \'\' OR cs.http_referer LIKE r.http_referer_like)
            AND (r.request_uri_like IS NULL OR r.request_uri_like = \'\' OR cs.request_uri LIKE r.request_uri_like)
            AND (r.http_referer_like_not IS NULL OR r.http_referer_like_not = \'\' OR cs.http_referer NOT LIKE r.http_referer_like_not)
            AND (r.request_uri_like_not IS NULL OR r.request_uri_like_not = \'\' OR cs.request_uri NOT LIKE r.request_uri_like_not)
            AND (r.http_referer_regexp IS NULL OR r.http_referer_regexp = \'\' OR cs.http_referer REGEXP r.http_referer_regexp)
            AND (r.request_uri_regexp IS NULL OR r.request_uri_regexp = \'\' OR cs.request_uri REGEXP r.request_uri_regexp)
            AND (r.http_referer_regexp_not IS NULL OR r.http_referer_regexp_not = \'\' OR cs.http_referer NOT REGEXP r.http_referer_regexp_not)
            AND (r.request_uri_regexp_not IS NULL OR r.request_uri_regexp_not = \'\' OR cs.request_uri NOT REGEXP r.request_uri_regexp_not)';
        $this->phenyxConfig = Configuration::getInstance();
        $this->_tools = PhenyxTool::getInstance();

    }
    
    public static function getInstance($id = null, $idLang = null) {

        if (!isset(static::$instance)) {
            static::$instance = new Referrer($id, $idLang);
        }

        return static::$instance;
    }

    /**
     * @param int $idConnectionsSource
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxException
     */
    public function cacheNewSource($idConnectionsSource) {

        if (!$idConnectionsSource) {
            return;
        }

        $sql = 'INSERT IGNORE INTO ' . _DB_PREFIX_ . 'referrer_cache (id_referrer, id_connections_source) (
                    SELECT id_referrer, id_connections_source
                    FROM ' . _DB_PREFIX_ . 'referrer r
                    LEFT JOIN ' . _DB_PREFIX_ . 'connections_source cs ON (' . $this->_join . ')
                    WHERE id_connections_source = ' . (int) $idConnectionsSource . '
                )';
        Db::getInstance()->execute($sql);
    }

    public function getReferrers($idCustomer) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('DISTINCT c.`date_add`, r.`name`')
                ->from('guest', 'g')
                ->leftJoin('connections', 'c', 'c.`id_guest` = g.`id_guest`')
                ->leftJoin('connections_source', 'cs', 'c.`id_connections` = cs.`id_connections`')
                ->leftJoin('referrer', 'r', $this->_join)
                ->where('g.`id_user` = ' . (int) $idCustomer)
                ->where('r.`name` IS NOT NULL')
                ->orderBy('c.`date_add` DESC')
        );
    }

    public function getAjaxProduct($idReferrer, $idProduct, $employee = null) {

        $product = new Product($idProduct, false, $this->phenyxConfig->get('EPH_LANG_DEFAULT'));
        $currency = Currency::getCurrencyInstance($this->phenyxConfig->get('EPH_CURRENCY_DEFAULT'));
        $referrer = new Referrer($idReferrer);
        $statsVisits = $referrer->getStatsVisits($idProduct, $employee);
        $registrations = $referrer->getRegistrations($idProduct, $employee);
        $statsSales = $referrer->getStatsSales($idProduct, $employee);

        // If it's a product and it has no visits nor orders

        if ((int) $idProduct && !$statsVisits['visits'] && !$statsSales['orders']) {
            exit;
        }

        $jsonArray = [
            'id_product'    => (int) $product->id,
            'product_name'  => htmlspecialchars($product->name),
            'uniqs'         => (int) $statsVisits['uniqs'],
            'visitors'      => (int) $statsVisits['visitors'],
            'visits'        => (int) $statsVisits['visits'],
            'pages'         => (int) $statsVisits['pages'],
            'registrations' => (int) $registrations,
            'orders'        => (int) $statsSales['orders'],
            'sales'         => $this->_tools->displayPrice($statsSales['sales'], $currency),
            'cart'          => $this->_tools->displayPrice(((int) $statsSales['orders'] ? $statsSales['sales'] / (int) $statsSales['orders'] : 0), $currency),
            'reg_rate'      => number_format((int) $statsVisits['uniqs'] ? (int) $registrations / (int) $statsVisits['uniqs'] : 0, 4, '.', ''),
            'order_rate'    => number_format((int) $statsVisits['uniqs'] ? (int) $statsSales['orders'] / (int) $statsVisits['uniqs'] : 0, 4, '.', ''),
            'click_fee'     => $this->_tools->displayPrice((int) $statsVisits['visits'] * $referrer->click_fee, $currency),
            'base_fee'      => $this->_tools->displayPrice($statsSales['orders'] * $referrer->base_fee, $currency),
            'percent_fee'   => $this->_tools->displayPrice($statsSales['sales'] * $referrer->percent_fee / 100, $currency),
        ];

        die('[' . json_encode($jsonArray) . ']');
    }

    public function getStatsVisits($id_product, $employee) {
        $join = $where = '';

        if ($id_product) {
            $join = 'LEFT JOIN `' . _DB_PREFIX_ . 'page` p ON cp.`id_page` = p.`id_page`
                     LEFT JOIN `' . _DB_PREFIX_ . 'page_type` pt ON pt.`id_page_type` = p.`id_page_type`';
            $where = ' AND pt.`name` = \'product\'
                      AND p.`id_object` = ' . (int) $id_product;
        }

        $sql = 'SELECT COUNT(DISTINCT cs.id_connections_source) AS visits,
            COUNT(DISTINCT cs.id_connections) as visitors,
            COUNT(DISTINCT c.id_guest) as uniqs,
            COUNT(DISTINCT cp.time_start) as pages
            FROM ' . _DB_PREFIX_ . 'referrer_cache rc
            LEFT JOIN ' . _DB_PREFIX_ . 'referrer r ON rc.id_referrer = r.id_referrer
            LEFT JOIN ' . _DB_PREFIX_ . 'referrer_company rs ON r.id_referrer_company = rs.id_referrer
            LEFT JOIN ' . _DB_PREFIX_ . 'connections_source cs ON rc.id_connections_source = cs.id_connections_source
            LEFT JOIN ' . _DB_PREFIX_ . 'connections c ON cs.id_connections = c.id_connections
            LEFT JOIN ' . _DB_PREFIX_ . 'connections_page cp ON cp.id_connections = c.id_connections
            ' . $join . '
            WHERE 1' .
        ((isset($employee->stats_date_from) && isset($employee->stats_date_to)) ? ' AND cs.date_add BETWEEN \'' . pSQL($employee->stats_date_from) . ' 00:00:00\' AND \'' . pSQL($employee->stats_date_to) . ' 23:59:59\'' : '') .
        ' AND rc.id_referrer = ' . (int) $this->id .
            $where;
        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow($sql);
    }

    public function getRegistrations($idProduct, $employee) {

        $sql = (new DbQuery())
            ->select('COUNT(DISTINCT cu.`id_user`) AS `registrations`')
            ->from('referrer_cache', 'rc')
            ->leftJoin('referrer_company', 'rs', 'rc.`id_referrer` = rs.`id_referrer_company`')
            ->leftJoin('connections_source', 'cs', 'rc.`id_connections_source` = cs.`id_connections_source`')
            ->leftJoin('connections', 'c', 'cs.`id_connections` = c.`id_connections`')
            ->leftJoin('guest', 'g', 'g.`id_guest` = c.`id_guest`')
            ->leftJoin('user', 'cu', 'cu.`id_user` = g.`id_user`')
            ->where('cu.`date_add` BETWEEN ' . PluginGraph::getDateBetween($employee))
            ->where('cu.`date_add` > cs.`date_add`')
            ->where('rc.`id_referrer` = ' . (int) $this->id);

        if ($idProduct) {
            $sql->leftJoin('connections_page', 'cp', 'cp.`id_connections` = c.`id_connections`');
            $sql->leftJoin('page', 'p', 'cp.`id_page` = p.`id_page`');
            $sql->leftJoin('page_type', 'pt', 'pt.`id_paget_type` = p.`id_page_type`');
            $sql->where('pt.`name` = \'product\'');
            $sql->where('p.`id_object` = ' . (int) $idProduct);
        }

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow($sql);

        return (int) $result['registrations'];
    }

    public function getStatsSales($idProduct = null, $employee = null) {

        $sql = (new DbQuery())
            ->select('oo.`id_customer_piece`')
            ->from('referrer_cache', 'rc')
            ->leftJoin('referrer_company', 'rs', 'rc.`id_referrer` = rs.`id_referrer_company`')
            ->innerJoin('connections_source', 'cs', 'rc.`id_connections_source` = cs.`id_connections_source`')
            ->innerJoin('connections', 'c', 'cs.`id_connections` = c.`id_connections`')
            ->innerJoin('guest', 'g', 'g.`id_guest` = c.`id_guest`')
            ->innerJoin('customer_pieces', 'oo', 'oo.`id_user` = g.`id_user`')
            ->where('oo.`date_upd` BETWEEN ' . PluginGraph::getDateBetween($employee))
            ->where('oo.`date_add` > cs.`date_add`')
            ->where('rc.`id_referrer` = ' . (int) $this->id)
            ->where('oo.`validate` = 1')
            ->where('oo.`piece_type` = "INVOICE"')
        ;

        if ($idProduct) {
            $sql->leftJoin('customer_piece_detail', 'od', 'oo.`id_customer_piece` = od.`id_customer_piece`');
            $sql->where('od.`id_product` = ' . (int) $idProduct);
        }

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql);

        $implode = [];

        foreach ($result as $row) {

            if ((int) $row['id_customer_piece']) {
                $implode[] = (int) $row['id_customer_piece'];
            }

        }

        if (count($implode)) {
            return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('COUNT(`id_customer_piece`) AS `orders`, SUM(`total_paid` / `conversion_rate`) AS `sales`')
                    ->from('customer_pieces')
                    ->where('`id_customer_piece` IN(' . implode(',', $implode) . ')')
            );

        } else {
            return ['orders' => 0, 'sales' => 0];
        }

    }

    public function add($autoDate = true, $nullValues = false) {

        if (!($result = parent::add($autoDate, $nullValues))) {
            return false;
        }

        $this->refreshCache([['id_referrer' => $this->id]]);
        $this->refreshIndex([['id_referrer' => $this->id]]);

        return $result;
    }

    public function refreshCache($referrers = null, $employee = null) {

        if (!$referrers || !is_array($referrers)) {
            $referrers = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())->select('`id_referrer`')->from('referrer')
            );
        }

        foreach ($referrers as $row) {
            $referrer = new Referrer($row['id_referrer']);
            $statsVisits = $referrer->getStatsVisits(null, $employee);
            $registrations = $referrer->getRegistrations(null, $employee);
            $statsSales = $referrer->getStatsSales(null, $employee);

            $refCompany = new ReferrerCompany((int) $referrer->id);
            $refCompany->cache_visitors = (int) $statsVisits['uniqs'];
            $refCompany->cache_visits = (int) $statsVisits['visits'];
            $refCompany->cache_pages = (int) $statsVisits['pages'];
            $refCompany->cache_registrations = (int) $registrations;
            $refCompany->cache_orders = (int) $statsSales['orders'];
            $refCompany->cache_sales = number_format($statsSales['sales'], 2, '.', '');
            $refCompany->cache_reg_rate = $statsVisits['uniqs'] ? $registrations / $statsVisits['uniqs'] : 0;
            $refCompany->cache_order_rate = $statsVisits['uniqs'] ? $statsSales['orders'] / $statsVisits['uniqs'] : 0;
            $refCompany->update();

        }

        $this->phenyxConfig->updateValue('EPH_REFERRERS_CACHE_LIKE', PluginGraph::getDateBetween($employee));
        $this->phenyxConfig->updateValue('EPH_REFERRERS_CACHE_DATE', date('Y-m-d H:i:s'));

        return true;
    }

    public function refreshIndex($referrers = null) {

        if (!$referrers || !is_array($referrers)) {
            Db::getInstance()->execute('TRUNCATE ' . _DB_PREFIX_ . 'referrer_cache');
            Db::getInstance()->execute(
                '
            INSERT INTO ' . _DB_PREFIX_ . 'referrer_cache (id_referrer, id_connections_source) (
                SELECT id_referrer, id_connections_source
                FROM ' . _DB_PREFIX_ . 'referrer r
                LEFT JOIN ' . _DB_PREFIX_ . 'connections_source cs ON (' . $this->_join . ')
            )'
            );
        } else {

            foreach ($referrers as $row) {
                Db::getInstance()->delete('referrer_cache', '`id_referrer` = ' . (int) $row['id_referrer']);
                Db::getInstance()->execute(
                    '
                INSERT INTO ' . _DB_PREFIX_ . 'referrer_cache (id_referrer, id_connections_source) (
                    SELECT id_referrer, id_connections_source
                    FROM ' . _DB_PREFIX_ . 'referrer r
                    LEFT JOIN ' . _DB_PREFIX_ . 'connections_source cs ON (' . $this->_join . ')
                    WHERE id_referrer = ' . (int) $row['id_referrer'] . '
                    AND id_connections_source IS NOT NULL
                )'
                );
            }

        }

    }

}
