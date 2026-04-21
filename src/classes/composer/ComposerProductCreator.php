<?php


class ComposerProductCreator extends PhenyxObjectModel {

    protected static $instance;
    public $id_vcproducttabcreator;
    public $active = 1;
    public $prd_page;
    public $prd_specify;
    public $content_type;
    public $PLUGINs_list;
    public $PLUGIN_hook_list;
    public $position;
    //lang field
    public $generated;
    public $title;
    public $content;

    public static $definition = [
        'table'     => 'vcproducttabcreator',
        'primary'   => 'id_vcproducttabcreator',
        'multilang' => true,
        'fields'    => [
            'content_type'     => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'PLUGINs_list'     => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'PLUGIN_hook_list' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'prd_page'         => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'prd_specify'      => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'position'         => ['type' => self::TYPE_INT],
            'active'           => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'generated' => ['type' => self::TYPE_BOOL, 'lang' => true],
            'title'            => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => true],
            'content'          => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isString'],
        ],
    ];
    public function __construct($id = null, $id_lang = null) {

        parent::__construct($id, $id_lang);
    }

    public function add($autodate = true, $null_values = false) {

        if ($this->position <= 0) {
            $this->position = ComposerProductCreato::getHigherPosition() + 1;
        }

        if (!parent::add($autodate, $null_values) || !Validate::isLoadedObject($this)) {
            return false;
        }

        return true;
    }

    public static function getHigherPosition() {

        $sql = 'SELECT MAX(`position`)
                FROM `' . _DB_PREFIX_ . 'vcproducttabcreator`';
        $position = DB::getInstance()->getValue($sql);
        return (is_numeric($position)) ? $position : -1;
    }
    
    
    public static function getInstance($id = null, $idLang = null) {

        if (!ComposerProductCreator::$instance) {
            ComposerProductCreator::$instance = new ComposerProductCreator($id, $idLang);
        }

        return ComposerProductCreator::$instance;
    }

    public function updatePosition($way, $position) {

        if (!$res = Db::getInstance()->executeS('
            SELECT `id_vcproducttabcreator`, `position`
            FROM `' . _DB_PREFIX_ . 'vcproducttabcreator`
            ORDER BY `position` ASC'
        )) {
            return false;
        }

        foreach ($res as $vcproducttabcreator) {
            if ((int) $vcproducttabcreator['id_vcproducttabcreator'] == (int) $this->id) {
                $moved_vcproducttabcreator = $vcproducttabcreator;
            }
        }

        if (!isset($moved_vcproducttabcreator) || !isset($position)) {
            return false;
        }

        $query_1 = ' UPDATE `' . _DB_PREFIX_ . 'vcproducttabcreator`
        SET `position`= `position` ' . ($way ? '- 1' : '+ 1') . '
        WHERE `position`
        ' . ($way
            ? '> ' . (int) $moved_vcproducttabcreator['position'] . ' AND `position` <= ' . (int) $position
            : '< ' . (int) $moved_vcproducttabcreator['position'] . ' AND `position` >= ' . (int) $position . '
        ');
        $query_2 = ' UPDATE `' . _DB_PREFIX_ . 'vcproducttabcreator`
        SET `position` = ' . (int) $position . '
        WHERE `id_vcproducttabcreator` = ' . (int) $moved_vcproducttabcreator['id_vcproducttabcreator'];
        return (Db::getInstance()->execute($query_1)
            && Db::getInstance()->execute($query_2));
    }

    public function GetTabContentByPRDID($id_product = 1) {

        $reslt = [];
        $id_lang = (int) $this->context->language->id;
        $id_company = (int) $this->context->company->id;
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'vcproducttabcreator` v
                INNER JOIN `' . _DB_PREFIX_ . 'vcproducttabcreator_lang` vl ON (v.`id_vcproducttabcreator` = vl.`id_vcproducttabcreator` AND vl.`id_lang` = ' . $id_lang . ')                
                WHERE ';
        $sql .= ' v.`active` = 1 ORDER BY v.`position` ASC';
        $cache_id = md5($sql);

        if (!CacheApi::isStored($cache_id)) {
            $results = Db::getInstance()->executeS($sql);

            if (isset($results) && !empty($results)) {

                foreach ($results as $i => $result) {

                    if (isset($result['prd_page']) && $result['prd_page'] == 1) {
                        $reslt[$i] = $result;
                    } else {
//                        $vccontentany = new VcContentAnyWhere();
                        //                        $id_prd_cats = $vccontentany->getProductCategories($id_product);
                        $prd_specify_arr = explode('-', $result['prd_specify']);

                        if (isset($prd_specify_arr) && !empty($prd_specify_arr)) {
                            unset($prd_specify_arr[count($prd_specify_arr) - 1]);

                            if (in_array($id_product, $prd_specify_arr)) {
//                                $result['content'] = JsComposer::do_shortcode($result['content']);
                                $reslt[$i] = $result;
                            }

                        }

                    }

                }

            }

            $outputs = $this->ContentFilterEngine($reslt);
            CacheApi::store($cache_id, $outputs);
        }

        return CacheApi::retrieve($cache_id);
    }

    public function ContentFilterEngine($results = []) {

        $outputs = [];

        if (isset($results) && !empty($results)) {
            $i = 0;

            foreach ($results as $vcvalues) {

                foreach ($vcvalues as $vckey => $vcval) {

                    if ($vckey == 'content') {
                        $outputs[$i]['content'] = JsComposer::vc_content_filter($vcval);
                    }

                    if ($vckey == 'title') {
                        $outputs[$i]['title'] = $vcval;
                    }

                    if ($vckey == 'id_vcproducttabcreator') {
                        $outputs[$i]['id_vcproducttabcreator'] = $vcval;
                    }

                    if ($vckey == 'content_type') {
                        $outputs[$i]['content_type'] = $vcval;
                    }

                    if ($vckey == 'PLUGINs_list') {
                        $outputs[$i]['PLUGINs_list'] = $vcval;
                    }

                    if ($vckey == 'PLUGIN_hook_list') {
                        $outputs[$i]['PLUGIN_hook_list'] = $vcval;
                    }

                }

                $i++;
            }

        }

        return $outputs;
    }

}
