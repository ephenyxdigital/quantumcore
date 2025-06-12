<?php

/**
 * Class Theme
 *
 * @since 1.9.1.0
 */
class Theme extends PhenyxObjectModel {

    public $require_context = false;
    
    const CACHE_FILE_CUSTOMER_THEMES_LIST = '/app/xml/customer_themes_list.xml';
    const CACHE_FILE_MUST_HAVE_THEMES_LIST = '/app/xml/must_have_themes_list.xml';
    const UPLOADED_THEME_DIR_NAME = 'uploaded';

    // @codingStandardsIgnoreStart
    /** @var int access rights of created folders (octal) */
    public static $access_rights = 0775;
    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'theme',
        'primary' => 'id_theme',
        'fields'  => [
            'name'                 => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64, 'required' => true],
            'directory'            => ['type' => self::TYPE_STRING, 'validate' => 'isDirName', 'size' => 64, 'required' => true],
            'plugin'               => ['type' => self::TYPE_STRING, 'validate' => 'isPluginName', 'size' => 64],
            'responsive'           => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'default_left_column'  => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'default_right_column' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'product_per_page'     => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
        ],
    ];
    /** @var string $name */
    public $name;
    /** @var string $directory */
    public $directory;
    
    public $plugin;
    /** @var bool $responsive */
    public $responsive;
    /** @var int $default_left_column */
    public $default_left_column;
    /** @var int $default_right_column */
    public $default_right_column;
    /** @var int $product_per_page */
    public $product_per_page;
    
    public $path;
    
    public $localpath;
    
    public $css_theme;
    
    public $js_theme;
    
    public $img_theme;
    
    public $mail_theme;
    
    public $pdf_theme;
    
    public function __construct($id = null) {
        
        $this->className = get_class($this);
        $this->context = Context::getContext();
        if (!isset($this->context->language)) {
            $this->context->language = Tools::jsonDecode(Tools::jsonEncode(Language::buildObject($this->context->phenyxConfig->get('EPH_LANG_DEFAULT'))));
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

        //parent::__construct($id);
        if ($id) {
            $this->id = $id;
            $entityMapper = Adapter_ServiceLocator::get("Adapter_EntityMapper");
            $entityMapper->load($this->id, null, $this, $this->def, false);
            if(!empty($this->plugin)) {
                
                $this->path = _EPH_PLUGIN_DIR_ . $this->plugin . DIRECTORY_SEPARATOR.'views/themes/' . $this->directory . DIRECTORY_SEPARATOR;
                $this->localpath = DIRECTORY_SEPARATOR. 'includes/plugins/' . $this->plugin . DIRECTORY_SEPARATOR.'views/themes/' . $this->directory . DIRECTORY_SEPARATOR;
                $this->css_theme = $this->localpath.'css'. DIRECTORY_SEPARATOR;
                $this->js_theme = $this->localpath.'js'. DIRECTORY_SEPARATOR;
                $this->img_theme = $this->localpath.'img'. DIRECTORY_SEPARATOR;
                $this->mail_theme = $this->localpath.'mail'. DIRECTORY_SEPARATOR;
                $this->pdf_theme = $this->localpath.'pdf'. DIRECTORY_SEPARATOR;
                
            } else {
                $this->path = _SHOP_ROOT_DIR_ . _EPH_THEMES_DIR_ . $this->directory . DIRECTORY_SEPARATOR;
                $this->localpath = DIRECTORY_SEPARATOR. 'content' .  _EPH_THEMES_DIR_  . $this->directory . DIRECTORY_SEPARATOR;
                $this->css_theme = $this->localpath.'css'. DIRECTORY_SEPARATOR;
                $this->js_theme = $this->localpath.'js'. DIRECTORY_SEPARATOR;
                $this->img_theme = $this->localpath.'img'. DIRECTORY_SEPARATOR;
                $this->mail_theme = $this->localpath.'mail'. DIRECTORY_SEPARATOR;
                $this->pdf_theme = $this->localpath.'pdf'. DIRECTORY_SEPARATOR;
            }
            
            
		}
    }
    
    public static function buildObject($id, $id_lang = null, $className = null) {
        
        $objectData = parent::buildObject($id, $id_lang, $className);
        
        if ($id) {
            
            if(!empty($objectData['plugin'])) {
                
                $objectData['path'] = _EPH_PLUGIN_DIR_ . $objectData['plugin'] . DIRECTORY_SEPARATOR.'views/themes/' . $objectData['directory'] . DIRECTORY_SEPARATOR;
                $objectData['localpath'] = DIRECTORY_SEPARATOR. 'includes/plugins/' . $objectData['plugin'] . DIRECTORY_SEPARATOR.'views/themes/' . $objectData['directory'] . DIRECTORY_SEPARATOR;                
                $objectData['css_theme'] = $objectData['localpath'].'css'. DIRECTORY_SEPARATOR;
                $objectData['js_theme'] = $objectData['localpath'].'js'. DIRECTORY_SEPARATOR;
                $objectData['img_theme'] = $objectData['localpath'].'img'. DIRECTORY_SEPARATOR;                
                $objectData['mail_theme'] = $objectData['localpath'].'mail'. DIRECTORY_SEPARATOR;                
                $objectData['pdf_theme'] = $objectData['localpath'].'pdf'. DIRECTORY_SEPARATOR;
                
            } else {
                $objectData['path'] = _SHOP_ROOT_DIR_ . _EPH_THEMES_DIR_ . $objectData['directory'] . DIRECTORY_SEPARATOR;
                $objectData['localpath'] = DIRECTORY_SEPARATOR. 'content' .  _EPH_THEMES_DIR_  . $objectData['directory'] . DIRECTORY_SEPARATOR;
                $objectData['css_theme'] = $objectData['localpath'].'css'. DIRECTORY_SEPARATOR;
                $objectData['js_theme'] = $objectData['localpath'].'js'. DIRECTORY_SEPARATOR;
                $objectData['img_theme'] = $objectData['localpath'].'img'. DIRECTORY_SEPARATOR;
                $objectData['mail_theme'] = $objectData['localpath'].'mail'. DIRECTORY_SEPARATOR;
                $objectData['pdf_theme'] = $objectData['localpath'].'pdf'. DIRECTORY_SEPARATOR;
            }
            
            
		}
        
       
        return Tools::jsonDecode(Tools::jsonEncode($objectData));
    }    
    
    public static function hasStaticColumns($id, $page) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('IFNULL(`left_column`, `default_left_column`) AS `left_column`, IFNULL(`right_column`, `default_right_column`) AS `right_column`')
                ->from('theme', 't')
                ->leftJoin('theme_meta', 'tm', 't.`id_theme` = tm.`id_theme`')
                ->leftJoin('meta', 'm', 'm.`id_meta` = tm.`id_meta`')
                ->where('t.`id_theme` = ' . (int) $id)
                ->where('m.`page` = \'' . pSQL($page) . '\'')
        );
    }
    
    public static function getAllThemes($excludedIds = false) {

        $themes = new PhenyxCollection('Theme');

        if (is_array($excludedIds) && !empty($excludedIds)) {
            $themes->where('id_theme', 'notin', $excludedIds);
        }

        $themes->orderBy('name');

        return $themes;
    }

    public static function getAvailable($installedOnly = true) {

        static $dirlist = [];
        $availableTheme = [];

        if (empty($dirlist)) {
            $themes = scandir(_EPH_ALL_THEMES_DIR_);

            foreach ($themes as $theme) {

                if (is_dir(_EPH_ALL_THEMES_DIR_ . DIRECTORY_SEPARATOR . $theme) && $theme[0] != '.') {
                    $dirlist[] = $theme;
                }

            }

        }

        $themesDir = [];

        if ($installedOnly) {
            $themes = Theme::getThemes();

            foreach ($themes as $themeObj) {
                /** @var Theme $themeObj */
                $themesDir[] = $themeObj->directory;
            }

            foreach ($dirlist as $theme) {

                if (false !== array_search($theme, $themesDir)) {
                    $availableTheme[] = $theme;
                }

            }

        } else {
            $availableTheme = $dirlist;
        }

        return $availableTheme;
    }
    
    public static function getThemes() {

        $themes = new PhenyxCollection('Theme');
        $themes->orderBy('name');

        return $themes;
    }

    public static function getByDirectory($directory) {

        if (is_string($directory) && strlen($directory) > 0) {
            $idTheme = (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`id_theme`')
                    ->from('theme')
                    ->where('`directory` = \'' . pSQL($directory) . '\'')
            );

            return $idTheme ? new Theme($idTheme) : false;
        }

        return false;
    }

    public static function getThemeInfo($idTheme) {

        $theme = new Theme((int) $idTheme);
        $themeArr = [];

        $xmlTheme = $theme->loadConfigFile();

        if ($xmlTheme) {
            $themeArr['theme_id'] = (int) $theme->id;

            foreach ($xmlTheme->attributes() as $key => $value) {
                $themeArr['theme_' . $key] = (string) $value;
            }

            foreach ($xmlTheme->author->attributes() as $key => $value) {
                $themeArr['author_' . $key] = (string) $value;
            }

            if ($themeArr['theme_name'] == 'community-theme-default') {
                $themeArr['tc'] = Plugin::isEnabled('themeconfigurator');
            }

        } else {
            // If no xml we use data from database
            $themeArr['theme_id'] = (int) $theme->id;
            $themeArr['theme_name'] = $theme->name;
            $themeArr['theme_directory'] = $theme->directory;
        }

        return $themeArr;
    }

    public static function getNonInstalledTheme() {

        $installedThemeDirectories = Theme::getInstalledThemeDirectories();
        $notInstalledTheme = [];

        foreach (glob(_EPH_ALL_THEMES_DIR_ . '*', GLOB_ONLYDIR) as $themeDir) {
            $dir = basename($themeDir);

            if (!in_array($dir, $installedThemeDirectories)) {
                $xmlTheme = static::loadConfigFromFile(_EPH_ALL_THEMES_DIR_ . $dir . '/Config.xml', true);

                if (!$xmlTheme) {
                    $xmlTheme = static::loadConfigFromFile(_EPH_ALL_THEMES_DIR_ . $dir . '/config.xml', true);
                }

                if ($xmlTheme) {
                    $theme = [];

                    foreach ($xmlTheme->attributes() as $key => $value) {
                        $theme[$key] = (string) $value;
                    }

                    if (!empty($theme)) {
                        $notInstalledTheme[] = $theme;
                    }

                }

            }

        }

        return $notInstalledTheme;
    }

    public static function getInstalledThemeDirectories() {

        $list = [];
        $tmp = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`directory`')
                ->from('theme')
        );

        foreach ($tmp as $t) {
            $list[] = $t['directory'];
        }

        return $list;
    }
    
    public static function isInstalled($theme_name) {
        
        $idTheme = (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
            ->select('`id_theme`')
            ->from('theme')
            ->where('`name` = \'' . pSQL($theme_name) . '\'')
        );
        
        return $idTheme ? $idTheme : 0;
    }

    public function isUsed() {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('shop')
                ->where('`id_theme` = ' . (int) $this->id)
        );
    }

    public function add($autoDate = true, $nullValues = false) {

        return parent::add($autoDate, $nullValues);
    }

    public function updateMetas($metas, $fullUpdate = false) {

        if ($fullUpdate) {
            Db::getInstance()->delete('theme_meta', 'id_theme=' . (int) $this->id);
        }

        $values = [];

        if ($this->id > 0) {

            foreach ($metas as $meta) {

                if (!$fullUpdate) {
                    Db::getInstance()->delete('theme_meta', 'id_theme=' . (int) $this->id . ' AND id_meta=' . (int) $meta['id_meta']);
                }

                $values[] = [
                    'id_theme'     => (int) $this->id,
                    'id_meta'      => (int) $meta['id_meta'],
                    'left_column'  => (int) $meta['left'],
                    'right_column' => (int) $meta['right'],
                ];
            }

            Db::getInstance()->insert('theme_meta', $values);
        }

    }

    public function hasColumns($page) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('IFNULL(`left_column`, `default_left_column`) AS `left_column`, IFNULL(`right_column`, `default_right_column`) AS `right_column`')
                ->from('theme', 't')
                ->leftJoin('theme_meta', 'tm', 't.`id_theme` = tm.`id_theme`')
                ->leftJoin('meta', 'm', 'm.`id_meta` = tm.`id_meta`')
                ->where('t.`id_theme` = ' . (int) $this->id)
                ->where('m.`page` = \'' . pSQL($page) . '\'')
        );
    }

    public function hasColumnsSettings($page) {

        return (bool) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('m.`id_meta`')
                ->from('theme', 't')
                ->leftJoin('theme_meta', 'tm', 't.`id_theme` = tm.`id_theme`')
                ->leftJoin('meta', 'm', 'm.`id_meta` = tm.`id_meta`')
                ->where('t.`id_theme` = ' . (int) $this->id)
                ->where('m.`page` = \'' . pSQL($page) . '\'')
        );
    }

    public function hasLeftColumn($page = null) {

        return (bool) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('IFNULL(`left_column`, `default_left_column`)')
                ->from('theme', 't')
                ->leftJoin('theme_meta', 'tm', 't.`id_theme` = tm.`id_theme`')
                ->leftJoin('meta', 'm', 'm.`id_meta` = tm.`id_meta`')
                ->where('t.`id_theme` = ' . (int) $this->id)
                ->where('m.`page` = \'' . pSQL($page) . '\'')
        );
    }

    public function hasRightColumn($page = null) {

        return (bool) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('IFNULL(`right_column`, `default_right_column`)')
                ->from('theme', 't')
                ->leftJoin('theme_meta', 'tm', 't.`id_theme` = tm.`id_theme`')
                ->leftJoin('meta', 'm', 'm.`id_meta` = tm.`id_meta`')
                ->where('t.`id_theme` = ' . (int) $this->id)
                ->where('m.`page` = \'' . pSQL($page) . '\'')
        );
    }

    public function getMetas() {

        if (!Validate::isUnsignedId($this->id) || $this->id == 0) {
            return false;
        }

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('theme_meta')
                ->where('`id_theme` = ' . (int) $this->id)
        );
    }

    public function removeMetas() {

        if (!Validate::isUnsignedId($this->id) || $this->id == 0) {
            return false;
        }

        return Db::getInstance()->delete('theme_meta', 'id_theme = ' . (int) $this->id);
    }

    public function toggleResponsive() {

        // Object must have a variable called 'responsive'

        if (!method_exists($this, 'responsive')) {
            throw new PhenyxException('property "responsive" is missing in object ' . get_class($this));
        }

        // Update only responsive field
        $this->setFieldsToUpdate(['responsive' => true]);

        // Update active responsive on object
        $this->responsive = !(int) $this->responsive;

        // Change responsive to active/inactive
        return $this->update(false);
    }

    public function toggleDefaultLeftColumn() {

        if (!method_exists($this, 'default_left_column')) {
            throw new PhenyxException('property "default_left_column" is missing in object ' . get_class($this));
        }

        $this->setFieldsToUpdate(['default_left_column' => true]);

        $this->default_left_column = !(int) $this->default_left_column;

        return $this->update(false);
    }

    public function toggleDefaultRightColumn() {

        if (!method_exists($this, 'default_right_column')) {
            throw new PhenyxException('property "default_right_column" is missing in object ' . get_class($this));
        }

        $this->setFieldsToUpdate(['default_right_column' => true]);

        $this->default_right_column = !(int) $this->default_right_column;

        return $this->update(false);
    }

    public function getConfiguration() {

        $ob = $this->loadConfigFile();

        if ($ob) {
            // convert SimpleXMLElement to array
            return json_decode(json_encode($ob), true);
        }

        return [];
    }

    public function loadConfigFile($validate = false) {

        $path = _EPH_ROOT_DIR_ . '/app/xml/themes/' . $this->name . '.xml';

        if (!file_exists($path)) {
            $path = _EPH_ROOT_DIR_ . '/app/xml/themes/default.xml';
        }

        if (!file_exists($path)) {
            return false;
        }

        $xml = static::loadConfigFromFile($path, $validate);

        if ((string) $xml->attributes()->name !== $this->name) {
            return false;
        }

        return $xml;
    }

    public static function loadConfigFromFile($filePath, $validate) {

        if (file_exists($filePath)) {
            $content = @simplexml_load_file($filePath);

            if ($content && $validate && !static::validateConfigFile($content)) {
                return false;
            }

            return $content;
        }

        return false;
    }

    public static function validateConfigFile($xml) {

        if (!$xml) {
            return false;
        }

        if (!$xml['version'] || !$xml['name']) {
            return false;
        }

        foreach ($xml->variations->variation as $val) {

            if (!$val['name'] || !$val['directory'] || !$val['from'] || !$val['to']) {
                return false;
            }

        }

        foreach ($xml->plugins->plugin as $val) {

            if (!$val['action'] || !$val['name']) {
                return false;
            }

        }

        foreach ($xml->plugins->hooks->hook as $val) {

            if (!$val['plugin'] || !$val['hook'] || !$val['position']) {
                return false;
            }

        }

        return true;
    }

}
