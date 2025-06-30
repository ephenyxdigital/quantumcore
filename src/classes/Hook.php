<?php

/**
 * Class HookCore
 *
 * @since 1.9.1.0
 */
class Hook extends PhenyxObjectModel {

    // @codingStandardsIgnoreStart
    /**
     * @var array List of executed hooks on this page
     */
    public static $executed_hooks = [];

    public static $native_plugin;

    public static $hook_instance;

    protected static $_available_plugins_cache = null;

    public $name;

    public $target;

    public $is_tag;

    public $position = false;

    public $metas = [];

    public $generated;

    public $title;

    public $description;
    
    public $plugins;
    
    public $plugin_count;

    public static $counter = 1;

    public static $total_time = 0;

    public $memoryStart;
    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'hook',
        'primary' => 'id_hook',
        'fields'  => [
            'name'        => ['type' => self::TYPE_STRING, 'validate' => 'isHookName', 'required' => true, 'size' => 64],
            'title'       => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'description' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'],
            'static'      => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],

        ],
    ];

    public function __construct($id = null, $idLang = null) {

        $this->className = get_class($this);
        $this->context = Context::getContext();

        if (!isset($this->context->phenyxConfig)) {
            $this->context->phenyxConfig = Configuration::getInstance();

        }

        if (!isset(PhenyxObjectModel::$loaded_classes[$this->className])) {
            $this->def = PhenyxObjectModel::getDefinition($this->className);

            if (!Validate::isTableOrIdentifier('id_hook') || !Validate::isTableOrIdentifier('hook')) {
                throw new PhenyxException('Identifier or table format not valid for class ' . $this->className);
                PhenyxLogger::addLog(sprintf($this->l('Identifier or table format not valid for class %s'), $this->className), 3, null, get_class($this));
            }

            PhenyxObjectModel::$loaded_classes[$this->className] = get_object_vars($this);
        } else {

            foreach (PhenyxObjectModel::$loaded_classes[$this->className] as $key => $value) {
                $this->{$key}

                = $value;
            }

        }

        $this->id_lang = (Language::getLanguage($idLang) !== false) ? $idLang : $this->context->phenyxConfig->get('EPH_LANG_DEFAULT');
        $this->context->_hook = $this;

        if ($id) {
            $this->id = $id;
            $entityMapper = Adapter_ServiceLocator::get("Adapter_EntityMapper");
            $entityMapper->load($this->id, $idLang, $this, $this->def, false);
            $this->plugins = $this->getPlugins();
            $this->plugin_count = is_array($this->plugins) ? count($this->plugins)  : 0;
        }

        $this->_session = PhenyxSession::getInstance();

        if (_EPH_DEBUG_PROFILING_ || _EPH_ADMIN_DEBUG_PROFILING_) {
            $this->memoryStart = memory_get_usage(true);
        }

    }
    
    public static function buildObject($id, $id_lang = null, $className = null) {
        
        $objectData = parent::buildObject($id, null, 'Hook');
        $objectData['plugins'] = self::getStaticPlugins($id);
        $objectData['plugin_count'] = is_array($objectData['plugins']) ? count($objectData['plugins'])  : 0;
        
        return PhenyxTool::getInstance()->jsonDecode(PhenyxTool::getInstance()->jsonEncode($objectData));
    }    
    
    
    public function getPlugins($use_cache = true) {
        
        if ($use_cache && $this->context->cache_enable) {

            if (is_object($this->context->cache_api)) {
                $value = $this->context->cache_api->getData('getPlugins'.$this->id);
                $plugins = empty($value) ? null : $this->context->_tools->jsonDecode($value, true);
                if (!empty($plugins) && is_array($plugins)) {
                    return $plugins;
                }
            }
        }
        $plugins = [];
        $query = new DbQuery();
        $query->select('id_plugin');
        $query->from('hook_plugin');
        $query->where('id_hook = '.$this->id);
        $query->orderBy('position');
        
        $resuts = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($query);
        $i = 1;
        foreach($resuts as $plugin) {
            $plugs = [];
            $pl = Plugin::getInstanceById((int) $plugin['id_plugin']);
            $plugs['id_hook_plugin'] = $plugin['id_hook_plugin'];  
            $plugs['id_plugin'] = $pl->id;            
            $plugs['name'] = $pl->name;
            $plugs['plugin_name'] = $pl->name;
            $plugs['name'] = '<div class="plugin_col_infos"><span class="plugin_name">
                                    ' . $pl->displayName . ' <small class="text-muted">&nbsp;-&nbsp;v' . $pl->version . '</span>
                                <p class="discret">' . $pl->description . '</p>
                            </div>';
            $plugs['displayName'] = $pl->displayName;
            $plugs['description'] = $pl->description;
            $plugs['version'] = $pl->version;
            $plugs['pluginPosition'] = $i;
            $plugs['position'] = '<div class="dragGroup"><div class="pluginPosition_' . $plugs['id_hook_plugin']. ' positions" data-id="' . $pl->id . '" data-parent="' . $plugs['id_hook_plugin'] . '" data-position="' . $i . '">' . $i . '</div></div>';;
            
            if (file_exists(_EPH_PLUGIN_DIR_ . $pl->name . '/logo.png')) {
                $plugs['image'] = '<img src="/includes/plugins/' . $pl->name . '/logo.png" class="imgm img-thumbnail">';
            } else


            if (file_exists(_EPH_SPECIFIC_PLUGIN_DIR_ . $pl->name . '/logo.png')) {
                $plugs['image'] = '<img src="includes/specific_plugins/' . $pl->name . '/logo.png" class="imgm img-thumbnail">';
            } else {
                $plugs['image'] = '<img src="content/img/no-plugin.png" class="imgm img-thumbnail">';
            }
            $i++;
            
            $plugins[] = $plugs;
        }
        if ($this->context->cache_enable) {

            if (is_object($this->context->cache_api)) {
                $temp = $this->context->_tools->jsonEncode($plugins);
                $this->context->cache_api->putData('getPlugins'.$this->id, $temp, 1864000);
            }
        }
        
        return $plugins;
    }
    
    public static function getStaticPlugins($id_hook, $use_cache = true) {
        
        $context = Context::getContext();
        if ($use_cache && $context->cache_enable) {

            if (is_object($context->cache_api)) {
                $value = $context->cache_api->getData('getPlugins'.$id_hook);
                $plugins = empty($value) ? null : $context->_tools->jsonDecode($value, true);
                if (!empty($plugins) && is_array($plugins)) {
                    return $plugins;
                }
            }
        }
        $plugins = [];
        $query = new DbQuery();
        $query->select('id_plugin, id_hook_plugin');
        $query->from('hook_plugin');
        $query->where('id_hook = '.$id_hook);
        $query->orderBy('position');
        
        $resuts = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($query);
        $i = 1;
        foreach($resuts as $plugin) {
            $plugs = [];
            $pl = Plugin::getInstanceById((int) $plugin['id_plugin']);
            $plugs['id_hook_plugin'] = $plugin['id_hook_plugin'];  
            $plugs['id_plugin'] = $pl->id;            
            $plugs['name'] = $pl->name;
            $plugs['plugin_name'] = $pl->name;
            $plugs['name'] = '<div class="plugin_col_infos"><span class="plugin_name">
                                    ' . $pl->displayName . ' <small class="text-muted">&nbsp;-&nbsp;v' . $pl->version . '</span>
                                <p class="discret">' . $pl->description . '</p>
                            </div>';
            $plugs['displayName'] = $pl->displayName;
            $plugs['description'] = $pl->description;
            $plugs['version'] = $pl->version;
            $plugs['pluginPosition'] = $i;
            $plugs['position'] = '<div class="dragGroup"><div class="pluginPosition_' . $plugs['id_hook_plugin']. ' positions" data-id="' . $pl->id . '" data-parent="' . $plugs['id_hook_plugin'] . '" data-position="' . $i . '">' . $i . '</div></div>';;
            
            if (file_exists(_EPH_PLUGIN_DIR_ . $pl->name . '/logo.png')) {
                $plugs['image'] = '<img src="/includes/plugins/' . $pl->name . '/logo.png" class="imgm img-thumbnail">';
            } else


            if (file_exists(_EPH_SPECIFIC_PLUGIN_DIR_ . $pl->name . '/logo.png')) {
                $plugs['image'] = '<img src="includes/specific_plugins/' . $pl->name . '/logo.png" class="imgm img-thumbnail">';
            } else {
                $plugs['image'] = '<img src="content/img/no-plugin.png" class="imgm img-thumbnail">';
            }
            $i++;
            
            $plugins[] = $plugs;
        }
        if ($context->cache_enable) {

            if (is_object($context->cache_api)) {
                $temp = $context->_tools->jsonEncode($plugins);
                $context->cache_api->putData('getPlugins'.$id_hook, $temp, 1864000);
            }
        }
        return $plugins;
    }

    public static function getInstance($id = null, $idLang = null) {

        if (!static::$hook_instance) {
            static::$hook_instance = new Hook($id, $idLang);
        }

        return static::$hook_instance;
    }

    public function add($autoDate = true, $nullValues = false) {

        $result = parent::add($autoDate, $nullValues);
        if($result) {
            $this->plugins = $this->getPlugins(false);
        }

        return $result;
    }

    public function update($nullValues = false) {

        $result = parent::update($nullValues);
        if($result) {
            $this->plugins = $this->getPlugins(false);
        }

        return $result;
    }

    public function getHookArgs() {

        $args = ['cookie' => 'construct cookie'];

        $args_conf_id = $this->getIdByName('actionHookExtraArgs');

        if ($args_conf_id > 0) {

            $plugins = $this->getPluginsFromHook($args_conf_id, null);

            foreach ($plugins as $plugin) {
                $pluginInstance = Plugin::getInstanceByName($plugin['name']);
                $hookCallable = is_callable([$pluginInstance, 'hook' . $plugin['title']]);

                if (($hookCallable) && Plugin::preCall($pluginInstance->name)) {
                    $display = $this->coreCallHook($pluginInstance, 'hook' . $plugin['title'], []);

                    foreach ($display as $key => $value) {

                        $args[$key] = $value;

                    }

                }

            }

        }

        return $args;
    }

    public function getHooksCollection($idLang = null) {

        $collection = [];

        $hooks = new PhenyxCollection('Hook');

        foreach ($hooks as $hook) {
            $collection[] = new Hook($hook->id);
        }

        return $collection;
    }

    public function getHooks($position = false) {

        $query = new DbQuery();
        $query->select('*');
        $query->from('hook');

        if ($position) {
            $query->where('`position` = 1');
        }

        $query->orderBy('`name`');

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($query);
    }
    
    public function getPluginHooks($id_plugin = 0, $use_cache = true) {
        
        if ($use_cache && $this->context->cache_enable) {

            if (is_object($this->context->cache_api)) {
                $value = $this->context->cache_api->getData('getPluginHooks_'.$id_plugin);
                $hooks = empty($value) ? null : $this->context->_tools->jsonDecode($value, true);
                if (!empty($hooks) && is_array($hooks)) {
                    return $hooks;
                }
            }
        }
        
        $hooks= $this->_session->get('getPluginHooks_'.$id_plugin);

        if (!empty($hooks) && is_array($hooks)) {
            return $hooks;
        }
        $hooks = [];
        $query = new DbQuery();
        $query->select('DISTINCT(h.id_hook), h.*');
        $query->from('hook', 'h');
        $query->leftJoin('hook_plugin', 'hp', 'hp.id_hook = h.id_hook');
        
        if($id_plugin > 0) {
            $query->where('hp.id_plugin = '.$id_plugin);
        } else {
            $query->where('hp.id_plugin > 0');
        }
        $query->orderBy('h.`name`, hp.position');
        $results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($query);
       
        foreach($results as &$hook) {
            $hook['plugins'] = self::getStaticPlugins($hook['id_hook']);
            $hook['plugin_count'] = is_array($hook['plugins']) ? count($hook['plugins'])  : 0;
            $hooks[$hook['name']] = $hook;
            
            
        }
        
        if ($this->context->cache_enable) {

            if (is_object($this->context->cache_api)) {
                $temp = $this->context->_tools->jsonEncode($hooks);
                $this->context->cache_api->putData('getPluginHooks_'.$id_plugin, $temp, 1864000);
            }
        }
        $this->_session->set('getPluginHooks_'.$id_plugin, $hooks);
        return $hooks;
      
    }
    
    public static function getStaticIdHookPlugin($hook) {
        
        $query = new DbQuery();
        $query->select('id_hook_plugin');
        $query->from('hook_plugin');
        $query->where('hp.id_plugin > 0');
        $query->orderBy('h.`name`, hp.position');
        
    }

    public function getNameById($hookId) {

        $cacheId = 'hook_namebyid_' . $hookId;

        if (!CacheApi::isStored($cacheId)) {
            $result = Db::getInstance()->getValue(
                (new DbQuery())
                    ->select('`name`')
                    ->from('hook')
                    ->where('`id_hook` = ' . (int) $hookId)
            );
            CacheApi::store($cacheId, $result);

            return $result;
        }

        return CacheApi::retrieve($cacheId);
    }

    public function getPluginsFromHook($idHook, $idPlugin = null) {

        $hmList = $this->getHookPluginList();
        $pluginList = (isset($hmList[$idHook])) ? $hmList[$idHook] : [];

        if ($idPlugin) {
            return (isset($pluginList[$idPlugin])) ? [$pluginList[$idPlugin]] : [];
        }

        return $pluginList;
    }

    public function getPluginHook() {

        return Db::getInstance()->executeS(
            (new DbQuery())
                ->select('DISTINCT(hm.`id_hook`), h.*')
                ->from('hook_plugin', 'hm')
                ->leftJoin('hook', 'h', 'h.`id_hook` = hm.`id_hook`')
                ->leftJoin('plugin', 'm', 'm.`id_plugin` = hm.`id_plugin`')
                ->orderBy('hm.`position` ASC')
                ->where('m.active = 1')
        );
    }

    public function getHookPluginList($use_cache = true) {

        $list = $this->_session->get('hook_plugin_list');

        if (!empty($list) && is_array($list)) {
            return $list;
        }

        $results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS('
            SELECT h.id_hook, h.name AS h_name, h.title, h.description, h.static, hm.position AS hm_position, m.id_plugin, m.name, m.active
            FROM `' . _DB_PREFIX_ . 'hook_plugin` hm
            STRAIGHT_JOIN `' . _DB_PREFIX_ . 'hook` h ON (h.id_hook = hm.id_hook)
            STRAIGHT_JOIN `' . _DB_PREFIX_ . 'plugin` AS m ON (m.id_plugin = hm.id_plugin)
            ORDER BY hm.position'
        );
        $list = [];

        foreach ($results as $result) {

            if (!isset($list[$result['id_hook']])) {
                $list[$result['id_hook']] = [];
            }

            $list[$result['id_hook']][$result['id_plugin']] = [
                'id_hook'     => $result['id_hook'],
                'title'       => $result['title'],
                'description' => $result['description'],
                'static'      => $result['static'],
                'm.position'  => $result['hm_position'],
                'id_plugin'   => $result['id_plugin'],
                'name'        => $result['name'],
                'active'      => $result['active'],
            ];
        }

        $this->_session->set('hook_plugin_list', $list);

        return $list;
    }

    public function exec(
        $hookName,
        $hookArgs = [],
        $idPlugin = null,
        $arrayReturn = false,
        $checkExceptions = true,
        $usePush = false,
        $objectReturn = false
    ) {

        if (!isset($hookArgs['cookie']) || !$hookArgs['cookie']) {
            $hookArgs['cookie'] = $this->context->cookie;
        }

        if (is_array($this->context->hook_args)) {

            foreach ($this->context->hook_args as $key => $value) {
                $hookArgs[$key] = $this->context->$key;
            }

        }

        if (!$this->context->phenyxConfig->get('EPH_PAGE_CACHE_ENABLED')) {

            return $this->execWithoutCache($hookName, $hookArgs, $idPlugin, $arrayReturn, $checkExceptions, $usePush, $objectReturn);
        }

        if (!$pluginList = $this->getHookPluginExecList($hookName)) {
            return '';
        }

        if ($arrayReturn) {

            $return = [];
        } else {
            $return = '';
        }

        if (!$idPlugin) {
            $cachedHooks = $this->getCachedHooks();

            foreach ($pluginList as $m) {
                $idPlugin = (int) $m['id_plugin'];
                $data = $this->execWithoutCache($hookName, $hookArgs, $idPlugin, $arrayReturn, $checkExceptions, $usePush, $objectReturn);

                if (is_array($data)) {
                    $data = array_shift($data);
                }

                if (is_array($data)) {
                    $return[$m['plugin']] = $data;
                } else {
                    $idHook = (int) $this->getIdByName($hookName);

                    if (isset($cachedHooks[$idPlugin][$idHook])) {
                        $dataWrapped = $data;
                    } else {

                        $dataWrapped = $data;
                    }

                    if ($arrayReturn) {
                        $return[$m['plugin']] = $dataWrapped;
                    } else

                    if ($objectReturn) {
                        $return = $dataWrapped;
                    } else {
                        $return .= $dataWrapped;
                    }

                }

            }

        } else {
            $return = static::execWithoutCache($hookName, $hookArgs, $idPlugin, $arrayReturn, $checkExceptions, $usePush, $objectReturn);
        }

        return $return;
    }

    public function execWithoutCache(
        $hookName,
        $hookArgs = [],
        $idPlugin = null,
        $arrayReturn = false,
        $checkExceptions = true,
        $usePush = false,
        $objectReturn = false
    ) {

        if (defined('EPH_INSTALLATION_IN_PROGRESS')) {

            return;
        }

        if (_EPH_DEBUG_PROFILING_ || _EPH_ADMIN_DEBUG_PROFILING_) {

            $perfs = $this->_session->get('HookPerformance');
            $plugperfs = $this->_session->get('pluginPerformance');
            $time_start = microtime(true);
            $memoryStart = memory_get_usage(true);

        }

        static $disableNonNativePlugins = null;

        if ($disableNonNativePlugins === null) {
            $disableNonNativePlugins = (bool) $this->context->phenyxConfig->get('EPH_DISABLE_NON_NATIVE_PLUGIN');
        }

        if (($idPlugin && !is_numeric((int) $idPlugin)) || !Validate::isHookName($hookName)) {

            throw new PhenyxException('Invalid id_plugin or hook_name');
        }

        if (!$pluginList = $this->getHookPluginExecList($hookName)) {

            return '';
        }

        if (!$idHook = $this->getIdByName($hookName)) {

            return false;
        }

        Hook::$executed_hooks[$idHook] = $hookName;

        $retroHookName = $this->getRetroHookName($hookName);

        $altern = 0;

        if ($arrayReturn) {
            $output = [];
        } else {
            $output = '';
        }

        if ($disableNonNativePlugins && !isset(Hook::$native_plugin)) {
            Hook::$native_plugin = Plugin::getNativePluginList();
        }

        foreach ($pluginList as $array) {

            if ($idPlugin && $idPlugin != $array['id_plugin']) {
                continue;
            }

            if ((bool) $disableNonNativePlugins && Hook::$native_plugin && count(Hook::$native_plugin) && !in_array($array['plugin'], Hook::$native_plugin)) {

                continue;
            }

            if ($checkExceptions) {

                $exceptions = Plugin::getExceptionsStatic($array['id_plugin'], $array['id_hook']);

                $controller = Performer::getInstance()->getController();
                $controllerObj = $this->context->controller;

                if (is_array($exceptions) && in_array($controller, $exceptions)) {
                   
                    continue;
                }

                $matchingName = [
                    'authentication'     => 'auth',
                    'productscomparison' => 'compare',
                ];

                if (isset($matchingName[$controller]) && in_array($matchingName[$controller], $exceptions)) {

                    continue;
                }

                if (Validate::isLoadedObject($this->context->employee) && !Plugin::getPermissionStatic($array['id_plugin'], 'view', $this->context->employee)) {

                    continue;
                }

            }

            if (!($pluginInstance = Plugin::getInstanceByName($array['plugin']))) {

                continue;
            }

            if ($usePush && !$pluginInstance->allow_push) {

                continue;
            }

            $hookCallable = is_callable([$pluginInstance, 'hook' . $hookName]);
            $hookRetroCallable = is_callable([$pluginInstance, 'hook' . $retroHookName]);

            if (($hookCallable || $hookRetroCallable) && Plugin::preCall($pluginInstance->name)) {

                $hookArgs['altern'] = ++$altern;

                if ($usePush && isset($pluginInstance->push_filename) && file_exists($pluginInstance->push_filename)) {

                    Tools::waitUntilFileIsModified($pluginInstance->push_filename, $pluginInstance->push_time_limit);
                }

                if ($hookCallable) {

                    $display = $this->coreCallHook($pluginInstance, 'hook' . $hookName, $hookArgs);
                } else

                if ($hookRetroCallable) {

                    $display = $this->coreCallHook($pluginInstance, 'hook' . $retroHookName, $hookArgs);
                }

                if ($arrayReturn) {
                    $output[$pluginInstance->name] = $display;
                } else

                if ($objectReturn) {
                    $return = $display;
                } else {
                    $output = $display;
                }

                if (_EPH_DEBUG_PROFILING_ || _EPH_ADMIN_DEBUG_PROFILING_) {

                    if (!empty($perfs) && is_array($perfs)) {
                        $perfs[$hookName] = [
                            'time'   => round(microtime(true) - $time_start, 3),
                            'memory' => $this->memoryStart - memory_get_usage(true),
                        ];

                    } else {

                        if (!is_array($perfs)) {
                            $perfs = [];
                        }

                        $perfs[$hookName] = [
                            'time'   => round(microtime(true) - $time_start, 3),
                            'memory' => $this->memoryStart - memory_get_usage(true),
                        ];
                    }

                    if (!empty($plugperfs) && is_array($plugperfs)) {
                        $plugperfs[$pluginInstance->name] = [
                            'time'   => round(microtime(true) - $time_start, 3),
                            'memory' => $this->memoryStart - memory_get_usage(true),
                        ];

                    } else {

                        if (!is_array($perfs)) {
                            $plugperfs = [];
                        }

                        $plugperfs[$pluginInstance->name] = [
                            'time'   => round(microtime(true) - $time_start, 3),
                            'memory' => $this->memoryStart - memory_get_usage(true),
                        ];
                    }

                    $this->_session->set('HookPerformance', $perfs);
                    $this->_session->set('pluginPerformance', $plugperfs);

                }

            }

        }

        return $output;
    }

    public function getHookPluginExecList($hookName = null) {

        $context = Context::getContext();
        $list = null;

        if ($this->context->cache_enable && is_object($this->context->cache_api)) {
            $cacheId = 'hook_plugin_exec_list_' . $hookName . ((isset($this->context->user->id)) ? '_' . $this->context->user->id : '');
            $value = $this->context->cache_api->getData($cacheId, 3600);
            $temp = empty($value) ? null : Tools::jsonDecode($value, true);

            if (!empty($temp)) {
                $list = $temp;
            }

        }

        if (is_null($list) || $hookName == 'displayPayment' || $hookName == 'displayPaymentEU') {
            $frontend = true;
            $groups = [];
            $useGroups = Group::isFeatureActive();

            if (isset($context->employee)) {
                $frontend = false;
            } else {
                // Get groups list

                if ($useGroups) {

                    if (isset($this->context->user) && $this->context->user->isLogged()) {
                        $groups = $context->user->getGroups();
                    } else

                    if (isset($this->context->user) && $this->context->user->isLogged(true)) {
                        $groups = [(int) $this->context->phenyxConfig->get('EPH_GUEST_GROUP')];
                    } else {
                        $groups = [(int) $this->context->phenyxConfig->get('EPH_UNIDENTIFIED_GROUP')];
                    }

                }

            }

            // SQL Request
            $sql = new DbQuery();
            $sql->select('h.`name` as hook, m.`id_plugin`, h.`id_hook`, m.`name` as plugin, h.`static`, hm.`position`');
            $sql->from('plugin', 'm');
            $sql->innerJoin('hook_plugin', 'hm', 'hm.`id_plugin` = m.`id_plugin`');
            $sql->innerJoin('hook', 'h', 'hm.`id_hook` = h.`id_hook`');
            $sql->where('m.enable_device & ' . (int) Context::getContext()->getDevice());
            $sql->where('m.active = 1');

            if ($hookName != 'displayPayment' && $hookName != 'displayPaymentEU') {
                $sql->where('h.`name` != "displayPayment" AND h.`name` != "displayPaymentEU"');
            }

            // For payment plugins, we check that they are available in the contextual country
            else

            if ($frontend) {

                if (Validate::isLoadedObject($this->context->country)) {
                    $sql->where('((h.`name` = "displayPayment" OR h.`name` = "displayPaymentEU") AND (SELECT `id_country` FROM `' . _DB_PREFIX_ . 'plugin_country` mc WHERE mc.`id_plugin` = m.`id_plugin` AND `id_country` = ' . (int) $context->country->id . '  LIMIT 1) = ' . (int) $this->context->country->id . ')');
                }

                if (Validate::isLoadedObject($this->context->currency)) {
                    $sql->where('((h.`name` = "displayPayment" OR h.`name` = "displayPaymentEU") AND (SELECT `id_currency` FROM `' . _DB_PREFIX_ . 'plugin_currency` mcr WHERE mcr.`id_plugin` = m.`id_plugin` AND `id_currency` IN (' . (int) $context->currency->id . ', -1, -2) LIMIT 1) IN (' . (int) $this->context->currency->id . ', -1, -2))');
                }

                if (Validate::isLoadedObject($this->context->cart)) {
                    $carrier = new Carrier($this->context->cart->id_carrier);

                    if (Validate::isLoadedObject($carrier)) {
                        $sql->where('((h.`name` = "displayPayment" OR h.`name` = "displayPaymentEU") AND (SELECT `id_reference` FROM `' . _DB_PREFIX_ . 'plugin_carrier` mcar WHERE mcar.`id_plugin` = m.`id_plugin` AND `id_reference` = ' . (int) $carrier->id_reference . ' LIMIT 1) = ' . (int) $carrier->id_reference . ')');
                    }

                }

            }

            if ($frontend) {

                if ($useGroups) {
                    $sql->leftJoin('plugin_group', 'mg', 'mg.`id_plugin` = m.`id_plugin`');

                    $sql->where('mg.`id_group` IN (' . implode(', ', $groups) . ')');

                }

            }

            $sql->groupBy('hm.id_hook, hm.id_plugin');
            $sql->orderBy('hm.`position`');

            $list = [];

            if ($result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql)) {

                foreach ($result as $row) {
                    $row['hook'] = strtolower($row['hook']);

                    if (!isset($list[$row['hook']])) {
                        $list[$row['hook']] = [];
                    }

                    $list[$row['hook']][] = [
                        'id_hook'   => $row['id_hook'],
                        'plugin'    => $row['plugin'],
                        'id_plugin' => $row['id_plugin'],
                        'position'  => $row['position'],
                        'static'    => $row['static'],
                    ];
                }

            }

        }

        if ($this->context->cache_enable && is_object($this->context->cache_api)) {
            $temp = $list === null ? null : Tools::jsonEncode($list);
            $this->context->cache_api->putData($cacheId, $temp);
        }

        // If hook_name is given, just get list of plugins for this hook

        if (!is_null($hookName) && !is_object($hookName)) {
            $retroHookName = strtolower($this->getRetroHookName($hookName));
            $hookName = strtolower($hookName);

            $return = [];
            $insertedPlugins = [];

            if (isset($list[$hookName])) {
                $return = $list[$hookName];
            }

            foreach ($return as $plugin) {
                $insertedPlugins[] = $plugin['id_plugin'];
            }

            if (isset($list[$retroHookName])) {

                foreach ($list[$retroHookName] as $retroPluginCall) {

                    if (!in_array($retroPluginCall['id_plugin'], $insertedPlugins)) {
                        $return[] = $retroPluginCall;
                    }

                }

            }

            return (count($return) > 0 ? $return : false);
        } else {

            return $list;
        }

    }

    public function getRetroHookName($hookName) {

        $aliasList = $this->getHookAliasList();

        if (isset($aliasList[strtolower($hookName)])) {
            return $aliasList[strtolower($hookName)];
        }

        $retroHookName = array_search($hookName, $aliasList);

        if ($retroHookName === false) {
            return '';
        }

        return $retroHookName;
    }

    public function getHookAliasList() {

        $cacheId = 'hook_alias';

        if (!CacheApi::isStored($cacheId)) {
            $hookAliasList = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'hook_alias`');
            $hookAlias = [];

            if ($hookAliasList) {

                foreach ($hookAliasList as $ha) {
                    $hookAlias[strtolower($ha['alias'])] = $ha['name'];
                }

            }

            CacheApi::store($cacheId, $hookAlias);

            return $hookAlias;
        }

        return CacheApi::retrieve($cacheId);
    }

    public function getIdByName($hookName) {

        $hookName = strtolower($hookName);

        if (!Validate::isHookName($hookName)) {
            return false;
        }

        $cacheId = 'hook_idsbyname';

        if (!CacheApi::isStored($cacheId)) {
            // Get all hook ID by name and alias
            $hookIds = [];
            $db = Db::getInstance(_EPH_USE_SQL_SLAVE_);
            $result = $db->executeS(
                '
            SELECT `id_hook`, `name`
            FROM `' . _DB_PREFIX_ . 'hook`
            UNION
            SELECT `id_hook`, ha.`alias` AS name
            FROM `' . _DB_PREFIX_ . 'hook_alias` ha
            INNER JOIN `' . _DB_PREFIX_ . 'hook` h ON ha.name = h.name', false
            );

            while ($row = $db->nextRow($result)) {
                $hookIds[strtolower($row['name'])] = $row['id_hook'];
            }

            CacheApi::store($cacheId, $hookIds);
        } else {
            $hookIds = CacheApi::retrieve($cacheId);
        }

        return (isset($hookIds[$hookName]) ? $hookIds[$hookName] : false);
    }

    public function coreCallHook($plugin, $method, $params) {

        $r = $plugin->{$method}

        ($params);

        return $r;
    }

    public function getCachedHooks() {

        $hookSettings = json_decode($this->context->phenyxConfig->get('EPH_PAGE_CACHE_HOOKS'), true);

        if (!is_array($hookSettings)) {
            return [];
        }

        $cachedHooks = [];

        foreach ($hookSettings as $idPlugin => $hookArr) {
            $idPlugin = (int) $idPlugin;

            if ($idPlugin) {
                $pluginHooks = [];

                foreach ($hookArr as $idHook => $bool) {
                    $idHook = (int) $idHook;

                    if ($idHook && $bool) {
                        $pluginHooks[$idHook] = 1;
                    }

                }

                if ($pluginHooks) {
                    $cachedHooks[$idPlugin] = $pluginHooks;
                }

            }

        }

        return $cachedHooks;
    }

}
