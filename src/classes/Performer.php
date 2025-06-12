<?php

/**
 * Class Performer
 *
 * @since 1.9.1.0
 */
class Performer {

    const FC_FRONT = 1;
    const FC_ADMIN = 2;
    const FC_PLUGIN = 3;

    public static $instance = null;

    public $default_routes = [

        'cms_rule' => [
            'controller' => 'cms',
            'rule'       => '{categories:/}{rewrite}',
            'keywords'   => [
                'id'            => [
                    'regexp' => '[0-9]+',
                    'alias'  => 'id_cms',
                ],
                'rewrite'       => [
                    'regexp' => '[_a-zA-Z0-9\pL\pS-]*',
                    'param'  => 'cms_rewrite',
                ],
                'categories'    => [
                    'regexp' => '[/_a-zA-Z0-9-\pL]*',
                ],

                'meta_keywords' => [
                    'regexp' => '[_a-zA-Z0-9-\pL]*',
                ],
                'meta_title'    => [
                    'regexp' => '[_a-zA-Z0-9-\pL]*',
                ],
            ],
        ],
        'pfg_rule' => [
            'controller' => 'pfg',
            'rule'       => 'formulaire/{id}-{rewrite}',
            'keywords'   => [
                'id'      => [
                    'regexp' => '[0-9]+',
                    'alias'  => 'id_pfg',
                ],
                'rewrite' => [
                    'regexp' => '[_a-zA-Z0-9\pL\pS-]*',
                    'param'  => 'pfg_rewrite',
                ],
            ],
        ],

        'plugin'   => [
            'controller' => null,
            'rule'       => 'plugin/{plugin}{/:controller}',
            'keywords'   => [
                'plugin'     => [
                    'regexp' => '[_a-zA-Z0-9_-]+',
                    'param'  => 'plugin',
                ],
                'controller' => [
                    'regexp' => '[_a-zA-Z0-9_-]+',
                    'param'  => 'controller',
                ],
            ],
            'params'     => [
                'fc' => 'plugin',
            ],
        ],

    ];

    protected $use_routes = false;

    protected $multilang_activated = false;

    public $routes = [];

    public $canonical_routes = [
        'cms_rule' => 'EPH_ROUTE_cms_rule',
        'plugin'   => 'EPH_ROUTE_plugin',
        'pfg_rule' => 'EPH_ROUTE_pfg_rule',
    ];

    public $cache_routes = [];

    public $plugins = [];

    public $front_controllers = [];

    public $context;

    protected $controller;

    protected $request_uri;

    protected $empty_route;

    protected $default_controller;

    protected $use_default_controller = false;

    protected $controller_not_found = 'pagenotfound';

    protected $front_controller = self::FC_FRONT;

    public function __construct() {

        if (!defined('TIME_START')) {
            define('TIME_START', microtime(true));
        }

        $this->context = Context::getContext();

        if (!isset($this->context->phenyxConfig)) {
            $this->context->phenyxConfig = Configuration::getInstance();
        }

        if (!isset($this->context->company)) {
            $this->context->company = Company::initialize();
        }

        if (!isset($this->context->_tools)) {
            $this->context->_tools = PhenyxTool::getInstance();
        }

        if (!isset($this->context->language)) {

            $this->context->language = $this->context->_tools->jsonDecode($this->context->_tools->jsonEncode(Language::buildObject($this->context->phenyxConfig->get('EPH_LANG_DEFAULT'))));
        }

        $this->use_routes = (bool) $this->context->phenyxConfig->get('EPH_REWRITING_SETTINGS');

        if (defined('_BACK_MODE_') && _BACK_MODE_) {
            $this->front_controller = static::FC_ADMIN;
            $this->controller_not_found = 'adminlogin';
        } else {

            $this->front_controllers = [_EPH_FRONT_CONTROLLER_DIR_, _EPH_OVERRIDE_DIR_ . 'controllers/front/', _EPH_SPECIFIC_CONTROLLER_DIR_ . 'front/'];
        }

        $this->plugins = $this->getListPlugins();

        if (is_array($this->plugins)) {

            foreach ($this->plugins as $plugin) {

                if (is_dir(_EPH_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'controllers/front/')) {

                    $this->front_controllers = array_merge(
                        $this->front_controllers,
                        [_EPH_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'controllers/front/']
                    );
                } else

                if (is_dir(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'controllers/front/')) {
                    $this->front_controllers = array_merge(
                        $this->front_controllers,
                        [_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin . DIRECTORY_SEPARATOR . 'controllers/front/']
                    );

                }

            }

        }

        if ($this->context->_tools->getValue('fc') == 'admin') {
            $this->front_controller = static::FC_ADMIN;
            $this->controller_not_found = 'admindashboard';
        } else

        if ($this->context->_tools->getValue('fc') == 'plugin') {
            $this->front_controller = static::FC_PLUGIN;
            $this->controller_not_found = 'pagenotfound';
        } else {
            $this->front_controller = static::FC_FRONT;
            $this->controller_not_found = 'pagenotfound';
        }

        $this->loadExtraRoutes();
        $this->loadRoutes();
        $this->setRequestUri();

        if (Language::isMultiLanguageActivated()) {
            $this->multilang_activated = true;
        }

    }

