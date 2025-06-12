<?php

/**
 * Class Translate
 *
 * @since 1.8.1.0
 */
class Translate {

    protected static $_plugins = [];

    protected static $_language;
    
    public static $instance;

    public $context;

    public $langadmin;

    public $langclass;

    public $langfront;

    public $langmail;

    public $langpdf;
    
    public $frontlang;
    
    public $translation;

    public function __construct($iso = null, $company = null, $affectContext = true) {
        
        $this->context = Context::getContext();
        if (!isset($this->context->phenyxConfig)) {
            $this->context->phenyxConfig = new Configuration();
            
        }
        
        if(!isset($this->context->company)) {
            if(is_null($company)) {
                $this->context->company = Company::initialize();
            } else {
                $this->context->company = $company;
            }
        }
        if (!isset($this->context->language)) {
            $this->context->language = Tools::jsonDecode(Tools::jsonEncode(Language::buildObject($this->context->phenyxConfig->get('EPH_LANG_DEFAULT')))); 
        }
        if(!isset($this->context->theme)) {
            $this->context->theme = new Theme((int) $this->context->company->id_theme);
        }
        
        if(is_null($iso)) {
            $iso = $this->context->language->iso_code;
        }

        global $_LANGADM, $_LANGOVADM, $_LANGCLASS, $_LANGFRONT, $_LANG, $_LANGMAIL, $_LANGPDF;

        if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/admin.php')) {
            require_once _EPH_TRANSLATIONS_DIR_ . $iso . '/admin.php';
        }
        if (file_exists(_EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/admin.php')) {

            include_once _EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/admin.php';
        }
        if(is_array($_LANGADM) && is_array($_LANGOVADM)) {
            $_LANGADM = array_merge(
                $_LANGADM,
                $_LANGOVADM
            );
        }

        if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/class.php')) {
            require_once _EPH_TRANSLATIONS_DIR_ . $iso . '/class.php';
        }

