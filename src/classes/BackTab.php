<?php

/**
 * Class BackTab
 *
 * @since 1.9.1.0
 */
class BackTab extends PhenyxObjectModel {

    protected static $_getIdFromClassName = null;

    protected static $_cache_back_tab = [];

    protected static $_tabAccesses = [];

    public $generated;

    public $name;

    public $function;

    public $plugin;

    public $class_name = null;

    public $id_parent;

    public $position;

    public $has_divider;

    public $active = true;

    public $master;

    public $is_global;

    public $accesses;

    protected static $instance;

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'back_tab',
        'primary'   => 'id_back_tab',
        'multilang' => true,
        'fields'    => [
            'class_name'      => ['type' => self::TYPE_STRING, 'size' => 64],
            'id_parent'       => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'position'        => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'function'        => ['type' => self::TYPE_STRING, 'size' => 64],
            'plugin'          => ['type' => self::TYPE_STRING],
            'has_divider'     => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'is_global'       => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'active'          => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'master'          => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],

            /* Lang fields */
            'generated'       => ['type' => self::TYPE_BOOL, 'lang' => true],
            'name'            => ['type' => self::TYPE_STRING, 'lang' => true],
        ],
    ];

    public function __construct($id = null, $full = true, $idLang = null) {

        parent::__construct($id, $idLang);

        if ($this->id) {

            $this->accesses = $this->getAccesses();
        }

    }
    
    public static function buildObject( $id, $idLang = null, $className = null) {
        
        $objectData = parent::buildObject( $id, $idLang, $className);
        $objectData['parent_class'] = self::getParentClass($objectData['id_parent']);
		       
        return Tools::jsonDecode(Tools::jsonEncode($objectData));
    }
    
    public static function getParentClass($id_parent) {
        
        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('class_name')
                ->from('back_tab')
                ->where('id_back_tab = ' . $id_parent)
        );
    }

    public static function getInstance($id = null, $idLang = null) {

        if (!BackTab::$instance) {
            BackTab::$instance = new BackTab($id, $idLang);
        }

        return BackTab::$instance;
    }

    public function getAccesses() {

        
        $profiles = Profile::getProfiles($this->context->language->id);
        $accesses = [];

        foreach ($profiles as $profile) {

            if ($profile['id_profile'] == 1) {
                continue;
            }

            $accesses[$profile['id_profile']] = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('`view`, `add`, `edit`, `delete`')
                    ->from('employee_access')
                    ->where('`id_profile` = ' . (int) $profile['id_profile'])
                    ->where('`id_back_tab` = ' . (int) $this->id)
            );

        }

        return $accesses;
    }

    public static function getCurrentTabId() {

        $idTab = BackTab::getIdFromClassName(Tools::getValue('controller'));

        if (empty($idTab)) {
            $idTab = BackTab::getIdFromClassName(Tools::getValue('BackTab'));
        }

        return $idTab;
    }
    
    public static function getIdBackTabByClass($controller) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('id_back_tab')
                ->from('back_tab')
                ->where('class_name = \'' . pSQL($controller) . '\'')
        );
    }

    public static function getCurrentParentId() {

        $cacheId = 'getCurrentParentId_' . mb_strtolower(Tools::getValue('controller'));

        if (!CacheApi::isStored($cacheId)) {
            $value = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`id_parent`')
                    ->from('back_tab')
                    ->where('LOWER(`class_name`) = \'' . pSQL(mb_strtolower(Tools::getValue('controller'))) . '\'')
            );

            if (!$value) {
                $value = -1;
            }

            CacheApi::store($cacheId, $value);

            return $value;
        }

        return CacheApi::retrieve($cacheId);
    }

    public static function getIdFromClassName($className) {

        if (!is_null($className)) {
            $className = strtolower($className);
        }
        $context = Context::getContext();        
        $cache = $context->cache_api;
        if($context->cache_enable && is_object($context->cache_api)) {
           $value = $cache->getData('idTabfrom_'.$className, 864000);
           $temp = empty($value) ? null : Tools::jsonDecode($value, true);
           if(!empty($temp)) {
               return $temp;
           }
        }

        if (static::$_getIdFromClassName === null) {
            static::$_getIdFromClassName = [];
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('`id_back_tab`, `class_name`')
                    ->from('back_tab'),
                true,
                false
            );

            if (is_array($result)) {

                foreach ($result as $row) {
                    static::$_getIdFromClassName[strtolower((string) $row['class_name'])] = $row['id_back_tab'];
                }

            }

        }
        $result = (isset(static::$_getIdFromClassName[$className]) ? (int) static::$_getIdFromClassName[$className] : false);
        if($context->cache_enable && is_object($context->cache_api)) {
            $temp = $result === null ? null : Tools::jsonEncode($result);
            $cache->putData('idTabfrom_'.$className, $temp);
        }	

        return $result;
    }

    public function getBrothers() {

        $back_tab = [];
        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('t.id_back_tab, t.position, tl.`name`')
                ->from('back_tab', 't')
                ->leftJoin('back_tab_lang', 'tl', 'tl.`id_lang` = ' . $this->context->language->id . ' AND tl.`id_back_tab` = ' . $this->id_parent)
                ->where('id_parent = ' . $this->id_parent)
                ->orderBy('t.`position` ASC')
        );

        foreach ($result as &$row) {

            $back_tab[] = [
                'position' => $row['position'],
                'name'     => sprintf($this->l('Position (%s) on %s'), $row['position'], $row['name']),
            ];

        }

        return $back_tab;
    }

    public function getChildrens() {

        $back_tab = [];
        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('t.id_back_tab, t.position, tl.`name`')
                ->from('back_tab', 't')
                ->leftJoin('back_tab_lang', 'tl', 't.`id_back_tab` = tl.`id_back_tab` AND tl.`id_lang` = 1')
                ->where('id_parent = ' . $this->id)
                ->orderBy('t.`position` ASC')
        );

        foreach ($result as &$row) {

            $back_tab[] = [
                'position' => $row['position'],
                'name'     => sprintf($this->l('Position (%s)'), $row['position']),
            ];

        }

        return $back_tab;
    }

    public static function getChlidren($idParent) {

        $back_tab = [];
        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('t.id_back_tab, t.id_parent, tl.`name`')
                ->from('back_tab', 't')
                ->leftJoin('back_tab_lang', 'tl', 't.`id_back_tab` = tl.`id_back_tab` AND tl.`id_lang` = 1')
                ->where('id_parent = ' . $idParent)
                ->orderBy('t.`position` ASC')
        );

        foreach ($result as &$row) {
            $row['children'] = self::getChlidren($row['id_back_tab']);
            $back_tab[] = $row;

        }

        return $back_tab;
    }

    public static function buildSelect($back_tab, $idParent) {

        $select = '';

        foreach ($back_tab as $key => $value) {

            foreach ($value['children'] as $child) {
                $select .= '<option value="' . $child['id_back_tab'] . '" ';

                if ($child['id_back_tab'] == $idParent) {
                    $select .= 'selected="selected"';
                }

                $select .= '>' . $value['name'] . ' > ' . $child['name'] . '</option>';

            }

        }

        return $select;
    }

    public function getBackTabSelects($idParent = null) {

        $select = '';

        $select .= '<select name="id_parent" id="id_parent">';
        $select .= '<option value="-1">' . $this->l('Invisible') . '</option>';
        $select .= '<option value="1" ';

        if ($idParent == 1) {
            $select .= 'selected="selected"';
        }

        $select .= '>' . $this->l('Home') . '</option>';
        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('t.id_back_tab, t.id_parent, tl.`name`')
                ->from('back_tab', 't')
                ->leftJoin('back_tab_lang', 'tl', 't.`id_back_tab` = tl.`id_back_tab` AND tl.`id_lang`  = ' . (int) $this->context->language->id)
                ->where('id_parent = 1')
                ->orderBy('t.`position` ASC')
        );

        if (is_array($result)) {

            foreach ($result as &$row) {
                $row['children'] = BackTab::getChlidren($row['id_back_tab']);
                $back_tab[$row['id_back_tab']] = $row;
            }

            foreach ($back_tab as $key => $value) {
                $select .= '<option value="' . $value['id_back_tab'] . '" ';

                if ($value['id_back_tab'] == $idParent) {
                    $select .= 'selected="selected"';
                }

                $select .= '>' . $value['name'] . '</option>';

                foreach ($value['children'] as $child) {
                    $select .= '<option value="' . $child['id_back_tab'] . '" ';

                    if ($child['id_back_tab'] == $idParent) {
                        $select .= 'selected="selected"';
                    }

                    $select .= '>' . $value['name'] . ' > ' . $child['name'] . '</option>';

                    if (is_array($child['children']) && count($child['children'])) {
                        $select .= BackTab::buildSelect($child['children'], $idParent);
                    }

                }

            }

        }

        $select .= '</select>';
        return $select;
    }

    public static function getBackTabs($idLang, $idParent = null, $cache_enable = true) {
        
        $context = Context::getContext();        
        $cache = $context->cache_api;
        if($cache_enable && $context->cache_enable && is_object($context->cache_api)) {
           $value = $cache->getData('getBckTab_'.$idLang.'_'.$idParent, 864000);
           $temp = empty($value) ? null : Tools::jsonDecode($value, true);
           if(!empty($temp)) {
               return $temp;
           }
        }

        if (!isset(static::$_cache_back_tab[$idLang])) {
            static::$_cache_back_tab[$idLang] = [];

            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('t.*, tl.`name`')
                    ->from('back_tab', 't')
                    ->leftJoin('back_tab_lang', 'tl', 't.`id_back_tab` = tl.`id_back_tab` AND tl.`id_lang` = ' . (int) $idLang)
                    ->orderBy('t.`position` ASC')
            );

            if (is_array($result)) {

                foreach ($result as $row) {

                    if (!isset(static::$_cache_back_tab[$idLang][$row['id_parent']])) {
                        static::$_cache_back_tab[$idLang][$row['id_parent']] = [];
                    }

                    static::$_cache_back_tab[$idLang][$row['id_parent']][] = $row;

                }

            }

        }

        if ($idParent === null) {
            $arrayAll = [];

            foreach (static::$_cache_back_tab[$idLang] as $arrayParent) {
                $arrayAll = array_merge($arrayAll, $arrayParent);
            }
            if($cache_enable && $context->cache_enable && is_object($context->cache_api)) {
                $temp = $arrayAll === null ? null : Tools::jsonEncode($arrayAll);
                $cache->putData('getBckTab_'.$idLang.'_'.$idParent, $temp);
            }	

            return $arrayAll;
        }
        
        $result = (isset(static::$_cache_back_tab[$idLang][$idParent]) ? static::$_cache_back_tab[$idLang][$idParent] : []);
        if($context->cache_enable && is_object($context->cache_api)) {
            $temp = $result === null ? null : Tools::jsonEncode($result);
            $cache->putData('getBckTab_'.$idLang.'_'.$idParent, $temp);
        }	

        return $result;

    }

    public static function enablingForPlugin($plugin) {

        $tabs = BackTab::getCollectionFromPlugin($plugin);

        if (!empty($tabs)) {

            foreach ($tabs as $tab) {
                /** @var Tab $tab */
                $tab->active = 1;
                $tab->save();
            }

            return true;
        }

        return false;
    }

    public static function getCollectionFromPlugin($plugin, $idLang = null) {

        if (is_null($idLang)) {
            $idLang = Context::getContext()->language->id;
        }

        if (!Validate::isPluginName($plugin)) {
            return [];
        }

        $tabs = new PhenyxCollection('BackTab', (int) $idLang);
        $tabs->where('plugin', '=', $plugin);

        return $tabs;
    }

    public static function disablingForPlugin($plugin) {

        $tabs = BackTab::getCollectionFromPlugin($plugin);

        if (!empty($tabs)) {

            foreach ($tabs as $tab) {
                /** @var Tab $tab */
                $tab->active = 0;
                $tab->save();
            }

            return true;
        }

        return false;
    }

    public static function getInstanceFromClassName($className, $idLang = null) {

        $idTab = (int) BackTab::getIdFromClassName($className);

        return new BackTab($idTab, $idLang);
    }

    public static function checkTabRights($idTab) {
        if (Context::getContext()->employee->id_profile == _EPH_ADMIN_PROFILE_) {
            return true;
        }
		static::$_tabAccesses = [];
		$idProfil = Context::getContext()->employee->id_profile;
		
		if(!isset(static::$_tabAccesses[$idProfil][$idTab])) {
			if ($tabAccesses === null) {
            	$tabAccesses = Profile::getProfileAccesses($idProfil);
       		}
			if (isset($tabAccesses[(int) $idTab]['view'])) {
            	static::$_tabAccesses[$idProfil][$idTab] =  $tabAccesses[(int) $idTab]['view'];
        	}
			return static::$_tabAccesses[$idProfil][$idTab];
		}
		

        return static::$_tabAccesses[$idProfil][$idTab];
    }

    public static function recursiveTab($idTab, $tabs) {

        $adminTab = BackTab::getTab((int) Context::getContext()->language->id, $idTab);
        $tabs[] = $adminTab;

        if ($adminTab['id_parent'] > 0) {
            $tabs = BackTab::recursiveTab($adminTab['id_parent'], $tabs);
        }

        return $tabs;
    }

    public static function getTab($idLang, $idTab) {

        $cacheId = 'BackTab::getTab_' . (int) $idLang . '-' . (int) $idTab;

        if (!CacheApi::isStored($cacheId)) {
            /* Tabs selection */
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('*')
                    ->from('back_tab', 't')
                    ->leftJoin('back_tab_lang', 'tl', 't.`id_back_tab` = tl.`id_back_tab` AND tl.`id_lang` = ' . (int) $idLang)
                    ->where('t.`id_back_tab` = ' . (int) $idTab)
            );
            CacheApi::store($cacheId, $result);

            return $result;
        }

        return CacheApi::retrieve($cacheId);
    }

    public static function getTabByIdProfile($idParent, $idProfile) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('t.`id_back_tab`, t.`id_parent`, tl.`name`, a.`id_profile`')
                ->from('back_tab', 't')
                ->leftJoin('employee_access', 'a', 'a.`id_back_tab` = t.`id_back_tab`')
                ->leftJoin('topba_langr', 'tl', 't.`id_back_tab` = tl.`id_back_tab` AND tl.`id_lang` = ' . (int) Context::getContext()->language->id)
                ->where('a.`id_profile` = ' . (int) $idProfile)
                ->where('t.`id_parent` = ' . (int) $idParent)
                ->where('a.`view` = 1')
                ->where('a.`edit` = 1')
                ->where('a.`delete` = 1')
                ->where('a.`add` = 1')
                ->where('t.`id_parent` != 0')
                ->where('t.`id_parent` != -1')
                ->orderBy('t.`id_parent` ASC')
        );
    }

    public static function getNewLastPosition($idParent) {

        return (Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('IFNULL(MAX(`position`), 0) + 1')
                ->from('back_tab')
                ->where('`id_parent` = ' . (int) $idParent)
        ));
    }

    public static function initAccess(BackTab $Tab, $context = null) {

        if (!$context) {
            $context = Context::getContext();
        }

        if (!$context->employee || !$context->employee->id_profile) {
            $rights = 0;
        } else {
            $rights = $profile['id_profile'] == $context->employee->id_profile ? 1 : 0;
        }

        if ($Tab->id_parent == 0) {
            $rights = 1;
        }

        $profiles = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_profile`')
                ->from('profile')
                ->where('`id_profile` != 1')
        );

        $replace = [];
        $replace[] = [
            'id_profile'  => 1,
            'id_back_tab' => (int) $Tab->id,
            'view'        => 1,
            'add'         => 1,
            'edit'        => 1,
            'delete'      => 1,
        ];

        $accesses = $Tab->accesses;

        if (is_array($accesses) && count($accesses)) {

            foreach ($profiles as $profile) {

                if (array_key_exists($profile['id_profile'], $accesses)) {
                    $replace[] = [
                        'id_profile'  => (int) $profile['id_profile'],
                        'id_back_tab' => (int) $Tab->id,
                        'view'        => (int) $accesses[$profile['id_profile']]['view'],
                        'add'         => (int) $accesses[$profile['id_profile']]['add'],
                        'edit'        => (int) $accesses[$profile['id_profile']]['edit'],
                        'delete'      => (int) $accesses[$profile['id_profile']]['delete'],
                    ];
                } else {
                    $replace[] = [
                        'id_profile'  => (int) $profile['id_profile'],
                        'id_back_tab' => (int) $Tab->id,
                        'view'        => (int) $rights,
                        'add'         => (int) $rights,
                        'edit'        => (int) $rights,
                        'delete'      => (int) $rights,
                    ];
                }

            }

        } else {

            foreach ($profiles as $profile) {
                $replace[] = [
                    'id_profile'  => (int) $profile['id_profile'],
                    'id_back_tab' => (int) $Tab->id,
                    'view'        => 0,
                    'add'         => 0,
                    'edit'        => 0,
                    'delete'      => 0,
                ];
            }

        }

        return Db::getInstance()->insert('employee_access', $replace, false, true, Db::REPLACE);
    }

    public function save($nullValues = false, $autodate = true) {

        static::$_getIdFromClassName = null;

        return parent::save();
    }

    public function cleanPositions() {

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_back_tab`')
                ->from('back_tab')
                ->where('`id_parent` = ' . (int) $this->id_parent)
                ->orderBy('position')
        );
        $sizeof = count($result);

        for ($i = 0; $i < $sizeof; ++$i) {
            Db::getInstance()->update(
                'back_tab',
                [
                    'position' => $i,
                ],
                '`id_back_tab` = ' . (int) $result[$i]['id_back_tab']
            );
        }

        return true;
    }

    public function move($direction) {

        $nbTabs = BackTab::getNbTabs($this->id_parent);

        if ($direction != 'l' && $direction != 'r') {
            return false;
        }

        if ($nbTabs <= 1) {
            return false;
        }

        if ($direction == 'l' && $this->position <= 1) {
            return false;
        }

        if ($direction == 'r' && $this->position >= $nbTabs) {
            return false;
        }

        $newPosition = ($direction == 'l') ? $this->position - 1 : $this->position + 1;
        Db::getInstance()->execute(
            '
            UPDATE `' . _DB_PREFIX_ . 'tab` t
            SET position = ' . (int) $this->position . '
            WHERE id_parent = ' . (int) $this->id_parent . '
                AND position = ' . (int) $newPosition
        );
        $this->position = $newPosition;

        return $this->update();
    }

    public static function getNbTabs($idParent = null) {

        return (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('back_tab', 't')
                ->where(!is_null($idParent) ? 't.`id_parent` = ' . (int) $idParent : '')
        );
    }

    public function add($autoDate = true, $nullValues = false, $init = true, $position = null) {

        static::$_cache_back_tab = [];

        $this->cleanPositions();

        if (is_null($position)) {
            $this->position = BackTab::getNewLastPosition($this->id_parent);
        } else {
            $this->adjustPosition($position);
            $this->position = $position;
        }

        if (parent::add($autoDate, $nullValues)) {
            //forces cache to be reloaded
            static::$_getIdFromClassName = null;
            $this->context->_tools->generateTabs(false);
            if ($init) {
                return BackTab::initAccess($this);
            }

            $idMeta = Meta::getIdMetaByPage(strtolower($this->class_name));

            if (!$idMeta) {
                $meta = new Meta();
                $meta->controller = 'admin';
                $meta->page = strtolower($this->class_name);
                $meta->plugin = $this->plugin;

                foreach (Language::getLanguages(false) as $language) {
                    $meta->title[$language['id_lang']] = $this->name[$language['id_lang']];
                    $meta->url_rewrite[$language['id_lang']] = Tools::str2url($this->name[$language['id_lang']]);
                }

                $meta->add();
            }
            

            return true;
        }

        return false;
    }

    public function adjustPosition($position) {

        $menus = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('t.`id_back_tab`, t.`position`, t.`id_parent`')
                ->from('back_tab', 't')
                ->where('t.`id_parent` = ' . (int) $this->id_parent)
                ->where('t.`position` >= ' . (int) $position)
                ->orderBy('t.`position` ASC')
        );
        $i = $position + 1;

        foreach ($menus as $menu) {
            $sql = 'UPDATE `' . _DB_PREFIX_ . 'back_tab` SET `position` = ' . (int) $i . ' WHERE `id_back_tab` = ' . (int) $menu['id_back_tab'];
            $result = Db::getInstance()->execute($sql);
            $i++;

        }

    }

    public function delete() {

        Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'employee_access` WHERE `id_back_tab` = ' . (int) $this->id);
        
        $result = parent::delete();
        $this->context->_tools->generateTabs(false);
        return $result;
    }

    public function update($nullValues = true, $init = true) {
        
        static::$_cache_back_tab = [];

        if (parent::update($nullValues)) {
            $this->context->_tools->generateTabs(false);
            
            if ($init) {
                return BackTab::initAccess($this);
            }

            return true;

        }

    }

    public function getLastPosition() {

        return (Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('IFNULL(MAX(`position`), 0)')
                ->from('back_tab')
                ->where('`id_parent` = ' . (int) $this->id_parent)
        ));
    }

    public function updatePosition($way, $position) {

        if (!$res = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
            ->select('t.`id_back_tab`, t.`position`, t.`id_parent`')
            ->from('back_tab', 't')
            ->where('t.`id_parent` = ' . (int) $this->id_parent)
            ->orderBy('t.`position` ASC')
        )) {
            return false;
        }

        foreach ($res as $tab) {

            if ((int) $tab['id_back_tab'] == (int) $this->id) {
                $movedTab = $tab;
            }

        }

        if (!isset($movedTab) || !isset($position)) {
            return false;
        }

        $result = (Db::getInstance()->update(
            'back_tab',
            [

                'position' => ['type' => 'sql', 'value' => '`position` ' . ($way ? '- 1' : '+ 1')],
            ],
            '`position` ' . ($way ? '> ' . (int) $movedTab['position'] . ' AND `position` <= ' . (int) $position : '< ' . (int) $movedTab['position'] . ' AND `position` >= ' . (int) $position) . ' AND `id_parent`=' . (int) $movedTab['id_parent']
        )
            && Db::getInstance()->update(
                'back_tab',
                [
                    'position' => (int) $position,
                ],
                '`id_parent` = ' . (int) $movedTab['id_parent'] . ' AND `id_back_tab`=' . (int) $movedTab['id_back_tab']
            ));

        return $result;
    }

    public static function getPluginTabList() {

        $list = [];

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('t.`class_name`, t.`plugin`')
                ->from('back_tab', 't')
                ->where('t.`plugin` IS NOT NULL')
                ->where('t.`plugin` != ""')
        );

        if (is_array($result)) {

            foreach ($result as $detail) {
                $list[strtolower($detail['class_name'])] = $detail;
            }

        }

        return $list;
    }

    public static function getTabPluginsList($idTab) {

        $pluginsList = ['default_list' => [], 'slider_list' => []];
        $xmlTabPluginsList = false;

        if (file_exists(_EPH_ROOT_DIR_ . Plugin::CACHE_FILE_TAB_PLUGINS_LIST)) {
            $xmlTabPluginsList = @simplexml_load_file(_EPH_ROOT_DIR_ . Plugin::CACHE_FILE_TAB_PLUGINS_LIST);
        }

        $className = null;
        $displayType = 'default_list';

        if ($xmlTabPluginsList) {

            foreach ($xmlTabPluginsList->tab as $tab) {

                foreach ($tab->attributes() as $key => $value) {

                    if ($key == 'class_name') {
                        $className = (string) $value;
                    }

                }

                if (BackTab::getIdFromClassName((string) $className) == $idTab) {

                    foreach ($tab->attributes() as $key => $value) {

                        if ($key == 'display_type') {
                            $displayType = (string) $value;
                        }

                    }

                    foreach ($tab->children() as $plugin) {
                        $pluginsList[$displayType][(int) $plugin['position']] = (string) $plugin['name'];
                    }

                    ksort($pluginsList[$displayType]);
                }

            }

        }

        return $pluginsList;
    }

    public static function getClassNameById($idTab) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`class_name`')
                ->from('back_tab')
                ->where('`id_back_tab` = ' . (int) $idTab)
        );
    }

    public static function getmetroTabColors() {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('tabmetro_color')
        );
    }

}
