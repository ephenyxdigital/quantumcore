<?php
#[AllowDynamicProperties]
/**
 * Class ControllerCore
 *
 * @since 1.6.6.3
 */
abstract class PhenyxController {

    protected static $_plugins = [];

    protected static $hook_instance;

    public $_hook;

    public static $_is_merge_lang = false;

    public static $php_errors = [];

    public $css_files = [];

    public $js_footers = [];

    public $js_heads = [];

    public $js_files = [];

    public $index_js_files = [];

    public $index_js_def = [];

    public $js_def = [];

    public $push_js_files = [];

    public $push_css_files = [];

    public $extracss;

    public $cacheId;

    public $mainControllers;

    public $extra_vars;

    public $ajax = false;

    public $ajax_submit = false;

    public $ajaxLayout = false;

    public $layout = 'layout.tpl';
    /** @var string Controller type. Possible values: 'front', 'pluginfront', 'admin', 'pluginadmin' */
    public $controller_type;

    public $php_self;

    public $cachable = false;

    public $table = 'configuration';

    public $className;

    public $tabAccess;

    public $identifier = false;

    public $link_rewrite;

    public $require_context = true;

    /** @var ObjectModel Instantiation of the class associated with the AdminController */
    protected $object;

    protected $context;

    protected $_user;

    protected $_company;

    protected $_cookie;

    protected $_link;

    protected $_language;

    protected $_smarty;

    /** @var string */
    protected $display;

    protected $ajax_display;

    protected $display_header;

    protected $display_header_javascript;

    protected $template;

    protected $display_footer;

    protected $content_only = false;

    protected $json = false;

    protected $status = '';

    protected $redirect_after = null;

    protected $total_filesize = 0;

    protected $total_query_time = 0;

    protected $total_global_var_size = 0;

    protected $total_plugins_time = 0;

    protected $total_plugins_memory = 0;

    protected $global_var_size = [];

    protected $total_cache_size;

    protected $plugins_perfs = [];

    protected $hooks_perfs = [];

    protected $array_queries = [];

    protected $profiler = [];

    public $content_ajax = '';

    public $controller_name;

    protected $paragridScript;

    public $contextMenuItems = [];

    public $paramToolBarItems = [];

    public $animModel = [];

    public $manageHeaderFields = false;

    public $default_language;

    public $ajaxOptions;

    protected $publicName;

    protected $viewName;

    protected $action;

    public $targetController;

    public $errors = [];

    public $warnings = [];

    /** @var bool */
    public $bootstrap = false;

    public $lang = false;

    public $updateableFields;

    protected $fields_form;

    public $fields_value = [];

    public $tpl_form_vars = [];

    protected $toolbar_btn = null;
    /** @var bool Scrolling toolbar */
    protected $toolbar_scroll = true;

    public $token;

    protected $helper;

    protected $submit_action;

    public $base_tpl_form = null;

    public $base_folder_form = null;

    public $base_tpl_view = null;

    public $page_title;

    public $page_description;

    public $ajax_li;

    public $dialog_title;

    public $ajax_content;

    public $form_included = false;

    public $paramClassName;

    public $paramController_name;

    public $paramTable;

    public $paramIdentifier;

    public $ajax_layout;

    public $_defer;

    public $_domAvailable;

    public $_compress;

    public $_front_css_cache;

    public $_front_jss_cache;

    public $_back_css_cache;

    public $_back_js_cache;

    public $_session;

    public $memoryStart;

    public $configurationField;
    
    
    public $backtab;

    public function __construct() {

        if (!defined('TIME_START')) {
            define('TIME_START', microtime(true));
        }

        if (_EPH_DEBUG_PROFILING_ || _EPH_ADMIN_DEBUG_PROFILING_) {
            $this->profiler[] = $this->stamp('config');
            $this->memoryStart = memory_get_usage(true);
        }

        if (is_null($this->display_header)) {
            $this->display_header = true;
        }

        if (is_null($this->display_header_javascript)) {
            $this->display_header_javascript = true;
        }

        if (is_null($this->display_footer)) {
            $this->display_footer = true;
        }

        $this->context = Context::getContext();

        $this->buildContext();

        $this->context->controller = $this;

        if ($this->context->cache_enable && !isset($this->context->cache_api)) {
            $this->context->cache_api = CacheApi::getInstance();
        }

        $this->context->smarty->assign([
            'shopName'    => $this->context->company->company_name,
            'css_dir'     => 'https://' . $this->context->company->domain_ssl . $this->context->theme->css_theme,
            'shop_url'    => 'https://' . $this->context->company->domain_ssl,
            'shop_mail'   => $this->context->company->company_email,
            'company'     => $this->context->company,
            'today'       => date("Y-m-d"),
            'smarty_now'  => date("Y-m-d H:m:s"),
            'smarty_year' => date("Y"),
            'smarty_tag'  => date("i-s"),
        ]);

        $this->ajax = $this->context->_tools->getValue('ajax') || $this->context->_tools->isSubmit('ajax');

        if (!headers_sent()
            && isset($_SERVER['HTTP_USER_AGENT'])
            && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false
                || strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== false)
        ) {
            header('X-UA-Compatible: IE=edge,chrome=1');
        }

        if (_EPH_DEBUG_PROFILING_ || _EPH_ADMIN_DEBUG_PROFILING_) {
            $this->profiler[] = $this->stamp('__construct');
        }

        if (empty(static::$_plugins)) {
            static::$_plugins = $this->getPlugins();
        }

        static::$_is_merge_lang = $this->context->phenyxConfig->get('CURENT_MERGE_LANG_' . $this->context->language->iso_code, null, false);

        if (!static::$_is_merge_lang) {
            $this->mergeLanguages($this->context->language->iso_code);
        }

        $this->context->phenyxgrid->create = 'function (evt, ui) {
            buildHeadingAction(\'' . 'grid_' . $this->controller_name . '\', \'' . $this->controller_name . '\');
        }';

        $this->ajax_layout = $this->getAjaxLayout();

        $this->_domAvailable = extension_loaded('dom') ? true : false;

        if ($this->controller_type == 'front') {
            $this->_compress = (bool) $this->context->phenyxConfig->get('EPH_JS_HTML_THEME_COMPRESSION');

            if ($this->_compress) {
                $this->context->smarty->registerFilter('output', 'smartyPackJSinHTML');
            }

            $this->_defer = (bool) $this->context->phenyxConfig->get('EPH_JS_DEFER');

            $this->_front_css_cache = $this->context->phenyxConfig->get('EPH_CSS_THEME_CACHE', null, false);
            $this->_front_js_cache = $this->context->phenyxConfig->get('EPH_JS_THEME_CACHE', null, false);

        }

        if ($this->controller_type == 'admin') {
            $this->_compress = (bool) $this->context->phenyxConfig->get('EPH_JS_HTML_BACKOFFICE_COMPRESSION');

            if ($this->_compress) {
                $this->context->smarty->registerFilter('output', 'smartyPackJSinHTML');
            }

            $this->_defer = (bool) $this->context->phenyxConfig->get('EPH_JS_BACKOFFICE_DEFER');

            $this->_back_css_cache = $this->context->phenyxConfig->get('EPH_CSS_BACKOFFICE_CACHE', null, false);
            $this->_back_js_cache = $this->context->phenyxConfig->get('EPH_JS_BACKOFFICE_CACHE', null, false);

        }