        if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/front.php')) {
            require_once _EPH_TRANSLATIONS_DIR_ . $iso . '/front.php';
        }

        if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/mail.php')) {
            require_once _EPH_TRANSLATIONS_DIR_ . $iso . '/mail.php';
        }

        if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/pdf.php')) {
            require_once _EPH_TRANSLATIONS_DIR_ . $iso . '/pdf.php';
        }
        $this->translation = Translation::getInstance();
        
        $this->fileExists();
        $this->frontlang = $_LANG;

        $this->langadmin = $_LANGADM;
        $this->langclass = $_LANGCLASS;
        $this->langfront = $_LANGFRONT;
        $this->langmail = $_LANGMAIL;
        $this->langpdf = $_LANGPDF;
        if($affectContext) {
            $this->context->translations = $this;
        }

    }
    
    public static function getInstance() {

        if (!static::$instance) {
            static::$instance = new Translate();
        }

        return static::$instance;
    }

    
    
    public function fileExists() {

		$var = '_LANG';
		$dir = $this->context->theme->path . 'lang/';
		$file = $this->context->language->iso_code . '.php';

		$$var = [];

		if (!file_exists($dir)) {

			if (!mkdir($dir, 0700)) {
				throw new PhenyxException('Directory ' . $dir . ' cannot be created.');
			}

		}
		
		@include $dir .  $file;

		return $$var;
	}

    public function getAdminTranslation($string, $class = 'Phenyx', $addslashes = false, $htmlentities = true, $sprintf = null) {

        $iso = $this->context->language->iso_code;
        if (str_contains($string, "\'")) {
            $string = str_replace("\'", "'", $string);
        }
        if (str_contains($string, "\‘")) {
            $string = str_replace("\‘", "'", $string);
        }
        if (str_contains($string, "‘")) {
            $string = str_replace("‘", "'", $string);
        }

        $key = md5($string);

        if (isset($this->context->translations->langadmin)) {

            if (file_exists(_EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/admin.php')) {

                include_once _EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/admin.php';
            }

            if (isset($this->context->translations->langadmin[$class . $key])) {

                $str = $this->context->translations->langadmin[$class . $key];

            } else if (isset($this->context->translations->langadmin['Phenyx' . $key])) {

                $str = $this->context->translations->langadmin['Phenyx' . $key];

            } else if (isset($_LANGOVADM[$class . $key])) {

                $str = $_LANGOVADM[$class . $key];

            } else {
                $str = $this->getGenericAdminTranslation($string, $this->context->translations->langadmin, $key);
            }

        } else {

            global $_LANGADMS, $_LANGADM, $_LANGOVADM;

            if (empty($_LANGADMS)) {
                $_LANGADMS = [];

                if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/admin.php')) {
                    include_once _EPH_TRANSLATIONS_DIR_ . $iso . '/admin.php';

                    if (is_array($_LANGADM)) {
                        $_LANGADMS = array_merge(
                            $_LANGADMS,
                            $_LANGADM
                        );
                    }

                }

            }

            if (file_exists(_EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/admin.php')) {

                include_once _EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/admin.php';

                if (isset($_LANGOVADM) && is_array($_LANGOVADM)) {
                    $_LANGADMS = array_merge(
                        $_LANGADMS,
                        $_LANGOVADM
                    );
                }

            }

            if (isset($this->context->translations->langadmin[$class . $key])) {

                $str = $this->context->translations->langadmin[$class . $key];

            } else

            if (isset($this->context->translations->langadmin['Phenyx' . $key])) {

                $str = $this->context->translations->langadmin['Phenyx' . $key];

            } else

            if (isset($_LANGOVADM[$class . $key])) {

                $str = $_LANGOVADM[$class . $key];

            } else {
                $str = $this->getGenericAdminTranslation($string, $this->context->translations->langfront, $key);
            }

        }

        if ($htmlentities) {
            $str = htmlspecialchars($str, ENT_QUOTES, 'utf-8');
        }

        $str = str_replace('"', '&quot;', $str);

        if ($sprintf !== null) {
            $str = $this->checkAndReplaceArgs($str, $sprintf);
        }

        return ($addslashes ? addslashes($str) : stripslashes($str));
    }

    public function getFrontTranslation($string, $class, $addslashes = false, $htmlentities = true, $sprintf = null) {
        
        $string = str_replace('"', '`', $string);
        $iso = $this->context->language->iso_code;
        if (str_contains($string, "\'")) {
            $string = str_replace("\'", "'", $string);
        }
        if (str_contains($string, "\‘")) {
            $string = str_replace("\‘", "'", $string);
        }
        $key = md5($string);


        if (isset($this->context->translations->langfront)) {

            if (isset($this->context->translations->langfront[$class . $key])) {
                $str = $this->context->translations->langfront[$class . $key];
            } else {
                $str = $this->getGenericFrontTranslation($string, $this->context->translations->langfront, $key);
            }

        } else {

            global $_LANGFRONTS, $_LANGFRONT, $_LANGOVFRONT;

            if (empty($_LANGFRONTS)) {
                $_LANGFRONTS = [];

                if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/front.php')) {
                    include_once _EPH_TRANSLATIONS_DIR_ . $iso . '/front.php';

                    if (is_array($_LANGFRONT)) {
                        $_LANGFRONTS = array_merge(
                            $_LANGFRONTS,
                            $_LANGFRONT
                        );
                    }

                }

                if (file_exists(_EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/front.php')) {

                    include_once _EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/front.php';
                }

            }

            if (isset($_LANGFRONTS[$class . $key])) {
                $str = $_LANGFRONTS[$class . $key];
            } else

            if (isset($_LANGOVFRONT[$class . $key])) {
                $str = $_LANGFRONT[$class . $key];
            } else {
                $str = $this->getGenericFrontTranslation($string, $_LANGOVFRONT, $key);
            }

        }

        if ($htmlentities) {
            $str = htmlspecialchars($str, ENT_QUOTES, 'utf-8');
        }

        $str = str_replace('"', '&quot;', $str);

        if ($sprintf !== null) {
            $str = $this->checkAndReplaceArgs($str, $sprintf);
        }

        return ($addslashes ? addslashes($str) : stripslashes($str));
    }

    public function getClassTranslation($string, $class, $addslashes = false, $htmlentities = true, $sprintf = null, $context = null) {

        if (is_null($string)) {
            return $string;
        }

        
        if (str_contains($string, "\'")) {
            $string = str_replace("\'", "'", $string);
        }
        if (str_contains($string, "\‘")) {
            $string = str_replace("\‘", "'", $string);
        }
        $key = md5($string);
        $iso = $this->context->language->iso_code;
        if (!isset($this->context->translations)) {
            $this->context->translations = new Translate($iso, $this->context->company);
        }

        if (isset($this->context->translations->langclass)) {

            if (isset($this->context->translations->langclass[$class . $key])) {

                $str = $this->context->translations->langclass[$class . $key];

            } else {
                $str = $this->getGenericFrontTranslation($string, $this->context->translations->langclass, $key);
            }

        } else {

            global $_LANGCLASSS, $_LANGCLASS, $_LANGOVCLASS;

            if (empty($_LANGCLASSS == null)) {
                $_LANGCLASSS = [];

                if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/class.php')) {
                    include_once _EPH_TRANSLATIONS_DIR_ . $iso . '/class.php';

                    if (is_array($_LANGCLASS)) {
                        $_LANGCLASSS = array_merge(
                            $_LANGCLASSS,
                            $_LANGCLASS
                        );
                    }

                }

                if (file_exists(_EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/class.php')) {

                    include_once _EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/class.php';
                }

            }

            if (isset($_LANGCLASSS[$class . $key])) {

                $str = $_LANGCLASSS[$class . $key];

            } else if (isset($_LANGOVCLASS[$class . $key])) {

                $str = $_LANGOVCLASS[$class . $key];

            } else {
                $str = $this->getGenericFrontTranslation($string, $_LANGCLASSS, $key);
            }

        }

        if ($htmlentities) {
            $str = htmlspecialchars($str, ENT_QUOTES, 'utf-8');
        }

        $str = str_replace('"', '&quot;', $str);

        if ($sprintf !== null) {
            $str = $this->checkAndReplaceArgs($str, $sprintf);
        }

        return ($addslashes ? addslashes($str) : stripslashes($str));
    }

    public function getPluginTranslation($plugin, $string, $source, $sprintf = null, $js = false, $context = null) {
        
        if (str_contains($string, "\'")) {
            $string = str_replace("\'", "'", $string);
        }
        if (str_contains($string, "\‘")) {
            $string = str_replace("\‘", "'", $string);
        }
        if (str_contains($string, "‘")) {
            $string = str_replace("‘", "'", $string);
        }
        $_PLUGIN = [];

        if (empty($string)) {
            return $string;
        }

        $name = $plugin instanceof Plugin ? $plugin->name : $plugin;
        
        if (!isset($this->context->theme)) {
            $this->context->company = Company::initialize();
            $this->context->theme = new Theme((int) $this->context->company->id_theme);
        }

        
        $theme = $this->context->theme->directory;
        if (!is_null($this->context->theme->plugin)) {
            $path = _EPH_PLUGIN_DIR_ . $this->context->theme->plugin . '/views/themes/' . $theme . '/';
        } else {
            $path = _SHOP_ROOT_DIR_ . '/themes/' . $theme . '/';
        }

        if (isset($this->context->language)) {
            $filesByPriority = [
                $path . 'plugins/' . $name . '/translations/' . $this->context->language->iso_code . '.php',
                _EPH_TRANSLATIONS_DIR_ . $this->context->language->iso_code . '/admin.php',
                _EPH_PLUGIN_DIR_ . $name . '/translations/' . $this->context->language->iso_code . '.php',
                _EPH_SPECIFIC_PLUGIN_DIR_ . $name . '/translations/' . $this->context->language->iso_code . '.php',
            ];
            
            foreach ($filesByPriority as $file) {

                if (file_exists($file)) {    
                    include($file);
                    if(isset($_PLUGINS) && is_array($_PLUGINS)) {
                        $_PLUGIN = array_merge(
                            $_PLUGIN,
                            $_PLUGINS
                        
                        );
                        
                    }

                }

            }
            
            $_PLUGINS = $_PLUGIN;

        }
        

        $string = preg_replace("/\\\*'/", "\'", $string);
        $key = md5($string);
                
        $ret = null;
      
        if ($_PLUGINS == null) {
             if ($sprintf !== null) {
                 $string = $this->checkAndReplaceArgs($string, $sprintf);
             }

             return str_replace('"', '&quot;', $string);
        }

        $currentKey = trim(strtolower('<{' . $name . '}' . $theme . '>' . $source) . '_' . $key);
        $defaultKey = trim(strtolower('<{' . $name . '}ephenyx>' . $source) . '_' . $key);
        $PhenyxShopKey = trim(strtolower('<{' . $name . '}phenyxshop>' . $source) . '_' . $key);   
        $PhenyxShopKey2 = trim(strtolower('<{' . $name . '}phenyxshop>' . $source) .  $key); 
        
        if ('controller' == substr($source, -10, 10)) {
            $file = substr($source, 0, -10);
            $currentKeyFile = strtolower('<{' . $name . '}' . $theme . '>' . $file) . '_' . $key;
            $defaultKeyFile = strtolower('<{' . $name . '}ephenyx>' . $file) . '_' . $key;
            $PhenyxShopKeyFile = strtolower('<{' . $name . '}phenyxshop>' . $file) . '_' . $key;
        }

        if (isset($currentKeyFile) && !empty($_PLUGINS[$currentKeyFile])) {
            $ret = stripslashes($_PLUGINS[$currentKeyFile]);
        } else if (isset($defaultKeyFile) && !empty($_PLUGINS[$defaultKeyFile])) {
            $ret = stripslashes($_PLUGINS[$defaultKeyFile]);
        } else if (isset($PhenyxShopKeyFile) && !empty($_PLUGINS[$PhenyxShopKeyFile])) {
            $ret = stripslashes($_PLUGINS[$PhenyxShopKeyFile]);
        } else if (!empty($_PLUGINS[$currentKey])) {
            $ret = stripslashes($_PLUGINS[$currentKey]);
            if ($sprintf !== null) {
               $ret = $this->checkAndReplaceArgs($ret, $sprintf);
            }
            return $ret;
        } else if (!empty($_PLUGINS[$defaultKey])) {
            $ret = stripslashes($_PLUGINS[$defaultKey]);
            if ($sprintf !== null) {
                $ret = $this->checkAndReplaceArgs($ret, $sprintf);
            }
            return $ret;
        } else if (!empty($_PLUGINS[$PhenyxShopKey])) {
            $ret = stripslashes($_PLUGINS[$PhenyxShopKey]);
            if ($sprintf !== null) {
               $ret = $this->checkAndReplaceArgs($ret, $sprintf);
            }
            return $ret;
        } else if (!empty($_PLUGINS[$PhenyxShopKey2])) {
			$ret = stripslashes($_PLUGINS[$PhenyxShopKey2]);
            if ($sprintf !== null) {
                $ret = $this->checkAndReplaceArgs($ret, $sprintf);
            }
            return $ret;
        } else if (!empty($_PLUGINS)) {

			foreach ($_PLUGINS as $k => $value) {
                if (str_ends_with($k, $key) && !empty($value)) {
                    $ret = stripslashes($value);
                }
            }

        } else if (!empty($_LANGADM)) {
			$ret = stripslashes($this->getGenericAdminTranslation($string, $_LANGADM, $key));
        } else if (is_null($ret)) {
			$ret = stripslashes($string);
        }

        if ($sprintf !== null) {
            $ret = $this->checkAndReplaceArgs($ret, $sprintf);
        }

        if ($js && !is_null($ret)) {
            $ret = addslashes($ret);
        } else if (!is_null($ret)) {
			$ret = htmlspecialchars($ret, ENT_COMPAT, 'UTF-8');
        }

        return $ret;

        
    }
    
    public function getPdfTranslation($string, $file, $sprintf = null, $context = null) {

        
        if (str_contains($string, "\'")) {
            $string = str_replace("\'", "'", $string);
        }
        if (str_contains($string, "\‘")) {
            $string = str_replace("\‘", "'", $string);
        }
        $key = md5($string);
        $iso = $this->context->language->iso_code;

        if (!isset($this->context->translations)) {
             $this->context->translations = new Translate($iso, $this->context->company);
        }

        if (isset($this->context->translations->langpdf)) {
            $str = (array_key_exists($file . $key, $this->context->translations->langpdf) ? $this->context->translations->langpdf[$file . $key] : $string);
        } else {

            global $_LANGPDFS, $_LANGPDF;

            if (empty($_LANGPDFS)) {
                $_LANGPDFS = [];
                $overrideI18NFile = _EPH_THEME_DIR_ . 'pdf/lang/' . $iso . '.php';
                $i18NFile = _EPH_TRANSLATIONS_DIR_ . $iso . '/pdf.php';

                if (file_exists($overrideI18NFile)) {
                    $i18NFile = $overrideI18NFile;
                }

                if (!include ($i18NFile)) {
                    $this->l(sprintf('Cannot include PDF translation language file : %s', $i18NFile));
                }

                if (is_array($_LANGPDF)) {
                    $_LANGPDFS = array_merge(
                        $_LANGPDFS,
                        $_LANGPDF
                    );
                }

            }

            if (!isset($_LANGPDFS) || !is_array($_LANGPDFS)) {
                return str_replace('"', '&quot;', $string);
            }

            $str = (array_key_exists($file . $key, $_LANGPDFS) ? $_LANGPDFS[$file . $key] : $string);
        }

        if ($sprintf !== null) {
            $str = $this->checkAndReplaceArgs($str, $sprintf);
        }

        return $str;
    }

    public function getMailsTranslation($string, $file, $sprintf = null, $context = null) {
        
        if (str_contains($string, "\'")) {
            $string = str_replace("\'", "'", $string);
        }
        if (str_contains($string, "\‘")) {
            $string = str_replace("\‘", "'", $string);
        }
        $key = md5($string);
        $iso = $this->context->language->iso_code;

        if (!isset($this->context->translations)) {
            $this->context->translations = new Translate($iso, $this->context->company);
        }

        if (isset($this->context->translations->langmail)) {
            $str = (array_key_exists($file . $key, $this->context->translations->langmail) ? $this->context->translations->langmail[$file . $key] : $string);
        } else {
            $_LANGMAILS = [];
            global $_LANGMAIL;

            $i18NFile = _EPH_TRANSLATIONS_DIR_ . $iso . '/mail.php';
            $this->context->_hook->exec('actionMailsTranslate', ['iso' => $iso]);

            if (!include ($i18NFile)) {
                $this->l(sprintf('Cannot include PDF translation language file : %s', $i18NFile));
            }

            $_LANGMAILS = !empty($_LANGMAIL) ? $_LANGMAILS + $_LANGMAIL : $_LANGMAIL;

            if (!isset($_LANGMAILS) || !is_array($_LANGMAILS)) {
                return str_replace('"', '&quot;', $string);
            }

            $str = (array_key_exists($file . $key, $_LANGMAILS) ? $_LANGMAILS[$file . $key] : $string);
        }

        if ($sprintf !== null) {
            $str = $this->checkAndReplaceArgs($str, $sprintf);
        }

        return $str;
    }

    public function checkAndReplaceArgs($string, $args) {

        if(!is_null($string)) {
        if (preg_match_all('#(?:%%|%(?:[0-9]+\$)?[+-]?(?:[ 0]|\'.)?-?[0-9]*(?:\.[0-9]+)?[bcdeufFosxX])#', $string, $matches) && !is_null($args)) {

            if (!is_array($args)) {
                $args = [$args];
            }

            return vsprintf($string, $args);
        }
        }

        return $string;
    }

    public function getGenericAdminTranslation($string, &$langArray, $key = null) {

        $string = preg_replace("/\\\*'/", "\'", $string);

        if (is_null($key)) {
            $key = md5($string);
        }

        if (isset($langArray['AdminController' . $key])) {
            $str = $langArray['AdminController' . $key];
        } else

        if (isset($langArray['Helper' . $key])) {
            $str = $langArray['Helper' . $key];
        } else

        if (isset($langArray['AdminTab' . $key])) {
            $str = $langArray['AdminTab' . $key];
        } else {
            // note in 1.5, some translations has moved from AdminXX to helper/*.tpl
            $str = $this->getDbTranslation($string);
        }

        return $str;
    }
    
    public function getDbTranslation($string) {
        
        $javareturn = $this->context->_session->get('getExistingTranslationByIso_'.$this->context->language->iso_code);
        if(is_array($javareturn) && array_key_exists($string, $javareturn)) {
            return $javareturn[$string];
        }
        
        $trad = $this->translation->getExistingTranslation($this->context->language->iso_code, $string);
        if(!empty($trad)) {
            $str = $trad;
        } else {
            $str = $string;
        }
        
        return $str;
        
    }

    public function getGenericFrontTranslation($string, &$langArray, $key = null) {

        if (str_contains($string, "\'")) {
            $string = str_replace("\'", "'", $string);
        }
        if (str_contains($string, "\‘")) {
            $string = str_replace("\‘", "'", $string);
        }

        if (is_null($key)) {
            $key = md5($string);
        }

        if (isset($langArray['FrontController' . $key])) {
            $str = $langArray['FrontController' . $key];
        } else {
            // note in 1.5, some translations has moved from AdminXX to helper/*.tpl
            $str = $string;
        }

        $extra = null;
        $extra = Context::getContext()->_hook->exec('actionGenericFrontTranslation', ['langArray' => $langArray, 'key' => $key]);

        if (!is_null($extra)) {
            $str = $extra;
        }

        return $str;
    }

    public function smartyPostProcessTranslation($string, $params) {

        return $this->postProcessTranslation($string, $params);
    }

    public function postProcessTranslation($string, $params) {

        // If tags were explicitely provided, we want to use them *after* the translation string is escaped.

        if (!empty($params['tags'])) {

            foreach ($params['tags'] as $index => $tag) {
                // Make positions start at 1 so that it behaves similar to the %1$d etc. sprintf positional params
                $position = $index + 1;
                // extract tag name
                $match = [];

                if (preg_match('/^\s*<\s*(\w+)/', $tag, $match)) {
                    $opener = $tag;
                    $closer = '</' . $match[1] . '>';

                    $string = str_replace('[' . $position . ']', $opener, $string);
                    $string = str_replace('[/' . $position . ']', $closer, $string);
                    $string = str_replace('[' . $position . '/]', $opener . $closer, $string);
                }

            }

        }

        return $string;
    }

    public static function ppTags($string, $tags) {

        return $this->postProcessTranslation($string, ['tags' => $tags]);
    }

    public function getInstallerTranslation($string, $class, $addslashes = false, $htmlentities = true, $sprintf = null) {
        
        if (str_contains($string, "\'")) {
            $string = str_replace("\'", "'", $string);
        }
        if (str_contains($string, "\‘")) {
            $string = str_replace("\‘", "'", $string);
        }

        global $_LANGINSTALL;

        if ($_LANGINSTALL == null) {

            $iso = $this->context->language->iso_code;
           
            if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/front.php')) {
                include_once _EPH_TRANSLATIONS_DIR_ . $iso . '/front.php';
            }

            if (file_exists(_EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/front.php')) {

                include_once _EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/front.php';
            }

        }

        $string = preg_replace("/\\\*'/", "\'", $string);
        $key = md5($string);

        if (isset($_LANGFRONT[$class . $key])) {
            $str = $_LANGFRONT[$class . $key];
        } else

        if (isset($_LANGOVFRONT[$class . $key])) {
            $str = $_LANGFRONT[$class . $key];
        } else {
            $str = $this->getGenericFrontTranslation($string, $_LANGOVFRONT, $key);
        }

        if ($htmlentities) {
            $str = htmlspecialchars($str, ENT_QUOTES, 'utf-8');
        }

        $str = str_replace('"', '&quot;', $str);

        if ($sprintf !== null) {
            $str = $this->checkAndReplaceArgs($str, $sprintf);
        }

        return ($addslashes ? addslashes($str) : stripslashes($str));
    }

}
