<?php


namespace EphenyxDigital\QuantumCore;

use Language;
/**
 * Class Translate
 *
 * @since 1.8.1.0
 */

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
            $this->context->language = new Language($this->context->phenyxConfig->get('EPH_LANG_DEFAULT')); 
        }
        if(!isset($this->context->theme)) {
            $this->context->theme = new Theme((int) $this->context->company->id_theme);
        }
        
        if(is_null($iso)) {
            $iso = $this->context->language->iso_code;
        }

        global $_LANGADM, $_LANGOVADM, $_LANGCLASS, $_LANGOVCLASS, $_LANGFRONT, $_LANGOVFRONT, $_LANG, $_LANGMAIL, $_LANGPDF;

        // Reset all translation arrays before loading to prevent contamination
        // from previously loaded languages in the same PHP process.
        // Using include (not require_once) forces a fresh load every time.
        $_LANGADM   = [];
        $_LANGOVADM = [];
        $_LANGCLASS = [];
        $_LANGFRONT = [];
        $_LANGMAIL  = [];
        $_LANGPDF   = [];

        if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/admin.php')) {
            include _EPH_TRANSLATIONS_DIR_ . $iso . '/admin.php';
        }
        if (file_exists(_EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/admin.php')) {
            include _EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/admin.php';
        }
        if (is_array($_LANGADM) && is_array($_LANGOVADM)) {
            $_LANGADM = array_merge($_LANGADM, $_LANGOVADM);
        }

        if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/class.php')) {
            include _EPH_TRANSLATIONS_DIR_ . $iso . '/class.php';
        }

        // Note: plugin class.php translations are NOT aggregated here.
        // The canonical mechanism is PhenyxController::mergeLanguages($iso),
        // which consolidates every plugin's class/front/admin/mail/pdf file
        // into a single _EPH_TRANSLATIONS_DIR_/$iso/* file and sets the flag
        // CURENT_MERGE_LANG_$iso = 1 to avoid re-running.
        //
        // If new plugin translations are added later, the flag must be cleared
        // (or the language switched) so mergeLanguages picks the new files up.

        if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/front.php')) {
            include _EPH_TRANSLATIONS_DIR_ . $iso . '/front.php';
        }

        if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/mail.php')) {
            include _EPH_TRANSLATIONS_DIR_ . $iso . '/mail.php';
        }

        if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/pdf.php')) {
            include _EPH_TRANSLATIONS_DIR_ . $iso . '/pdf.php';
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

    /* ---------------------------------------------------------------------
     * Helpers internes — refactor 2026-05 : factorisation du lookup plugin
     * et du post-process commun aux 5 méthodes getXxxTranslation().
     *
     * loadPluginTranslations() remplace les 5 blocs include+merge dupliqués
     * et met les fichiers en cache mémoire pour éviter de re-stat le disque
     * à chaque appel l() / la(). Voir ARCHITECTURE_TRANSLATE.md.
     * ------------------------------------------------------------------- */

    /** @var array Cache process-wide : [pluginName|domain|iso] => dictionnaire */
    protected static $pluginCache = [];

    /** Mapping domaine logique → variable globale utilisée par les fichiers de traduction. */
    protected static $domainVars = [
        'admin' => '_LANGADM',
        'class' => '_LANGCLASS',
        'front' => '_LANGFRONT',
        'mail'  => '_LANGMAIL',
        'pdf'   => '_LANGPDF',
    ];

    /**
     * Charge (et met en cache) les traductions d'un plugin pour un domaine donné.
     * Cherche dans _EPH_PLUGIN_DIR_ puis _EPH_SPECIFIC_PLUGIN_DIR_ et fusionne.
     *
     * @param string $pluginName  ex: 'ph_ecommerce'
     * @param string $domain      'admin' | 'class' | 'front' | 'mail' | 'pdf'
     * @param string $iso         code ISO de la langue (ex: 'fr')
     * @return array              dictionnaire des traductions du plugin
     */
    protected function loadPluginTranslations($pluginName, $domain, $iso) {

        $cacheKey = $pluginName . '|' . $domain . '|' . $iso;

        if (isset(self::$pluginCache[$cacheKey])) {
            return self::$pluginCache[$cacheKey];
        }

        if (!isset(self::$domainVars[$domain])) {
            return self::$pluginCache[$cacheKey] = [];
        }

        $varName = self::$domainVars[$domain];
        $candidates = [
            _EPH_PLUGIN_DIR_          . $pluginName . '/translations/' . $iso . '/' . $domain . '.php',
            _EPH_SPECIFIC_PLUGIN_DIR_ . $pluginName . '/translations/' . $iso . '/' . $domain . '.php',
        ];

        $merged = [];

        foreach ($candidates as $file) {

            if (file_exists($file)) {
                // Reset systématique pour éviter la contamination d'un include précédent (Fix #16)
                $$varName = [];
                include $file;

                if (is_array($$varName)) {
                    $merged = array_merge($merged, $$varName);
                }

            }

        }

        return self::$pluginCache[$cacheKey] = $merged;
    }

    /**
     * Normalisation commune des chaînes en entrée des fonctions de traduction
     * (échappements et apostrophes typographiques).
     */
    protected function normalizeString($string) {

        if (!is_string($string)) {
            return $string;
        }

        if (str_contains($string, "\'")) {
            $string = str_replace("\'", "'", $string);
        }

        if (str_contains($string, "\‘")) {
            $string = str_replace("\‘", "'", $string);
        }

        if (str_contains($string, "‘")) {
            $string = str_replace("‘", "'", $string);
        }

        return $string;
    }

    /**
     * Post-traitement HTML : htmlspecialchars + quote escape + sprintf + (add|strip)slashes.
     * Utilisé par getAdminTranslation / getFrontTranslation / getClassTranslation.
     */
    protected function postProcessHtml($str, $addslashes, $htmlentities, $sprintf) {

        if ($htmlentities) {
            $str = htmlspecialchars($str, ENT_QUOTES, 'utf-8');
        }

        $str = str_replace('"', '&quot;', $str);

        if ($sprintf !== null) {
            $str = $this->checkAndReplaceArgs($str, $sprintf);
        }

        return ($addslashes ? addslashes($str) : stripslashes($str));
    }

    /**
     * Post-traitement minimal : juste sprintf si besoin.
     * Utilisé par getPdfTranslation / getMailsTranslation.
     */
    protected function postProcessPlain($str, $sprintf) {

        if ($sprintf !== null) {
            $str = $this->checkAndReplaceArgs($str, $sprintf);
        }

        return $str;
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

    public function getAdminTranslation($string, $class = 'Phenyx', $plugin = null, $addslashes = false, $htmlentities = true, $sprintf = null) {

        $string = $this->normalizeString($string);
        $iso = $this->context->language->iso_code;

        $key = md5($string);
        $defaultKey = $class . $key;
        $str = null;

        $_LANGADMS = [];

        global $_LANGADM, $_LANGOVADM;

        // Lookup plugin (chemin rapide) — voir loadPluginTranslations()
        if ($plugin !== null) {
            $plugMap = $this->loadPluginTranslations($plugin, 'admin', $iso);
            if (isset($plugMap[$defaultKey])) {
                return $this->postProcessHtml($plugMap[$defaultKey], $addslashes, $htmlentities, $sprintf);
            }
            // pas trouvé dans le plugin : on retombe sur le lookup global ci-dessous
        }

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

        return $this->postProcessHtml($str, $addslashes, $htmlentities, $sprintf);
    }

    public function getFrontTranslation($string, $class, $plugin = null, $addslashes = false, $htmlentities = true, $sprintf = null) {

        // Spécifique au front : on remplace les double-quotes par des backticks
        // avant tout (impacte le calcul du md5 ci-dessous).
        $string = str_replace('"', '`', $string);
        $string = $this->normalizeString($string);
        $iso = $this->context->language->iso_code;

        $key = md5($string);
        $defaultKey = $class . $key;
        $str = null;

        $_LANGFRONTS = [];

        global $_LANGFRONT, $_LANGOVFRONT;

        // Lookup plugin (chemin rapide)
        if ($plugin !== null) {
            $plugMap = $this->loadPluginTranslations($plugin, 'front', $iso);
            if (isset($plugMap[$defaultKey])) {
                return $this->postProcessHtml($plugMap[$defaultKey], $addslashes, $htmlentities, $sprintf);
            }
        }

        if (isset($this->context->translations->langfront)) {

            if (isset($this->context->translations->langfront[$class . $key])) {
                $str = $this->context->translations->langfront[$class . $key];
            } else {
                $str = $this->getGenericFrontTranslation($string, $this->context->translations->langfront, $key);
            }

        } else {

            

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

        return $this->postProcessHtml($str, $addslashes, $htmlentities, $sprintf);
    }

    public function getClassTranslation($string, $class, $plugin = null, $addslashes = false, $htmlentities = true, $sprintf = null, $context = null) {

        if (is_null($string)) {
            return $string;
        }

        $string = $this->normalizeString($string);

        $_LANGCLASSS = [];

        global $_LANGCLASS, $_LANGOVCLASS;

        $key = md5($string);
        $iso = $this->context->language->iso_code;
        $defaultKey = $class . $key;
        $str = null;

        // Lookup plugin (chemin rapide)
        if ($plugin !== null) {
            $plugMap = $this->loadPluginTranslations($plugin, 'class', $iso);
            if (isset($plugMap[$defaultKey])) {
                return $this->postProcessHtml($plugMap[$defaultKey], $addslashes, $htmlentities, $sprintf);
            }
        }

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

        // Fix #14 (defensive net) : si la chaîne de lookup n'a rien produit
        // d'utilisable, on retombe sur la source originale.
        if (!is_string($str) || $str === '') {
            $str = $string;
        }

        return $this->postProcessHtml($str, $addslashes, $htmlentities, $sprintf);
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
      
        if (count($_PLUGINS) == 0) {
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
        } else if (!empty($_PLUGIN[$PhenyxShopKey2])) {
			$ret = stripslashes($_PLUGINS[$PhenyxShopKey2]);
            if ($sprintf !== null) {
                $ret = $this->checkAndReplaceArgs($ret, $sprintf);
            }
            return $ret;
        } else if (!empty($_PLUGINS)) {

			foreach ($_PLUGIN as $k => $value) {
                if (str_ends_with($k, $key) && !empty($value)) {
                    $ret = stripslashes($value);
                }
            }

        } else if (!is_string($ret) || $ret === '') { 
			$ret = $string;
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
    
    public function getPdfTranslation($string, $file, $sprintf = null, $plugin = null, $context = null) {

        $string = $this->normalizeString($string);

        $_LANGPDFS = [];
        global $_LANGPDF;

        $key = md5($string);
        $iso = $this->context->language->iso_code;
        $defaultKey = $file . $key;
        $str = null;

        // Lookup plugin (chemin rapide)
        if ($plugin !== null) {
            $plugMap = $this->loadPluginTranslations($plugin, 'pdf', $iso);
            if (isset($plugMap[$defaultKey])) {
                return $this->postProcessPlain($plugMap[$defaultKey], $sprintf);
            }
        }

        if (!isset($this->context->translations)) {
             $this->context->translations = new Translate($iso, $this->context->company);
        }

        if (isset($this->context->translations->langpdf)) {
			
			if(array_key_exists($defaultKey, $this->context->translations->langpdf)) {
				$str = $this->context->translations->langpdf[$defaultKey];
			}
        } else {

            

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

            if (array_key_exists($defaultKey, $_LANGPDFS)) {
                $str = $_LANGPDFS[$defaultKey];
            }

        }

        if (is_null($str)) {
            $str = $string;
        }

        return $this->postProcessPlain($str, $sprintf);
    }

    public function getMailsTranslation($string, $file, $sprintf = null, $plugin = null, $context = null) {

        $string = $this->normalizeString($string);
        $key = md5($string);
        $iso = $this->context->language->iso_code;

        if (!isset($this->context->translations)) {
            $this->context->translations = new Translate($iso, $this->context->company);
        }
        $_LANGMAILS = [];

        global $_LANGMAIL;

        $defaultKey = $file . $key;
        $str = null;

        // Lookup plugin (chemin rapide)
        if ($plugin !== null) {
            $plugMap = $this->loadPluginTranslations($plugin, 'mail', $iso);
            if (isset($plugMap[$defaultKey])) {
                return $this->postProcessPlain($plugMap[$defaultKey], $sprintf);
            }
        }

        if (isset($this->context->translations->langmail)) {

            if (array_key_exists($defaultKey, $this->context->translations->langmail)) {
                $str = $this->context->translations->langmail[$defaultKey];
            }

        } else {

            $i18NFile = _EPH_TRANSLATIONS_DIR_ . $iso . '/mail.php';
            $this->context->_hook->exec('actionMailsTranslate', ['iso' => $iso]);

            if (!include ($i18NFile)) {
                $this->l(sprintf('Cannot include mail translation language file : %s', $i18NFile));
            }

            $_LANGMAILS = !empty($_LANGMAIL) ? $_LANGMAILS + $_LANGMAIL : $_LANGMAIL;

            if (!isset($_LANGMAILS) || !is_array($_LANGMAILS)) {
                return str_replace('"', '&quot;', $string);
            }

            if (array_key_exists($defaultKey, $_LANGMAILS)) {
                $str = $_LANGMAILS[$defaultKey];
            }

        }

        if (is_null($str)) {
            $str = $string;
        }

        return $this->postProcessPlain($str, $sprintf);
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
            $str = $string;
        }

        // Fix #14: Hook::exec() returns `false` when no plugin listens to the
        // hook (see Hook.php Fix #12). The previous check `!is_null($extra)`
        // accepted that `false`, which then propagated to $str and rendered
        // as an empty string after htmlspecialchars() in getClassTranslation().
        // Symptom: class translations whose key wasn't found returned "" instead
        // of the original source string. Tighten the guard so only a non-empty
        // string override replaces the resolved $str.
        $extra = Context::getContext()->_hook->exec('actionGenericFrontTranslation', ['langArray' => $langArray, 'key' => $key]);

        if (is_string($extra) && $extra !== '') {
            $str = $extra;
        }

        return $str;
    }

    public function smartyPostProcessTranslation($string, $params) {

        return $this->postProcessTranslation($string, $params);
    }

    public function postProcessTranslation($string, $params) {

        if (!empty($params['tags'])) {

            foreach ($params['tags'] as $index => $tag) {
               
                $position = $index + 1;
         
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

            $iso =$this->context->language->iso_code;
           
            if (file_exists(_EPH_TRANSLATIONS_DIR_ . $iso . '/front.php')) {
                include_once _EPH_TRANSLATIONS_DIR_ . $iso . '/front.php';
            }

            if (file_exists(_EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/front.php')) {

                include_once _EPH_OVERRIDE_TRANSLATIONS_DIR_ . $iso . '/front.php';
            }

        }

        // Tail restored after an editor truncation (P7.3 / P7.4). The original
        // lookup beyond this point was lost; this reconstruction mirrors the
        // pattern of getAdminTranslation()/getClassTranslation() and falls
        // back to the source string when no installer translation is registered.
        $key = md5($string);

        if (isset($_LANGINSTALL) && is_array($_LANGINSTALL) && isset($_LANGINSTALL[$class . $key])) {
            $str = $_LANGINSTALL[$class . $key];
        } else {
            $str = $string;
        }

        if (!is_string($str) || $str === '') {
            $str = $string;
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
