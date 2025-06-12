<?php

/**
 * Class Meta
 *
 * @since 1.9.1.0
 */
class Meta extends PhenyxObjectModel {

    protected static $instance;
    // @codingStandardsIgnoreStart
    public $page;
    public $controller;
    public $plugin;
    public $configurable = 1;
    public $generated;
    public $title;
    public $description;
    public $keywords;
    public $url_rewrite;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'meta',
        'primary'   => 'id_meta',
        'multilang' => true,
        'fields'    => [
            'page'         => ['type' => self::TYPE_STRING, 'validate' => 'isFileName', 'required' => true, 'size' => 64],
            'controller'   => ['type' => self::TYPE_STRING, 'validate' => 'isFileName', 'size' => 12],
            'plugin'       => ['type' => self::TYPE_STRING],
            'configurable' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],

            /* Lang fields */
            'generated'    => ['type' => self::TYPE_BOOL, 'lang' => true],
            'title'        => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 128],
            'description'  => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
            'keywords'     => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
            'url_rewrite'  => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isLinkRewrite', 'size' => 255],
        ],
    ];

    public function __construct($id = null) {

        parent::__construct($id);
    }

    public static function getInstance($id = null, $idLang = null) {

        if (!isset(static::$instance)) {
            static::$instance = new Meta($id, $idLang);
        }

        return static::$instance;
    }

    public function add($autoDate = true, $nullValues = true) {

        $success = parent::add($autoDate, $nullValues);

        if ($success) {

            if ($this->context->cache_enable && is_object($this->context->cache_api)) {
                $this->context->cache_api->cleanByStartingKey('metaGetPages_');
            }

        }

        return $success;
    }

    public function update($nullValues = false) {

        $result = parent::update(true);

        if ($result) {

            if ($this->context->cache_enable && is_object($this->context->cache_api)) {
                $this->context->cache_api->cleanByStartingKey('metaGetPages_');
            }

            Tools::generateHtaccess();
        }

        return $result;
    }

    public static function getPages($excludeFilled = false, $addPage = false, $pageExludes = true) {

        $context = Context::getContext();

        if ($context->cache_enable && is_object($context->cache_api)) {
            $addPg = isset($addPage) ? 1 : 0;
            $pageExs = isset($pageExludes) ? 1 : 0;
            $value = $context->cache_api->getData('metaGetPages_' . $excludeFilled . '_' . $addPg . '_' . $pageExs, 864000);
            $temp = empty($value) ? null : Tools::jsonDecode($value, true);

            if (!empty($temp)) {
                return $temp;
            }

        }

        $plugins = Plugin::getPluginsInstalled();

        $selectedPages = [];
        $pluginFiles = [];
        $adminPluginFiles = [];
        $adminFiles = [];
        $extraAdminFiles = [];

        if ($pageExludes) {
            $exludePages = [
                'cms',
                'footer',
                'header',
                'pfgmodel',
            ];
            $extraPages = $context->_hook->exec('actionMetaGetExtraPages', [], null, true);

            if (is_array($extraPages) && count($extraPages)) {

                foreach ($extraPages as $plugin => $pages) {

                    if (is_array($pages) && count($pages)) {

                        foreach ($pages as $key => $value) {
                            $exludePages[] = $value;
                        }

                    }

                }

            }

            if ($addPage) {

                $exludePages = array_merge(
                    $exludePages,
                    self::getReferentPages()
                );

            }

        } else {
            $exludePages = [];
        }

        if (!$files = Tools::scandir(_EPH_CORE_DIR_ . DIRECTORY_SEPARATOR . '/includes/controllers/front' . DIRECTORY_SEPARATOR, 'php', '', true)) {
            die(Tools::displayError('Cannot scan front root directory'));
        }

        if (!$extraFrontFiles = Tools::scandir(_EPH_CORE_DIR_ . DIRECTORY_SEPARATOR . 'includes/specific_controllers/front' . DIRECTORY_SEPARATOR, 'php', '', true)) {
            die(Tools::displayError('Cannot scan specific front controllers directory'));
        }

        if (is_array($extraFrontFiles) && count($extraFrontFiles)) {
            $files = array_values(array_unique(array_merge($files, $extraFrontFiles)));
        }

        if (!$adminFiles = Tools::scandir(_EPH_CORE_DIR_ . DIRECTORY_SEPARATOR . 'includes/controllers/backend' . DIRECTORY_SEPARATOR, 'php', '', true)) {
            die(Tools::displayError('Cannot scan admin root directory'));
        }

        if (!$extraAdminFiles = Tools::scandir(_EPH_CORE_DIR_ . DIRECTORY_SEPARATOR . 'includes/specific_controllers/backend' . DIRECTORY_SEPARATOR, 'php', '', true)) {
            die(Tools::displayError('Cannot scan specific controllers directory'));
        }

        if (is_array($extraAdminFiles) && count($extraAdminFiles)) {
            $adminFiles = array_values(array_unique(array_merge($adminFiles, $extraAdminFiles)));
        }

        foreach ($adminFiles as $file) {

            if ($file != 'index.php' && !in_array(strtolower(str_replace('Controller.php', '', $file)), $exludePages)) {
                $className = str_replace('.php', '', $file);
                $reflection = class_exists($className) ? new ReflectionClass(str_replace('.php', '', $file)) : false;
                $properties = $reflection ? $reflection->getDefaultProperties() : [];

                if (isset($properties['php_self'])) {
                    $selectedPages['admin'][$properties['php_self']] = $properties['php_self'];
                } else

                if (preg_match('/^[a-z0-9_.-]*\.php$/i', $file)) {
                    $selectedPages['admin'][strtolower(str_replace('Controller.php', '', $file))] = strtolower(str_replace('Controller.php', '', $file));
                } else

                if (preg_match('/^([a-z0-9_.-]*\/)?[a-z0-9_.-]*\.php$/i', $file)) {
                    $selectedPages['admin'][strtolower(sprintf(Tools::displayError('%2$s (in %1$s)'), dirname($file), str_replace('Controller.php', '', basename($file))))] = strtolower(str_replace('Controller.php', '', basename($file)));
                }

            }

        }

        foreach ($plugins as $plugin) {

            if (is_dir(_EPH_PLUGIN_DIR_ . $plugin['name'])) {

                foreach (glob(_EPH_PLUGIN_DIR_ . $plugin['name'] . '/controllers/admin/*.php') as $file) {
                    $file = str_replace(_EPH_PLUGIN_DIR_ . $plugin['name'] . '/controllers/admin/', '', $file);

                    if ($file == 'index.php') {
                        continue;
                    }

                    $className = str_replace('.php', '', $file);
                    $reflection = class_exists($className) ? new ReflectionClass(str_replace('.php', '', $file)) : false;
                    $properties = $reflection ? $reflection->getDefaultProperties() : [];

                    if (isset($properties['php_self']) && !in_array($properties['php_self'], $exludePages)) {
                        $selectedPages['admin'][$plugin['name'] . '/' . $properties['php_self']] = $properties['php_self'] . ' (' . $plugin['name'] . ')';
                    }

                }

            }

            if (is_dir(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin['name'])) {

                foreach (glob(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin['name'] . '/controllers/admin/*.php') as $file) {
                    $file = str_replace(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin['name'] . '/controllers/admin/', '', $file);

                    if ($file == 'index.php') {
                        continue;
                    }

                    $className = str_replace('.php', '', $file);
                    $reflection = class_exists($className) ? new ReflectionClass(str_replace('.php', '', $file)) : false;
                    $properties = $reflection ? $reflection->getDefaultProperties() : [];

                    if (isset($properties['php_self']) && !in_array($properties['php_self'], $exludePages)) {
                        $selectedPages['admin'][$plugin['name'] . '/' . $properties['php_self']] = $properties['php_self'] . ' (' . $plugin['name'] . ')';
                    }

                }

            }

        }

        foreach ($plugins as $plugin) {

            if (is_dir(_EPH_PLUGIN_DIR_ . $plugin['name'])) {

                foreach (glob(_EPH_PLUGIN_DIR_ . $plugin['name'] . '/controllers/front/*.php') as $file) {
                    $file = str_replace(_EPH_PLUGIN_DIR_ . $plugin['name'] . '/controllers/front/', '', $file);

                    if ($file == 'index.php') {
                        continue;
                    }

                    $className = str_replace('.php', '', $file);
                    $reflection = class_exists($className) ? new ReflectionClass(str_replace('.php', '', $file)) : false;
                    $properties = $reflection ? $reflection->getDefaultProperties() : [];
                    $_GET['plugin'] = $plugin['name'];
                    $tmpPlugin = new $className();

                    if ($tmpPlugin instanceof PluginFrontController) {

                        if (isset($properties['php_self']) && !in_array($properties['php_self'], $exludePages)) {
                            $selectedPages['plugin'][$plugin['name'] . '/' . $properties['php_self']] = $properties['php_self'] . ' (' . $plugin['name'] . ')';
                        }

                    } else {

                        if (isset($properties['php_self']) && !in_array($properties['php_self'], $exludePages)) {
                            $selectedPages['front'][$properties['php_self']] = $properties['php_self'];
                        }

                    }

                }

            }

        }

        foreach ($plugins as $plugin) {

            if (is_dir(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin['name'])) {

                foreach (glob(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin['name'] . '/controllers/front/*.php') as $file) {
                    $file = str_replace(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin['name'] . '/controllers/front/', '', $file);

                    if ($file == 'index.php') {
                        continue;
                    }

                    $className = str_replace('.php', '', $file);
                    $reflection = class_exists($className) ? new ReflectionClass(str_replace('.php', '', $file)) : false;
                    $properties = $reflection ? $reflection->getDefaultProperties() : [];
                    $_GET['plugin'] = $plugin['name'];
                    $tmpPlugin = new $className();

                    if ($tmpPlugin instanceof PluginFrontController) {

                        if (isset($properties['php_self']) && !in_array($properties['php_self'], $exludePages)) {
                            $selectedPages['plugin'][$plugin['name'] . '/' . $properties['php_self']] = $properties['php_self'] . ' (' . $plugin['name'] . ')';
                        }

                    } else {

                        if (isset($properties['php_self']) && !in_array($properties['php_self'], $exludePages)) {
                            $selectedPages['front'][$properties['php_self']] = $properties['php_self'];
                        }

                    }

                }

            }

        }

        if (!$overrideFiles = Tools::scandir(_EPH_CORE_DIR_ . DIRECTORY_SEPARATOR . 'includes/override' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'front' . DIRECTORY_SEPARATOR, 'php', '', true)) {
            die(Tools::displayError('Cannot scan "override" directory'));
        }

        if (is_array($overrideFiles) && count($overrideFiles)) {
            $files = array_values(array_unique(array_merge($files, $overrideFiles)));
        }

        // Exclude pages forbidden

        foreach ($files as $file) {

            if ($file != 'index.php' && !in_array(strtolower(str_replace('Controller.php', '', $file)), $exludePages)) {
                $className = str_replace('.php', '', $file);
                $reflection = class_exists($className) ? new ReflectionClass(str_replace('.php', '', $file)) : false;
                $properties = $reflection ? $reflection->getDefaultProperties() : [];

                if (isset($properties['php_self'])) {
                    $selectedPages['front'][$properties['php_self']] = $properties['php_self'];

                } else

                if (preg_match('/^[a-z0-9_.-]*\.php$/i', $file)) {
                    $selectedPages['front'][strtolower(str_replace('Controller.php', '', $file))] = strtolower(str_replace('Controller.php', '', $file));
                } else

                if (preg_match('/^([a-z0-9_.-]*\/)?[a-z0-9_.-]*\.php$/i', $file)) {
                    $selectedPages['front'][strtolower(sprintf(Tools::displayError('%2$s (in %1$s)'), dirname($file), str_replace('Controller.php', '', basename($file))))] = strtolower(str_replace('Controller.php', '', basename($file)));
                }

            }

        }

        // Add plugins controllers to list (this function is cool !)

        // Exclude page already filled

        if ($excludeFilled) {
            $metas = Meta::getMetas();

            foreach ($metas as $meta) {

                if (in_array($meta['page'], $selectedPages)) {
                    unset($selectedPages[array_search($meta['page'], $selectedPages)]);
                }

            }

        }

        // Add selected page

        if ($addPage) {
            $name = $addPage;

            if (preg_match('#plugin-([a-z0-9_-]+)-([a-z0-9]+)$#i', $addPage, $m)) {
                $addPage = $m[1] . ' - ' . $m[2];
            }

            $selectedPages[$addPage] = $name;

        }

        ksort($selectedPages);

        if ($context->cache_enable && is_object($context->cache_api)) {
            $temp = $selectedPages === null ? null : Tools::jsonEncode($selectedPages);
            $context->cache_api->putData('metaGetPages_' . $excludeFilled . '_' . $addPg . '_' . $pageExs, $temp);
        }

        return $selectedPages;
    }

    public static function cleanPluginMeta() {

        $tools = new PhenyxTools();

        $metas = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('id_meta, plugin')
                ->from('meta')
                ->where('`plugin` != \'\'')
        );

        if (is_array($tools->plugins)) {

            foreach ($metas as $meta) {

                if (array_key_exists($meta['plugin'], $tools->plugins)) {
                    continue;
                }

                $meta = new Meta((int) $meta['id_meta']);
                $meta->delete();
            }

        }

    }

    public static function getPluginControllerPage($pluginName, $controller = null) {

        $context = Context::getContext();
        $adminPluginFiles = [];
        $plugin = Plugin::getInstanceByName($pluginName);

        if (is_dir(_EPH_PLUGIN_DIR_ . $plugin->name)) {
            $local_path = _EPH_PLUGIN_DIR_ . $plugin->name . '/';
        } else

        if (is_dir(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin->name)) {
            $local_path = _EPH_SPECIFIC_PLUGIN_DIR_ . $plugin->name . '/';
        }

        if (is_null($controller)) {

            foreach (glob($local_path . 'controllers/admin/*.php') as $file) {
                $filename = basename($file, '.php');

                if ($filename == 'index') {
                    continue;
                }

                $adminPluginFiles[] = str_replace('Controller', '', basename($filename));
            }

        } else {

            if (file_exists($local_path . 'controllers/admin/' . $controller . '.php')) {
                $adminPluginFiles[] = str_replace('Controller.php', '', $controller);
            }

        }

        if (count($adminPluginFiles)) {
            $metas = Meta::getMetas();

            foreach ($metas as $meta) {

                if (in_array($meta['page'], array_map('strtolower', $adminPluginFiles))) {
                    unset($adminPluginFiles[array_search($meta['page'], $adminPluginFiles)]);
                }

            }

        }

        if (count($adminPluginFiles)) {

            foreach ($adminPluginFiles as $pluginController) {
                $id_tab = (int) BackTab::getIdFromClassName($pluginController);

                if ($id_tab > 0) {
                    $tab = new BackTab($id_tab);
                    $link_rewrite = Tools::str2url($tab->name[$context->language->id]);
                    $meta = new Meta();
                    $meta->controller = 'admin';
                    $meta->page = strtolower($pluginController);
                    $meta->plugin = $plugin->name;

                    foreach (Language::getLanguages(false) as $language) {
                        $meta->title[$language['id_lang']] = $tab->name[$language['id_lang']];
                        $meta->url_rewrite[$language['id_lang']] = Tools::str2url($tab->name[$language['id_lang']]);
                    }

                    $meta->add();
                }

            }

        }

        return true;
    }

    public static function getMetas() {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('meta')
                ->orderBy('`page` ASC')
        );
    }

    public static function getIdMetaByPage($page) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('id_meta')
                ->from('meta')
                ->where('page = \'' . pSQL($page) . '\'')
        );
    }

    public static function getLinkRewrite($page, $idLang) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('ml.url_rewrite')
                ->from('meta', 'm')
                ->leftJoin('meta_lang', 'ml', 'm.`id_meta` = ml.`id_meta` AND ml.`id_lang` = ' . (int) $idLang)
                ->where('page = \'' . pSQL($page) . '\'')
        );
    }

    public static function getTitle($page, $idLang) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('ml.title')
                ->from('meta', 'm')
                ->leftJoin('meta_lang', 'ml', 'm.`id_meta` = ml.`id_meta` AND ml.`id_lang` = ' . (int) $idLang)
                ->where('page = \'' . pSQL($page) . '\'')
        );
    }

    public static function getDescription($page, $idLang) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('ml.description')
                ->from('meta', 'm')
                ->leftJoin('meta_lang', 'ml', 'm.`id_meta` = ml.`id_meta` AND ml.`id_lang` = ' . (int) $idLang)
                ->where('page = \'' . pSQL($page) . '\'')
        );
    }

    public static function getMetasByIdLang($idLang, $type = null, $configurable = null) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('meta', 'm')
                ->leftJoin('meta_lang', 'ml', 'm.`id_meta` = ml.`id_meta`')
                ->where('ml.`id_lang` = ' . (int) $idLang)
                ->where($type ? 'm.`controller` LIKE "' . $type . '"' : '1')
                ->where(!is_null($configurable) ? 'm.`configurable` = ' . $configurable . '' : '1')
                ->orderBy('`page` ASC')
        );
    }

    public static function getEquivalentUrlRewrite($newIdLang, $idLang, $urlRewrite) {

        $metaSql = (new DbQuery())
            ->select('`id_meta`')
            ->from('meta_lang')
            ->where('`url_rewrite` = \'' . pSQL($urlRewrite) . '\'')
            ->where('`id_lang` = ' . (int) $idLang);

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('url_rewrite')
                ->from('meta_lang')
                ->where('id_meta = (' . $metaSql->build() . ')')
                ->where('`id_lang` = ' . (int) $newIdLang)
        );
    }

    public static function getMetaTags($idLang, $pageName, $title = '') {
        $allowed = false;

        if (!empty(Context::getContext()->phenyxConfig->get('EPH_MAINTENANCE_IP'))) {
            $allowed = in_array(Tools::getRemoteAddr(), explode(',', Context::getContext()->phenyxConfig->get('EPH_MAINTENANCE_IP')));
        }

        if (!(!Context::getContext()->phenyxConfig->get('EPH_SHOP_ENABLE') && !$allowed)) {
            $tags = Context::getContext()->_hook->exec('actiongetMetaTags', ['idLang' => $idLang, 'pageName' => $pageName, 'title' => $title]);

            if ($pageName == 'cms' && ($idCms = Tools::getValue('id_cms'))) {
                return Meta::getCmsMetas($idCms, $idLang, $pageName);
            }

        }

        return Meta::getHomeMetas($idLang, $pageName);
    }

    public static function getAdminControllers() {

        $return = [];

        $controllers = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('class_name')
                ->from('back_tab')
        );

        foreach ($controllers as $controller) {

            if (!is_null($controller['class_name'])) {
                $return[] = $controller['class_name'];
            }

        }

        return $return;

    }

    public static function completeMetaTags($metaTags, $defaultValue, $context = null) {

        if (!$context) {
            $context = Context::getContext();
        }

        if (empty($metaTags['meta_title'])) {
            $metaTags['meta_title'] = $defaultValue . ' - ' . Context::getContext()->phenyxConfig->get('EPH_SHOP_NAME');
        }

        if (empty($metaTags['meta_description'])) {
            $metaTags['meta_description'] = Context::getContext()->phenyxConfig->get('EPH_META_DESCRIPTION', $context->language->id) ? Context::getContext()->phenyxConfig->get('EPH_META_DESCRIPTION', $context->language->id) : '';
        }

        if (empty($metaTags['meta_keywords'])) {
            $metaTags['meta_keywords'] = Context::getContext()->phenyxConfig->get('EPH_META_KEYWORDS', $context->language->id) ? Context::getContext()->phenyxConfig->get('EPH_META_KEYWORDS', $context->language->id) : '';
        }

        return $metaTags;
    }

    public static function getHomeMetas($idLang, $pageName) {

        $metas = Meta::getMetaByPage($pageName, $idLang);
        $ret['meta_title'] = (isset($metas['title']) && $metas['title']) ? $metas['title'] . ' - ' . Context::getContext()->phenyxConfig->get('EPH_SHOP_NAME') : Context::getContext()->phenyxConfig->get('EPH_SHOP_NAME');
        $ret['meta_description'] = (isset($metas['description']) && $metas['description']) ? $metas['description'] : '';
        $ret['meta_keywords'] = (isset($metas['keywords']) && $metas['keywords']) ? $metas['keywords'] : '';

        return $ret;
    }

    public static function getMetaByPage($page, $idLang) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('*')
                ->from('meta', 'm')
                ->leftJoin('meta_lang', 'ml', 'm.`id_meta` = ml.`id_meta`')
                ->where('m.`page` = \'' . pSQL($page) . '\' OR m.`page` = \'' . pSQL(str_replace('_', '', strtolower($page))) . '\'')
                ->where('ml.`id_lang` = ' . (int) $idLang)
        );
    }

    public static function getMetaById($idMeta, $idLang) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('*')
                ->from('meta', 'm')
                ->leftJoin('meta_lang', 'ml', 'm.`id_meta` = ml.`id_meta`')
                ->where('m.`id_meta` = ' . (int) $idMeta)
                ->where('ml.`id_lang` = ' . (int) $idLang)
        );
    }

    public static function getCmsMetas($idCms, $idLang, $pageName) {

        if ($row = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
            ->select('`meta_title`, `meta_description`, `meta_keywords`')
            ->from('cms_lang')
            ->where('`id_lang` = ' . (int) $idLang)
            ->where('`id_cms` = ' . (int) $idCms)

        )) {
            $row['meta_title'] = $row['meta_title'] . ' - ' . Context::getContext()->phenyxConfig->get('EPH_SHOP_NAME');

            return Meta::completeMetaTags($row, $row['meta_title']);
        }

        return Meta::getHomeMetas($idLang, $pageName);
    }

    public static function getCmsCategoryMetas($idCmsCategory, $idLang, $pageName) {

        if ($row = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
            ->select('`meta_title`, `meta_description`, `meta_keywords`')
            ->from('cms_category_lang')
            ->where('`id_lang` = ' . (int) $idLang)
            ->where('`id_cms_category` = ' . (int) $idCmsCategory)

        )) {
            $row['meta_title'] = $row['meta_title'] . ' - ' . Context::getContext()->phenyxConfig->get('EPH_SHOP_NAME');

            return Meta::completeMetaTags($row, $row['meta_title']);
        }

        return Meta::getHomeMetas($idLang, $pageName);
    }

    public function deleteSelection($selection) {

        if (!is_array($selection)) {
            die(Tools::displayError());
        }

        $result = true;

        foreach ($selection as $id) {
            $this->id = (int) $id;
            $result = $result && $this->delete();
        }

        return $result && Tools::generateHtaccess();
    }

    public function delete() {

        if (!parent::delete()) {
            return false;
        }

        return Tools::generateHtaccess();
    }

    public static function getReferentPages() {

        $return = [];
        $pages = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`page`')
                ->from('meta')
                ->orderBy('page')
        );

        foreach ($pages as $page) {
            $return[] = $page['page'];
        }

        return $return;

    }

}