    public static function getInstance() {

        if (!static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function addRoute($routeId, $rule, $controller, $idLang = null, array $keywords = [], array $params = [], $plugin = null) {

        if (isset(Context::getContext()->language) && $idLang === null) {
            $idLang = (int) Context::getContext()->language->id;
        }

        if (!$rule && in_array($routeId, array_keys($this->default_routes))) {
            $rule = $this->default_routes[$routeId]['rule'];
        }

        $regexp = preg_quote($rule, '#');

        if ($keywords) {
            $transformKeywords = [];
            preg_match_all('#\\\{(([^{}]*)\\\:)?(' . implode('|', array_keys($keywords)) . ')(\\\:([^{}]*))?\\\}#', $regexp, $m);

            for ($i = 0, $total = count($m[0]); $i < $total; $i++) {
                $prepend = $m[2][$i];
                $keyword = $m[3][$i];
                $append = $m[5][$i];
                $transformKeywords[$keyword] = [
                    'required' => isset($keywords[$keyword]['param']),
                    'prepend'  => stripslashes($prepend),
                    'append'   => stripslashes($append),
                ];

                $prependRegexp = $appendRegexp = '';

                if ($prepend || $append) {
                    $prependRegexp = '(' . $prepend;
                    $appendRegexp = $append . ')?';
                }

                if (isset($keywords[$keyword]['param']) && $keywords[$keyword]['param']) {
                    $regexp = str_replace($m[0][$i], $prependRegexp . '(?P<' . $keywords[$keyword]['param'] . '>' . $keywords[$keyword]['regexp'] . ')' . $appendRegexp, $regexp);
                } else

                if ($keyword === 'id') {
                    $regexp = str_replace($m[0][$i], $prependRegexp . '(?P<id>' . $keywords[$keyword]['regexp'] . ')' . $appendRegexp, $regexp);
                } else {
                    $regexp = str_replace($m[0][$i], $prependRegexp . '(' . $keywords[$keyword]['regexp'] . ')' . $appendRegexp, $regexp);
                }

            }

            $keywords = $transformKeywords;
        }

        $regexp = '#^/' . $regexp . '$#u';

        if (!isset($this->routes)) {
            $this->routes = [];
        }

        if (!isset($this->cache_routes[$idLang])) {
            $this->cache_routes[$idLang] = [];
        }

        if (!isset($this->routes[$idLang])) {
            $this->routes[$idLang] = [];
        }

        $this->routes[$idLang][$routeId] = [
            'rule'       => $rule,
            'regexp'     => $regexp,
            'controller' => $controller,
            'plugin'     => $plugin,
            'keywords'   => $keywords,
            'params'     => $params,
        ];
        $this->cache_routes[$idLang][$rule] = [
            'route'      => $routeId,
            'regexp'     => $regexp,
            'controller' => $controller,
            'plugin'     => $plugin,
            'keywords'   => $keywords,
            'params'     => $params,
        ];
    }

    public static function getPluginControllers($type = 'all', $plugin = null) {

        $pluginsControllers = [];

        if (is_null($plugin)) {

            $plugins = Plugin::getPluginsOnDisk(true);
        } else

        if (!is_array($plugin)) {
            $plugins = [Plugin::getInstanceByName($plugin)];
        } else {
            $plugins = [];

            foreach ($plugin as $_mod) {
                $plugins[] = Plugin::getInstanceByName($_mod);
            }

        }

        foreach ($plugins as $mod) {

            if (is_dir(_EPH_PLUGIN_DIR_ . $mod->name . '/')) {

                foreach (Performer::getControllersInDirectory(_EPH_PLUGIN_DIR_ . $mod->name . '/controllers/') as $controller) {

                    if ($type == 'admin') {

                        if (strpos($controller, 'Admin') !== false) {
                            $pluginsControllers[$mod->name][] = $controller;
                        }

                    } else

                    if ($type == 'front') {

                        if (strpos($controller, 'Admin') === false) {
                            $pluginsControllers[$mod->name][] = $controller;
                        }

                    } else {
                        $pluginsControllers[$mod->name][] = $controller;
                    }

                }

            } else

            if (is_dir(_EPH_SPECIFIC_PLUGIN_DIR_ . $mod->name . '/')) {

                foreach (Performer::getControllersInDirectory(_EPH_SPECIFIC_PLUGIN_DIR_ . $mod->name . '/controllers/') as $controller) {

                    if ($type == 'admin') {

                        if (strpos($controller, 'Admin') !== false) {
                            $pluginsControllers[$mod->name][] = $controller;
                        }

                    } else

                    if ($type == 'front') {

                        if (strpos($controller, 'Admin') === false) {
                            $pluginsControllers[$mod->name][] = $controller;
                        }

                    } else {
                        $pluginsControllers[$mod->name][] = $controller;
                    }

                }

            }

        }

        return $pluginsControllers;
    }

    public function getPluginsControllers($type = 'all', $plugin = null) {

        $pluginsControllers = [];

        if (is_null($plugin)) {

            $plugins = Plugin::getPluginsOnDisk(true);
        } else

        if (!is_array($plugin)) {
            $plugins = [Plugin::getInstanceByName($plugin)];
        } else {
            $plugins = [];

            foreach ($plugin as $_mod) {
                $plugins[] = Plugin::getInstanceByName($_mod);
            }

        }

        foreach ($plugins as $mod) {

            if ($type == 'front') {

                if (is_dir(_EPH_PLUGIN_DIR_ . $mod->name . '/')) {
                    $controllers = Performer::getControllers(_EPH_PLUGIN_DIR_ . $mod->name . '/controllers/front/');

                    if (is_array($controllers) && count($controllers)) {

                        foreach ($controllers as $key => $frontController) {
                            $pluginsControllers[$mod->name][] = [$key => $frontController];
                        }

                    }

                } else

                if (is_dir(_EPH_SPECIFIC_PLUGIN_DIR_ . $mod->name . '/')) {
                    $controllers = Performer::getControllers(_EPH_SPECIFIC_PLUGIN_DIR_ . $mod->name . '/controllers/front/');

                    if (is_array($controllers) && count($controllers)) {

                        foreach ($controllers as $key => $frontController) {
                            $pluginsControllers[$mod->name][] = [$key => $frontController];
                        }

                    }

                }

            }

            if ($type == 'admin') {

                if (is_dir(_EPH_PLUGIN_DIR_ . $mod->name . '/')) {
                    $controllers = Performer::getControllers(_EPH_PLUGIN_DIR_ . $mod->name . '/controllers/admin/');

                    if (is_array($controllers) && count($controllers)) {

                        foreach ($controllers as $key => $backController) {
                            $pluginsControllers[$mod->name][] = [$key => $backController];
                        }

                    }

                } else

                if (is_dir(_EPH_SPECIFIC_PLUGIN_DIR_ . $mod->name . '/')) {
                    $controllers = Performer::getControllers(_EPH_SPECIFIC_PLUGIN_DIR_ . $mod->name . '/controllers/admin/');

                    if (is_array($controllers) && count($controllers)) {

                        foreach ($controllers as $key => $backController) {
                            $pluginsControllers[$mod->name][] = [$key => $backController];
                        }

                    }

                }

            }

        }

        return $pluginsControllers;
    }

    public function setRequestUri() {

        if ($this->context->cache_enable) {
            $this->context->cache_api = CacheApi::getInstance();

        }

        if (isset($_SERVER['REQUEST_URI'])) {
            $this->request_uri = $_SERVER['REQUEST_URI'];
        } else

        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            $this->request_uri = $_SERVER['HTTP_X_REWRITE_URL'];
        }

        $this->controller = null;

        $urlDetails = parse_url($this->request_uri);
        $request_uri_clean = substr($urlDetails['path'], 1);

        if ($this->context->cache_enable && is_object($this->context->cache_api)) {
            $cacheId = 'controller_' . $this->context->language->id . '_' . $request_uri_clean;
            $value = $this->context->cache_api->getData($cacheId, 864000);

            if (!empty($value)) {
                $this->controller = $value;
            }

        }

        if ($this->context->company->mode == 'full_back') {

            $this->front_controller = static::FC_ADMIN;

            if (is_null($this->controller)) {

                if (isset($this->routes[$this->context->language->id])) {

                    foreach ($this->routes[$this->context->language->id] as $route) {

                        if (isset($route['rule']) && $route['rule'] == $request_uri_clean) {
                            $this->controller = $route['controller'];
                            break;
                        }

                    }

                }

            }

        } else

        if ($this->context->company->mode == 'full_front') {
            $this->front_controller = static::FC_FRONT;

            if (is_null($this->controller)) {

                if (isset($this->routes[$this->context->language->id])) {

                    foreach ($this->routes[$this->context->language->id] as $route) {

                        if (isset($route['rule']) && $route['rule'] == $request_uri_clean) {
                            $this->controller = $route['controller'];
                            break;
                        }

                    }

                }

            }

            if ($this->request_uri == '/' || str_starts_with($this->request_uri, '/?')) {
                $this->front_controller = static::FC_FRONT;
                $this->controller = 'index';
            }

        } else {

            if (str_contains($this->request_uri, 'backend/') || str_contains($this->request_uri, 'admin')) {

                $this->front_controller = static::FC_ADMIN;

                if (is_null($this->controller)) {

                    if (isset($this->routes[$this->context->language->id])) {

                        foreach ($this->routes[$this->context->language->id] as $route) {

                            if (isset($route['rule']) && $route['rule'] == $request_uri_clean) {
                                $this->controller = $route['controller'];
                                break;
                            }

                        }

                    }

                }

            } else {
                $this->front_controller = static::FC_FRONT;

                if (is_null($this->controller)) {

                    if (isset($this->routes[$this->context->language->id])) {

                        foreach ($this->routes[$this->context->language->id] as $route) {

                            if (isset($route['rule']) && $route['rule'] == $request_uri_clean) {
                                $this->controller = $route['controller'];
                                break;
                            }

                        }

                    }

                }

                if ($this->request_uri == '/' || str_starts_with($this->request_uri, '/?')) {
                    $this->front_controller = static::FC_FRONT;
                    $this->controller = 'index';
                }

            }

        }

        if (!is_null($this->controller) && $this->context->cache_enable && is_object($this->context->cache_api)) {

            if (array_key_exists($request_uri_clean, $this->cache_routes[$this->context->language->id])) {
                $this->context->cache_api->putData($cacheId, $this->controller, 864000);

            }

        }

        $this->request_uri = rawurldecode($this->request_uri);

        if (isset(Context::getContext()->company) && is_object(Context::getContext()->company)) {
            $this->request_uri = preg_replace('#^' . preg_quote(Context::getContext()->company->getBaseURI(), '#') . '#i', '/', $this->request_uri);
        }

        $request_uri = $this->context->_hook->exec('setRequestUri', ['request_uri' => $this->request_uri, 'routes' => $this->routes], null, true, false);

        if (is_array($request_uri)) {

            foreach ($request_uri as $plugin => $value) {

                if (!empty($value)) {
                    $this->controller = $value;
                }

            }

        }

    }

    public function loadExtraRoutes() {

        $default_routes = $this->context->_hook->exec('defaultRoutesModifier', [], null, true, false);

        if (is_array($default_routes)) {

            foreach ($default_routes as $key => $default_route) {

                if (is_array($default_route)) {

                    foreach ($default_route as $route => $routeDetails) {

                        if (!isset($this->default_routes[$route])) {
                            $this->default_routes[$route] = $routeDetails;
                        }

                    }

                }

            }

        }

        $extraRoutes = $this->context->_hook->exec('actionGetExtraRoutes', ['canonical_routes' => $this->canonical_routes], null, true, false);

        if (is_array($extraRoutes)) {

            foreach ($extraRoutes as $plugin => $extraRoute) {

                if (is_array($extraRoute)) {

                    foreach ($extraRoute as $route => $routeDetails) {
                        $this->canonical_routes[$route] = $routeDetails;
                    }

                }

            }

        }

        $pluginsRoutes = $this->context->_hook->exec('pluginRoutes', ['id_lang' => 1], null, true, false);

        if (is_array($pluginsRoutes) && count($pluginsRoutes)) {

            foreach ($pluginsRoutes as $pluginRoute) {

                if (is_array($pluginRoute) && count($pluginRoute)) {

                    foreach ($pluginRoute as $route => $routeDetails) {

                        if (array_key_exists('controller', $routeDetails)
                            && array_key_exists('rule', $routeDetails)
                            && array_key_exists('keywords', $routeDetails)
                            && array_key_exists('params', $routeDetails)
                        ) {

                            if (!isset($this->default_routes[$route])) {
                                $this->default_routes[$route] = [];
                            }

                            $this->default_routes[$route] = array_merge($this->default_routes[$route], $routeDetails);
                        }

                    }

                }

            }

        }

    }

    public function loadRoutes() {

        foreach (Language::getLanguages() as $lang) {

            foreach ($this->default_routes as $id => $route) {

                if (array_key_exists($id, $this->canonical_routes)) {
                    $rule = $this->context->phenyxConfig->get($this->canonical_routes[$id], (int) $lang['id_lang']);
                }

                $this->addRoute(
                    $id,
                    $rule,
                    $route['controller'],
                    $lang['id_lang'],
                    $route['keywords'],
                    isset($route['params']) ? $route['params'] : [],
                    null
                );
            }

        }

        if ($this->use_routes) {

            $results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('m.`page`, m.`controller`, m.`plugin`, ml.`url_rewrite`, ml.`id_lang`')
                    ->from('meta', 'm')
                    ->leftJoin('meta_lang', 'ml', 'm.`id_meta` = ml.`id_meta` ')
                    ->orderBy('LENGTH(ml.`url_rewrite`) DESC')
            );

            foreach ($results as $row) {

                if ($row['url_rewrite']) {
                    $this->addRoute(
                        $row['page'],
                        (!defined('_BACK_MODE_') && ($row['controller'] == 'admin')) ? 'backend/' . $row['url_rewrite'] : $row['url_rewrite'],
                        $row['page'],
                        $row['id_lang'],
                        [],
                        [],
                        $row['plugin']
                    );
                }

            }

            if (!$this->empty_route) {
                $this->empty_route = [
                    'routeID'    => 'index',
                    'rule'       => '',
                    'controller' => 'index',
                ];
            }

        }

    }

