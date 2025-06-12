<?php

/**
 * Class Connection
 *
 * @since 1.9.1.0
 */
class Connection extends PhenyxObjectModel {

    protected static $instance;

    public $require_context = false;
    // @codingStandardsIgnoreStart
    /** @var int */
    public $id_guest;
    /** @var int */
    public $id_page;
    /** @var string */
    public $ip_address;
    /** @var string */
    public $http_referer;
    /** @var string */
    public $date_add;

    public $context;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'connections',
        'primary' => 'id_connections',
        'fields'  => [
            'id_guest'     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_page'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'ip_address'   => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'http_referer' => ['type' => self::TYPE_STRING, 'validate' => 'isAbsoluteUrl'],
            'date_add'     => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    public function __construct($id = null, $id_lang = null) {

        parent::__construct($id, $id_lang);
        $this->context->cookie = Context::getContext()->cookie;
        $this->context->phenyxConfig = Configuration::getInstance();

    }

    public static function getInstance($id = null, $idLang = null) {

        if (!isset(static::$instance)) {
            static::$instance = new Connection($id, $idLang);
        }

        return static::$instance;
    }

    public function setPageConnection($full = true) {

        $idPage = false;
        // The connection is created if it does not exist yet and we get the current page id

        if (!isset($this->context->cookie->id_connections) || !strstr(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '', Tools::getHttpHost(false, false))) {
            $idPage = $this->setNewConnection();
        }

        // If we do not track the pages, no need to get the page id

        if (!$this->context->phenyxConfig->get('EPH_STATSDATA_PAGESVIEWS') && !$this->context->phenyxConfig->get('EPH_STATSDATA_CUSTOMER_PAGESVIEWS')) {
            return [];
        }

        if (!$idPage) {
            $idPage = Page::getCurrentId();
        }

        if (!$this->context->phenyxConfig->get('EPH_STATSDATA_CUSTOMER_PAGESVIEWS')) {
            return ['id_page' => $idPage];
        }

        $timeStart = date('Y-m-d H:i:s');

        if (isset($this->context->cookie->id_connections) && $this->context->cookie->id_connections > 0 && $idPage > 0) {
            // The ending time will be updated by an ajax request when the guest will close the page

            $values = [$this->context->cookie->id_connections, $idPage, '\'' . $timeStart . '\''];
            Db::getInstance()->execute(
                (new DbQuery())
                    ->type('INSERT')
                    ->insert('IGNORE')
                    ->fields('id_connections, id_page, time_start')
                    ->values(implode(',', $values))
                    ->from('connections_page'), false, true
            );
            $this->_session->removeEndByKey('_dashboardData');
        }

        return [
            'id_connections' => (int) $this->context->cookie->id_connections,
            'id_page'        => (int) $idPage,
            'time_start'     => $timeStart,
        ];
    }

    public function setNewConnection() {

        if (isset($_SERVER['HTTP_USER_AGENT'])
            && preg_match('/BotLink|ahoy|AlkalineBOT|anthill|appie|arale|araneo|AraybOt|ariadne|arks|ATN_Worldwide|Atomz|bbot|Bjaaland|Ukonline|borg\-bot\/0\.9|boxseabot|bspider|calif|christcrawler|CMC\/0\.01|combine|confuzzledbot|CoolBot|cosmos|Internet Cruiser Robot|cusco|cyberspyder|cydralspider|desertrealm, desert realm|digger|DIIbot|grabber|downloadexpress|DragonBot|dwcp|ecollector|ebiness|elfinbot|esculapio|esther|fastcrawler|FDSE|FELIX IDE|ESI|fido|H�m�h�kki|KIT\-Fireball|fouineur|Freecrawl|gammaSpider|gazz|gcreep|golem|googlebot|griffon|Gromit|gulliver|gulper|hambot|havIndex|hotwired|htdig|iajabot|INGRID\/0\.1|Informant|InfoSpiders|inspectorwww|irobot|Iron33|JBot|jcrawler|Teoma|Jeeves|jobo|image\.kapsi\.net|KDD\-Explorer|ko_yappo_robot|label\-grabber|larbin|legs|Linkidator|linkwalker|Lockon|logo_gif_crawler|marvin|mattie|mediafox|MerzScope|NEC\-MeshExplorer|MindCrawler|udmsearch|moget|Motor|msnbot|muncher|muninn|MuscatFerret|MwdSearch|sharp\-info\-agent|WebMechanic|NetScoop|newscan\-online|ObjectsSearch|Occam|Orbsearch\/1\.0|packrat|pageboy|ParaSite|patric|pegasus|perlcrawler|phpdig|piltdownman|Pimptrain|pjspider|PlumtreeWebAccessor|PortalBSpider|psbot|Getterrobo\-Plus|Raven|RHCS|RixBot|roadrunner|Robbie|robi|RoboCrawl|robofox|Scooter|Search\-AU|searchprocess|Senrigan|Shagseeker|sift|SimBot|Site Valet|skymob|SLCrawler\/2\.0|slurp|ESI|snooper|solbot|speedy|spider_monkey|SpiderBot\/1\.0|spiderline|nil|suke|http:\/\/www\.sygol\.com|tach_bw|TechBOT|templeton|titin|topiclink|UdmSearch|urlck|Valkyrie libwww\-perl|verticrawl|Victoria|void\-bot|Voyager|VWbot_K|crawlpaper|wapspider|WebBandit\/1\.0|webcatcher|T\-H\-U\-N\-D\-E\-R\-S\-T\-O\-N\-E|WebMoose|webquest|webreaper|webs|webspider|WebWalker|wget|winona|whowhere|wlm|WOLP|WWWC|none|XGET|Nederland\.zoek|AISearchBot|woriobot|NetSeer|Nutch|YandexBot/i', $_SERVER['HTTP_USER_AGENT'])
        ) {
            // This is a bot and we have to retrieve its connection ID
            $idConnections = Db::getInstance()->getValue(
                (new DbQuery())
                    ->select('SQL_NO_CACHE `id_connections`')
                    ->from('connections')
                    ->where('ip_address = ' . (int) ip2long(Tools::getRemoteAddr()))
                    ->where('`date_add` > \'' . pSQL(date('Y-m-d H:i:00', time() - 1800)) . '\'')
                    ->orderBy('`date_add` DESC'), false
            );

            if ($idConnections) {
                $this->context->cookie->id_connections = (int) $idConnections;
                $this->_session->removeEndByKey('_dashboardData');

                return Page::getCurrentId();
            }

        }

        $result = Db::getInstance()->getRow(
            (new DbQuery())
                ->select('SQL_NO_CACHE `id_guest`')
                ->from('connections')
                ->where('`id_guest` = ' . (int) $this->context->cookie->id_guest)
                ->where('`date_add` > \'' . pSQL(date('Y-m-d H:i:00', time() - 1800)) . '\'')
                ->orderBy('`date_add` DESC'), false
        );

        if (empty($result) && (int) $this->context->cookie->id_guest) {
            // The old connections details are removed from the database in order to spare some memory
            $this->cleanConnectionsPages();

            $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
            $arrayUrl = parse_url($referer);

            if (!isset($arrayUrl['host']) || preg_replace('/^www./', '', $arrayUrl['host']) == preg_replace('/^www./', '', Tools::getHttpHost(false, false))) {
                $referer = '';
            }

            $connection = new Connection();
            $connection->id_guest = (int) $this->context->guest->id;
            $connection->id_page = Page::getCurrentId();
            $connection->ip_address = Tools::getRemoteAddr() ? (int) ip2long(Tools::getRemoteAddr()) : '';
            $connection->date_add = $this->context->cookie->date_add;

            if (Validate::isAbsoluteUrl($referer)) {
                $connection->http_referer = substr($referer, 0, 254);
            }

            $connection->add();
            $this->_session->removeEndByKey('_dashboardData');
            $this->context->cookie->id_connections = $connection->id;
            Db::getInstance()->execute(
                (new DbQuery())
                    ->type('UPDATE')
                    ->from('guest')
                    ->set('`last_activity` = "' . time() . '"')
                    ->where('`id_guest` = ' . (int) $this->context->guest->id)
            );

            return $connection->id_page;
        }

    }

    public function cleanConnectionsPages() {

        $period = $this->context->phenyxConfig->get('EPH_STATS_OLD_CONNECT_AUTO_CLEAN');

        if ($period === 'week') {
            $interval = '1 WEEK';
        } else

        if ($period === 'month') {
            $interval = '1 MONTH';
        } else

        if ($period === 'year') {
            $interval = '1 YEAR';
        } else {
            return;
        }

        if ($interval != null) {
            Db::getInstance()->execute(
                (new DbQuery())
                    ->type('DELETE')
                    ->from('connections_page')
                    ->where('time_start < LAST_DAY(DATE_SUB(NOW(), INTERVAL ' . $interval . '))')
            );

        }

    }

    public function setPageTime($idConnections, $idPage, $timeStart, $time) {

        if (!Validate::isUnsignedId($idConnections)
            || !Validate::isUnsignedId($idPage)
            || !Validate::isDate($timeStart)
        ) {
            return;
        }

        // Limited to 5 minutes because more than 5 minutes is considered as an error

        if ($time > 300000) {
            $time = 300000;
        }

        Db::getInstance()->execute(
            (new DbQuery())
                ->type('UPDATE')
                ->from('connections_page')
                ->set('`time_end` = `time_start` + INTERVAL ' . (int) ($time / 1000) . ' SECOND')
                ->where('`id_connections` = ' . (int) $idConnections)
                ->where('`id_page` = ' . (int) $idPage)
                ->where('`time_start` = \'' . pSQL($timeStart) . '\'')
        );

    }

}
