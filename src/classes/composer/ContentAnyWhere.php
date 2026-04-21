<?php

use MatthiasMullie\Minify;

class ContentAnyWhere extends PhenyxObjectModel {
    
    protected static $instance;

    public $hook_name;
    public $display_type;
    public $extra_css;
    public $extra_js;
    public $position;
    public $active = 1;
    public $generated;
    public $title;
    public $content;

    public $skip_plugins = ['ph_manager', 'revsliderPhenyxShop', 'smartshortcode'];

    public static $definition = [
        'table'     => 'contentanywhere',
        'primary'   => 'id_contentanywhere',
        'multilang' => true,
        'fields'    => [
            'hook_name'    => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'display_type' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'extra_js'        => ['type' => self::TYPE_STRING],
            'extra_css'        => ['type' => self::TYPE_STRING],
            'position'     => ['type' => self::TYPE_INT],
            'active'       => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],

            'generated'    => ['type' => self::TYPE_BOOL, 'lang' => true],
            'title'        => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => true],
            'content'      => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isString'],
        ],
    ];

    public function __construct($id = null, $id_lang = null) {

        parent::__construct($id, $id_lang);
    }

    public function add($autodate = true, $null_values = false) {

        $this->position = $this->getHigherPosition();

        if (!parent::add($autodate, $null_values) || !Validate::isLoadedObject($this)) {
            return false;
        }

        $this->clearCache();
        return true;
    }

    public function getHigherPosition() {

        $position = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('MAX(`position`)')
                ->from('contentanywhere')
                ->where('hook_name LIKE \'' . $this->hook_name . '\'')
        );
        return (is_numeric($position)) ? $position : -1;
    }
    
    public static function getInstance($id = null, $idLang = null) {

        if (!ContentAnyWhere::$instance) {
            ContentAnyWhere::$instance = new ContentAnyWhere($id, $idLang);
        }

        return ContentAnyWhere::$instance;
    }


    public function getTemplateAnywhereByHook($hook_name = '') {

        $id_lang = (int) $this->context->language->id;
        $query = new DbQuery();
        $query->select('v.*,vl.content');
        $query->from('contentanywhere', 'v');
        $query->leftJoin('contentanywhere_lang', 'vl', 'vl.`id_contentanywhere` = v.`id_contentanywhere` AND vl.`id_lang` = ' . $id_lang);
        $query->where('v.`hook_name` LIKE \'' . $hook_name . '\' AND v.`active` = 1');
        $query->orderBy('v.`position` ASC');

        $results = Db::getInstance()->executeS($query);
        return $this->ContentFilterEngine($results);
    }

    public function ContentFilterEngine($results = []) {

        $outputs = [];

        if (isset($results) && !empty($results)) {
            $i = 0;

            foreach ($results as $vcvalues) {
                $extra_css = '';
                $extra_js = '';
                foreach ($vcvalues as $vckey => $vcval) {
                    
                    if ($vckey == 'content') {
                        $outputs[$i][$vckey] =  Composer::vc_content_filter($vcval);
                    } else {
                        if ($vckey == 'extra_css' && !is_null($vcval)) {
                            $extra_css = str_replace(["\\r\\n", "\\r"], '', $vcval);
                            $minifier = new Minify\CSS();
                            $minifier->add($extra_css);
                            $extra_css = $minifier->minify();     
                            $outputs[$i][$vckey] = $extra_css;
                        } else if($vckey == 'extra_js' && !is_null($vcval)) {
                             $outputs[$i][$vckey] = $vcval;  
                        } else {
                            $outputs[$i][$vckey] = $vcval;
                        }
                    
                        
                    }

                }
                
                $i++;
            }

        }

        return $outputs;
    }

    public function GetcontentanywhereByHook($hook_name = '') {

        $id_lang = (int) $this->context->language->id;
        $sql = 'SELECT v.*,vl.content FROM `' . _DB_PREFIX_ . 'contentanywhere` v
                INNER JOIN `' . _DB_PREFIX_ . 'contentanywhere_lang` vl ON (v.`id_contentanywhere` = vl.`id_contentanywhere` AND vl.`id_lang` = ' . $id_lang . ')

                WHERE ';

        if (isset($hook_name) && !empty($hook_name)) {
            $hook_retro_name = $this->context->_hook->getRetroHookName($hook_name);
            $sql .= '( v.`hook_name` = "' . $hook_name . '" or v.`hook_name` = "' . $hook_retro_name . '") AND ';
        }

        $sql .= ' v.`active` = 1 ORDER BY v.`position` ASC';
        $cache_id = md5($sql);

        if (!CacheApi::isStored($cache_id)) {
            $results = Db::getInstance()->executeS($sql);
            $outputs = $this->ContentFilterEngine($results);
            CacheApi::store($cache_id, $outputs);
        }

        return CacheApi::retrieve($cache_id);
    }

    public function GetVcContentByAll($hook_name = '') {

        $id_lang = (int) $this->context->language->id;
        $sql = 'SELECT v.*,vl.content,v.id_contentanywhere FROM `' . _DB_PREFIX_ . 'contentanywhere` v
                INNER JOIN `' . _DB_PREFIX_ . 'contentanywhere_lang` vl ON (v.`id_contentanywhere` = vl.`id_contentanywhere` AND vl.`id_lang` = ' . $id_lang . ')

                WHERE ';

        if (isset($hook_name) && !empty($hook_name)) {
            $hook_retro_name = $this->context->_hook->getRetroHookName($hook_name);
            $sql .= '( v.`hook_name` = "' . $hook_name . '" or v.`hook_name` = "' . $hook_retro_name . '") AND ';
        }

        $sql .= ' v.`display_type` = 1 AND ';
        $sql .= ' v.`active` = 1 ORDER BY v.`position` ASC';
        $cache_id = md5($sql);

        if (!CacheApi::isStored($cache_id)) {
            $results = Db::getInstance()->executeS($sql);
            $outputs = $this->ContentFilterEngine($results);
            CacheApi::store($cache_id, $outputs);
        }

        return CacheApi::retrieve($cache_id);
    }

    public function ModHookExec($mod_name = '', $hook_name = '') {

        $results = '';

        if (Plugin::isInstalled($mod_name) && Plugin::isEnabled($mod_name)) {
            $mod_ins = Plugin::getInstanceByName($mod_name);

            if (!is_object($mod_ins)) {
                return $results;
            }

           
            $retro_hook_name = $this->context->_hook->getRetroHookName($hook_name);
            $params = ['cookie' => $this->context->cookie, 'cart' => $this->context->cart];

            if (is_callable([
                $mod_ins,
                'hook' . $hook_name,
            ])) {
                $mod_method = 'hook' . $hook_name;
                $results = $mod_ins->$mod_method($params);
            } else
            if (is_callable([
                $mod_ins,
                'hook' . $retro_hook_name,
            ])) {
                $mod_retro_method = 'hook' . $retro_hook_name;
                $results = $mod_ins->$mod_retro_method($params);
            }

        } else {
            $results = '<strong>' . $mod_name . '</strong> is not install. Please Install <strong>' . $mod_name . '</strong> Plugin.';
        }

        return $results;
    }

    public function GetVcContentByAllPRD($hook_name = '') {

        $id_lang = (int) $this->context->language->id;
        $sql = 'SELECT v.*,vl.content,v.id_contentanywhere FROM `' . _DB_PREFIX_ . 'contentanywhere` v
                INNER JOIN `' . _DB_PREFIX_ . 'contentanywhere_lang` vl ON (v.`id_contentanywhere` = vl.`id_contentanywhere` AND vl.`id_lang` = ' . $id_lang . ')

                WHERE ';

        if (isset($hook_name) && !empty($hook_name)) {
            $hook_retro_name = $this->context->_hook->getRetroHookName($hook_name);
            $sql .= '( v.`hook_name` = "' . $hook_name . '" or v.`hook_name` = "' . $hook_retro_name . '") AND ';
        }

        $sql .= ' v.`display_type` = 0 AND ';
        $sql .= ' v.`prd_page` = 1 AND ';
        $sql .= ' v.`active` = 1 ORDER BY v.`position` ASC';
        $cache_id = md5($sql);

        if (!CacheApi::isStored($cache_id)) {
            $results = Db::getInstance()->executeS($sql);
            $outputs = $this->ContentFilterEngine($results);
            CacheApi::store($cache_id, $outputs);
        }

        return CacheApi::retrieve($cache_id);
    }

    public function GetVcContentByAllCAT($hook_name = '') {

        $id_lang = (int) $this->context->language->id;
        $sql = 'SELECT v.*,vl.content,v.id_contentanywhere FROM `' . _DB_PREFIX_ . 'contentanywhere` v
                INNER JOIN `' . _DB_PREFIX_ . 'contentanywhere_lang` vl ON (v.`id_contentanywhere` = vl.`id_contentanywhere` AND vl.`id_lang` = ' . $id_lang . ')

                WHERE ';

        if (isset($hook_name) && !empty($hook_name)) {
            $hook_retro_name = $this->context->_hook->getRetroHookName($hook_name);
            $sql .= '( v.`hook_name` = "' . $hook_name . '" or v.`hook_name` = "' . $hook_retro_name . '") AND ';
        }

        $sql .= ' v.`display_type` = 0 AND ';
        $sql .= ' v.`cat_page` = 1 AND ';
        $sql .= ' v.`active` = 1 ORDER BY v.`position` ASC';
        $cache_id = md5($sql);

        if (!CacheApi::isStored($cache_id)) {
            $results = Db::getInstance()->executeS($sql);
            $outputs = $this->ContentFilterEngine($results);
            CacheApi::store($cache_id, $outputs);
        }

        return CacheApi::retrieve($cache_id);
    }

    public function GetVcContentByAllCMS($hook_name = '') {

        $id_lang = (int) $this->context->language->id;
        $sql = 'SELECT v.*,vl.content,v.id_contentanywhere FROM `' . _DB_PREFIX_ . 'contentanywhere` v
                INNER JOIN `' . _DB_PREFIX_ . 'contentanywhere_lang` vl ON (v.`id_contentanywhere` = vl.`id_contentanywhere` AND vl.`id_lang` = ' . $id_lang . ')

                WHERE ';

        if (isset($hook_name) && !empty($hook_name)) {
            $hook_retro_name = $this->context->_hook->getRetroHookName($hook_name);
            $sql .= '( v.`hook_name` = "' . $hook_name . '" or v.`hook_name` = "' . $hook_retro_name . '") AND ';
        }

        $sql .= ' v.`display_type` = 0 AND ';
        $sql .= ' v.`cms_page` = 1 AND ';
        $sql .= ' v.`active` = 1 ORDER BY v.`position` ASC';
        $cache_id = md5($sql);

        if (!CacheApi::isStored($cache_id)) {
            $results = Db::getInstance()->executeS($sql);
            $outputs = $this->ContentFilterEngine($results);
            CacheApi::store($cache_id, $outputs);
        }

        return CacheApi::retrieve($cache_id);
    }

    public function GetVcContentByAllCMSID($hook_name = '', $id_cms = 1) {

        $reslt = [];
        $id_lang = (int) $this->context->language->id;
        $sql = 'SELECT v.*,vl.content,v.id_contentanywhere FROM `' . _DB_PREFIX_ . 'contentanywhere` v
                INNER JOIN `' . _DB_PREFIX_ . 'contentanywhere_lang` vl ON (v.`id_contentanywhere` = vl.`id_contentanywhere` AND vl.`id_lang` = ' . $id_lang . ')

                WHERE ';

        if (isset($hook_name) && !empty($hook_name)) {
            $hook_retro_name = $this->context->_hook->getRetroHookName($hook_name);
            $sql .= '( v.`hook_name` = "' . $hook_name . '" or v.`hook_name` = "' . $hook_retro_name . '") AND ';
        }

        $sql .= ' v.`display_type` = 0 AND ';
        $sql .= ' v.`cms_page` = 0 AND ';
        $sql .= ' v.`active` = 1 ORDER BY v.`position` ASC';
        $cache_id = md5($sql);

        if (!CacheApi::isStored($cache_id)) {
            $results = Db::getInstance()->executeS($sql);

            if (isset($results) && !empty($results)) {
                $i = 0;

                foreach ($results as $result) {

                    if (isset($result['cms_specify']) && !empty($result['cms_specify'])) {
                        $cms_specify = explode(',', $result['cms_specify']);

                        if (isset($cms_specify) && !empty($cms_specify)) {

                            if (in_array($id_cms, $cms_specify)) {
                                $reslt[$i] = $result;
                            }

                        }

                    }

                    $i++;
                }

            }

            $outputs = $this->ContentFilterEngine($reslt);
            CacheApi::store($cache_id, $outputs);
        }

        return CacheApi::retrieve($cache_id);
    }

    public function GetVcContentByAllCATID($hook_name = '', $id_category = 1) {

        $reslt = [];
        $id_lang = (int) $this->context->language->id;

        $sql = 'SELECT v.*,vl.content,v.id_contentanywhere FROM `' . _DB_PREFIX_ . 'contentanywhere` v
                INNER JOIN `' . _DB_PREFIX_ . 'contentanywhere_lang` vl ON (v.`id_contentanywhere` = vl.`id_contentanywhere` AND vl.`id_lang` = ' . $id_lang . ')

                WHERE ';

        if (isset($hook_name) && !empty($hook_name)) {
            $hook_retro_name = $this->context->_hook->getRetroHookName($hook_name);
            $sql .= '( v.`hook_name` = "' . $hook_name . '" or v.`hook_name` = "' . $hook_retro_name . '") AND ';
        }

        $sql .= ' v.`display_type` = 0 AND ';
        $sql .= ' v.`cat_page` = 0 AND ';
        $sql .= ' v.`active` = 1 ORDER BY v.`position` ASC';
        $cache_id = md5($sql);

        if (!CacheApi::isStored($cache_id)) {
            $results = Db::getInstance()->executeS($sql);

            if (isset($results) && !empty($results)) {
                $i = 0;

                foreach ($results as $result) {

                    if (isset($result['cat_specify']) && !empty($result['cat_specify'])) {
                        $cat_specify = explode(',', $result['cat_specify']);

                        if (isset($cat_specify) && !empty($cat_specify)) {

                            if (in_array($id_category, $cat_specify)) {
                                $reslt[$i] = $result;
                            }

                        }

                    }

                    $i++;
                }

            }

            $outputs = $this->ContentFilterEngine($reslt);
            CacheApi::store($cache_id, $outputs);
        }

        return CacheApi::retrieve($cache_id);
    }

    public function GetVcContentByAllPRDID($hook_name = '', $id_product = 1) {

        $reslt = [];
        $id_lang = (int) $this->context->language->id;

        $sql = 'SELECT v.*,vl.content,v.id_contentanywhere FROM `' . _DB_PREFIX_ . 'contentanywhere` v
                INNER JOIN `' . _DB_PREFIX_ . 'contentanywhere_lang` vl ON (v.`id_contentanywhere` = vl.`id_contentanywhere` AND vl.`id_lang` = ' . $id_lang . ')

                WHERE ';

        if (isset($hook_name) && !empty($hook_name)) {
            $hook_retro_name = $this->context->_hook->getRetroHookName($hook_name);
            $sql .= '( v.`hook_name` = "' . $hook_name . '" or v.`hook_name` = "' . $hook_retro_name . '") AND ';
        }

        $sql .= ' v.`display_type` = 0 AND ';
        $sql .= ' v.`prd_page` = 0 AND ';
        $sql .= ' v.`active` = 1 ORDER BY v.`position` ASC';
        $cache_id = md5($sql);

        if (!CacheApi::isStored($cache_id)) {
            $results = Db::getInstance()->executeS($sql);

            if (isset($results) && !empty($results)) {
                $i = 0;

                foreach ($results as $result) {

                    if (isset($result['prd_specify']) && !empty($result['prd_specify'])) {
                        $prd_specify = explode('-', $result['prd_specify']);

                        if (isset($prd_specify) && !empty($prd_specify)) {
                            unset($prd_specify[count($prd_specify) - 1]);

                            if (in_array($id_product, $prd_specify)) {
                                $reslt[$i] = $result;
                            }

                        }

                    }

                    $i++;
                }

            }

            $outputs = $this->ContentFilterEngine($reslt);
            CacheApi::store($cache_id, $outputs);
        }

        return CacheApi::retrieve($cache_id);
    }

    public function GetVcContentByAllPRDCATID($hook_name = '', $id_prd_cat = 1) {

        $reslt = [];
        $id_lang = (int) $this->context->language->id;

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'contentanywhere` v
                INNER JOIN `' . _DB_PREFIX_ . 'contentanywhere_lang` vl ON (v.`id_contentanywhere` = vl.`id_contentanywhere` AND vl.`id_lang` = ' . $id_lang . ')

                WHERE ';

        if (isset($hook_name) && !empty($hook_name)) {
            $hook_retro_name = $this->context->_hook->getRetroHookName($hook_name);
            $sql .= '( v.`hook_name` = "' . $hook_name . '" or v.`hook_name` = "' . $hook_retro_name . '") AND ';
        }

        $sql .= ' v.`display_type` = 0 AND ';
        $sql .= ' v.`prd_page` = 0 AND ';
        $sql .= ' v.`active` = 1 ORDER BY v.`position` ASC';
        $cache_id = md5($sql);

        if (!CacheApi::isStored($cache_id)) {
            $results = Db::getInstance()->executeS($sql);

            if (isset($results) && !empty($results)) {
                $i = 0;

                foreach ($results as $result) {

                    if (isset($result['prd_specify']) && !empty($result['prd_specify'])) {
                        $prd_specify = explode(',', $result['prd_specify']);

                        if (isset($prd_specify) && !empty($prd_specify)) {

                            if (in_array('CAT_' . $id_prd_cat, $prd_specify)) {
                                $reslt[$i] = $result;
                            }

                        }

                    }

                    $i++;
                }

            }

            $outputs = $this->ContentFilterEngine($reslt);
            CacheApi::store($cache_id, $outputs);
        }

        return CacheApi::retrieve($cache_id);
    }

    public static function getProductsByCategoryID($category_id, $id_lang = null, $limit = false, $order_by = 'id_product', $order_way = "DESC") {

        $context = Context::getContext();
        $id_lang = is_null($id_lang) ? $context->language->id : $id_lang;
        $id_supplier = '';
        $active = true;
        $front = true;
        $sql = 'SELECT p.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, MAX(pA.id_product_attribute) id_product_attribute, pa.minimal_quantity AS product_attribute_minimal_quantity, pl.`description`, pl.`description_short`, pl.`available_now`,
                                    pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, MAX(i.`id_image`) id_image,
                                    il.`legend`, m.`name` AS manufacturer_name, cl.`name` AS category_default,
                                    DATEDIFF(p.`date_add`, DATE_SUB(NOW(),
                                    INTERVAL ' . (Validate::isUnsignedInt(Context::getContext()->phenyxConfig->get('EPH_NB_DAYS_NEW_PRODUCT')) ? Context::getContext()->phenyxConfig->get('EPH_NB_DAYS_NEW_PRODUCT') : 20) . '
                        DAY)) > 0 AS new, p.price AS orderprice
                FROM `' . _DB_PREFIX_ . 'category_product` cp
                LEFT JOIN `' . _DB_PREFIX_ . 'product` p
                    ON p.`id_product` = cp.`id_product`
                LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa
                ON (p.`id_product` = pa.`id_product`)

                ' . Product::sqlStock('p', 'product_attribute', false) . '
                LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl
                    ON (p.`id_category_default` = cl.`id_category`
                    AND cl.`id_lang` = ' . (int) $id_lang . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                    ON (p.`id_product` = pl.`id_product`
                    AND pl.`id_lang` = ' . (int) $id_lang . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'image` i
                    ON (i.`id_product` = p.`id_product`)
                LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il
                    ON (i.`id_image` = il.`id_image`
                    AND il.`id_lang` = ' . (int) $id_lang . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m
                    ON m.`id_manufacturer` = p.`id_manufacturer`
                WHERE cp.`id_category` = ' . (int) $category_id
            . ($active ? ' AND p.`active` = 1' : '')
            . ($front ? ' AND p.`visibility` IN ("both", "catalog")' : '')
            . ($id_supplier ? ' AND p.id_supplier = ' . (int) $id_supplier : '')
            . ' GROUP BY p.id_product';

        if (empty($order_by) || $order_by == 'position') {
            $order_by_prefix = 'cp';
        }

        if (empty($order_way)) {
            $order_way = 'DESC';
        }

        if ($order_by == 'id_product' || $order_by == 'price' || $order_by == 'date_add' || $order_by == 'date_upd') {
            $order_by_prefix = 'p';
        } else

        if ($order_by == 'name') {
            $order_by_prefix = 'pl';
        }

        $sql .= " ORDER BY {$order_by_prefix}.{$order_by} {$order_way}";

        if (!empty($limit) && is_numeric($limit)) {
            $sql .= " LIMIT {$limit}";
        }

        $cache_id = md5($sql);

        if (!CacheApi::isStored($cache_id)) {
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql);

            if (!$result) {
                return [];
            }

            $outputs = Product::getProductsProperties($id_lang, $result);
            CacheApi::store($cache_id, $outputs);
        }

        return CacheApi::retrieve($cache_id);
    }

    public function getSimpleProducts() {

        
        $id_lang = (int) $this->context->language->id;
        $front = true;

        if (!in_array($this->context->controller->controller_type, ['front', 'pluginfront'])) {
            $front = false;
        }

        $sql = 'SELECT p.`id_product`, pl.`name`
                FROM `' . _DB_PREFIX_ . 'product` p
                LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (p.`id_product` = pl.`id_product` )
                WHERE pl.`id_lang` = ' . (int) $id_lang . '
                ' . ($front ? ' AND p.`visibility` IN ("both", "catalog")' : '') . '
                ORDER BY pl.`name`';
        $cache_id = md5($sql);

        if (!CacheApi::isStored($cache_id)) {
            $outputs = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql);
            CacheApi::store($cache_id, $outputs);
        }

        return CacheApi::retrieve($cache_id);
    }

    public function getFilterValueByContentAnyWhereId($id, $page) {

        $sql = 'SELECT page, id_specify_page
                FROM `' . _DB_PREFIX_ . 'contentanywhere_filter`

                 WHERE  `id_contentanywhere` = ' . (int) $id . ' AND page =' . $page;

        $rs = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql);

        return $rs;
    }

    public function getProductsById($ids) {

        
        $id_lang = (int) $this->context->language->id;
        $front = true;

        if (!in_array($this->context->controller->controller_type, ['front', 'pluginfront'])) {
            $front = false;
        }

        if (empty($ids)) {
            return false;
        }

        $sqlids = '';

        if (is_string($ids)) {
            $ids = explode('-', $ids);
            unset($ids[count($ids) - 1]);

            foreach ($ids as $k => $id) {

                if ($k > 0) {
                    $sqlids .= ',';
                }

                $sqlids .= $id;
            }

        } else {

            foreach ($ids as $k => $id) {
                // print_r($id['id_specify_page']);

                if ($k > 0) {
                    $sqlids .= ',';
                }

                $sqlids .= $id['id_specify_page'];
            }

        }

        $limit = PhenyxTool::getInstance()->getValue('limit') ? pSQL(PhenyxTool::getInstance()->getValue('limit')) : 60;

        $sql = 'SELECT p.`id_product`, pl.`name`
                FROM `' . _DB_PREFIX_ . 'product` p
                LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (p.`id_product` = pl.`id_product` )
                WHERE pl.`id_lang` = ' . (int) $id_lang . '
                ' . ($front ? ' AND p.`visibility` IN ("both", "catalog")' : '') .
            ' AND p.`id_product` IN(' . $sqlids . ')' .
            'ORDER BY pl.`name` LIMIT ' . $limit;

        $rs = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql);
        $rslt = [];

        foreach ($rs as $i => $r) {
            $rslt[$i]['id_product'] = $r['id_product'];
            $rslt[$i]['name'] = $r['name'];
            $i++;
        }

        return $rslt;
    }

    public function getProductsByName() {

        
        $id_lang = (int) $this->context->language->id;
        $front = true;

        if (!in_array($this->context->controller->controller_type, ['front', 'pluginfront'])) {
            $front = false;
        }

        $q = PhenyxTool::getInstance()->getValue('q');
        $exid = PhenyxTool::getInstance()->getValue('excludeIds');
        $limit = PhenyxTool::getInstance()->getValue('limit');
        $exSql = '';

        if (!empty($exid)) {
            $exid = substr($exid, strlen($exid) - 1) == ',' ? substr($exid, 0, strrpos($exid, ',')) : $exid;
            $exSql .= ' AND p.`id_product` NOT IN(';
            $exSql .= $exid;
            $exSql .= ') ';
        }

        $sql = 'SELECT p.`id_product`, pl.`name`
                FROM `' . _DB_PREFIX_ . 'product` p
                LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (p.`id_product` = pl.`id_product` )
                WHERE pl.`id_lang` = ' . (int) $id_lang . '
                ' . ($front ? ' AND p.`visibility` IN ("both", "catalog")' : '') .
        ' AND pl.`name` LIKE "%' . pSQL($q) . '%" ' . $exSql .
            'ORDER BY pl.`name` LIMIT ' . $limit;

        $rs = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql, true, false);
        $rslt = '';

        foreach ($rs as $r) {
            $rslt .= $r['name'] . '&nbsp;|';
            $rslt .= $r['id_product'] . "\n";
        }

        return $rslt;
    }

    public function getCatsByName() {
        
        $id_lang = (int) $this->context->language->id;
        $limit = PhenyxTool::getInstance()->getValue('limit');
        $q = PhenyxTool::getInstance()->getValue('q');
        $exid = PhenyxTool::getInstance()->getValue('excludeIds');

        $exSql = '';

        if (!empty($exid)) {
            $exid = substr($exid, strlen($exid) - 1) == ',' ? substr($exid, 0, strrpos($exid, ',')) : $exid;
            $exSql .= ' AND p.`id_category` NOT IN(';
            $exSql .= $exid;
            $exSql .= ') ';
        }

        $sql = 'SELECT p.`id_category`, pl.`name`
                FROM `' . _DB_PREFIX_ . 'category` p
                LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` pl ON (p.`id_category` = pl.`id_category` )
                WHERE pl.`id_lang` = ' . (int) $id_lang . '
                 AND pl.`name` LIKE "%' . pSQL($q) . '%" ' . $exSql .
            'ORDER BY pl.`name` ASC LIMIT ' . $limit;

        $rs = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql, true, false);
        $rslt = '';

        foreach ($rs as $r) {
            $rslt .= $r['name'] . '&nbsp;|';
            $rslt .= $r['id_category'] . "\n";
        }

        return $rslt;
    }

    public function getManufacturersByName() {

        $q = PhenyxTool::getInstance()->getValue('q');
        $exid = PhenyxTool::getInstance()->getValue('excludeIds');
        $limit = PhenyxTool::getInstance()->getValue('limit');
        $exSql = '';

        if (!empty($exid)) {
            $exid = substr($exid, strlen($exid) - 1) == ',' ? substr($exid, 0, strrpos($exid, ',')) : $exid;
            $exSql .= ' AND p.`id_manufacturer` NOT IN(';
            $exSql .= $exid;
            $exSql .= ') ';
        }

        $sql = 'SELECT p.`id_manufacturer`, p.`name`
                FROM `' . _DB_PREFIX_ . 'manufacturer` p
                WHERE
                  p.`name` LIKE "%' . pSQL($q) . '%" ' . $exSql .
            'ORDER BY p.`name` ASC LIMIT ' . $limit;

        $rs = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql, true, false);
        $rslt = '';

        foreach ($rs as $r) {
            $rslt .= $r['name'] . '&nbsp;|';
            $rslt .= $r['id_manufacturer'] . "\n";
        }

        return $rslt;
    }

    public function getSuppliersByName() {

        $q = PhenyxTool::getInstance()->getValue('q');
        $exid = PhenyxTool::getInstance()->getValue('excludeIds');
        $limit = PhenyxTool::getInstance()->getValue('limit');
        $exSql = '';

        if (!empty($exid)) {
            $exid = substr($exid, strlen($exid) - 1) == ',' ? substr($exid, 0, strrpos($exid, ',')) : $exid;
            $exSql .= ' AND p.`id_supplier` NOT IN(';
            $exSql .= $exid;
            $exSql .= ') ';
        }

        $sql = 'SELECT p.`id_supplier`, p.`name`
                FROM `' . _DB_PREFIX_ . 'supplier` p
                WHERE
                  p.`name` LIKE "%' . pSQL($q) . '%" ' . $exSql .
            'ORDER BY p.`name` ASC LIMIT ' . $limit;

        $rs = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql, true, false);
        $rslt = '';

        foreach ($rs as $r) {
            $rslt .= $r['name'] . '&nbsp;|';
            $rslt .= $r['id_supplier'] . "\n";
        }

        return $rslt;
    }

    public static function getSelectedCategories($id_categories) {

        $context = Context::getContext();
        $id_lang = $context->language->id;
        $table_identifier = 'c';
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'category` c';

        $sql .= ' LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON c.`id_category` = cl.`id_category`';

        $sql .= ' WHERE 1 ' . ($id_lang ? 'AND `id_lang` = ' . (int) $id_lang : '') . '
            AND c.`id_category` IN(' . $id_categories . ')
            AND `active` = 1
            ORDER BY FIELD(' . $table_identifier . '.id_category,' . $id_categories . ')';

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql);

        return $result;
    }

    public static function getSelectedProducts($id_products, $order_by = null, $order_way = null) {

        $context = Context::getContext();
        $id_lang = $context->language->id;
        $front = true;

        if (!in_array($context->controller->controller_type, ['front', 'pluginfront'])) {
            $front = false;
        }

        $str = $id_products;

        if ($order_by == 'id_product' || $order_by == 'price' || $order_by == 'date_add' || $order_by == 'date_upd') {
            $order_by_prefix = 'p';
        } else
        if ($order_by == 'name') {
            $order_by_prefix = 'pl';
        }

        $sql = 'SELECT p.*,  pl.*, i.`id_image`, il.`legend`, m.`name` AS manufacturer_name, s.`name` AS supplier_name
                FROM `' . _DB_PREFIX_ . 'product` p
                LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (p.`id_product` = pl.`id_product` )
                LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
                LEFT JOIN `' . _DB_PREFIX_ . 'supplier` s ON (s.`id_supplier` = p.`id_supplier`)
                LEFT JOIN `' . _DB_PREFIX_ . 'image` i ON (i.`id_product` = p.`id_product`)
                LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int) Context::getContext()->language->id . ')
                WHERE pl.`id_lang` = ' . (int) $id_lang .
            ' AND p.`id_product` IN( ' . $str . ')' .
            ($front ? ' AND p.`visibility` IN ("both", "catalog")' : '') .
            ' AND ((i.id_image IS NOT NULL OR i.id_image IS NULL) OR (i.id_image IS NULL AND i.cover=1))' .
            ' AND p.`active` = 1';

        if (!empty($order_by) && isset($order_by_prefix)) {
            $sql .= " ORDER BY {$order_by_prefix}.{$order_by} {$order_way}";
        }

        $rq = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql);

        if (!$rq) {
            return [];
        }

        return Product::getProductsProperties($id_lang, $rq);
    }

    public function getproduct() {

        $rslt[0]['id_product'] = '0';
        $rslt[0]['name'] = 'None';
        $rs = $this->getSimpleProducts();
        $i = 1;

        foreach ($rs as $r) {
            $rslt[$i]['id_product'] = $r['id_product'];
            $rslt[$i]['name'] = $r['name'];
            $i++;
        }

        return $rslt;
    }

    public function getAllCMSPage() {

        $results = [];
        $results[0]['id_cms'] = 'none';
        $results[0]['name'] = 'None';
        $i = 1;
        $allcategories = CMS::listCms();

        foreach ($allcategories as $value) {
            $results[$i]['id_cms'] = $value['id_cms'];
            $results[$i]['name'] = $value['meta_title'];
            $i++;
        }

        return $results;
    }

    public function GetPluginHook($plugin_name = '', $hook_name = '') {

        $results = '';

        if (isset($plugin_name) && !empty($plugin_name)) {
            $hooks = $this->getPluginHooks($plugin_name);

            if (isset($hooks) && !empty($hooks)) {

                foreach ($hooks as $hook) {

                    if (isset($hook_name) && !empty($hook_name) && $hook['name'] == $hook_name) {
                        $results .= '<option value="' . $hook['name'] . '"selected="selected">' . $hook['name'] . "</option>";
                    } else {
                        $results .= '<option value="' . $hook['name'] . '">' . $hook['name'] . "</option>";
                    }

                }

            }

        }

        return $results;
    }

    public function getActivePlugins() {

        $results = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('`name`')
                ->from('plugin')
                ->where('active = 1'));

        $return = [];

        if (!empty($results)) {

            foreach ($results as $plugin) {
                $return[] = $plugin['name'];
            }

        }

        return $return;
    }

    public function GetAllPlugins() {

        $activePlugins = $this->getActivePlugins();

        require _EPH_COMPOSER_PARAM_DIR_. 'plugins_list.php';

        $include = $this->context->phenyxConfig->get('vc_include_plugins');
        $exclude = $this->context->phenyxConfig->get('vc_exclude_plugins');

        if (!empty($include)) {
            $include = explode("\n", $include);

            foreach ($include as $inc) {
                $inc = trim($inc);

                if (Validate::isPluginName($inc) && in_array($inc, $activePlugins) && !in_array($inc, $plugins_list) && !in_array($inc, $this->skip_plugins)
                ) {
                    $plugins_list[] = $inc;
                }

            }

        }

        if (!empty($exclude)) {
            $exclude = explode("\n", $exclude);

            foreach ($exclude as $inc) {
                $inc = trim($inc);

                if (Validate::isPluginName($inc) && in_array($inc, $activePlugins) && ($index = array_search($inc, $plugins_list)) !== FALSE
                ) {
                    unset($plugins_list[$index]);
                }

            }

        }

        return $plugins_list;
    }

    public function GetAllFilterPlugins() {

        $results = [];

        $AllPlugins = $this->GetAllPlugins();

        if (isset($AllPlugins) && !empty($AllPlugins)) {
            $i = 0;

            foreach ($AllPlugins as $mod) {

                if ($this->getPluginHooks($mod)) {
                    $results[$i]['id'] = $mod;
                    $results[$i]['name'] = Plugin::getPluginName($mod);
                    $i++;
                }

            }

        }

        return $results;
    }

    public function GetAllHooks() {

        $results = [];
        $support_hooks = [];
        require _EPH_COMPOSER_PARAM_DIR_. 'support_hooks.php';

        if (isset($support_hooks) && !empty($support_hooks)) {
            $i = 0;

            foreach ($support_hooks as $value) {

                if (!empty($value)) {
                    $results[$i]['id'] = $this->context->_hook->getRetroHookName($value);
                    $results[$i]['name'] = $this->context->_hook->getRetroHookName($value);
                    $i++;
                }

            }

        }

        return $results;
    }

    public function getPluginHooks($plugin = '') {

        $support_hooks = [];
        require _EPH_COMPOSER_PARAM_DIR_ . 'support_hooks.php';
        $plugin_Ins = Plugin::getInstanceByName($plugin);
        $hooks = [];

        if (isset($support_hooks) && !empty($support_hooks)) {

            foreach ($support_hooks as $support_hook) {
                $support_retro_hook = $this->context->_hook->getRetroHookName($support_hook);

                if (is_callable([$plugin_Ins, 'hook' . $support_hook]) || is_callable([$plugin_Ins, 'hook' . $support_retro_hook])) {

                    if (empty($support_retro_hook)) {
                        $support_retro_hook = $support_hook;
                    }

                    $hooks[] = ['id' => $support_retro_hook, 'name' => $support_retro_hook];
                }

            }

        }

        return $hooks;
    }

    public function getPluginHookbyedit($plugin = '') {

        $reslt = [];
        $support_hooks = [];
        require _EPH_COMPOSER_PARAM_DIR_ . 'support_hooks.php';
        $plugin_Ins = Plugin::getInstanceByName($plugin);
        $hooks = [];

        if (isset($support_hooks) && !empty($support_hooks)) {

            foreach ($support_hooks as $support_hook) {
                $support_retro_hook = $this->context->_hook->getRetroHookName($support_hook);

                if (is_callable([$plugin_Ins, 'hook' . $support_hook]) || is_callable([$plugin_Ins, 'hook' . $support_retro_hook])) {

                    if (empty($support_retro_hook)) {
                        $support_retro_hook = $support_hook;
                    }

                    $hooks[] = ['id' => $support_retro_hook, 'name' => $support_retro_hook];
                }

            }

        }

        return $hooks;

    }

    public function GetHookName($hooks = []) {

        $results = [];

        if (isset($hooks) && !empty($hooks)) {
            $sql = 'SELECT `id_hook`, `name`
            FROM `' . _DB_PREFIX_ . 'hook`
            WHERE `name` IN (\'' . implode("','", $hooks) . '\')';
            $cache_id = md5($sql);

            if (!CacheApi::isStored($cache_id)) {
                $results = Db::getInstance()->ExecuteS($sql);
                CacheApi::store($cache_id, $results);
            }

            return CacheApi::retrieve($cache_id);
        } else {
            return $results;
        }

    }

    public function GetPluginsList($type = 'plugin', $mod_name = '') {

        $GetAllplugins_list = [];
        $plugins_list = $this->getAllPlugins();

        if ($type == 'plugin') {

            if (isset($plugins_list) && !empty($plugins_list)) {
                $i = 0;

                foreach ($plugins_list as $key => $value) {

                    if (Plugin::isInstalled($key)) {
                        $GetAllplugins_list[$i]['id'] = $key;
                        $GetAllplugins_list[$i]['name'] = $key;
                        $i++;
                    }

                }

            }

        } else
        if ($type == 'hook') {

            if (isset($plugins_list[$mod_name]) && !empty($plugins_list[$mod_name])) {
                $i = 0;

                foreach ($plugins_list[$mod_name] as $key => $value) {

                    if (Plugin::isInstalled($key)) {
                        $GetAllplugins_list[$i]['id'] = $value;
                        $GetAllplugins_list[$i]['name'] = $value;
                        $i++;
                    }

                }

            }

        }

        return $GetAllplugins_list;
    }

    public function getProductCategories($id_product = 1) {

        $reslt = [];
        $sql = 'SELECT cp.`id_category` AS id
            FROM `' . _DB_PREFIX_ . 'category_product` cp
            LEFT JOIN `' . _DB_PREFIX_ . 'category` c ON (c.id_category = cp.id_category)
            WHERE cp.`id_product` = ' . (int) $id_product;
        $cache_id = md5($sql);

        if (!CacheApi::isStored($cache_id)) {
            $results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql);

            if (isset($results) && !empty($results)) {

                foreach ($results as $result) {
                    $reslt[] = $result['id'];
                }

            }

            CacheApi::store($cache_id, $reslt);
        }

        return CacheApi::retrieve($cache_id);
    }

    public function getAllProductsByCats() {

        $results = [];
        $results[0]['id_product'] = 'none';
        $results[0]['name'] = 'None';
        $i = 1;
        $allcategories = $this->generateCategoriesOption(Category::getNestedCategories(null, (int) $this->context->language->id, true));

        foreach ($allcategories as $value) {
            $results[$i]['id_product'] = 'CAT_' . $value['id_category'];
            $results[$i]['name'] = 'Category-------' . $value['name'];
            $catproducts = self::getProductsByCategoryID($value['id_category']);

            if (isset($catproducts) && !empty($catproducts)) {

                foreach ($catproducts as $catproduct) {
                    $i++;
                    $results[$i]['id_product'] = 'PRD_' . $catproduct['id_product'];
                    $results[$i]['name'] = $catproduct['name'];
                }

            }

            $i++;
        }

        return $results;
    }

    public function generatesubCategoriesOption($categories, $items_to_skip = null) {

        $subcatvals = [];
        $spacer_size = '5';
        $this->element_index++;

        foreach ($categories as $key => $category) {
            $this->smartcat[$this->element_index]['id_category'] = $category['id_category'];
            $this->smartcat[$this->element_index]['name'] = str_repeat('&nbsp;', $spacer_size * (int) $category['level_depth']) . $category['name'];

            if (isset($category['children'])) {
                $this->generatesubCategoriesOption($category['children']);
            }

            $this->element_index++;
        }

        return true;
    }

    public function generateCategoriesOption($categories, $items_to_skip = null) {

        $subcatvals = [];
        $spacer_size = '3';
        $this->smartcat[0]['id_category'] = 'none';
        $this->smartcat[0]['name'] = 'None';
        $this->element_index = 1;

        foreach ($categories as $key => $category) {
            $this->smartcat[$this->element_index]['id_category'] = $category['id_category'];
            $this->smartcat[$this->element_index]['name'] = str_repeat('&nbsp;', $spacer_size * (int) $category['level_depth']) . $category['name'];

            if (isset($category['children'])) {
                $this->generatesubCategoriesOption($category['children']);
            }

            $this->element_index++;
        }

        return $this->smartcat;
    }

    
    public function updatePosition($way, $position) {

        if (!$res = Db::getInstance()->executeS('
            SELECT `id_contentanywhere`, `position`
            FROM `' . _DB_PREFIX_ . 'contentanywhere`
            ORDER BY `position` ASC'
        )) {
            return false;
        }

        foreach ($res as $contentanywhere) {

            if ((int) $contentanywhere['id_contentanywhere'] == (int) $this->id) {
                $moved_contentanywhere = $contentanywhere;
            }

        }

        if (!isset($moved_contentanywhere) || !isset($position)) {
            return false;
        }

        $query_1 = ' UPDATE `' . _DB_PREFIX_ . 'contentanywhere`
        SET `position`= `position` ' . ($way ? '- 1' : '+ 1') . '
        WHERE `position`
        ' . ($way ? '> ' . (int) $moved_contentanywhere['position'] . ' AND `position` <= ' . (int) $position : '< ' . (int) $moved_contentanywhere['position'] . ' AND `position` >= ' . (int) $position . '
        ');
        $query_2 = ' UPDATE `' . _DB_PREFIX_ . 'contentanywhere`
        SET `position` = ' . (int) $position . '
        WHERE `id_contentanywhere` = ' . (int) $moved_contentanywhere['id_contentanywhere'];
        return (Db::getInstance()->execute($query_1) && Db::getInstance()->execute($query_2));
    }

    public function GetVcContentByAllException($hook_name = '', $page = '') {

        $id_lang = (int)$this->context->language->id;

        $sql = 'SELECT v.*,v.id_contentanywhere FROM `' . _DB_PREFIX_ . 'contentanywhere` v
                    INNER JOIN `' . _DB_PREFIX_ . 'contentanywhere_lang` vl ON (v.`id_contentanywhere` = vl.`id_contentanywhere` AND vl.`id_lang` = ' . $id_lang . ')
                    WHERE ';

        if (isset($hook_name) && !empty($hook_name)) {
            $hook_retro_name = $this->context->_hook->getRetroHookName($hook_name);
            $sql .= '( v.`hook_name` = "' . $hook_name . '" or v.`hook_name` = "' . $hook_retro_name . '") AND ';
        }

        $sql .= ' v.`exception_type` = 1 AND  v.`exception` LIKE "%' . $page . '%"  AND ';
        $sql .= ' v.`display_type` = 1 AND ';
        $sql .= ' v.`active` = 1 ORDER BY v.`position` ASC';
        $cache_id = md5($sql);

        if (!CacheApi::isStored($cache_id)) {
            $results = Db::getInstance()->executeS($sql);
            $outputs = $this->ContentFilterEngine($results);
            CacheApi::store($cache_id, $outputs);
        }

        return CacheApi::retrieve($cache_id);
    }

    public static function displayPluginExceptionList() {

        $results = [];
        $controllers = Performer::getControllers(_EPH_FRONT_CONTROLLER_DIR_);
        ksort($controllers);
        $i = 0;

        if (isset($controllers) && !empty($controllers)) {

            foreach ($controllers as $key => $value) {
                $results[$i]['id_exception'] = $key;
                $results[$i]['name'] = $key;
                $i++;
            }

        }

        $all_plugins_controllers = Performer::getPluginControllers('front');

        if (isset($all_plugins_controllers) && !empty($all_plugins_controllers)) {

            foreach ($all_plugins_controllers as $plugin => $plugins_controllers) {

                if (isset($plugins_controllers) && !empty($plugins_controllers)) {

                    foreach ($plugins_controllers as $cont) {
                        $results[$i]['id_exception'] = 'plugin-' . $plugin . '-' . $cont;
                        $results[$i]['name'] = 'plugin-' . $plugin . '-' . $cont;
                        $i++;
                    }

                }

            }

        }

        return $results;
    }

    /**
     * Delete product accessories
     *
     * @return mixed Deletion result
     */
    public function deleteContentAnywherProductAccessories($option_page) {

        return Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'contentanywhere_filter` WHERE `id_contentanywhere` = ' . (int) $this->id . ' AND page = ' . $option_page);
    }

}