    public function dispatch() {

        $controllerClass = '';

        if (!$this->controller) {
            $this->getController();
        }

        if (!$this->controller) {
            $this->controller = $this->useDefaultController();
        }

        switch ($this->front_controller) {
        case static::FC_FRONT:
            $this->controller = str_replace('-', '', $this->controller);
            $controllers = Performer::getControllers($this->front_controllers);
            $controllers['index'] = 'IndexController';
            $controllers['installer'] = 'InstallerController';

            if (isset($controllers['auth'])) {
                $controllers['authentication'] = $controllers['auth'];
            }

            if (isset($controllers['compare'])) {
                $controllers['productscomparison'] = $controllers['compare'];
            }

            if (isset($controllers['contact'])) {
                $controllers['contactform'] = $controllers['contact'];
            }

            if (!isset($controllers[strtolower($this->controller)])) {
                $this->controller = $this->controller_not_found;
            }

            $extraControllers = $this->context->_hook->exec('actionGetFrontController', ['controller' => $this->controller], null, true);

            if (is_array($extraControllers) && count($extraControllers)) {

                foreach ($extraControllers as $plugin => $extraController) {

                    if (!empty($extraController)) {
                        $this->controller = $extraController;
                    }

                }

            }

            $controllerClass = $controllers[strtolower($this->controller)];
            $paramsHookActionDispatcher = ['controller_type' => static::FC_FRONT, 'controller_class' => $controllerClass, 'is_plugin' => 0];
            break;

        case static::FC_PLUGIN:

            $pluginName = Validate::isPluginName($this->context->_tools->getValue('plugin')) ? $this->context->_tools->getValue('plugin') : '';
            $plugin = Plugin::getInstanceByName($pluginName);
            $controllerClass = 'PageNotFoundController';

            if (Validate::isLoadedObject($plugin) && $plugin->active) {

                if (is_dir(_EPH_PLUGIN_DIR_ . $pluginName . '/')) {
                    $controllers = Performer::getControllers(_EPH_PLUGIN_DIR_ . $pluginName . '/controllers/front/');

                    if (isset($controllers[strtolower($this->controller)])) {
                        include_once _EPH_PLUGIN_DIR_ . $pluginName . '/controllers/front/' . $controllers[strtolower($this->controller)] . '.php';
                        $controllerClass = str_replace('Controller', '', $controllers[strtolower($this->controller)]) . 'Controller';
                    }

                    $ajaxControllers = Performer::getControllers(_EPH_PLUGIN_DIR_ . $pluginName . '/controllers/ajax/');

                    if (isset($ajaxControllers[strtolower($this->controller)])) {
                        include_once _EPH_PLUGIN_DIR_ . $pluginName . '/controllers/ajax/' . $this->controller . '.php';
                        $controllerClass = $pluginName . $this->controller . 'PluginAjaxController';
                    }

                } else

                if (is_dir(_EPH_SPECIFIC_PLUGIN_DIR_ . $pluginName . '/')) {
                    $controllers = Performer::getControllers(_EPH_SPECIFIC_PLUGIN_DIR_ . $pluginName . '/controllers/front/');

                    if (isset($controllers[strtolower($this->controller)])) {
                        include_once _EPH_SPECIFIC_PLUGIN_DIR_ . $pluginName . '/controllers/front/' . $controllers[strtolower($this->controller)] . '.php';
                        $controllerClass = str_replace('Controller', '', $controllers[strtolower($this->controller)]) . 'Controller';
                    }

                    $ajaxControllers = Performer::getControllers(_EPH_SPECIFIC_PLUGIN_DIR_ . $pluginName . '/controllers/ajax/');

                    if (isset($ajaxControllers[strtolower($this->controller)])) {
                        include_once _EPH_SPECIFIC_PLUGIN_DIR_ . $pluginName . '/controllers/ajax/' . $this->controller . '.php';
                        $controllerClass = $pluginName . $this->controller . 'PluginAjaxController';
                    }

                }

            }

            $paramsHookActionDispatcher = ['controller_type' => static::FC_FRONT, 'controller_class' => $controllerClass, 'is_plugin' => 1];
            break;

        case static::FC_ADMIN:

            $tab = BackTab::getInstanceFromClassName($this->controller, Context::getContext()->language->id);

            if ($tab->plugin) {

                $controller_directories = [
                    _EPH_PLUGIN_DIR_ . $tab->plugin . '/controllers/admin/',
                    _EPH_SPECIFIC_PLUGIN_DIR_ . $tab->plugin . '/controllers/admin/',
                    _EPH_OVERRIDE_DIR_ . 'controllers/admin/',
                ];

                $controllers = Performer::getControllers($controller_directories);

                if (!isset($controllers[strtolower($this->controller)])) {
                    $this->controller = $this->controller_not_found;
                    $controllerClass = 'AdminDashboardController';
                } else {
                    // Controllers in plugins can be named AdminXXX.php or AdminXXXController.php

                    if (is_dir(_EPH_PLUGIN_DIR_ . $tab->plugin)) {
                        include_once _EPH_PLUGIN_DIR_ . $tab->plugin . '/controllers/admin/' . $controllers[strtolower($this->controller)] . '.php';
                    } else

                    if (is_dir(_EPH_SPECIFIC_PLUGIN_DIR_ . $tab->plugin)) {
                        include_once _EPH_SPECIFIC_PLUGIN_DIR_ . $tab->plugin . '/controllers/admin/' . $controllers[strtolower($this->controller)] . '.php';
                    }

                    $controllerClass = $controllers[strtolower($this->controller)] . (strpos($controllers[strtolower($this->controller)], 'Controller') ? '' : 'Controller');
                }

                $paramsHookActionDispatcher = ['controller_type' => static::FC_ADMIN, 'controller_class' => $controllerClass, 'is_plugin' => 1];
            } else {
                $controller_directories = [
                    _EPH_ADMIN_CONTROLLER_DIR_,
                    _EPH_SPECIFIC_CONTROLLER_DIR_ . 'backend/',
                    _EPH_OVERRIDE_DIR_ . 'controllers/admin/',
                ];

                $controllers = Performer::getControllers($controller_directories);
                $controllers['admindashboard'] = 'AdminDashboardController';
                $controllers['adminlogin'] = 'AdminLoginController';

                if (!isset($controllers[strtolower($this->controller)])) {

                    if (Validate::isLoadedObject($tab) && $tab->id_parent == 0 && ($tabs = BackTab::getTabs(Context::getContext()->language->id, $tab->id)) && isset($tabs[0])) {

                        $this->context->_tools->redirectAdmin(Context::getContext()->_link->getAdminLink($tabs[0]['class_name']));
                    }

                    $this->controller = 'admindashboard';
                    $this->context->_tools->redirectAdmin(Context::getContext()->_link->getAdminLink('admindashboard'));
                }

                $controllerClass = $controllers[strtolower($this->controller)];
                $paramsHookActionDispatcher = ['controller_type' => static::FC_ADMIN, 'controller_class' => $controllerClass, 'is_plugin' => 0];
            }

            break;

        default:
            throw new PhenyxException('Bad front controller chosen');
        }

        $_GET['controller'] = $controllerClass;
        // Instantiate controller
        try {

            // Loading controller
            $controller = PhenyxController::getController($controllerClass);

            // Running controller
            $controller->run();
        } catch (PhenyxException $e) {
            $e->displayMessage();
        }

    }