        if (!is_object($this->_session)) {
            $this->_session = $this->context->_session;
        }

    }

    public function buildContext() {

        if (!isset($this->context->phenyxConfig)) {
            $this->context->phenyxConfig = Configuration::getInstance();

        }

        if (!isset($this->context->company)) {
            $this->context->company = Company::initialize();
        }

        if (!isset($this->context->_hook)) {
            $this->context->_hook = Hook::getInstance();
        }

        if (!isset($this->context->hook_args)) {
            $this->context->hook_args = $this->context->_hook->getHookArgs();
        }

        if (!isset($this->context->media)) {
            $this->context->media = Media::getInstance();
        }

        if (!isset($this->context->_session)) {
            $this->context->_session = new PhenyxSession();
        }

        if (!isset($this->context->_link)) {
            $this->context->_link = Link::getInstance();
        }

        if (!isset($this->context->_tools)) {
            $this->context->_tools = PhenyxTool::getInstance();
        }

        if (!isset($this->context->img_manager)) {
            $this->context->img_manager = ImageManager::getInstance();
        }

        if (!isset($this->context->language)) {
            $this->context->language = $this->context->_tools->jsonDecode($this->context->_tools->jsonEncode(Language::buildObject($this->context->phenyxConfig->get('EPH_LANG_DEFAULT'))));
        }

        if (!isset($this->context->translations)) {
            $this->context->translations = new Translate($this->context->language->iso_code, $this->context->company);
        }

        if (!isset($this->context->cache_enable)) {
            $this->context->cache_enable = $this->context->phenyxConfig->get('EPH_PAGE_CACHE_ENABLED');
        }

        if (!isset($this->context->phenyxgrid)) {
            $this->context->phenyxgrid = new ParamGrid();
        }

    }

    public function mergeLanguages($iso) {

        global $_LANGADM, $_LANGCLASS, $_LANGFRONT, $_LANGMAIL, $_LANGPDF;
        $_plugins = $this->getPlugins();

        $_LANGAD = [];

        if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/admin.php')) {
            @include _EPH_TRANSLATIONS_DIR_ . $iso . '/admin.php';
            $_LANGAD = $_LANGADM;
        }

        $toInsert = [];

        if (file_exists(_EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/admin.php')) {

            @include _EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/admin.php';

            if (isset($_LANGOVADM) && is_array($_LANGOVADM)) {
                $_LANGAD = array_merge(
                    $_LANGAD,
                    $_LANGOVADM
                );
            }

        }

        foreach ($_plugins as $plugin) {

            if (file_exists(_EPH_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/admin.php')) {

                @include _EPH_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/admin.php';

                if (is_array($_LANGADM)) {
                    $_LANGAD = array_merge(
                        $_LANGAD,
                        $_LANGADM
                    );
                }

            }

        }

        foreach ($_plugins as $plugin) {

            if (file_exists(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/admin.php')) {

                @include _EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/admin.php';

                if (is_array($_LANGADM)) {
                    $_LANGAD = array_merge(
                        $_LANGAD,
                        $_LANGADM
                    );
                }

            }

        }

        $toInsert = $_LANGAD;
        ksort($toInsert);
        $file = fopen(_EPH_TRANSLATIONS_DIR_ . $iso . '/admin.php', "w");
        fwrite($file, "<?php\n\nglobal \$_LANGADM;\n\n");
        fwrite($file, "\$_LANGADM = [];\n");

        foreach ($toInsert as $key => $value) {
            $value = htmlspecialchars_decode($value, ENT_QUOTES);
            fwrite($file, '$_LANGADM[\'' . translateSQL($key, true) . '\'] = \'' . translateSQL($value, true) . '\';' . "\n");
        }

        fwrite($file, "\n" . 'return $_LANGADM;' . "\n");
        fclose($file);
        $_LANGCLAS = [];

        if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/class.php')) {
            @include _EPH_TRANSLATIONS_DIR_ . $iso . '/class.php';
            $_LANGCLAS = $_LANGCLASS;
        }

        $toInsert = [];

        if (file_exists(_EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/class.php')) {

            @include _EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/class.php';

            if (isset($_LANGOVCLASS) && is_array($_LANGOVCLASS)) {
                $_LANGCLAS = array_merge(
                    $_LANGCLAS,
                    $_LANGOVCLASS
                );
            }

        }

        foreach ($_plugins as $plugin) {

            if (file_exists(_EPH_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/class.php')) {
                require_once _EPH_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/class.php';

                if (is_array($_LANGCLASS)) {
                    $_LANGCLAS = array_merge(
                        $_LANGCLAS,
                        $_LANGCLASS
                    );
                }

            }

        }

        foreach ($_plugins as $plugin) {

            if (file_exists(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/class.php')) {
                require_once _EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/class.php';

                if (is_array($_LANGCLASS)) {
                    $_LANGCLAS = array_merge(
                        $_LANGCLAS,
                        $_LANGCLASS
                    );
                }

            }

        }

        $toInsert = $_LANGCLAS;
        ksort($toInsert);
        $file = fopen(_EPH_TRANSLATIONS_DIR_ . $iso . '/class.php', "w");
        fwrite($file, "<?php\n\nglobal \$_LANGCLASS;\n\n");
        fwrite($file, "\$_LANGCLASS = [];\n");

        foreach ($toInsert as $key => $value) {
            $value = htmlspecialchars_decode($value, ENT_QUOTES);
            fwrite($file, '$_LANGCLASS[\'' . translateSQL($key, true) . '\'] = \'' . translateSQL($value, true) . '\';' . "\n");
        }

        fwrite($file, "\n" . 'return $_LANGCLASS;' . "\n");
        fclose($file);

        $_LANGFRON = [];

        if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/front.php')) {
            @include _EPH_TRANSLATIONS_DIR_ . $iso . '/front.php';
            $_LANGFRON = $_LANGFRONT;
        }

        $toInsert = [];

        if (file_exists(_EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/front.php')) {

            @include _EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/front.php';

            if (isset($_LANGOVFRONT) && is_array($_LANGOVFRONT)) {
                $_LANGFRON = array_merge(
                    $_LANGFRON,
                    $_LANGOVFRONT
                );
            }

        }

        foreach ($_plugins as $plugin) {

            if (file_exists(_EPH_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/front.php')) {

                require_once _EPH_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/front.php';

                if (is_array($_LANGFRONT)) {
                    $_LANGFRON = array_merge(
                        $_LANGFRON,
                        $_LANGFRONT
                    );
                }

            }

        }

        foreach ($_plugins as $plugin) {

            if (file_exists(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/front.php')) {

                require_once _EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/front.php';

                if (is_array($_LANGFRONT)) {
                    $_LANGFRON = array_merge(
                        $_LANGFRON,
                        $_LANGFRONT
                    );
                }

            }

        }

        $toInsert = $_LANGFRON;
        ksort($toInsert);
        $file = fopen(_EPH_TRANSLATIONS_DIR_ . $iso . '/front.php', "w");
        fwrite($file, "<?php\n\nglobal \$_LANGFRONT;\n\n");
        fwrite($file, "\$_LANGFRONT = [];\n");

        foreach ($toInsert as $key => $value) {
            $value = htmlspecialchars_decode($value, ENT_QUOTES);
            fwrite($file, '$_LANGFRONT[\'' . translateSQL($key, true) . '\'] = \'' . translateSQL($value, true) . '\';' . "\n");
        }

        fwrite($file, "\n" . 'return $_LANGFRONT;' . "\n");
        fclose($file);

        $_LANGMAI = [];

        if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/mail.php')) {
            @include _EPH_TRANSLATIONS_DIR_ . $iso . '/mail.php';
            $_LANGMAI = $_LANGMAIL;
        }

        $toInsert = [];

        foreach ($_plugins as $plugin) {

            if (file_exists(_EPH_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/mail.php')) {

                @include _EPH_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/mail.php';

                if (is_array($_LANGMAIL)) {
                    $_LANGMAI = array_merge(
                        $_LANGMAI,
                        $_LANGMAIL
                    );
                }

            }

        }

        $toInsert = $_LANGMAI;
        ksort($toInsert);
        $file = fopen(_EPH_TRANSLATIONS_DIR_ . $iso . '/mail.php', "w");
        fwrite($file, "<?php\n\nglobal \$_LANGMAIL;\n\n");
        fwrite($file, "\$_LANGMAIL = [];\n");

        foreach ($toInsert as $key => $value) {
            $value = htmlspecialchars_decode($value, ENT_QUOTES);
            fwrite($file, '$_LANGMAIL[\'' . translateSQL($key, true) . '\'] = \'' . translateSQL($value, true) . '\';' . "\n");
        }

        fwrite($file, "\n" . 'return $_LANGMAIL;' . "\n");
        fclose($file);

        $_LANGPD = [];

        if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/pdf.php')) {
            @include _EPH_TRANSLATIONS_DIR_ . $iso . '/pdf.php';
            $_LANGPD = $_LANGPDF;
        }

        $toInsert = [];

        foreach ($_plugins as $plugin) {

            if (file_exists(_EPH_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/pdf.php')) {

                @include _EPH_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'translations/' . $iso . '/pdf.php';

                if (is_array($_LANGPDF)) {
                    $_LANGPD = array_merge(
                        $_LANGPD,
                        $_LANGPDF
                    );
                }

            }

        }

        $toInsert = $_LANGPD;
        ksort($toInsert);
        $file = fopen(_EPH_TRANSLATIONS_DIR_ . $iso . '/pdf.php', "w");
        fwrite($file, "<?php\n\nglobal \$_LANGPDF;\n\n");
        fwrite($file, "\$_LANGPDF = [];\n");

        foreach ($toInsert as $key => $value) {
            $value = htmlspecialchars_decode($value, ENT_QUOTES);
            fwrite($file, '$_LANGPDF[\'' . translateSQL($key, true) . '\'] = \'' . translateSQL($value, true) . '\';' . "\n");
        }

        fwrite($file, "\n" . 'return $_LANGPDF;' . "\n");
        fclose($file);

        $this->context->translations = new Translate($iso, $this->context->company);
        $this->context->phenyxConfig->updateValue('CURENT_MERGE_LANG_' . $this->context->language->iso_code, 1);
        return true;

    }

    public function getPlugins() {

        $plugs = [];
        $plugins = Plugin::getPluginsDirOnDisk();

        foreach ($plugins as $plugin) {

            if (Plugin::isInstalled($plugin)) {

                if (is_dir(_EPH_PLUGIN_DIR_ . $plugin . '/translations/' . $this->context->language->iso_code)) {
                    $plugs[] = $plugin;
                } else

                if (is_dir(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . '/translations/' . $this->context->language->iso_code)) {
                    $plugs[] = $plugin;
                }

            }

        }

        return $plugs;
    }

    public static function getController($className, $auth = false, $ssl = false) {

        return new $className($auth, $ssl);
    }

    public function ajaxProcessSwitchAdminLanguage() {

        $idLang = $this->context->_tools->getValue('id_lang');

        $language = $this->context->_tools->jsonDecode($this->context->_tools->jsonEncode(Language::buildObject((int) $idLang)));

        if (Validate::isLoadedObject($language) && $language->active) {
            $this->context->cookie->id_lang = $idLang;
            $this->context->cookie->write();
            $this->_language = $this->context->language = $language;
            $this->context->employee->id_lang = $idLang;
            $this->context->employee->update();
            $this->processClearRedisCache;
        }

        $result = [
            'link' => $this->context->_link->getAdminLink('admindashboard'),
        ];

        die($this->context->_tools->jsonEncode($result));
    }

    public function ajaxProcessSetLanguage() {

        $idLang = $this->context->_tools->getValue('id_lang');
        $cookieIdLang = $this->context->cookie->id_lang;
        $configurationIdLang = $this->context->phenyxConfig->get('EPH_LANG_DEFAULT');

        $this->context->cookie->id_lang = $idLang;
        $language = $this->context->_tools->jsonDecode($this->context->_tools->jsonEncode(Language::buildObject((int) $idLang)));

        if (Validate::isLoadedObject($language) && $language->active) {
            $this->_language = $this->context->language = $language;
        }

        if (Validate::isUnsignedId($this->_user->id)) {
            $user = new User($this->_user->id);

            if ($user->is_admin) {
                $user = new Employee($user->id);
            }

            $user->id_lang = $idLang;
            $user->update();
            $this->_user = $this->context->user = $user;
            $this->processClearCache();
        }

        die(true);
    }

    public function processClearCache() {

        $result = $this->context->cache_api->cleanCache();
        $result = [
            'success' => true,
        ];

        die($this->context->_tools->jsonEncode($result));
    }

    public function generateParaGridToolBar() {

        $paramToolBarItems = null;

        if ($this->context->cache_enable && is_object($this->context->cache_api)) {
            $value = $this->context->cache_api->getData('get' . $this->controller_name . 'ParaGridToolBar');
            $temp = empty($value) ? null : Tools::jsonDecode($value, true);

            if (!empty($temp) && is_array($temp) && count($temp)) {
                $paramToolBarItems = $temp;
            }

        }

        if (is_null($paramToolBarItems)) {
            $paramToolBarItems = $this->context->_hook->exec('action' . $this->controller_name . 'generateParaGridToolBar', [], null, true);

            if ($this->context->cache_enable && is_object($this->context->cache_api)) {
                $temp = $paramToolBarItems === null ? null : Tools::jsonEncode($paramToolBarItems);
                $this->context->cache_api->putData('get' . $this->controller_name . 'ParaGridToolBar', $temp);
            }

        }

        if (is_array($paramToolBarItems)) {

            foreach ($paramToolBarItems as $plugin => $toolBars) {

                if (is_array($toolBars)) {

                    foreach ($toolBars as $toolBar) {
                        $this->paramToolBarItems[] = $toolBar;
                    }

                }

            }

        }

        $toolBar = new ParamToolBar();
        $toolBar->items = $this->paramToolBarItems;

        return $toolBar->buildToolBar();
    }

    public function generateParaGridContextMenu() {

        $menuItem = [];
        $contextMenu = new ParamContextMenu($this->className, $this->controller_name);
        $contextMenuItems = null;

        if ($this->context->cache_enable && is_object($this->context->cache_api)) {
            $value = $this->context->cache_api->getData('get' . $this->controller_name . 'ParaGridContextMenu');
            $temp = empty($value) ? null : Tools::jsonDecode($value, true);

            if (!empty($temp) && is_array($temp) && count($temp)) {
                $contextMenuItems = $temp;
            }

        }

        if (is_null($contextMenuItems)) {
            $contextMenuItems = $this->context->_hook->exec('action' . $this->controller_name . 'generateParaGridContextMenu', ['class' => $this->className, 'contextMenuItems' => $this->contextMenuItems], null, true);

            if ($this->context->cache_enable && is_object($this->context->cache_api)) {
                $temp = $contextMenuItems === null ? null : Tools::jsonEncode($contextMenuItems);
                $this->context->cache_api->putData('get' . $this->controller_name . 'ParaGridContextMenu', $temp);
            }

        }

        if (!empty($contextMenuItems) && is_array($contextMenuItems)) {

            foreach ($contextMenuItems as $plugin => $contextMenuItem) {

                if (is_array($contextMenuItem)) {
                    $idPlugin = Plugin::getIdPluginByName($plugin);

                    if (count($menuItem) > 0) {
                        $contextMenuPlugin = $this->context->_hook->exec('action' . $this->controller_name . 'generateParaGridContextMenu', ['class' => $this->className, 'contextMenuItems' => $menuItem[$last_plugin]], $idPlugin, true);
                    } else {
                        $contextMenuPlugin = $this->context->_hook->exec('action' . $this->controller_name . 'generateParaGridContextMenu', ['class' => $this->className, 'contextMenuItems' => $this->contextMenuItems], $idPlugin, true);
                    }

                    foreach ($contextMenuPlugin[$plugin] as $key => $item) {
                        $menuItem[$plugin][$key] = $item;
                    }

                    $last_plugin = $plugin;
                }

            }

        }

        if (isset($last_plugin) && is_array($menuItem[$last_plugin]) && count($menuItem[$last_plugin]) > 0) {
            $this->contextMenuItems = $menuItem[$last_plugin];
        }

        $contextMenu->items = $this->contextMenuItems;

        return $contextMenu->buildContextMenu();
    }

    public function generateParaGridScript($idObjet = null, $use_cache = true) {

        if ($use_cache && $this->context->cache_enable) {
            $temp = $this->cache_get_data('grid_' . $this->className . '_' . $idObjet);

            if (!empty($temp)) {
                return $temp;
            }

        }
        
        $this->context->phenyxgrid->docReady = 0;

        $this->context->phenyxgrid->paramClass = !empty($this->paramClassName) ? $this->paramClassName : $this->className;
        $this->context->phenyxgrid->paramController = !empty($this->paramController_name) ? $this->paramController_name : $this->controller_name;
        $this->context->phenyxgrid->paramTable = !empty($this->paramTable) ? $this->paramTable : $this->table;
        $this->context->phenyxgrid->paramIdentifier = !empty($this->paramIdentifier) ? $this->paramIdentifier : $this->identifier;

        $extraVars = $this->context->_hook->exec('action' . $this->controller_name . 'ParaGridScript', ['controller_name' => $this->controller_name, 'phenyxgrid' => $this->context->phenyxgrid]);

        $option = $this->context->phenyxgrid->generateParaGridOption();

        $script = $this->context->phenyxgrid->generateParagridScript();
        $this->paragridScript = $script;

        if ($this->is_subModel) {
            return $this->paragridScript;
        }

        $result = '<script type="text/javascript" data-defer="headJs">' . PHP_EOL . JSMin\JSMin::minify($this->paragridScript) . PHP_EOL . '</script>';

        if ($use_cache && $this->context->cache_enable) {
            $this->cache_put_data('grid_' . $this->className . '_' . $idObjet, $result);
        }

        return $result;
    }

    public static function myErrorHandler($errno, $errstr, $errfile, $errline) {

        if (error_reporting() === 0) {
            return false;
        }

        switch ($errno) {
        case E_USER_ERROR:
        case E_ERROR:
            die('Fatal error: ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
            break;
        case E_USER_WARNING:
        case E_WARNING:
            $type = 'Warning';
            break;
        case E_USER_NOTICE:
        case E_NOTICE:
            $type = 'Notice';
            break;
        default:
            $type = 'Unknown error';
            break;
        }

        static::$php_errors[] = [
            'type'    => $type,
            'errline' => (int) $errline,
            'errfile' => str_replace('\\', '\\\\', $errfile), // Hack for Windows paths
            'errno'   => (int) $errno,
            'errstr'  => $errstr,
        ];

        Context::getContext()->smarty->assign('php_errors', static::$php_errors);

        return true;
    }

    public function &__get($property) {

        $camelCaseProperty = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $property))));

        if (property_exists($this, $camelCaseProperty)) {
            return $this->$camelCaseProperty;
        }

        return $this->$property;
    }

    public function __set($property, $value) {

        $blacklist = [
            '_select',
            '_join',
            '_where',
            '_group',
            '_having',
            '_conf',
            '_lang',
        ];

        // Property to camelCase for backwards compatibility
        $snakeCaseProperty = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $property))));

        if (!in_array($property, $blacklist) && property_exists($this, $snakeCaseProperty)) {
            $this->$snakeCaseProperty = $value;
        } else {
            $this->$property = $value;
        }

    }

    public function run() {

        $this->init();

        if (_EPH_DEBUG_PROFILING_ || _EPH_ADMIN_DEBUG_PROFILING_) {
            $this->profiler[] = $this->stamp('init');
        }

        if ($this->checkAccess()) {

            if (!$this->content_only && ($this->display_header || (isset($this->className) && $this->className))) {
                $this->setMedia();
            }

            $this->postProcess();

            if (!empty($this->redirect_after)) {
                $this->redirect();
            }

            if (!$this->content_only && ($this->display_header || (isset($this->className) && $this->className))) {
                $this->initHeader();
            }

            if ($this->viewAccess()) {
                $this->initContent();
            } else {
                $this->errors[] = $this->context->_tools->displayError('Access denied.');
            }

            if (!$this->content_only && ($this->display_footer || (isset($this->className) && $this->className))) {
                $this->initFooter();
            }

            if ($this->ajax) {
                $action = $this->context->_tools->toCamelCase($this->context->_tools->getValue('action'), true);

                if (!empty($action) && method_exists($this, 'displayAjax' . $action)) {
                    $this->{'displayAjax' . $action}

                    ();
                } else

                if (method_exists($this, 'displayAjax')) {
                    $this->displayAjax();
                }

            } else {
                $this->display();
            }

        } else {
            $this->initCursedPage();

            if (isset($this->layout)) {
                $this->smartyOutputContent($this->layout);
            }

        }

    }

    public function init() {

        if ($this->controller_type == 'admin' && $this->cachable && isset($this->context->employee)) {
            $this->cacheId = 'pageAdminCache_' . $this->php_self . '_' . $this->context->employee->id_profile;
        } else

        if ($this->controller_type == 'front' && $this->cachable) {

            if (isset($this->context->user->id)) {
                $tag = str_replace(' ', '', $this->context->user->group);
            } else {
                $tag = 'guest';
            }

            $this->cacheId = 'pageCache_' . $this->php_self . '_' . $tag;
        }

        if (_EPH_MODE_DEV_ && $this->controller_type == 'admin') {
            set_error_handler([__CLASS__, 'myErrorHandler']);
        }

        if (!defined('_EPH_BASE_URL_')) {
            define('_EPH_BASE_URL_', $this->context->_tools->getDomain(true));
        }

        if (!defined('_EPH_BASE_URL_SSL_')) {
            define('_EPH_BASE_URL_SSL_', $this->context->_tools->getDomainSsl(true));
        }

    }

    public function setMedia($isNewTheme = false) {

        $this->addHeaderJS([
            _EPH_JS_DIR_ . 'jquery/jquery-' . _EPH_JQUERY_VERSION_ . '.min.js',
            _EPH_JS_DIR_ . 'jquery-ui/jquery-ui.min.js',
            _EPH_JS_DIR_ . 'tools.js',

        ]);

        if ($this->controller_type == 'front') {
            $this->addCSS([
                _EPH_CSS_DIR_ . 'front.css'                                => 'all',
                _EPH_JS_DIR_ . 'jquery/ui/themes/base/jquery.ui.theme.css' => 'all',
            ]);
        }

    }

    public function getUserIpAddr() {

        return $_SERVER['SERVER_ADDR'];
    }

    abstract public function checkAccess();

    abstract public function postProcess();

    abstract protected function redirect();

    abstract public function initHeader();

    abstract public function viewAccess();

    abstract public function initContent();

    abstract public function initFooter();

    abstract public function display();

    protected function afterAdd() {

        return true;
    }

    protected function beforeAdd() {

        return true;
    }

    protected function beforeUpdate() {

        return true;
    }

    protected function afterUpdate() {

        $this->context->smarty->clearCache($this->ajax_layout, $this->php_self);
        return true;
    }

    abstract public function initCursedPage();

    public function smartyOutputContent($content) {

        $this->context->cookie->write();
        $html = '';
        $jsTag = 'js_def';
        $this->context->smarty->assign($jsTag, $jsTag);
        $this->context->smarty->assign('load_time', round(microtime(true) - TIME_START, 3));

        $html = $this->context->smarty->fetch($content);

        $html = trim($html);

        if (!empty($html)) {

            if ($this->_defer && $this->_domAvailable) {
                $html = $this->context->media->deferInlineScripts($html);
            }

            $html = trim(str_replace(['</body>', '</html>'], '', $html)) . "\n";

            $this->context->smarty->assign(
                [
                    $jsTag      => $this->context->media->getJsDef(),
                    'js_files'  => $this->_defer ? array_unique($this->js_files) : [],
                    'js_inline' => ($this->_defer && $this->_domAvailable) ? $this->context->media->getInlineScript() : [],
                    'js_heads'  => ($this->_defer) ? $this->js_heads : [],
                ]
            );
            $javascript = $this->context->smarty->fetch(_EPH_ALL_THEMES_DIR_ . 'javascript.tpl');

            if ($this->_defer && (!isset($this->ajax) || !$this->ajax)) {
                echo $html . $javascript;
            } else

            if ($this->_defer && $this->ajax) {

                die($this->context->_tools->jsonEncode(['html', $html . $javascript]));

            } else {
                echo preg_replace('/(?<!\$)' . $jsTag . '/', $javascript, $html);
            }

            echo ((!$this->context->_tools->getIsset($this->ajax) || !$this->ajax) ? '</body></html>' : '');

        } else {
            echo $html;
        }

    }

    public function ajaxOutputContent($content) {

        $this->context->cookie->write();
        $html = '';
        $jsTag = 'js_def';
        $this->context->smarty->assign($jsTag, $jsTag);

        if (is_array($content)) {

            foreach ($content as $tpl) {
                $html .= $this->context->smarty->fetch($tpl);
            }

        } else {
            $html = $this->context->smarty->fetch($content);
        }

        $html = trim($html);

        $html = trim(str_replace(['</body>', '</html>'], '', $html)) . "\n";
        $this->ajax_head = str_replace(['<head>', '</head>'], '', $this->context->media->deferTagOutput('head', $html));
        $page = $this->context->media->deferIdOutput('page', $html);

        $this->context->smarty->assign(
            [
                $jsTag      => $this->context->media->getJsDef(),
                'js_files'  => $this->_defer ? array_unique($this->js_files) : [],
                'js_inline' => [],
            ]
        );
        $javascript = $this->context->smarty->fetch(_EPH_ALL_THEMES_DIR_ . 'javascript.tpl');

        if ($this->_defer) {
            $templ = $page . $javascript;
            $return = [
                'historyState' => $this->historyState,
                'page_title'   => $this->page_title,
                'ajax_head'    => $this->ajax_head,
                'html'         => $templ,
            ];

        } else {
            $templ = preg_replace('/(?<!\$)' . $jsTag . '/', $javascript, $page);
            $return = [
                'historyState' => $this->historyState,
                'page_title'   => $this->page_title,
                'ajax_head'    => $this->ajax_head,
                'html'         => $templ,
            ];
        }

        if (!is_null($this->cacheId) && $this->cachable && $this->context->cache_enable) {
            $temp = $return === null ? null : $this->context->_tools->jsonEncode($return);
            $this->context->cache_api->putData($this->cacheId, $temp, 1864000);
        }

        die($this->context->_tools->jsonEncode($return));

    }

    public function displayHeader($display = true) {

        $this->display_header = $display;
    }

    public function displayHeaderJavaScript($display = true) {

        $this->display_header_javascript = $display;
    }

    public function displayFooter($display = true) {

        $this->display_footer = $display;
    }

    public function setTemplate($template) {

        $this->template = $template;
    }

    public function setRedirectAfter($url) {

        $this->redirect_after = $url;
    }

    public function removeCSS($cssUri, $cssMediaType = 'all', $checkPath = true) {

        if (!is_array($cssUri)) {
            $cssUri = [$cssUri];
        }

        foreach ($cssUri as $cssFile => $media) {

            if (is_string($cssFile) && strlen($cssFile) > 1) {

                if ($checkPath) {
                    $cssPath = $this->context->media->getCSSPath($cssFile, $media);
                } else {
                    $cssPath = [$cssFile => $media];
                }

            } else {

                if ($checkPath) {

                    if (file_exists($media)) {
                        $cssPath = '/' . ltrim(str_replace(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, _EPH_ROOT_DIR_), __EPH_BASE_URI__, $media), '/\\');
                    } else {
                        $cssPath = $this->context->media->getCSSPath($media, $cssMediaType);
                    }

                } else {
                    $cssPath = [$media => $cssMediaType];
                }

            }

            if ($cssPath && isset($this->css_files[$cssPath])) {
                unset($this->css_files[$cssPath]);
            }

        }

    }

    public function removeJS($jsUri, $checkPath = true) {

        if (is_array($jsUri)) {

            foreach ($jsUri as $jsFile) {
                $jsPath = $jsFile;

                if ($checkPath) {
                    $jsPath = $this->context->media->getJSPath($jsFile);
                }

                if ($jsPath && in_array($jsPath, $this->js_files)) {
                    unset($this->js_files[array_search($jsPath, $this->js_files)]);
                }

            }

        } else {
            $jsPath = $jsUri;

            if ($checkPath) {
                $jsPath = $this->context->media->getJSPath($jsUri);
            }

            if ($jsPath) {
                unset($this->js_files[array_search($jsPath, $this->js_files)]);
            }

        }

    }

    public function addJquery($version = null, $folder = null, $minifier = true) {

        $this->addJS($this->context->media->getJqueryPath($version, $folder, $minifier), false);
    }

    public function addJS($jsUri, $checkPath = true) {

        if (is_array($jsUri)) {

            foreach ($jsUri as $jsFile) {
                $jsFile = explode('?', $jsFile);
                $version = '';

                if (isset($jsFile[1]) && $jsFile[1]) {
                    $version = $jsFile[1];
                }

                $jsPath = $jsFile = $jsFile[0];

                if ($checkPath) {
                    $jsPath = $this->context->media->getJSPath($jsFile);
                }

                // $key = is_array($js_path) ? key($js_path) : $js_path;

                if ($jsPath && !in_array($jsPath, $this->js_files)) {
                    $this->js_files[] = $jsPath . ($version ? '?' . $version : '');
                }

            }

        } else {
            $jsUri = explode('?', $jsUri);
            $version = '';

            if (isset($jsUri[1]) && $jsUri[1]) {
                $version = $jsUri[1];
            }

            $jsPath = $jsUri = $jsUri[0];

            if ($checkPath) {
                $jsPath = $this->context->media->getJSPath($jsUri);
            }

            if ($jsPath && !in_array($jsPath, $this->js_files)) {
                $this->js_files[] = $jsPath . ($version ? '?' . $version : '');
            }

        }

    }

    public function addHeaderJS($jsUri, $checkPath = true) {

        if (is_array($jsUri)) {

            foreach ($jsUri as $jsFile) {
                $jsFile = explode('?', $jsFile);
                $version = '';

                if (isset($jsFile[1]) && $jsFile[1]) {
                    $version = $jsFile[1];
                }

                $jsPath = $jsFile = $jsFile[0];

                if ($checkPath) {
                    $jsPath = $this->context->media->getJSPath($jsFile);
                }

                // $key = is_array($js_path) ? key($js_path) : $js_path;

                if ($jsPath && !in_array($jsPath, $this->js_heads)) {
                    $this->js_heads[] = $jsPath . ($version ? '?' . $version : '');
                }

            }

        } else {
            $jsUri = explode('?', $jsUri);
            $version = '';

            if (isset($jsUri[1]) && $jsUri[1]) {
                $version = $jsUri[1];
            }

            $jsPath = $jsUri = $jsUri[0];

            if ($checkPath) {
                $jsPath = $this->context->media->getJSPath($jsUri);
            }

            if ($jsPath && !in_array($jsPath, $this->js_heads)) {
                $this->js_heads[] = $jsPath . ($version ? '?' . $version : '');
            }

        }

    }

    public function addFooterJS($jsUri, $checkPath = true) {

        if (is_array($jsUri)) {

            foreach ($jsUri as $jsFile) {
                $jsFile = explode('?', $jsFile);
                $version = '';

                if (isset($jsFile[1]) && $jsFile[1]) {
                    $version = $jsFile[1];
                }

                $jsPath = $jsFile = $jsFile[0];

                if ($checkPath) {
                    $jsPath = $this->context->media->getJSPath($jsFile);
                }

                // $key = is_array($js_path) ? key($js_path) : $js_path;

                if ($jsPath && !in_array($jsPath, $this->js_footers)) {
                    $this->js_footers[] = $jsPath . ($version ? '?' . $version : '');
                }

            }

        } else {
            $jsUri = explode('?', $jsUri);
            $version = '';

            if (isset($jsUri[1]) && $jsUri[1]) {
                $version = $jsUri[1];
            }

            $jsPath = $jsUri = $jsUri[0];

            if ($checkPath) {
                $jsPath = $this->context->media->getJSPath($jsUri);
            }

            if ($jsPath && !in_array($jsPath, $this->js_footers)) {
                $this->js_footers[] = $jsPath . ($version ? '?' . $version : '');
            }

        }

    }

    public function addJqueryUI($component, $theme = 'base', $checkDependencies = true) {

        if (!is_array($component)) {
            $component = [$component];
        }

        foreach ($component as $ui) {
            $uiPath = $this->context->media->getJqueryUIPath($ui, $theme, $checkDependencies);
            $this->addCSS($uiPath['css'], 'all', false);
            $this->addJS($uiPath['js'], false);
        }

    }

    public function addCSS($cssUri, $cssMediaType = 'all', $offset = null, $checkPath = true) {

        if (!is_array($cssUri)) {
            $cssUri = [$cssUri];
        }

        foreach ($cssUri as $cssFile => $media) {

            if (is_string($cssFile) && strlen($cssFile) > 1) {

                if ($checkPath) {
                    $cssPath = $this->context->media->getCSSPath($cssFile, $media);
                } else {
                    $cssPath = [$cssFile => $media];
                }

            } else {

                if ($checkPath) {
                    $cssPath = $this->context->media->getCSSPath($media, $cssMediaType);
                } else {
                    $cssPath = [$media => is_string($cssMediaType) ? $cssMediaType : 'all'];
                }

            }

            $key = is_array($cssPath) ? key($cssPath) : $cssPath;

            if ($cssPath && (!isset($this->css_files[$key]) || ($this->css_files[$key] != reset($cssPath)))) {
                $size = count($this->css_files);

                if ($offset === null || $offset > $size || $offset < 0 || !is_numeric($offset)) {
                    $offset = $size;
                }

                $this->css_files = array_merge(array_slice($this->css_files, 0, $offset), $cssPath, array_slice($this->css_files, $offset));
            }

        }

    }

    public function pushCSS($cssUri, $cssMediaType = 'all', $offset = null, $checkPath = true) {

        if (!is_array($cssUri)) {

            $cssUri = [$cssUri];
        }

        $result = [];

        foreach ($cssUri as $cssFile => $media) {

            if (is_string($cssFile) && strlen($cssFile) > 1) {

                if ($checkPath) {
                    $cssPath = $this->context->media->getCSSPath($cssFile, $media);
                } else {
                    $cssPath = [$cssFile => $media];
                }

            } else {

                if ($checkPath) {
                    $cssPath = $this->context->media->getCSSPath($media, $cssMediaType);
                } else {

                    $cssPath = [$media => is_string($cssMediaType) ? $cssMediaType : 'all'];
                }

            }

            $key = is_array($cssPath) ? key($cssPath) : $cssPath;

            if ($cssPath) {
                $size = count($this->push_css_files);

                if ($offset === null || $offset > $size || $offset < 0 || !is_numeric($offset)) {
                    $offset = $size;
                }

                $this->push_css_files = array_merge(array_slice($this->push_css_files, 0, $offset), $cssPath, array_slice($this->push_css_files, $offset));
            }

        }

        return $this->push_css_files;

    }

    public function pushJS($jsUri, $checkPath = true) {

        if (is_array($jsUri)) {

            foreach ($jsUri as $jsFile) {
                $jsFile = explode('?', $jsFile);
                $version = '';

                if (isset($jsFile[1]) && $jsFile[1]) {
                    $version = $jsFile[1];
                }

                $jsPath = $jsFile = $jsFile[0];

                if ($checkPath) {
                    $jsPath = $this->context->media->getJSPath($jsFile);
                }

                // $key = is_array($js_path) ? key($js_path) : $js_path;

                if ($jsPath && !in_array($jsPath, $this->push_js_files)) {
                    $this->push_js_files[] = $jsPath . ($version ? '?' . $version : '');
                }

            }

        } else {
            $jsUri = explode('?', $jsUri);
            $version = '';

            if (isset($jsUri[1]) && $jsUri[1]) {
                $version = $jsUri[1];
            }

            $jsPath = $jsUri = $jsUri[0];

            if ($checkPath) {
                $jsPath = $this->context->media->getJSPath($jsUri);
            }

            if ($jsPath && !in_array($jsPath, $this->push_js_files)) {
                $this->push_js_files[] = $jsPath . ($version ? '?' . $version : '');
            }

        }

        return $this->push_js_files;

    }

    public function addJsDef($jsDef) {

        $this->js_def = [];

        if (is_array($jsDef)) {

            foreach ($jsDef as $key => $js) {
                // @codingStandardsIgnoreStart
                $this->js_def[$key] = $js;
                // @codingStandardsIgnoreEnd
            }

        } else

        if ($jsDef) {
            // @codingStandardsIgnoreStart
            $this->js_def[] = $jsDef;
            // @codingStandardsIgnoreEnd
        }

    }

    public function addJqueryPlugin($name, $folder = null, $css = true) {

        if (!is_array($name)) {
            $name = [$name];
        }

        if (is_array($name)) {

            foreach ($name as $plugin) {
                $pluginPath = $this->context->media->getJqueryPluginPath($plugin, $folder);

                if (!empty($pluginPath['js'])) {
                    $this->addJS($pluginPath['js'], false);
                }

                if ($css && !empty($pluginPath['css'])) {
                    $this->addCSS(key($pluginPath['css']), 'all', null, false);
                }

            }

        }

    }

    public function isXmlHttpRequest() {

        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }

    public function manageFieldsVisibility($fields) {

        $return = [];

        if (is_array($fields)) {

            foreach ($fields as $field) {
                $name = '';
                $hidden = false;
                $hiddenable = 'yes';

                foreach ($field as $key => $value) {

                    if ($key == 'title') {
                        $name = $value;
                    }

                    if ($key == 'hidden') {
                        $hidden = $value;
                    }

                    if ($key == 'hiddenable') {
                        $hiddenable = $value;

                        if ($value == 'no') {
                            $name = $field['dataIndx'];
                        }

                    }

                }

                $return[$name] = $field;
                $return[$name]['hidden'] = $hidden;
                $return[$name]['hiddenable'] = $hiddenable;
            }

        }

        return $return;
    }

    public function openTargetController($active) {

        $this->paragridScript = $this->generateParaGridScript();
        $data = $this->createTemplate($this->table . '.tpl');
        $extraVars = $this->context->_hook->exec('action' . $this->controller_name . 'TargetGetExtraVars', ['controller_type' => $this->controller_type], null, true);

        if (is_array($extraVars)) {

            foreach ($extraVars as $plugin => $vars) {

                if (is_array($vars)) {

                    foreach ($vars as $key => $value) {
                        $data->assign($key, $value);
                    }

                }

            }

        }

        if (is_array($this->extra_vars)) {

            foreach ($this->extra_vars as $key => $value) {
                $data->assign($key, $value);
            }

        }

        if (method_exists($this, 'get' . $this->className . 'Fields')) {
            $configurationField = $this->{'get' . $this->className . 'Fields'}

            ();
            $data->assign([
                'manageHeaderFields' => $this->manageHeaderFields,
                'customHeaderFields' => $this->manageFieldsVisibility($configurationField),
            ]);
        }

        $this->addJsDef([
            'AjaxLink' . $this->controller_name => Link::getInstance()->getAdminLink($this->controller_name),
        ]);

        $data->assign([
            'paragridScript'  => $this->paragridScript,
            'controller'      => $this->controller_name,
            'tableName'       => $this->table,
            'className'       => $this->className,
            'link'            => Link::getInstance(),
            'id_lang_default' => $this->default_language,
            'languages'       => Language::getLanguages(false),
            'tabs'            => $this->ajaxOptions,
            'bo_imgdir'       => __EPH_BASE_URI__ . 'content/backoffice/' . $this->bo_theme . '/img/',
        ]);

        $this->tab_content = '<div id="content' . $this->controller_name . '" class="panel wpb_text_column  wpb_slideInUp slideInUp wpb_start_animation animated col-lg-12" style="display: content;">' . $data->fetch() . '</div>';

        $this->context->tabs_controllers[] = [
            $this->builderTabController($active) => $this->tabDisplay(),
        ];

    }

    public function ajaxProcessRefreshTargetController() {

        $args = $this->context->_tools->getValue('args');
        $this->paragridScript = $this->generateParaGridScript();
        $this->setAjaxMedia();
        $data = $this->createTemplate($this->table . '.tpl');
        $extraVars = $this->context->_hook->exec('action' . $this->controller_name . 'TargetGetExtraVars', ['controller_type' => $this->controller_type], null, true);

        if (is_array($extraVars)) {

            foreach ($extraVars as $plugin => $vars) {

                if (is_array($vars)) {

                    foreach ($vars as $key => $value) {
                        $data->assign($key, $value);
                    }

                }

            }

        }

        if (is_array($this->extra_vars)) {

            foreach ($this->extra_vars as $key => $value) {
                $data->assign($key, $value);
            }

        }

        if (method_exists($this, 'get' . $this->className . 'Fields')) {
            $this->configurationField = $this->{'get' . $this->className . 'Fields'}

            ();
            $data->assign([
                'manageHeaderFields' => $this->manageHeaderFields,
                'customHeaderFields' => $this->manageFieldsVisibility($This->configurationField),
            ]);

        }

        $this->addJsDef([
            'AjaxLink' . $this->controller_name => Link::getInstance()->getAdminLink($this->controller_name),
        ]);

        $data->assign([
            'paragridScript'  => $this->paragridScript,
            'controller'      => $this->controller_name,
            'tableName'       => $this->table,
            'className'       => $this->className,
            'link'            => $this->context->_link,
            'id_lang_default' => $this->default_language,
            'languages'       => Language::getLanguages(false),
            'tabs'            => $this->ajaxOptions,
            'allow_acc_char'  => $this->context->phenyxConfig->get('EPH_ALLOW_ACCENTED_CHARS_URL') ? $this->context->phenyxConfig->get('EPH_ALLOW_ACCENTED_CHARS_URL') : 0,
            'bo_imgdir'       => __EPH_BASE_URI__ . 'content/backoffice/' . $this->bo_theme . '/img/',
        ]);

        $this->ajax_li = null;
        $this->ajax_content = '<div id="content' . $this->controller_name . '" class="panel wpb_text_column  wpb_slideInUp slideInUp wpb_start_animation animated col-lg-12" style="display: content;">' . $data->fetch() . '</div>';

        $this->ajaxContentDisplay();

    }

    public function ajaxProcessViewTargetController() {

        $this->ajax_display = 'view';
        $this->ajax_li = '<li id="view' . $this->controller_name . '" data-self="' . $this->link_rewrite . '" data-name="' . $this->page_title . '" data-controller="' . $this->controller_name . '"><a href="#contentview' . $this->controller_name . '">' . $this->viewName . '</a><button type="button" class="close tabdetail" onClick="closeViewObject(\'' . $this->controller_name . '\');" data-id="view' . $this->controller_name . '"><i class="fa-duotone fa-regular fa-circle-xmark"></i></button></li>';
        $this->ajax_content = '<div id="contentview' . $this->controller_name . '" class="panel wpb_text_column  wpb_slideInUp slideInUp wpb_start_animation animated col-lg-12" style="display: content;">' . $this->renderView() . '</div>';

        $this->ajaxDisplay();

    }

    public function ajaxProcessOpenTargetController() {

        if ($this->cachable) {

            if ($this->context->cache_enable) {

                if (is_object($this->context->cache_api)) {
                    $value = $this->context->cache_api->getData($this->cacheId);
                    $result = empty($value) ? null : $this->context->_tools->jsonDecode($value, true);

                    if (!empty($temp)) {
                        die($this->context->_tools->jsonEncode($result));

                    }

                }

            }

        }

        $this->paragridScript = $this->generateParaGridScript();
        $this->setAjaxMedia();
        $data = $this->createTemplate($this->table . '.tpl');
        $extraVars = $this->context->_hook->exec('action' . $this->controller_name . 'TargetGetExtraVars', ['controller_type' => $this->controller_type], null, true);

        if (is_array($extraVars)) {

            foreach ($extraVars as $plugin => $vars) {

                if (is_array($vars)) {

                    foreach ($vars as $key => $value) {
                        $data->assign($key, $value);
                    }

                }

            }

        }

        if (is_array($this->extra_vars)) {

            foreach ($this->extra_vars as $key => $value) {
                $data->assign($key, $value);
            }

        }

        if (method_exists($this, 'get' . $this->className . 'Fields')) {
            $configurationField = $this->{'get' . $this->className . 'Fields'}

            ();
            $data->assign([
                'manageHeaderFields' => $this->manageHeaderFields,
                'customHeaderFields' => $this->manageFieldsVisibility($configurationField),
            ]);
        }

        $this->addJsDef([
            'AjaxLink' . $this->controller_name => Link::getInstance()->getAdminLink($this->controller_name),
        ]);

        $data->assign([
            'paragridScript'  => $this->paragridScript,
            'controller'      => $this->controller_name,
            'tableName'       => $this->table,
            'className'       => $this->className,
            'link'            => $this->context->_link,
            'id_lang_default' => $this->default_language,
            'languages'       => Language::getLanguages(false),
            'tabs'            => $this->ajaxOptions,
            'bo_imgdir'       => __EPH_BASE_URI__ . 'content/backoffice/' . $this->bo_theme . '/img/',
        ]);

        $this->ajax_li = '<li id="uper' . $this->controller_name . '" data-self="' . $this->link_rewrite . '" data-name="' . $this->page_title . '" data-controller="' . $this->controller_name . '"><a href="#content' . $this->controller_name . '"><i class="'.$this->backtab->fa_duatone.'"></i>' . $this->publicName . '</a><button type="button" class="close tabdetail" onClick="closeTabObject(\'' . $this->controller_name . '\');" data-id="uper' . $this->controller_name . '"><i class="fa-duotone fa-regular fa-circle-xmark"></i></button></li>';
        $this->ajax_content = '<div id="content' . $this->controller_name . '" class="panel wpb_text_column  wpb_slideInUp slideInUp wpb_start_animation animated col-lg-12" style="display: content;">' . $data->fetch() . '</div>';

        $this->ajaxDisplay();

    }
    
    public function ajaxProcessRefreshContent() {

        $this->paragridScript = $this->generateParaGridScript();
        $this->setAjaxMedia();
        $data = $this->createTemplate($this->table . '.tpl');
        $extraVars = $this->context->_hook->exec('action' . $this->controller_name . 'TargetGetExtraVars', ['controller_type' => $this->controller_type], null, true);

        if (is_array($extraVars)) {

            foreach ($extraVars as $plugin => $vars) {

                if (is_array($vars)) {

                    foreach ($vars as $key => $value) {
                        $data->assign($key, $value);
                    }

                }

            }

        }

        if (is_array($this->extra_vars)) {

            foreach ($this->extra_vars as $key => $value) {
                $data->assign($key, $value);
            }

        }

        if (method_exists($this, 'get' . $this->className . 'Fields')) {
            $configurationField = $this->{'get' . $this->className . 'Fields'}

            ();
            $data->assign([
                'manageHeaderFields' => $this->manageHeaderFields,
                'customHeaderFields' => $this->manageFieldsVisibility($configurationField),
            ]);
        }

        $this->addJsDef([
            'AjaxLink' . $this->controller_name => Link::getInstance()->getAdminLink($this->controller_name),
        ]);

        $data->assign([
            'paragridScript'  => $this->paragridScript,
            'controller'      => $this->controller_name,
            'tableName'       => $this->table,
            'className'       => $this->className,
            'link'            => $this->context->_link,
            'id_lang_default' => $this->default_language,
            'languages'       => Language::getLanguages(false),
            'tabs'            => $this->ajaxOptions,
            'bo_imgdir'       => __EPH_BASE_URI__ . 'content/backoffice/' . $this->bo_theme . '/img/',
        ]);

        $this->ajax_li = null;
        $this->ajax_content = '<div id="content' . $this->controller_name . '" class="panel wpb_text_column  wpb_slideInUp slideInUp wpb_start_animation animated col-lg-12" style="display: content;">' . $data->fetch() . '</div>';

        $this->ajaxContentDisplay();

    }


    public function tabDisplay() {

        if ($this->ajax_layout) {

            $this->context->smarty->assign(
                [
                    'js_def'           => $this->js_def,
                    'extracss'         => $this->extracss,
                    'favicon_dir'      => __EPH_BASE_URI__ . 'content/backoffice/img/',
                    'meta_title'       => $this->page_title,
                    'meta_description' => $this->page_description,
                ]
            );

            $dir = $this->context->smarty->getTemplateDir(0);
            $override_dir = $this->context->smarty->getTemplateDir(1) . DIRECTORY_SEPARATOR;
            $pluginListDir = $this->context->smarty->getTemplateDir(0) . 'helpers' . DIRECTORY_SEPARATOR . 'plugins_list' . DIRECTORY_SEPARATOR;

            $headerTpl = file_exists($dir . 'ajax_header.tpl') ? $dir . 'ajax_header.tpl' : 'ajax_header.tpl';
            $footerTpl = file_exists($dir . 'ajax_footer.tpl') ? $dir . 'ajax_footer.tpl' : 'ajax_footer.tpl';

            $this->context->smarty->assign(
                [
                    'content'     => $this->tab_content,
                    'ajax_header' => $this->context->smarty->fetch($headerTpl),
                    'ajax_footer' => $this->context->smarty->fetch($footerTpl),
                ]
            );

            $content = $this->context->smarty->fetch($this->ajax_layout);

            return $this->tabShowContent($content);
        }

    }

    public function tabShowContent($content) {

        $this->context->cookie->write();
        $html = '';
        $jsTag = 'js_def';
        $this->context->smarty->assign($jsTag, $jsTag);
        $html = $content;

        $html = trim($html);

        if (!empty($html)) {

            if ($this->_defer && $this->_domAvailable) {
                $html = $this->context->media->deferInlineScripts($html);
            }

            $head = '<div id="content' . $this->controller_name . '" class="panel wpb_text_column  wpb_slideInUp slideInUp wpb_start_animation animated col-lg-12" style="display: content;">' . "\n";
            $foot = '</div></content>';
            $header = $this->context->media->deferTagOutput('ajax_head', $html) . '<content>';
            $html = trim(str_replace($header, '', $html)) . "\n";

            $content = $this->context->media->deferIdOutput('content' . $this->controller_name, $html);

            $content = str_replace('<input>', '', $content);
            $content = $head . $header . $content . $foot;

            return $content;
        }

    }

    public function ajaxDisplay() {

        if ($this->ajax_layout) {

            if ($this->cachable) {

                if ($this->context->cache_enable) {

                    if (is_object($this->context->cache_api)) {
                        $value = $this->context->cache_api->getData($this->cacheId);
                        $result = empty($value) ? null : $this->context->_tools->jsonDecode($value, true);

                        if (!empty($result)) {
                            die($this->context->_tools->jsonEncode($result));

                        }

                    }

                }

            }

            if (($this->_back_css_cache || $this->_back_js_cache) && is_writable(_EPH_BO_ALL_THEMES_DIR_ . 'backend/cache')) {

                if ($this->_back_css_cache) {
                    $this->extracss = $this->context->media->admincccCss($this->extracss);
                }

                if ($this->_back_js_cache) {
                    $this->push_js_files = $this->context->media->admincccJS($this->push_js_files);
                }

            }

            $controller = $this->context->_tools->getValue('controller');

            $this->context->smarty->assign(
                [
                    'js_def'           => ($this->_defer && $this->_domAvailable) ? [] : $this->js_def,
                    'extracss'         => $this->extracss,
                    'js_heads'         => [],
                    'js_files'         => $this->_defer ? [] : $this->push_js_files,
                    'favicon_dir'      => __EPH_BASE_URI__ . 'content/backoffice/img/',
                    'meta_title'       => $this->page_title,
                    'meta_description' => $this->page_description,
                ]
            );

            $dir = $this->context->smarty->getTemplateDir(0);
            $override_dir = $this->context->smarty->getTemplateDir(1) . DIRECTORY_SEPARATOR;
            $pluginListDir = $this->context->smarty->getTemplateDir(0) . 'helpers' . DIRECTORY_SEPARATOR . 'plugins_list' . DIRECTORY_SEPARATOR;

            $headerTpl = file_exists($dir . 'ajax_header.tpl') ? $dir . 'ajax_header.tpl' : 'ajax_header.tpl';
            $footerTpl = file_exists($dir . 'ajax_footer.tpl') ? $dir . 'ajax_footer.tpl' : 'ajax_footer.tpl';

            $this->context->smarty->assign(
                [
                    'content'     => $this->ajax_content,
                    'ajax_header' => $this->context->smarty->fetch($headerTpl),
                    'ajax_footer' => $this->context->smarty->fetch($footerTpl),
                ]
            );

            $content = $this->context->smarty->fetch($this->ajax_layout, $this->php_self);
            $this->ajaxShowContent($content);
        }

    }

    public function ajaxContentDisplay() {

        
        if (($this->_back_css_cache || $this->_back_js_cache) && is_writable(_EPH_BO_ALL_THEMES_DIR_ . 'backend/cache')) {

            if ($this->_back_css_cache) {
                $this->extracss = $this->context->media->admincccCss($this->extracss);
            }

            if ($this->_back_js_cache) {
                $this->push_js_files = $this->context->media->admincccJS($this->push_js_files);
            }

        }

        $controller = $this->context->_tools->getValue('controller');

        $this->context->smarty->assign(
            [
                'js_def'           => ($this->_defer && $this->_domAvailable) ? [] : $this->js_def,
                'extracss'         => $this->extracss,
                'js_heads'         => [],
                'js_files'         => $this->_defer ? [] : $this->push_js_files,
                'favicon_dir'      => __EPH_BASE_URI__ . 'content/backoffice/img/',
                'meta_title'       => $this->page_title,
                'meta_description' => $this->page_description,
            ]
        );

        $dir = $this->context->smarty->getTemplateDir(0);
        $override_dir = $this->context->smarty->getTemplateDir(1) . DIRECTORY_SEPARATOR;
        $pluginListDir = $this->context->smarty->getTemplateDir(0) . 'helpers' . DIRECTORY_SEPARATOR . 'plugins_list' . DIRECTORY_SEPARATOR;

        $headerTpl = file_exists($dir . 'ajax_header.tpl') ? $dir . 'ajax_header.tpl' : 'ajax_header.tpl';
        $footerTpl = file_exists($dir . 'ajax_footer.tpl') ? $dir . 'ajax_footer.tpl' : 'ajax_footer.tpl';

        $this->context->smarty->assign(
            [
                'content'     => $this->ajax_content,
                'ajax_header' => $this->context->smarty->fetch($headerTpl),
                'ajax_footer' => $this->context->smarty->fetch($footerTpl),
            ]
        );

        $content = $this->context->smarty->fetch($this->ajax_layout);
        $this->ajax_display == 'refresh';
        $this->ajaxRefreshContent($content);

    }

    public function refeshDisplay() {

        if ($this->ajax_layout) {

            if ($this->cachable) {

                if ($this->context->cache_enable) {

                    if (is_object($this->context->cache_api)) {
                        $value = $this->context->cache_api->getData($this->cacheId);
                        $result = empty($value) ? null : $this->context->_tools->jsonDecode($value, true);

                        if (!empty($temp)) {
                            die($this->context->_tools->jsonEncode($result));

                        }

                    }

                }

            }

            if (($this->_back_css_cache || $this->_back_js_cache) && is_writable(_EPH_BO_ALL_THEMES_DIR_ . 'backend/cache')) {

                if ($this->_back_css_cache) {
                    $this->extracss = $this->context->media->admincccCss($this->extracss);
                }

                if ($this->_back_js_cache) {
                    $this->push_js_files = $this->context->media->admincccJS($this->push_js_files);
                }

            }

            $controller = $this->context->_tools->getValue('controller');

            $this->context->smarty->assign(
                [
                    'js_def'           => ($this->_defer && $this->_domAvailable) ? [] : $this->js_def,
                    'extracss'         => $this->extracss,
                    'js_heads'         => [],
                    'js_files'         => $this->_defer ? [] : $this->push_js_files,
                    'favicon_dir'      => __EPH_BASE_URI__ . 'content/backoffice/img/',
                    'meta_title'       => $this->page_title,
                    'meta_description' => $this->page_description,
                ]
            );

            $dir = $this->context->smarty->getTemplateDir(0);
            $override_dir = $this->context->smarty->getTemplateDir(1) . DIRECTORY_SEPARATOR;
            $pluginListDir = $this->context->smarty->getTemplateDir(0) . 'helpers' . DIRECTORY_SEPARATOR . 'plugins_list' . DIRECTORY_SEPARATOR;

            $headerTpl = file_exists($dir . 'ajax_header.tpl') ? $dir . 'ajax_header.tpl' : 'ajax_header.tpl';
            $footerTpl = file_exists($dir . 'ajax_footer.tpl') ? $dir . 'ajax_footer.tpl' : 'ajax_footer.tpl';

            $this->context->smarty->assign(
                [
                    'content'     => $this->ajax_content,
                    'ajax_header' => $this->context->smarty->fetch($headerTpl),
                    'ajax_footer' => $this->context->smarty->fetch($footerTpl),
                ]
            );

            $content = $this->context->smarty->fetch($this->ajax_layout);
            return $this->showContent($content);
        } else {

        }

    }

    public function showContent($content) {

        $this->context->cookie->write();
        $html = '';
        $jsTag = 'js_def';
        $this->context->smarty->assign($jsTag, $jsTag);
        $html = $content;

        $html = trim($html);

        if (!empty($html)) {
            $javascript = "";

            if ($this->_defer && $this->_domAvailable) {
                $html = $this->context->media->deferInlineScripts($html);
            }

            $head = '<div id="content' . $this->controller_name . '" class="panel wpb_text_column  wpb_slideInUp slideInUp wpb_start_animation animated col-lg-12" style="display: content;">' . "\n";
            $foot = '</div>';
            $header = $this->context->media->deferTagOutput('ajax_head', $html) . '<content>';
            $html = trim(str_replace($header, '', $html)) . "\n";

            $content = $this->context->media->deferIdOutput('content' . $this->controller_name, $html);

            $js_def = ($this->_defer && $this->_domAvailable) ? $this->js_def : [];
            $js_files = $this->_defer ? array_unique($this->push_js_files) : [];
            $js_inline = ($this->_defer && $this->_domAvailable) ? $this->context->media->getInlineScript() : [];

            $this->context->smarty->assign(
                [
                    'js_def'    => $js_def,
                    'js_files'  => $js_files,
                    'js_inline' => $js_inline,
                ]
            );
            $javascript = $this->context->smarty->fetch(_EPH_ALL_THEMES_DIR_ . 'javascript.tpl');

            if ($this->_defer) {
                $javascript = $javascript . '</content>';
            }

            $content = $head . $header . $content . $javascript . $foot;

            return $content;

        }

    }

    public function getAjaxLayout() {

        $layout = false;

        $layoutDir = $this->context->smarty->getTemplateDir(0);

        if (!$layout && file_exists($layoutDir . 'ajaxlayout.tpl')) {
            $layout = $layoutDir . 'ajaxlayout.tpl';
        }

        return $layout;
    }

    public function ajaxShowContent($content) {

        $this->context->cookie->write();
        $html = '';
        $jsTag = 'js_def';
        $this->context->smarty->assign($jsTag, $jsTag);
        $html = $content;

        $html = trim($html);

        if (!empty($html)) {

            $javascript = "";

            if ($this->_defer && $this->_domAvailable) {
                $html = $this->context->media->deferInlineScripts($html);
            }

            if ($this->ajax_display == 'view') {
                $head = '<div id="contentview' . $this->controller_name . '" class="panel wpb_text_column  wpb_slideInUp slideInUp wpb_start_animation animated col-lg-12" style="display: content;">' . "\n";
            } else {
                $head = '<div id="content' . $this->controller_name . '" class="panel wpb_text_column  wpb_slideInUp slideInUp wpb_start_animation animated col-lg-12" style="display: content;">' . "\n";
            }

            $foot = '</div>';
            $header = $this->context->media->deferTagOutput('ajax_head', $html) . '<content>';
            $html = trim(str_replace($header, '', $html)) . "\n";

            if ($this->ajax_display == 'view') {
                $content = $this->context->media->deferIdOutput('contentview' . $this->controller_name, $html);
            } else {
                $content = $this->context->media->deferIdOutput('content' . $this->controller_name, $html);
            }

            $js_def = ($this->_defer && $this->_domAvailable) ? $this->js_def : [];
            $js_files = $this->_defer ? array_unique($this->push_js_files) : [];
            $js_inline = ($this->_defer && $this->_domAvailable) ? $this->context->media->getInlineScript() : [];

            $this->context->smarty->assign(
                [
                    'js_def'    => $js_def,
                    'js_files'  => $js_files,
                    'js_inline' => $js_inline,
                ]
            );
            $javascript = '<cntscript>' . $this->context->smarty->fetch(_EPH_ALL_THEMES_DIR_ . 'javascript.tpl') . '</cntscript>';

            if ($this->_defer) {
                $javascript = $javascript . '</content>';
            }

            $content = str_replace('<input>', '', $content);
            $content = $head . $header . $content . $javascript . $foot;
            $result = [
                'li'         => $this->ajax_li,
                'html'       => $content,
                'page_title' => $this->page_title,
                'controller' => $this->controller_name,
                'load_time'  => sprintf($this->la('Load time %s seconds'), round(microtime(true) - TIME_START, 3)),
            ];

            if (_EPH_ADMIN_DEBUG_PROFILING_) {
                $result['profiling_mode'] = true;
                $result['profiling'] = $this->displayProfiling();
            } else {

                if (!is_null($this->cacheId) && $this->cachable && $this->context->cache_enable) {
                    $temp = $this->context->_tools->jsonEncode($result);
                    $this->context->cache_api->putData($this->cacheId, $temp, 1864000);
                }

            }

            die($this->context->_tools->jsonEncode($result));

        }

    }
    
    public function ajaxRefreshContent($content) {
        
        $this->context->cookie->write();
        $html = '';
        $jsTag = 'js_def';
        $this->context->smarty->assign($jsTag, $jsTag);
        $html = $content;

        $html = trim($html);

        if (!empty($html)) {

            $javascript = "";

            if ($this->_defer && $this->_domAvailable) {
                $html = $this->context->media->deferInlineScripts($html);
            }

            
            $header = $this->context->media->deferTagOutput('ajax_head', $html) . '<content>';
            $html = trim(str_replace($header, '', $html)) . "\n";

            if ($this->ajax_display == 'view') {
                $content = $this->context->media->deferIdOutput('contentview' . $this->controller_name, $html);
            } else {
                $content = $this->context->media->deferIdOutput('content' . $this->controller_name, $html);
            }
            

            $js_def = ($this->_defer && $this->_domAvailable) ? $this->js_def : [];
            $js_files = $this->_defer ? array_unique($this->push_js_files) : [];
            $js_inline = ($this->_defer && $this->_domAvailable) ? $this->context->media->getInlineScript() : [];

            $this->context->smarty->assign(
                [
                    'js_def'    => $js_def,
                    'js_files'  => $js_files,
                    'js_inline' => $js_inline,
                ]
            );
            $javascript = '<cntscript>' . $this->context->smarty->fetch(_EPH_ALL_THEMES_DIR_ . 'javascript.tpl') . '</cntscript>';

            if ($this->_defer) {
                $javascript = $javascript . '</content>';
            }

            $content = str_replace('<input>', '', $content);
            $content = $header . $content . $javascript ;
            $result = [                
                'html'       => $content,
            ];

            if (_EPH_ADMIN_DEBUG_PROFILING_) {
                $result['profiling_mode'] = true;
                $result['profiling'] = $this->displayProfiling();
            } else {

                if (!is_null($this->cacheId) && $this->cachable && $this->context->cache_enable) {
                    $temp = $this->context->_tools->jsonEncode($result);
                    $this->context->cache_api->putData($this->cacheId, $temp, 1864000);
                }

            }

            die($this->context->_tools->jsonEncode($result));

        }

    }

    public function ajaxProcessEditObject() {

        $this->checkAccess();

        if ($this->tabAccess['edit'] == 1) {

            $idObject = $this->context->_tools->getValue('idObject');

            $_GET[$this->identifier] = $idObject;
            $_GET['controller'] = $this->controller_name;
            $_GET['update' . $this->table] = "";

            $html = $this->renderForm();
            $this->ajax_li = '<li id="uperEdit' . $this->controller_name . '" data-controller="' . $this->controller_name . '"><a href="#contentEdit' . $this->controller_name . '">' . $this->editObject . '</a><button type="button" onClick="closeEditFormObject(\'' . $this->controller_name . '\', ' . $this->composer_editor . ');" class="close tabdetail" data-id="uperEdit' . $this->controller_name . '"><i class="fa-duotone fa-regular fa-circle-xmark"></i></button></li>';
            $this->ajax_content = '<div id="contentEdit' . $this->controller_name . '" class="panel wpb_text_column  wpb_slideInUp slideInUp wpb_start_animation animated col-lg-12" style="display; flow-root;">' . $html . '</div>';

            $this->ajaxEditDisplay();

        } else {
            $result = [
                'success' => false,
                'message' => $this->la('Your administrative profile does not allow you to edit this object'),
            ];
        }

        die($this->context->_tools->jsonEncode($result));
    }

    public function ajaxProcessAddObject() {

        $this->checkAccess();
        $_GET['controller'] = $this->controller_name;
        $_GET['add' . $this->table] = "";
        $_GET['id_parent'] = $this->context->_tools->getValue('idParent', '');

        $scripHeader = $this->context->_hook->exec('displayBackOfficeHeader', []);
        $scriptFooter = $this->context->_hook->exec('displayBackOfficeFooter', []);
        $html = $this->renderForm();

        $this->ajax_li = '<li id="uperAdd' . $this->controller_name . '" data-controller="' . $this->controller_name . '"><a href="#contentAdd' . $this->controller_name . '">' . $this->editObject . '</a><button type="button" onClick="closeAddFormObject(\'' . $this->controller_name . '\', ' . $this->composer_editor . ')" class="close tabdetail" data-id="uperAdd' . $this->controller_name . '"><i class="fa-duotone fa-regular fa-circle-xmark"></i></button></li>';
        $this->ajax_content = '<div id="contentAdd' . $this->controller_name . '" class="panel wpb_text_column  wpb_slideInUp slideInUp wpb_start_animation animated col-lg-12" style="display; flow-root;">' . $scripHeader . $html . $scriptFooter . '</div>';

        $this->ajaxEditDisplay();
    }

    public function ajaxEditDisplay() {

        $layout = $this->getAjaxLayout();

        if ($layout) {

            if (($this->_back_css_cache || $this->_back_js_cache) && is_writable(_EPH_BO_ALL_THEMES_DIR_ . 'backend/cache')) {

                if ($this->_back_css_cache) {
                    $this->extracss = $this->context->media->admincccCss($this->extracss);
                }

                if ($this->_back_js_cache) {
                    $this->extraJs = $this->context->media->admincccJS($this->extraJs);
                }

            }

            $controller = $this->context->_tools->getValue('controller');

            $this->context->smarty->assign(
                [
                    'js_def'           => ($this->_defer && $this->_domAvailable) ? [] : $this->js_def,
                    'extracss'         => $this->extracss,
                    'js_heads'         => [],
                    'js_files'         => $this->_defer ? [] : $this->extraJs,
                    'favicon_dir'      => __EPH_BASE_URI__ . 'content/backoffice/img/',
                    'meta_title'       => $this->page_title,
                    'meta_description' => $this->page_description,
                ]
            );

            $dir = $this->context->smarty->getTemplateDir(0);
            $override_dir = $this->context->smarty->getTemplateDir(1) . DIRECTORY_SEPARATOR;
            $pluginListDir = $this->context->smarty->getTemplateDir(0) . 'helpers' . DIRECTORY_SEPARATOR . 'plugins_list' . DIRECTORY_SEPARATOR;

            $headerTpl = file_exists($dir . 'ajax_header.tpl') ? $dir . 'ajax_header.tpl' : 'ajax_header.tpl';
            $footerTpl = file_exists($dir . 'ajax_footer.tpl') ? $dir . 'ajax_footer.tpl' : 'ajax_footer.tpl';

            $this->context->smarty->assign(
                [
                    'content'     => $this->ajax_content,
                    'ajax_header' => $this->context->smarty->fetch($headerTpl),
                    'ajax_footer' => $this->context->smarty->fetch($footerTpl),
                ]
            );

            $content = $this->context->smarty->fetch($layout);
            $this->ajaxShowEditContent($content);
        } else {

        }

    }

    public function ajaxShowEditContent($content) {

        $this->context->cookie->write();
        $html = '';
        $jsTag = 'js_def';
        $this->context->smarty->assign($jsTag, $jsTag);
        $html = $content;

        $html = trim($html);

        if (!empty($html)) {
            $javascript = "";

            if ($this->_defer && $this->_domAvailable) {
                $html = $this->context->media->deferInlineScripts($html);
            }

            if (isset($this->object->id) && $this->object->id > 0) {
                $head = '<div id="contentEdit' . $this->controller_name . '" class="panel wpb_text_column  wpb_slideInUp slideInUp wpb_start_animation animated col-lg-12" style="display: flow-root;">' . "\n";
            } else {
                $head = '<div id="contentAdd' . $this->controller_name . '" class="panel wpb_text_column  wpb_slideInUp slideInUp wpb_start_animation animated col-lg-12" style="display: flow-root;">' . "\n";
            }

            $foot = '</div>';
            $header = $this->context->media->deferTagOutput('ajax_head', $html) . '<content>';
            $html = trim(str_replace($header, '', $html)) . "\n";

            if (isset($this->object->id) && $this->object->id > 0) {
                $content = $this->context->media->deferIdOutput('contentEdit' . $this->controller_name, $html);
            } else {
                $content = $this->context->media->deferIdOutput('contentAdd' . $this->controller_name, $html);
            }

            $js_def = ($this->_defer && $this->_domAvailable) ? $this->js_def : [];
            $js_files = (!is_null($this->extraJs) && $this->_defer) ? array_unique($this->extraJs) : [];
            $js_inline = ($this->_defer && $this->_domAvailable) ? $this->context->media->getInlineScript() : [];

            $this->context->smarty->assign(
                [
                    'js_def'    => $js_def,
                    'js_files'  => $js_files,
                    'js_inline' => $js_inline,
                    'js_heads'  => [],
                ]
            );
            $javascript = $this->context->smarty->fetch(_EPH_ALL_THEMES_DIR_ . 'javascript.tpl');

            if ($this->_defer) {
                $javascript = $javascript . '</content>';
            }

            $content = $head . $header . $content . $javascript . $foot;

            $result = [
                'success'    => true,
                'li'         => $this->ajax_li,
                'html'       => $content,
                'page_title' => $this->page_title,
                'load_time'  => sprintf($this->la('Load time %s seconds'), round(microtime(true) - TIME_START, 3)),
            ];

            if (_EPH_ADMIN_DEBUG_PROFILING_) {
                $result['profiling_mode'] = true;
                $result['profiling'] = $this->displayProfiling();
            }

            die($this->context->_tools->jsonEncode($result));

        }

    }

    public function ajaxModalEditDisplay() {

        $layout = $this->getAjaxLayout();

        if ($layout) {

            if (($this->_back_css_cache || $this->_back_js_cache) && is_writable(_EPH_BO_ALL_THEMES_DIR_ . 'backend/cache')) {

                if ($this->_back_css_cache) {
                    $this->extracss = $this->context->media->admincccCss($this->extracss);
                }

                if ($this->_back_js_cache) {
                    $this->extraJs = $this->context->media->admincccJS($this->extraJs);
                }

            }

            $controller = $this->context->_tools->getValue('controller');

            $this->context->smarty->assign(
                [
                    'js_def'           => ($this->_defer && $this->_domAvailable) ? [] : $this->js_def,
                    'extracss'         => $this->extracss,
                    'js_heads'         => [],
                    'js_files'         => $this->_defer ? [] : $this->extraJs,
                    'favicon_dir'      => __EPH_BASE_URI__ . 'content/backoffice/img/',
                    'meta_title'       => $this->page_title,
                    'meta_description' => $this->page_description,
                ]
            );

            $dir = $this->context->smarty->getTemplateDir(0);
            $override_dir = $this->context->smarty->getTemplateDir(1) . DIRECTORY_SEPARATOR;
            $pluginListDir = $this->context->smarty->getTemplateDir(0) . 'helpers' . DIRECTORY_SEPARATOR . 'plugins_list' . DIRECTORY_SEPARATOR;

            $headerTpl = file_exists($dir . 'ajax_header.tpl') ? $dir . 'ajax_header.tpl' : 'ajax_header.tpl';
            $footerTpl = file_exists($dir . 'ajax_footer.tpl') ? $dir . 'ajax_footer.tpl' : 'ajax_footer.tpl';

            $this->context->smarty->assign(
                [
                    'content'     => $this->ajax_content,
                    'ajax_header' => $this->context->smarty->fetch($headerTpl),
                    'ajax_footer' => $this->context->smarty->fetch($footerTpl),
                ]
            );

            $content = $this->context->smarty->fetch($layout);
            $this->ajaxShowModalEditContent($content);
        } else {

        }

    }

    public function ajaxShowModalEditContent($content) {

        $this->context->cookie->write();
        $html = '';
        $jsTag = 'js_def';
        $this->context->smarty->assign($jsTag, $jsTag);
        $html = $content;

        $html = trim($html);

        if (!empty($html)) {
            $javascript = "";

            if ($this->_defer && $this->_domAvailable) {
                $html = $this->context->media->deferInlineScripts($html);
            }

            $header = $this->context->media->deferTagOutput('ajax_head', $html) . '<content>';
            $html = trim(str_replace($header, '', $html)) . "\n";

            if (isset($this->object->id) && $this->object->id > 0) {
                $content = $this->context->media->deferIdOutput('contentEdit' . $this->controller_name, $html);
            } else {
                $content = $this->context->media->deferIdOutput('contentAdd' . $this->controller_name, $html);
            }

            $js_def = ($this->_defer && $this->_domAvailable) ? $this->js_def : [];
            $js_files = (!is_null($this->extraJs) && $this->_defer) ? array_unique($this->extraJs) : [];
            $js_inline = ($this->_defer && $this->_domAvailable) ? $this->context->media->getInlineScript() : [];

            $this->context->smarty->assign(
                [
                    'js_def'    => $js_def,
                    'js_files'  => $js_files,
                    'js_inline' => $js_inline,
                    'js_heads'  => [],
                ]
            );
            $javascript = $this->context->smarty->fetch(_EPH_ALL_THEMES_DIR_ . 'javascript.tpl');

            if ($this->_defer) {
                $javascript = $javascript . '</content>';
            }

            $content = $header . $content . $javascript;

            $result = [
                'success'    => true,
                'title'      => $this->dialog_title,
                'html'       => $content,
                'page_title' => $this->page_title,
                'load_time'  => sprintf($this->la('Load time %s seconds'), round(microtime(true) - TIME_START, 3)),
            ];

            if (_EPH_ADMIN_DEBUG_PROFILING_) {
                $result['profiling_mode'] = true;
                $result['profiling'] = $this->displayProfiling();
            }

            die($this->context->_tools->jsonEncode($result));

        }

    }

    public function generateTabs($use_cache = true) {

        return $this->context->_tools->generateTabs($use_cache);
    }

    protected function initTabPluginList() {

        $this->tab_plugins_list = BackTab::getTabPluginsList($this->id);

        if (is_array($this->tab_plugins_list['default_list']) && count($this->tab_plugins_list['default_list'])) {
            $this->filter_plugins_list = $this->tab_plugins_list['default_list'];
        } else

        if (is_array($this->tab_plugins_list['slider_list']) && count($this->tab_plugins_list['slider_list'])) {
            $this->addToolBarPluginsListButton();
            $this->addPageHeaderToolBarPluginsListButton();
            $this->context->smarty->assign(
                [
                    'tab_plugins_list'      => implode(',', $this->tab_plugins_list['slider_list']),
                    'admin_plugin_ajax_url' => $this->context->_link->getAdminLink('AdminPlugins'),
                    'back_tab_plugins_list' => $this->context->_link->getAdminLink($this->context->_tools->getValue('controller')),
                    'tab_plugins_open'      => (int) $this->context->_tools->getValue('tab_plugins_open'),
                ]
            );
        }

    }

    protected function addToolBarPluginsListButton() {

        $this->filterTabPluginList();

        if (is_array($this->tab_plugins_list['slider_list']) && count($this->tab_plugins_list['slider_list'])) {
            $this->toolbar_btn['plugins-list'] = [
                'href' => '#',
                'desc' => $this->la('Recommended Plugins and Services'),
            ];
        }

    }

    protected function filterTabPluginList() {

        static $listIsFiltered = null;

        if ($listIsFiltered !== null) {
            return;
        }

        libxml_use_internal_errors(true);

        $allPluginList = [];

        libxml_clear_errors();

        $this->tab_plugins_list['slider_list'] = array_intersect($this->tab_plugins_list['slider_list'], $allPluginList);

        $listIsFiltered = true;
    }

    protected function addPageHeaderToolBarPluginsListButton() {

        $this->filterTabPluginList();

        if (is_array($this->tab_plugins_list['slider_list']) && count($this->tab_plugins_list['slider_list'])) {
            $this->page_header_toolbar_btn['plugins-list'] = [
                'href' => '#',
                'desc' => $this->la('Recommended Plugins and Services'),
            ];
        }

    }

    public function renderModal() {

        $modal_render = '';

        if (is_array($this->modals) && count($this->modals)) {

            foreach ($this->modals as $modal) {
                $this->context->smarty->assign($modal);
                $modal_render .= $this->context->smarty->fetch('modal.tpl');
            }

        }

        return $modal_render;
    }

    public function ajaxProcessDuplicateObject() {

        $this->checkAccess();

        if ($this->tabAccess['edit'] == 1) {

            $idObject = $this->context->_tools->getValue('idObject');
            $objet = new $this->className($idObject);
            $this->object = $objet->duplicateObject();

            $_GET[$this->identifier] = $this->object->id;
            $_GET['controller'] = $this->controller_name;
            $_GET['update' . $this->table] = "";

            $html = $this->renderForm();

            $li = '<li id="uperEdit' . $this->controller_name . '" data-controller="' . $this->controller_name . '"><a href="#contentEdit' . $this->controller_name . '">' . $this->editObject . '</a><button type="button" onClick="closeEditFormObject(\'' . $this->controller_name . '\', ' . $this->composer_editor . ');" class="close tabdetail" data-id="uperEdit' . $this->controller_name . '"><i class="fa-duotone fa-regular fa-circle-xmark"></i></button></li>';

            $html = '<div id="contentEdit' . $this->controller_name . '" class="panel wpb_text_column  wpb_slideInUp slideInUp wpb_start_animation animated col-lg-12" style="display; flow-root;">' . $html . '</div>';

            $result = [
                'success' => true,
                'li'      => $li,
                'html'    => $html,
            ];
        } else {
            $result = [
                'success' => false,
                'message' => $this->la('Your administrative profile does not allow you to edit this object'),
            ];
        }

        die($this->context->_tools->jsonEncode($result));
    }

    public function ajaxProcessDeleteObject() {

        $this->checkAccess();
        $idObject = $this->context->_tools->getValue('idObject');

        $this->className = $this->context->_tools->getValue('targetClass');

        $this->object = new $this->className($idObject);

        $this->object->delete();

        $result = [
            'success' => true,
            'message' => $this->la('The deletion was successful.'),
        ];

        die($this->context->_tools->jsonEncode($result));
    }

    public function ajaxProcessUpdateObject() {

        $this->checkAccess();
        $has_keyword = $this->context->_tools->getValue('has_keyword');
        $idObject = $this->context->_tools->getValue($this->identifier);
        $this->object = new $this->className($idObject);

        $this->copyFromPost($this->object, $this->table, $has_keyword);

        $this->beforeUpdate();

        $result = $this->object->update();

        $this->afterUpdate();

        if ($result) {

            $return = [
                'success' => true,
                'message' => sprintf($this->la('Object of type %s was successfully updated'), $this->className),
            ];
        } else {
            $return = [
                'success' => false,
                'message' => $this->la('An error occurred while trying to update this object'),
            ];
        }

        die($this->context->_tools->jsonEncode($return));
    }

    public function ajaxProcessAddNewObject() {

        $this->checkAccess();
        $this->object = new $this->className();

        $this->copyFromPost($this->object, $this->table);

        $this->beforeAdd();

        $result = $this->object->add();

        $this->afterAdd();

        if ($result) {
            $return = [
                'success' => true,
                'message' => sprintf($this->la('Object of type %s was successfully added'), $this->className),
            ];

        } else {
            $return = [
                'success' => false,
                'message' => $this->la('An error occurred while trying to add this object'),
            ];

        }

        die($this->context->_tools->jsonEncode($return));

    }

    protected function la($string, $class = null, $addslashes = false, $htmlentities = true) {

        if ($class === null) {
            $class = substr(get_class($this), 0, -10);
        } else

        if (strtolower(substr($class, -10)) == 'controller') {
            /* classname has changed, from AdminXXX to AdminXXXController, so we remove 10 characters and we keep same keys */
            $class = substr($class, 0, -10);
        }

        return $this->context->translations->getAdminTranslation($string, $class, $addslashes, $htmlentities);
    }

    protected function isCached($template, $cacheId = null, $compileId = null) {

        $this->context->_tools->enableCache();
        $res = $this->context->smarty->isCached($template, $cacheId, $compileId);
        $this->context->_tools->restoreCacheSettings();

        return $res;
    }

    public function getWizardFieldsValues($obj) {

        foreach ($this->fields_form as $fieldset) {

            if (isset($fieldset['input'])) {

                foreach ($fieldset['input'] as $input) {

                    if (!isset($this->fields_value[$input['name']])) {

                        if (isset($input['lang']) && $input['lang']) {

                            foreach ($this->_languages as $language) {
                                $fieldValue = $this->getWizardFieldsValue($obj, $input['name'], $language['id_lang']);

                                if (empty($fieldValue)) {

                                    if (isset($input['default_value']) && is_array($input['default_value']) && isset($input['default_value'][$language['id_lang']])) {
                                        $fieldValue = $input['default_value'][$language['id_lang']];
                                    } else

                                    if (isset($input['default_value'])) {
                                        $fieldValue = $input['default_value'];
                                    }

                                }

                                $this->fields_value[$input['name']][$language['id_lang']] = $fieldValue;
                            }

                        } else {

                            $fieldValue = $this->getWizardFieldsValue($obj, $input['name']);

                            if ($fieldValue === false && isset($input['default_value'])) {
                                $this->fields_value[$input['name']] = $input['default_value'];
                            } else

                            if ($fieldValue === false) {
                                $this->fields_value[$input['name']] = [];
                            } else {
                                $this->fields_value[$input['name']] = $fieldValue;
                            }

                        }

                    }

                }

            }

        }

        foreach ($this->fields_form['steps'] as $fieldset) {

            if (isset($fieldset['input'])) {

                foreach ($fieldset['input'] as $input) {

                    if (!isset($this->fields_value[$input['name']])) {

                        if (isset($input['lang']) && $input['lang']) {

                            foreach ($this->_languages as $language) {
                                $fieldValue = $this->getWizardFieldsValue($obj, $input['name'], $language['id_lang']);

                                if (empty($fieldValue)) {

                                    if (isset($input['default_value']) && is_array($input['default_value']) && isset($input['default_value'][$language['id_lang']])) {
                                        $fieldValue = $input['default_value'][$language['id_lang']];
                                    } else

                                    if (isset($input['default_value'])) {
                                        $fieldValue = $input['default_value'];
                                    }

                                }

                                $this->fields_value[$input['name']][$language['id_lang']] = $fieldValue;
                            }

                        } else {

                            $fieldValue = $this->getWizardFieldsValue($obj, $input['name']);

                            if ($fieldValue === false && isset($input['default_value'])) {
                                $this->fields_value[$input['name']] = $input['default_value'];
                            } else

                            if ($fieldValue === false) {
                                $this->fields_value[$input['name']] = [];
                            } else {
                                $this->fields_value[$input['name']] = $fieldValue;
                            }

                        }

                    }

                }

            }

        }

        return $this->fields_value;
    }

    public function getFieldsValue($obj) {

        foreach ($this->fields_form as $fieldset) {

            if (isset($fieldset['form']['input'])) {

                foreach ($fieldset['form']['input'] as $input) {

                    if (!isset($this->fields_value[$input['name']])) {

                        if (isset($input['lang']) && $input['lang']) {

                            foreach ($this->_languages as $language) {
                                $fieldValue = $this->getFieldValue($obj, $input['name'], $language['id_lang']);

                                if (empty($fieldValue)) {

                                    if (isset($input['default_value']) && is_array($input['default_value']) && isset($input['default_value'][$language['id_lang']])) {
                                        $fieldValue = $input['default_value'][$language['id_lang']];
                                    } else

                                    if (isset($input['default_value'])) {
                                        $fieldValue = $input['default_value'];
                                    }

                                }

                                $this->fields_value[$input['name']][$language['id_lang']] = $fieldValue;
                            }

                        } else {

                            $fieldValue = $this->getFieldValue($obj, $input['name']);

                            if ($fieldValue === false && isset($input['default_value'])) {
                                $this->fields_value[$input['name']] = $input['default_value'];
                            } else

                            if ($fieldValue === false) {
                                $this->fields_value[$input['name']] = [];
                            } else {
                                $this->fields_value[$input['name']] = $fieldValue;
                            }

                        }

                    }

                }

            }

        }

        return $this->fields_value;
    }

    public function getWizardFieldsValue($obj, $key, $idLang = null) {

        if ($idLang) {
            $defaultValue = (isset($obj->id) && $obj->id && isset($obj->{$key}

                [$idLang])) ? $obj->{$key}

            [$idLang] : false;

        } else {
            $defaultValue = isset($obj->{$key}) ? $obj->{$key}

            : false;

        }

        return $this->context->_tools->getValue($key . ($idLang ? '_' . $idLang : ''), $defaultValue);
    }

    public function renderView() {

        $this->display == 'view';

        if (!$this->default_form_language) {
            $this->getLanguages();
        }

        $helper = new HelperView($this);
        $helper->view_extraCss = $this->extracss;
        $helper->view_extraJs = $this->extraJs;
        $this->setHelperDisplay($helper);
        $helper->tpl_vars = $this->getTemplateViewVars();

        if (!is_null($this->base_tpl_view)) {
            $helper->base_tpl = $this->base_tpl_view;
        }

        $view = $helper->generateView();

        return $view;
    }

    public function getTemplateViewVars() {

        return $this->tpl_view_vars;
    }

    public function renderForm() {

        if (!$this->default_form_language) {
            $this->getLanguages();
        }

        if ($this->context->_tools->getValue('submitFormAjax')) {
            $this->content .= $this->context->smarty->fetch('form_submit_ajax.tpl');
        }

        $extraFields = $this->context->_hook->exec('action' . $this->controller_name . 'FormModifier', [], null, true);

        if (is_array($extraFields) && count($extraFields)) {

            foreach ($extraFields as $plugin => $fields) {

                foreach ($fields as $field) {
                    $this->fields_form['input'][] = $field;
                }

            }

        }

        if ($this->fields_form && is_array($this->fields_form)) {

            if (!$this->multiple_fieldsets) {
                $this->fields_form = [['form' => $this->fields_form]];
            }

            // For add a fields via an override of $fields_form, use $fields_form_override

            if (is_array($this->fields_form_override) && !empty($this->fields_form_override)) {
                $this->fields_form[0]['form']['input'] = array_merge($this->fields_form[0]['form']['input'], $this->fields_form_override);
            }

            $fieldsValue = $this->getFieldsValue($this->object);

            if ($this->tabList == true) {
                $this->tpl_form_vars['controller'] = $this->context->_tools->getValue('controller');
                $this->tpl_form_vars['tabScript'] = $this->generateTabScript($this->context->_tools->getValue('controller'));
            }

            $has_editor = false;

            if ($this->composer_editor) {
                $has_editor = true;

            }

            $helper = new HelperForm($this);
            $this->setHelperDisplay($helper);
            $helper->controllerName = $this->controller_name;
            $helper->table = $this->table;
            $helper->header_title = $this->editObject;
            $helper->form_extraCss = $this->extracss;
            $helper->form_extraJs = $this->extraJs;
            $helper->js_def = $this->jsDef;
            $helper->fields_value = $fieldsValue;
            $helper->submit_action = $this->submit_action;
            $helper->form_included = $this->form_included;
            $helper->tpl_vars = $this->getTemplateFormVars();
            $helper->ajax_submit = $this->ajax_submit;
            $helper->tagHeader = $this->editObject;
            $helper->has_editor = $has_editor;
            $helper->show_cancel_button = (isset($this->show_form_cancel_button)) ? $this->show_form_cancel_button : ($this->display == 'add' || $this->display == 'edit');

            !is_null($this->base_tpl_form) ? $helper->base_tpl = $this->base_tpl_form : '';

            !is_null($this->base_folder_form) ? $helper->base_folder = $this->base_folder_form : '';

            $form = $helper->generateForm($this->fields_form);

            return $form;
        }

    }

    public function renderFormWizard() {

        if (!$this->default_form_language) {
            $this->getLanguages();
        }

        if ($this->context->_tools->getValue('submitFormAjax')) {
            $this->content .= $this->context->smarty->fetch('form_submit_ajax.tpl');
        }

        if ($this->fields_form && is_array($this->fields_form)) {

            $fieldsValue = $this->getWizardFieldsValues($this->object);

            $has_editor = false;

            if ($this->composer_editor) {
                $has_editor = true;
            }

            $helper = new HelperFormWizard($this);
            $this->setHelperDisplay($helper);
            $helper->controllerName = $this->controller_name;
            $helper->header_title = $this->editObject;
            $helper->className = $this->className;
            $helper->form_extraCss = $this->extracss;
            $helper->form_extraJs = $this->extraJs;
            $helper->js_def = $this->jsDef;
            $helper->fields_value = $fieldsValue;
            $helper->submit_action = $this->submit_action;
            $helper->tpl_vars = $this->getTemplateFormVars();
            $helper->tagHeader = $this->editObject;
            $helper->has_editor = $has_editor;
            $helper->js_def = $this->js_def;
            $helper->show_cancel_button = (isset($this->show_form_cancel_button)) ? $this->show_form_cancel_button : ($this->display == 'add' || $this->display == 'edit');

            !is_null($this->base_tpl_form) ? $helper->base_tpl = $this->base_tpl_form : '';

            $form = $helper->generateForm($this->fields_form);

            return $form;
        }

    }

    public function getLanguages() {

        $cookie = $this->context->cookie;
        $this->allow_employee_form_lang = (int) $this->context->phenyxConfig->get('EPH_BO_ALLOW_EMPLOYEE_FORM_LANG');

        if ($this->allow_employee_form_lang && !$cookie->employee_form_lang) {
            $cookie->employee_form_lang = (int) $this->default_language;
        }

        $langExists = false;
        $this->_languages = Language::getLanguages(false);

        foreach ($this->_languages as $lang) {

            if (isset($cookie->employee_form_lang) && $cookie->employee_form_lang == $lang['id_lang']) {
                $langExists = true;
            }

        }

        $this->default_form_language = $langExists ? (int) $cookie->employee_form_lang : (int) $this->default_language;

        foreach ($this->_languages as $k => $language) {
            $this->_languages[$k]['is_default'] = (int) ($language['id_lang'] == $this->default_form_language);
        }

        return $this->_languages;
    }

    public function setHelperDisplay(Helper $helper) {

        // tocheck

        if ($this->object && $this->object->id) {
            $helper->id = $this->object->id;
        }

        // @todo : move that in Helper
        $helper->title = '';
        $helper->toolbar_btn = $this->toolbar_btn;
        $helper->show_toolbar = $this->show_toolbar;
        $helper->toolbar_scroll = $this->toolbar_scroll;
        $helper->override_folder = $this->tpl_folder;
        $helper->currentIndex = static::$currentIndex;
        $helper->className = $this->className;
        $helper->table = $this->table;
        $helper->name_controller = $this->context->_tools->getValue('controller');
        $helper->identifier = $this->identifier;
        $helper->token = $this->token;
        $helper->languages = $this->_languages;
        $helper->default_form_language = $this->default_form_language;
        $helper->allow_employee_form_lang = $this->allow_employee_form_lang;
        $helper->controller_name = $this->controller_name;
        $helper->bootstrap = $this->bootstrap;

        $this->helper = $helper;
    }

    public function getTemplateFormVars() {

        return $this->tpl_form_vars;
    }

    public function ajaxProcessGetAccountTypeRequest() {

        $type = $this->context->_tools->getValue('type');

        switch ($type) {
        case 'Banks':
            die($this->context->_tools->jsonEncode(StdAccount::getBankStdAccount()));
            break;
        case 'Profits':
            die($this->context->_tools->jsonEncode(StdAccount::getProfitsStdAccount()));
            break;
        case 'Expenses':
            die($this->context->_tools->jsonEncode(StdAccount::getExpensesStdAccount()));
            break;
        case 'VAT':
            die($this->context->_tools->jsonEncode(StdAccount::getVATStdAccount()));
            break;
        case 'Supplier':
            die($this->context->_tools->jsonEncode(StdAccount::getAccountByidType(4)));

            break;
        case 'Customer':
            die($this->context->_tools->jsonEncode(StdAccount::getAccountByidType(5)));
            break;
        case 'Others':
            die($this->context->_tools->jsonEncode(StdAccount::getAccountByidType(6)));
            break;
        case 'Capital':
            die($this->context->_tools->jsonEncode(StdAccount::getAccountByidType(1)));
            break;
        }

    }

    public function getFieldValue($obj, $key, $idLang = null) {

        if ($idLang) {
            $defaultValue = (isset($obj->id) && $obj->id && isset($obj->{$key}

                [$idLang])) ? $obj->{$key}

            [$idLang] : false;

        } else {
            $defaultValue = isset($obj->{$key}) ? $obj->{$key}

            : false;

        }

        return $this->context->_tools->getValue($key . ($idLang ? '_' . $idLang : ''), $defaultValue);
    }

    public function getExportFields() {

        if (method_exists($this, 'getFields')) {

            $fields = [];
            $gridFields = $this->{'getFields'}

            ();

            if (is_array($gridFields) && count($gridFields)) {

                foreach ($gridFields as $grifField) {

                    if (isset($grifField['hidden']) && $grifField['hidden'] && isset($grifField['hiddenable']) && $grifField['hiddenable'] == 'no') {
                        continue;
                    }

                    if (isset($grifField['dataIndx'])) {
                        $fields[$grifField['dataIndx']] = $grifField['title'];
                    }

                }

            }

            return $fields;

        }

        return false;

    }

    public function getUpdatableFields() {

        $class = new $this->className();
        return $class->getUpdatableFields();

    }

    public function getUpdatableFieldType($dataIndx) {

        $gridFields = $this->getFields();

        if (is_array($gridFields) && count($gridFields)) {

            foreach ($gridFields as $grifField) {

                if ($grifField['dataIndx'] == $dataIndx) {
                    return $grifField;
                }

            }

        }

    }

    public function removeRequestFields($requests) {

        $objects = [];
        $gridFields = $this->getFields();

        if (is_array($gridFields)) {
            $fields = [];

            foreach ($gridFields as $grifField) {
                $fields[] = $grifField['dataIndx'];
            }

            foreach ($requests as $key => $object) {

                foreach ($object as $field => $value) {

                    if (in_array($field, $fields)) {
                        $objects[$key][$field] = $value;
                    }

                }

            }

        }

        return $objects;

    }

    public function getExportFormatFields() {

        if (method_exists($this, 'getFields')) {

            $fields = [];
            $gridFields = $this->{'getFields'}

            ();

            if (is_array($gridFields) && count($gridFields)) {

                foreach ($gridFields as $grifField) {

                    if (isset($grifField['hidden']) && $grifField['hidden'] && isset($grifField['hiddenable']) && $grifField['hiddenable'] == 'no') {
                        continue;
                    }

                    if (isset($grifField['dataIndx'])) {

                        if (isset($grifField['exWidth'])) {
                            $fields[$grifField['dataIndx']]['width'] = $grifField['exWidth'];
                        }

                        if (isset($grifField['halign'])) {
                            $fields[$grifField['dataIndx']]['halign'] = $grifField['halign'];
                        } else {
                            $fields[$grifField['dataIndx']]['halign'] = 'Alignment::HORIZONTAL_LEFT';
                        }

                        if (isset($grifField['numberFormat'])) {
                            $fields[$grifField['dataIndx']]['numberFormat'] = $grifField['numberFormat'];
                        }

                        if (isset($grifField['dataType']) && $grifField['dataType'] == 'date') {
                            $fields[$grifField['dataIndx']]['date'] = true;

                        }

                        if (isset($grifField['exportType']) && $grifField['exportType'] == 'Image') {
                            $fields[$grifField['dataIndx']]['image'] = true;

                        }

                    }

                }

            }

            return $fields;

        }

        return false;

    }

    protected function copyFromPost(&$object, $table, $has_keyword = false) {

        /* Classical fields */

        foreach ($_POST as $key => $value) {

            if (property_exists($object, $key) && $key != 'id_' . $table) {
                /* Do not take care of password field if empty */

                if ($key == 'passwd' && $this->context->_tools->getValue('id_' . $table) && empty($value)) {
                    continue;
                }

                /* Automatically hash password */

                if ($key == 'passwd' && !empty($value)) {

                    if (property_exists($object, 'password')) {
                        $object->password = $value;
                    }

                    $value = $this->context->_tools->hash($value);
                }

                if ($key === 'email') {

                    if (mb_detect_encoding($value, 'UTF-8', true) && mb_strpos($value, '@') > -1) {
                        // Convert to IDN
                        list($local, $domain) = explode('@', $value, 2);
                        $domain = $this->context->_tools->utf8ToIdn($domain);
                        $value = "$local@$domain";
                    }

                }

                $object->{$key}

                = $value;
            }

        }

        /* Multilingual fields */
        $classVars = get_class_vars(get_class($object));
        $fields = [];

        if (isset($classVars['definition']['fields'])) {
            $fields = $classVars['definition']['fields'];
        }

        foreach ($fields as $field => $params) {

            if (array_key_exists('lang', $params) && $params['lang']) {

                foreach (Language::getIDs(false) as $idLang) {
                    $referent = '';

                    if ($this->context->_tools->isSubmit($field . '_' . (int) $idLang)) {

                        if (!isset($object->{$field}) || !is_array($object->{$field})) {
                            $object->{$field}

                            = [];
                        }

                        if ($idLang == $this->context->language->id) {
                            $referent = $this->context->_tools->getValue($field . '_' . (int) $idLang);
                        }

                        $value = !empty($this->context->_tools->getValue($field . '_' . (int) $idLang)) ? $this->context->_tools->getValue($field . '_' . (int) $idLang) : $referent;
                        $object->{$field}

                        [(int) $idLang] = $value;
                    } else {
                        $object->{$field}

                        [(int) $idLang] = $referent;
                    }

                }

            }

        }

        if ($has_keyword) {

            foreach (Language::getIDs(false) as $idLang) {

                if (isset($_POST['meta_keywords_' . $idLang])) {
                    $_POST['meta_keywords_' . $idLang] = $this->_cleanMetaKeywords(mb_strtolower($_POST['meta_keywords_' . $idLang]));
                    $object->keywords[$idLang] = $_POST['meta_keywords_' . $idLang];
                }

            }

        }

    }

    protected function _cleanMetaKeywords($keywords) {

        if (!empty($keywords) && $keywords != '') {
            $out = [];
            $words = explode(',', $keywords);

            foreach ($words as $wordItem) {
                $wordItem = trim($wordItem);

                if (!empty($wordItem) && $wordItem != '') {
                    $out[] = $wordItem;
                }

            }

            return ((count($out) > 0) ? implode(',', $out) : '');
        } else {
            return '';
        }

    }

    public function getRequest($identifier = null) {

        $request = $this->context->_hook->exec('action' . $this->controller_name . 'getRequestModifier', ['paramRequest' => $this->paramRequest], null, true);

        if (is_array($request)) {

            foreach ($request as $plugin => $result) {

                if (is_array($result)) {
                    $this->paramRequest = $result;
                }

            }

        }

        return null;
    }

    protected function ajaxDie($value = null, $controller = null, $method = null) {

        if ($controller === null) {
            $controller = get_class($this);
        }

        if ($method === null) {
            $bt = debug_backtrace();
            $method = $bt[1]['function'];
        }

        $this->context->_hook->exec('actionBeforeAjaxDie', ['controller' => $controller, 'method' => $method, 'value' => $value]);
        $this->context->_hook->exec('actionBeforeAjaxDie' . $controller . $method, ['value' => $value]);

        die($value);
    }

    private function getMemoryColor($n) {

        $n /= 1048576;

        if ($n > 3) {
            return '<span style="color:red">' . sprintf('%0.2f', $n) . '</span>';
        } else

        if ($n > 1) {
            return '<span style="color:#EF8B00">' . sprintf('%0.2f', $n) . '</span>';
        } else

        if (round($n, 2) > 0) {
            return '<span style="color:green">' . sprintf('%0.2f', $n) . '</span>';
        }

        return '<span style="color:green">-</span>';
    }

    private function getPeakMemoryColor($n) {

        $n /= 1048576;

        if ($n > 16) {
            return '<span style="color:red">' . sprintf('%0.1f', $n) . '</span>';
        }

        if ($n > 12) {
            return '<span style="color:#EF8B00">' . sprintf('%0.1f', $n) . '</span>';
        }

        return '<span style="color:green">' . sprintf('%0.1f', $n) . '</span>';
    }

    private function displaySQLQueries($n) {

        if ($n > 150) {
            return '<span style="color:red">' . $n . ' ' . $this->la('queries') . '</span>';
        }

        if ($n > 100) {
            return '<span style="color:#EF8B00">' . $n . ' ' . $this->la('queries') . '</span>';
        }

        return '<span style="color:green">' . $n . ' ' . ($n == 1 ? $this->la('query') : $this->la('queries')) . '</span>';
    }

    private function displayRowsBrowsed($n) {

        if ($n > 400) {
            return '<span style="color:red">' . $n . ' rows browsed</span>';
        }

        if ($n > 100) {
            return '<span style="color:#EF8B00">' . $n . '  rows browsed</span>';
        }

        return '<span style="color:green">' . $n . ' row' . ($n == 1 ? '' : 's') . ' browsed</span>';
    }

    private function getPhpVersionColor($version) {

        if (version_compare($version, '5.3') < 0) {
            return '<span style="color:red">' . $version . $this->la('(Upgrade strongly recommended)') . '</span>';
        } else

        if (version_compare($version, '5.4') < 0) {
            return '<span style="color:#EF8B00">' . $version . $this->la('(Consider upgrading)') . ' </span>';
        }

        return '<span style="color:green">' . $version . $this->la('(OK)') . '</span>';
    }

    private function getMySQLVersionColor($version) {

        if (version_compare($version, '5.5') < 0) {
            return '<span style="color:red">' . $version . $this->la('(Upgrade strongly recommended)') . '</span>';
        } else

        if (version_compare($version, '5.6') < 0) {
            return '<span style="color:#EF8B00">' . $version . $this->la('(Consider upgrading)') . ' </span>';
        }

        return '<span style="color:green">' . $version . $this->la('(OK)') . '</span>';
    }

    private function getLoadTimeColor($n, $kikoo = false) {

        if ($n > 1.6) {
            return '<span style="color:red">' . round($n * 1000) . '</span>' . ($kikoo ? $this->la('You‘d better run your shop on a toaster') : '');
        } else

        if ($n > 0.8) {
            return '<span style="color:#EF8B00">' . round($n * 1000) . '</span>' . ($kikoo ? $this->la('OK... for a shared hosting') : '');
        } else

        if ($n > 0) {
            return '<span style="color:green">' . round($n * 1000) . '</span>' . ($kikoo ? $this->la('Unicorn powered webserver!') : '');
        }

        return '<span style="color:green">-</span>' . ($kikoo ? $this->la('Faster than light') : '');
    }

    private function getTotalQueriyingTimeColor($n) {

        if ($n >= 100) {
            return '<span style="color:red">' . $n . '</span>';
        } else

        if ($n >= 50) {
            return '<span style="color:#EF8B00">' . $n . '</span>';
        }

        return '<span style="color:green">' . $n . '</span>';
    }

    private function getNbQueriesColor($n) {

        if ($n >= 100) {
            return '<span style="color:red">' . $n . '</span>';
        } else

        if ($n >= 50) {
            return '<span style="color:#EF8B00">' . $n . '</span>';
        }

        return '<span style="color:green">' . $n . '</span>';
    }

    private function getTimeColor($n) {

        if ($n > 4) {
            return 'style="color:red"';
        }

        if ($n > 2) {
            return 'style="color:#EF8B00"';
        }

        return 'style="color:green"';
    }

    private function getQueryColor($n) {

        if ($n > 5) {
            return 'style="color:red"';
        }

        if ($n > 2) {
            return 'style="color:#EF8B00"';
        }

        return 'style="color:green"';
    }

    private function getTableColor($n) {

        if ($n > 30) {
            return 'style="color:red"';
        }

        if ($n > 20) {
            return 'style="color:#EF8B00"';
        }

        return 'style="color:green"';
    }

    private function getObjectModelColor($n) {

        if ($n > 50) {
            return 'style="color:red"';
        }

        if ($n > 10) {
            return 'style="color:#EF8B00"';
        }

        return 'style="color:green"';
    }

    protected function stamp($block) {

        return ['block' => $block, 'memory_usage' => memory_get_usage(), 'peak_memory_usage' => memory_get_peak_usage(), 'time' => microtime(true)];
    }

    private function getVarSize($var) {

        $start_memory = memory_get_usage();
        try {
            $tmp = json_decode(json_encode($var));
        } catch (Exception $e) {
            $tmp = $this->getVarData($var);
        }

        $size = memory_get_usage() - $start_memory;
        return $size;
    }

    private function getVarData($var) {

        if (is_object($var)) {
            return $var;
        }

        return (string) $var;
    }

    protected function processProfilingData() {

        global $start_time;

        // Including a lot of files uses memory

        foreach (get_included_files() as $file) {
            $this->total_filesize += filesize($file);

        }

        // Sum querying time

        foreach (Db::getInstance()->queries as $data) {
            $this->total_query_time += $data['time'];
        }

        foreach ($GLOBALS as $key => $value) {

            if ($key != 'GLOBALS') {
                $this->total_global_var_size += ($size = $this->getVarSize($value));

                if ($size > 1024) {
                    $this->global_var_size[$key] = round($size / 1024);
                }

            }

        }

        arsort($this->global_var_size);

        $cache = CacheApi::retrieveAll();
        $this->total_cache_size = $this->getVarSize($cache);

        // Retrieve plugin perfs

        $queries = Db::getInstance()->queries;
        uasort($queries, 'phenyxshop_querytime_sort');

        foreach ($queries as $data) {
            $query_row = [
                'time'     => $data['time'],
                'query'    => $data['query'],
                'location' => str_replace('\\', '/', substr($data['stack'][0]['file'], strlen(_EPH_ROOT_DIR_))) . ':' . $data['stack'][0]['line'],
                'filesort' => false,
                'rows'     => 1,
                'group_by' => false,
                'stack'    => [],
            ];

            if (preg_match('/^\s*select\s+/i', $data['query'])) {
                $explain = Db::getInstance()->executeS('explain ' . $data['query']);

                if (isset($explain[0]['Extra']) && stristr($explain[0]['Extra'], 'filesort')) {
                    $query_row['filesort'] = true;
                }

                foreach ($explain as $row) {
                    $query_row['rows'] *= $row['rows'];
                }

                if (stristr($data['query'], 'group by') && !preg_match('/(avg|count|min|max|group_concat|sum)\s*\(/i', $data['query'])) {
                    $query_row['group_by'] = true;
                }

            }

            array_shift($data['stack']);

            foreach ($data['stack'] as $call) {
                $query_row['stack'][] = str_replace('\\', '/', substr($call['file'], strlen(_EPH_ROOT_DIR_))) . ':' . $call['line'];
            }

            $this->array_queries[] = $query_row;
        }

        uasort(PhenyxObjectModel::$debug_list, function ($b, $a) {

            if (count($a) < count($b)) {
                return 1;
            }

            return -1;
        });
        arsort(Db::getInstance()->tables);
        arsort(Db::getInstance()->uniqQueries);
    }

    protected function displayProfilingLinks() {

        $this->content_ajax .= '
        <div id="profiling_link" class="subTabs col-lg-12">
            <ul>
                <li><a href="#stopwatch">' . $this->la('Stopwatch SQL') . '</a></li>
                <li><a href="#sql_doubles">' . $this->la('Doubles') . '</a></li>
                <li><a href="#stress_tables">' . $this->la('Tables stress') . '</a></li>
                ' . (isset(PhenyxObjectModel::$debug_list) ? '
                <li><a href="#objectModels">' . $this->la('ObjectModel instances') . '</a></li>' : '') . '
                <li><a href="#hooksPerf">' . $this->la('Hooks Performance') . '</a></li>
                <li><a href="#pluginsPerf">' . $this->la('Plugins Performance') . '</a></li>
                <li><a href="#includedFiles">' . $this->la('Included Files') . '</a></li>
            </ul>
        <div id="tabs-profilling-content" class="tabs-controller-content">';
    }

    protected function displayProfilingStyle() {

        $this->content_ajax .= '

        <script type="text/javascript" src="https://cdn.rawgit.com/drvic10k/bootstrap-sortable/1.11.2/Scripts/bootstrap-sortable.js"></script>';
    }

    protected function displayProfilingSummary() {

        global $start_time;

        $this->content_ajax .= '
        <div class="col-4">
            <table class="table table-condensed">
                <tr><td>' . $this->la('Load time') . '</td><td>' . $this->getLoadTimeColor(round(microtime(true) - TIME_START, 3) - $start_time, true) . '</td></tr>
                <tr><td>' . $this->la('Querying time') . '</td><td>' . $this->getTotalQueriyingTimeColor(round(1000 * $this->total_query_time)) . ' ms</span>
                <tr><td>' . $this->la('Queries') . '</td><td>' . $this->getNbQueriesColor(count($this->array_queries)) . '</td></tr>
                <tr><td>' . $this->la('Memory peak usage') . '</td><td>' . $this->getPeakMemoryColor($this->profiler[count($this->profiler) - 1]['peak_memory_usage']) . ' Mb</td></tr>
                <tr><td>' . $this->la('Included files') . '</td><td>' . count(get_included_files()) . ' files - ' . $this->getMemoryColor($this->total_filesize) . ' Mb</td></tr>
                <tr><td>' . $this->la('Ephenyx cache') . '</td><td>' . $this->getMemoryColor($this->total_cache_size) . ' Mb</td></tr>
                <tr><td><a href="javascript:void(0);" onclick="$(\'.global_vars_detail\').toggle();">Global vars</a></td><td>' . $this->getMemoryColor($this->total_global_var_size) . ' Mb</td></tr>';

        foreach ($this->global_var_size as $global => $size) {
            $this->content_ajax .= '<tr class="global_vars_detail" style="display:none"><td>- global $' . $global . '</td><td>' . $size . 'k</td></tr>';
        }

        $this->content_ajax .= '
            </table>
        </div>';
    }

    protected function displayProfilingConfiguration() {

        $compileType = $this->context->phenyxConfig->get('EPH_PAGE_CACHE_TYPE');
        $this->content_ajax .= '
        <div class="col-4">
            <table class="table table-condensed">
                <tr><td>' . $this->la('Ephenyx version') . '</td><td>' . _EPH_VERSION_ . '</td></tr>
                <tr><td>' . $this->la('Ephenyx (emulated) version') . '</td><td>' . _EPH_VERSION_ . '</td></tr>
                <tr><td>' . $this->la('PHP version') . '</td><td>' . $this->getPhpVersionColor(phpversion()) . '</td></tr>
                <tr><td>' . $this->la('MySQL version') . '</td><td>' . $this->getMySQLVersionColor(Db::getInstance()->getVersion()) . '</td></tr>
                <tr><td>' . $this->la('Memory limit') . '</td><td>' . ini_get('memory_limit') . '</td></tr>
                <tr><td>' . $this->la('Max execution time') . '</td><td>' . ini_get('max_execution_time') . 's</td></tr>
                <tr><td>' . $this->la('Smarty cache') . '</td><td><span style="color:' . ($this->context->phenyxConfig->get('EPH_PAGE_CACHE_ENABLED') ? 'green">enabled' : 'red">disabled') . '</td></tr>
                <tr><td>' . $this->la('Smarty Compilation') . '</td><td><span style="color:' . (in_array($compileType, ['CacheApcu', 'AwsRedis']) ? 'green">' . $this->la('Redis') : '#EF8B00">' . $compileType) . '</td></tr>
            </table>
        </div>';
    }

    protected function displayProfilingRun() {

        global $start_time;

        $this->content_ajax .= '
        <div class="col-4">
            <table class="table table-condensed">
                <tr><th>&nbsp;</th><th>' . $this->la('Time') . '</th><th>' . $this->la('Cumulated Time') . '</th><th>' . $this->la('Memory Usage') . '</th><th>' . $this->la('Memory Peak Usage') . '</th></tr>';
        $last = ['time' => $start_time, 'memory_usage' => 0];

        foreach ($this->profiler as $row) {

            if ($row['block'] == 'checkAccess' && $row['time'] == $last['time']) {
                continue;
            }

            $this->content_ajax .= '<tr>
                <td>' . $row['block'] . '</td>
                <td>' . $this->getLoadTimeColor($row['time'] - $last['time']) . ' ms</td>
                <td>' . $this->getLoadTimeColor($row['time'] - $start_time) . ' ms</td>
                <td>' . $this->getMemoryColor($row['memory_usage'] - $last['memory_usage']) . ' Mb</td>
                <td>' . $this->getMemoryColor($row['peak_memory_usage']) . ' Mb</td>
            </tr>';
            $last = $row;
        }

        $this->content_ajax .= '
            </table>
        </div>';
    }

    protected function displayProfilingHooks() {

        $perfs = $this->_session->get('HookPerformance');

        $count_hooks = count($perfs);
        $peformances = [];
        $total_hook_time = 0;
        $total_memory_time = 0;

        foreach ($perfs as $hook => $value) {

            $time = $value['time'];
            $total_hook_time = $total_hook_time + $value['time'];
            $memory = $value['memory'];
            $total_memory_time = $total_memory_time + $value['memory'];
            $peformances[$hook] = [
                'time'   => $time,
                'memory' => $memory,
            ];

        }

        $this->content_ajax .= '
        <div id="hooksPerf"><div class="col-lg-12">
        <h2><a name="includedFiles">' . $this->la('Hooks Performance') . '</a></h2>
            <table class="table table-condensed">
                <tr>
                    <th>' . $this->la('Hook') . '</th>
                    <th>' . $this->la('Time') . '</th>
                    <th>' . $this->la('Memory Usage') . '</th>
                </tr>';

        foreach ($peformances as $hook => $perf) {
            $this->content_ajax .= '
                <tr>
                    <td>
                        <a href="javascript:void(0);" onclick="$(\'.' . $hook . '_plugins_details\').toggle();">' . $hook . '</a>
                    </td>
                    <td>
                        ' . $this->getLoadTimeColor($perf['time']) . ' ms
                    </td>
                    <td>
                        ' . $this->getMemoryColor($perf['memory']) . ' Mb
                    </td>
                </tr>';

        }

        $this->content_ajax .= '  <tr>
                    <th><b>' . ($count_hooks == 1 ? '1 hook' : (int) $count_hooks . ' hooks') . '</b></th>
                    <th>' . $this->getLoadTimeColor($total_hook_time) . ' ms</th>
                    <th>' . $this->getMemoryColor($total_memory_time) . ' Mb</th>
                </tr>
            </table>
        </div></div>';
    }

    protected function displayProfilingPlugins() {

        $perfs = $this->_session->get('pluginPerformance');

        $count_plugins = count($perfs);
        $peformances = [];
        $total_plugin_time = 0;
        $total_plugins_memory = 0;

        foreach ($perfs as $plugin => $value) {

            $time = $value['time'];
            $total_plugin_time = $total_plugin_time + $value['time'];

            $memory = $value['memory'];
            $total_plugins_memory = $total_plugins_memory + $value['memory'];

            $peformances[$plugin] = [
                'time'   => $time,
                'memory' => $memory,
            ];

        }

        $this->content_ajax .= '
        <div id="pluginsPerf"><div class="col-lg-12">
        <h2><a name="pluginsPerf">' . $this->la('Plugins Performance') . '</a></h2>
            <table class="table table-condensed">
                <tr>
                    <th>Plugin</th>

                    <th>Time</th>
                    <th>Memory Usage</th>
                </tr>';

        foreach ($peformances as $plugin => $perf) {
            $this->content_ajax .= '
                <tr>
                    <td>
                        <a href="javascript:void(0);" onclick="$(\'.' . $plugin . '_hooks_details\').toggle();">' . $plugin . '</a>
                    </td>
                    <td>
                        ' . $this->getLoadTimeColor($perf['time']) . ' ms
                    </td>
                    <td>
                        ' . $this->getMemoryColor($perf['memory']) . ' Mb
                    </td>
                </tr>';

        }

        $this->content_ajax .= '  <tr>
                    <th><b>' . ($count_plugins == 1 ? '1 plugin' : (int) $count_plugins . ' plugins') . '</b></th>
                    <th>' . $this->getLoadTimeColor($total_plugin_time) . ' ms</th>
                    <th>' . $this->getMemoryColor($total_plugins_memory) . ' Mb</th>
                </tr>
            </table>
        </div></div>';
    }

    protected function displayProfilingStopwatch() {

        $this->content_ajax .= '
        <div id="stopwatch">
            <h2><a name="stopwatch">' . $this->la('Stopwatch SQL') . ' - ' . count($this->array_queries) . ' queries</a></h2>
            <table class="table table-condensed table-bordered sortable col-lg-12">
                <thead>
                    <tr>
                        <th style="width:50%">Query</th>

                        <th style="width:10%">' . $this->la('Time (ms)') . '</th>
                        <th style="width:10%">' . $this->la('Rows') . '</th>
                        <th style="width:5%">' . $this->la('Filesort') . '</th>
                        <th style="width:5%">' . $this->la('Group By') . '</th>
                        <th style="width:20%">' . $this->la('Time (ms)') . '</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($this->array_queries as $data) {
            $callstack = implode('<br>', $data['stack']);
            $callstack_md5 = md5($callstack);

            $this->content_ajax .= '
                <tr>
                    <td class="pre" style="width:50%; display:table-cell">' . preg_replace("/(^[\s]*)/m", "", htmlspecialchars(substr($data['query'], 0, 256), ENT_NOQUOTES, 'utf-8', false)) . '</td>
                    <td style="width:10%"><span ' . $this->getTimeColor($data['time'] * 1000) . '>' . (round($data['time'] * 1000, 1) < 0.1 ? '< 1' : round($data['time'] * 1000, 1)) . '</span></td>
                    <td>' . (int) $data['rows'] . '</td>
                    <td>' . ($data['filesort'] ? '<span style="color:red">' . $this->la('Yes') . '</span>' : '') . '</td>
                    <td>' . ($data['group_by'] ? '<span style="color:red">' . $this->la('Yes') . '</span>' : '') . '</td>
                    <td>
                        <a href="javascript:void(0);" onclick="$(\'#callstack_' . $callstack_md5 . '\').toggle();">' . $data['location'] . '</a>
                        <div id="callstack_' . $callstack_md5 . '" style="display:none">' . implode('<br>', $data['stack']) . '</div>
                    </td>
                </tr>';
        }

        $this->content_ajax .= '</table>


        </div>';
    }

    protected function displayProfilingDoubles() {

        $this->content_ajax .= '<div id="sql_doubles">
        <h2><a name="doubles">' . $this->la('Doubles') . '</a></h2>
            <table class="table table-condensed">';

        foreach (Db::getInstance()->uniqQueries as $q => $nb) {

            if ($nb > 1) {
                $this->content_ajax .= '<tr><td><span ' . $this->getQueryColor($nb) . '>' . $nb . '</span></td><td class="pre"><pre>' . $q . '</pre></td></tr>';
            }

        }

        $this->content_ajax .= '</table>
        </div>';
    }

    protected function displayProfilingTableStress() {

        $this->content_ajax .= '<div id="stress_tables">
        <h2><a name="tables">' . $this->la('Tables stress') . '</a></h2>
        <table class="table table-condensed">';

        foreach (Db::getInstance()->tables as $table => $nb) {
            $this->content_ajax .= '<tr><td><span ' . $this->getTableColor($nb) . '>' . $nb . '</span> ' . $table . '</td></tr>';
        }

        $this->content_ajax .= '</table>
        </div>';

    }

    protected function displayProfilingObjectModel() {

        $this->content_ajax .= '
        <div id="objectModels">
            <h2><a name="objectModels">' . $this->la('ObjectModel instances') . '</a></h2>
            <table class="table table-condensed">
                <tr><th>' . $this->la('Name') . '</th><th>' . $this->la('Instances') . '</th><th>' . $this->la('Source') . '</th></tr>';

        foreach (PhenyxObjectModel::$debug_list as $class => $info) {
            $this->content_ajax .= '<tr>
                    <td>' . $class . '</td>
                    <td><span ' . $this->getObjectModelColor(count($info)) . '>' . count($info) . '</span></td>
                    <td>';

            foreach ($info as $trace) {
                $this->content_ajax .= str_replace([_EPH_ROOT_DIR_, '\\'], ['', '/'], $trace['file']) . ' [' . $trace['line'] . ']<br />';
            }

            $this->content_ajax .= '  </td>
                </tr>';
        }

        $this->content_ajax .= '</table>
        </div>';
    }

    protected function displayProfilingFiles() {

        $i = 0;

        $this->content_ajax .= '<div id="includedFiles">
        <h2><a name="includedFiles">' . $this->la('Included Files') . '</a></h2>
        <table class="table table-condensed">
            <tr><th>#</th><th>' . $this->la('File name') . '</th></tr>';

        foreach (get_included_files() as $file) {
            $file = str_replace('\\', '/', str_replace(_EPH_ROOT_DIR_, '', $file));

            if (strpos($file, '/tools/profiling/') === 0) {
                continue;
            }

            $this->content_ajax .= '<tr><td>' . (++$i) . '</td><td>' . $file . '</td></tr>';
        }

        $this->content_ajax .= '</table>
        </div>';
    }

    public function displayProfiling() {

        $this->profiler[] = $this->stamp('display');
        // Process all profiling data
        $this->processProfilingData();

        // Add some specific style for profiling information
        //$this->displayProfilingStyle();

        $this->content_ajax .= '<div id="phenyxshop_profiling" class="bootstrap">';

        $this->content_ajax .= $this->la('Summary') . '<div class="row">';
        $this->displayProfilingSummary();
        $this->displayProfilingConfiguration();
        $this->displayProfilingRun();
        $this->content_ajax .= '</div><div class="row">';

        $this->displayProfilingLinks();

        $this->displayProfilingStopwatch();
        $this->displayProfilingDoubles();
        $this->displayProfilingTableStress();

        if (isset(PhenyxObjectModel::$debug_list)) {
            $this->displayProfilingObjectModel();
        }

        $this->displayProfilingHooks();
        $this->displayProfilingPlugins();
        $this->displayProfilingFiles();

        $this->content_ajax .= '</div>';

        return $this->content_ajax;

    }

    public function loadCacheAccelerator($overrideCache = '') {

        if (!($this->context->cache_enable)) {
            return false;
        }

        if (is_object($this->context->cache_api)) {
            return $this->context->cache_api;
        } else

        if (is_null($this->context->cache_api)) {
            $cache_api = false;
        }

        if (class_exists('CacheApi')) {
            // What accelerator we are going to try.
            $cache_class_name = !empty($overrideCache) ? $overrideCache : CacheApi::APIS_DEFAULT;

            if (class_exists($cache_class_name)) {

                $cache_api = new $cache_class_name($this->context);

                // There are rules you know...

                if (!($cache_api instanceof CacheApiInterface) || !($cache_api instanceof CacheApi)) {
                    return false;
                }

                if (!$cache_api->isSupported()) {
                    return false;
                }

                // Connect up to the accelerator.

                if ($cache_api->connect() === false) {
                    return false;
                }

                return $cache_api;
            }

            return false;
        }

        return false;
    }

    public function cache_put_data($key, $value, $ttl = 120) {

        if (empty($this->context->cache_enable)) {
            return;
        }

        if (empty($this->context->cache_api)) {
            $this->context->cache_api = $this->loadCacheAccelerator();
            return;
        }

        $value = $value === null ? null : $this->context->_tools->jsonEncode($value);
        $this->context->cache_api->putData($key, $value, $ttl);

    }

    public function cache_get_data($key, $ttl = 120) {

        if (empty($this->context->cache_enable) || empty($this->context->cache_api)) {
            return null;
        }

        $value = $this->context->cache_api->getData($key, $ttl);

        return empty($value) ? null : $this->context->_tools->jsonDecode($value, true);
    }

}