    public function getListPlugins() {

        $plugins = Plugin::getPluginsInstalled();

        foreach ($plugins as &$plugin) {
            $plugin = $plugin['name'];
        }

        return $plugins;
    }

    public function getController($idCompany = null) {

        if (!$this->context->language) {
            $this->context->language = $this->context->_tools->jsonDecode($this->context->_tools->jsonEncode(Language::buildObject($this->context->phenyxConfig->get('EPH_LANG_DEFAULT'))));
        }

        if (isset($this->context->employee->id) && $this->context->employee->id && ($this->context->company->mode == 'conventionnel' || $this->context->company->mode == 'full_front')) {

            if ($this->request_uri == '/' || str_starts_with($this->request_uri, '/?')) {
                $this->front_controller = static::FC_FRONT;
                $this->controller = 'index';
            }

        }

        if ($this->controller) {
            $_GET['controller'] = $this->controller;
            return $this->controller;
        }

        list($uri) = explode('?', $this->request_uri);

        if (isset($this->context->company) && $idCompany === null) {
            $idCompany = (int) $this->context->company->id;
        }

        $controller = $this->context->_tools->getValue('controller');

        if (isset($controller) && is_string($controller)) {

            if (preg_match('/^([0-9a-z_-]+)\?(.*)=(.*)$/Ui', $controller, $m)) {
                $controller = $m[1];

                if (isset($_GET['controller'])) {
                    $_GET[$m[2]] = $m[3];
                } else {

                    if (isset($_POST['controller'])) {
                        $_POST[$m[2]] = $m[3];
                    }

                }

            } else

            if (!$this->use_routes && Validate::isControllerName($controller) && $this->context->_tools->isSubmit('id_' . $controller)) {
                $id = $this->context->_tools->getValue('id_' . $controller);
                $_GET['id_' . $controller] = $id;
                $this->controller = $controller;

                return $this->controller;
            }

        }

        if (!Validate::isControllerName($controller)) {
            $controller = false;
        }

        if ($this->use_routes && !$controller) {

            if (!$this->request_uri) {
                return mb_strtolower($this->controller_not_found);
            }

            // Check basic controllers & params
            $controller = $this->controller_not_found;
            $testRequestUri = preg_replace('/(=http:\/\/)/', '=', $this->request_uri);

            if (!preg_match('/\.(css|js)$/i', parse_url($testRequestUri, PHP_URL_PATH))) {
                // Add empty route as last route to prevent this greedy regexp to match request uri before right time

                if ($this->empty_route) {
                    $this->addRoute(
                        $this->empty_route['routeID'],
                        $this->empty_route['rule'],
                        $this->empty_route['controller'],
                        $this->context->language->id,
                        [],
                        [],
                        null
                    );
                }

                list($uri) = explode('?', $this->request_uri);

                if (isset($this->routes[$this->context->language->id])) {
                    $routes = $this->routes[$this->context->language->id];
                    $identifyController = $this->context->_hook->exec('actionPerformerIdentifyRoute', ['uri' => $uri, 'routes' => $routes, 'front_controller' => $this->front_controller], null, true, false);

                    if (is_array($identifyController)) {

                        foreach ($identifyController as $key => $getController) {

                            if (isset($getController['controller'])) {
                                $controller = $getController['controller'];
                                $this->controller = str_replace('-', '', $controller);
                                $_GET['controller'] = $this->controller;

                                return $this->controller;
                            }

                        }

                    }

                    $need_more = true;

                    foreach ($routes as $route) {

                        if ((isset($this->context->employee->id) && $this->context->employee->id) || $this->front_controller == static::FC_ADMIN) {

                            if ("/" . $route['rule'] == $uri) {
                                $controller = $route['controller'] ? $route['controller'] : $_GET['controller'];
                                $need_more = false;

                                if (isset($_GET['fc']) && $_GET['fc'] == 'plugin') {
                                    $this->front_controller = self::FC_PLUGIN;
                                }

                                break;
                            }

                        }

                    }

                    if ($need_more) {

                        $hookGetController = $this->context->_hook->exec('actionPerformerGetController', ['uri' => $uri, 'routes' => $routes, 'routes' => $routes, 'front_controller' => $this->front_controller], null, true, false);

                        if (is_array($hookGetController)) {

                            foreach ($hookGetController as $key => $getController) {

                                if (isset($getController['front_controller']) && !is_null($getController['front_controller'])) {

                                    $this->front_controller = $getController['front_controller'];
                                    $this->controller = $getController['controller'];
                                    $_GET['controller'] = $this->controller;

                                    return $this->controller;
                                }

                            }

                        }

                        foreach ($routes as $route) {

                            if (preg_match($route['regexp'], $uri, $m)) {

                                if (array_key_exists('pfg_rewrite', $m)) {

                                    if ($route['controller'] === 'pfg') {

                                        if (isset($m['id']) && $m['id']) {
                                            $idPagepfg = (int) $m['id'];
                                        } else {
                                            $idPagepfg = $this->pagepfgID($m['pfg_rewrite']);

                                            if (!$idPagepfg) {
                                                $idPagepfg = in_array('id_pfg', $m) ? (int) $m['id_pfg'] : 0;

                                                if (!$idPagepfg) {
                                                    continue;
                                                }

                                            }

                                        }

                                        $_GET['id_pfg'] = $idPagepfg;
                                    }

                                }

                                if (array_key_exists('cms_rewrite', $m)) {

                                    if ($route['controller'] === 'cms') {

                                        if (isset($m['id']) && $m['id']) {
                                            $idCms = (int) $m['id'];
                                        } else {
                                            $idCms = $this->cmsID($m['cms_rewrite'], $uri);

                                            if (!$idCms) {
                                                $idCms = in_array('id_cms', $m) ? (int) $m['id_cms'] : 0;

                                                if (!$idCms) {
                                                    continue;
                                                }

                                            }

                                        }

                                        $_GET['id_cms'] = $idCms;
                                    }

                                }

                                $isPlugin = isset($route['params']['fc']) && $route['params']['fc'] === 'plugin';

                                foreach ($m as $k => $v) {
                                    // We might have us an external plugin page here, in that case we set whatever we can

                                    if (!is_numeric($k) &&
                                        ($isPlugin
                                            || $k !== 'id'
                                            && $k !== 'ipa'
                                            && $k !== 'rewrite'
                                            && $k !== 'cms_rewrite'
                                            && $k !== 'pfg_rewrite'
                                        )) {
                                        $_GET[$k] = $v;
                                    }

                                }

                                $controller = $route['controller'] ? $route['controller'] : $_GET['controller'];

                                if (!empty($route['params'])) {

                                    foreach ($route['params'] as $k => $v) {
                                        $_GET[$k] = $v;
                                    }

                                }

                                // A patch for plugin friendly urls

                                if (preg_match('#plugin-([a-z0-9_-]+)-([a-z0-9_]+)$#i', $controller, $m)) {
                                    $_GET['plugin'] = $m[1];
                                    $_GET['fc'] = 'plugin';
                                    $controller = $m[2];
                                }

                                if (isset($_GET['fc']) && $_GET['fc'] == 'plugin') {
                                    $this->front_controller = self::FC_PLUGIN;
                                }

                                break;
                            }

                        }

                    }

                }

            }

            // Check if index

            if ($controller == 'index' || preg_match('/^\/index.php(?:\?.*)?$/', $this->request_uri)
                || $uri == ''
            ) {
                $controller = $this->useDefaultController();
            }

        }

        $this->controller = str_replace('-', '', $controller);
        $_GET['controller'] = $this->controller;

        return $this->controller;
    }

    public function useDefaultController() {

        $this->use_default_controller = true;

        if ($this->default_controller === null) {

            if (defined('_EPH_ROOT_DIR_')) {

                if (isset(Context::getContext()->employee) && Validate::isLoadedObject(Context::getContext()->employee) && isset(Context::getContext()->employee->default_tab)) {
                    $this->default_controller = BackTab::getClassNameById((int) Context::getContext()->employee->default_tab);
                }

                if (empty($this->default_controller)) {
                    $this->default_controller = 'AdminDashboard';
                }

            } else

            if ($this->context->_tools->getValue('fc') == 'plugin') {
                $this->default_controller = 'default';
            } else {
                $this->default_controller = 'index';
            }

        }

        return $this->default_controller;
    }

    public static function getControllers($dirs) {

        if (!is_array($dirs)) {
            $dirs = [$dirs];
        }

        $controllers = [];

        foreach ($dirs as $dir) {
            $controllers = array_merge($controllers, Performer::getControllersInDirectory($dir));
        }

        return $controllers;
    }

    public static function getControllersInDirectory($dir) {

        if (!is_dir($dir)) {
            return [];
        }

        $controllers = [];
        $controllerFiles = scandir($dir);

        foreach ($controllerFiles as $controllerFilename) {

            if ($controllerFilename[0] != '.') {

                if (!strpos($controllerFilename, '.php') && is_dir($dir . $controllerFilename)) {
                    $controllers += Performer::getControllersInDirectory($dir . $controllerFilename . DIRECTORY_SEPARATOR);
                } else

                if ($controllerFilename != 'index.php') {
                    $key = str_replace(['controller.php', '.php'], '', strtolower($controllerFilename));
                    $controllers[$key] = basename($controllerFilename, '.php');
                }

            }

        }

        return $controllers;
    }

    public function hasRoute($routeId, $idLang = null) {

        if (isset(Context::getContext()->language) && $idLang === null) {
            $idLang = (int) Context::getContext()->language->id;
        }

        return isset($this->routes) && isset($this->routes[$idLang]) && isset($this->routes[$idLang][$routeId]);
    }

    public function hasKeyword($routeId, $idLang, $keyword) {

        if (!isset($this->routes)) {
            $this->loadRoutes();
        }

        if (!isset($this->routes) || !isset($this->routes[$idLang]) || !isset($this->routes[$idLang][$routeId])) {
            return false;
        }

        return preg_match('#\{([^{}]*:)?' . preg_quote($keyword, '#') . '(:[^{}]*)?\}#', $this->routes[$idLang][$routeId]['rule']);
    }

    public function validateRoute($routeId, $rule, &$errors = []) {

        $errors = [];

        if (!isset($this->default_routes[$routeId])) {
            return false;
        }

        foreach ($this->default_routes[$routeId]['keywords'] as $keyword => $data) {

            if ($this->use_routes && $keyword === 'id') {
                continue;
            }

            if ($this->use_routes && $keyword === 'rewrite') {
                $data['param'] = true;
            }

            if (isset($data['param']) && !preg_match('#\{([^{}]*:)?' . $keyword . '(:[^{}]*)?\}#', $rule)) {
                $errors[] = $keyword;
            }

        }

        return (count($errors)) ? false : true;
    }

    public function createUrl($routeId, $idLang = null, array $params = [], $forceRoutes = false, $anchor = '') {

        if ($idLang === null) {
            $idLang = (int) Context::getContext()->language->id;
        }

        if (!isset($this->routes)) {
            $this->loadRoutes();
        }

        if (!isset($this->routes[$idLang][$routeId])) {

            $query = http_build_query($params, '', '&');
            $indexLink = $this->use_routes ? '' : 'index.php';

            if (!is_null($routeId)) {
                $routeId = trim($routeId);
            }

            return ($routeId == 'index') ? $indexLink . (($query) ? '?' . $query : '') : (($routeId == '') ? '' : 'index.php?controller=' . $routeId) . (($query) ? '&' . $query : '') . $anchor;
        }

        $route = $this->routes[$idLang][$routeId];
        // Check required fields
        $queryParams = isset($route['params']) ? $route['params'] : [];
        // Skip if we are not using routes
        // Build an url which match a route

        if ($this->use_routes || $forceRoutes) {

            foreach ($route['keywords'] as $key => $data) {

                if (!$data['required']) {
                    continue;
                }

                if (!array_key_exists($key, $params)) {
                    throw new PhenyxException('Performer::createUrl() miss required parameter "' . $key . '" for route "' . $routeId . '"');
                }

                if (isset($this->default_routes[$routeId])) {
                    $queryParams[$this->default_routes[$routeId]['keywords'][$key]['param']] = $params[$key];
                }

            }

            $url = $route['rule'];
            $addParam = [];

            foreach ($params as $key => $value) {

                if (!isset($route['keywords'][$key])) {

                    if (!isset($this->default_routes[$routeId]['keywords'][$key])) {
                        $addParam[$key] = $value;
                    }

                } else {

                    if ($params[$key] && !is_array($params[$key])) {
                        $replace = (string) $route['keywords'][$key]['prepend'] . $params[$key] . (string) $route['keywords'][$key]['append'];
                    } else {
                        $replace = '';
                    }

                    $url = preg_replace('#\{([^{}]*:)?' . $key . '(:[^{}]*)?\}#', $replace, $url);
                }

            }

            $url = preg_replace('#\{([^{}]*:)?[a-z0-9_]+?(:[^{}]*)?\}#', '', $url);

            if (count($addParam)) {
                $url .= '?' . http_build_query($addParam, '', '&');
            }

        } else {
            $addParams = [];

            foreach ($route['keywords'] as $key => $data) {

                if (!$data['required'] || !array_key_exists($key, $params) || ($key === 'rewrite' && in_array($route['controller'], ['product', 'category', 'supplier', 'manufacturer', 'cms']))) {
                    continue;
                }

                if (isset($this->default_routes[$routeId])) {
                    $queryParams[$this->default_routes[$routeId]['keywords'][$key]['param']] = $params[$key];
                }

            }

            foreach ($params as $key => $value) {

                if (!isset($route['keywords'][$key]) && !isset($this->default_routes[$routeId]['keywords'][$key])) {
                    $addParams[$key] = $value;
                }

            }

            if (isset($this->default_routes[$routeId])) {

                foreach ($this->default_routes[$routeId]['keywords'] as $key => $keyword) {

                    if (isset($keyword['alias']) && $keyword['alias']) {
                        $addParams[$keyword['alias']] = $params[$key];
                    }

                }

            }

            if (!empty($route['controller'])) {
                $queryParams['controller'] = $route['controller'];
            }

            $query = http_build_query(array_merge($addParams, $queryParams), '', '&');

            if ($this->multilang_activated) {
                $query .= (!empty($query) ? '&' : '') . 'id_lang=' . (int) $idLang;
            }

            $url = 'index.php?' . $query;
        }

        return $url . $anchor;
    }

    public function pagepfgID($rewrite) {

        // Rewrite cannot be empty

        if (empty($rewrite)) {
            return 0;
        }

        $context = Context::getContext();

        $pages = PFGModel::getPagePfg($this->context->language->id, true);

        foreach ($pages as $page) {

            if ($page['link_rewrite'] === $rewrite) {
                return (int) $page['id_pfg'];
            }

        }

        return 0;
    }

    public function cmsID($rewrite, $url = '') {

        // Rewrite cannot be empty

        if (empty($rewrite)) {
            return 0;
        }

        // Remove leading slash from URL
        $url = ltrim($url, '/');

        $context = Context::getContext();
        $pages = CMS::getPageCms($this->context->language->id);

        foreach ($pages as $page) {

            if ($page['link_rewrite'] === $rewrite) {
                return (int) $page['id_cms'];
            }

        }

        return 0;
    }

    protected function createRegExp($rule, $keywords) {

        $regexp = preg_quote($rule, '#');

        if ($keywords) {
            $transformKeywords = [];
            preg_match_all('#\\\{(([^{}]*)\\\:)?(' . implode('|', array_keys($keywords)) . ')(\\\:([^{}]*))?\\\}#', $regexp, $m);

            for ($i = 0, $total = count($m[0]); $i < $total; $i++) {
                $prepend = $m[2][$i];
                $keyword = $m[3][$i];
                $append = $m[5][$i];
                $transformKeywords[$keyword] = [
                    'required' => isset($keywords[$keyword]['param']),
                    'prepend'  => $this->context->_tools->stripslashes($prepend),
                    'append'   => $this->context->_tools->stripslashes($append),
                ];
                $prependRegexp = $appendRegexp = '';

                if ($prepend || $append) {
                    $prependRegexp = '(' . preg_quote($prepend);
                    $appendRegexp = preg_quote($append) . ')?';
                }

                if (isset($keywords[$keyword]['param'])) {
                    $regexp = str_replace($m[0][$i], $prependRegexp . '(?P<' . $keywords[$keyword]['param'] . '>' . $keywords[$keyword]['regexp'] . ')' . $appendRegexp, $regexp);
                } else {
                    $regexp = str_replace($m[0][$i], $prependRegexp . '(' . $keywords[$keyword]['regexp'] . ')' . $appendRegexp, $regexp);
                }

            }

        }

        return '#^/' . $regexp . '$#u';
    }

}
