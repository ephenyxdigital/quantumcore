<?php

namespace EphenyxDigital\QuantumCore;

use AppendIterator;
use Archive_Tar;
use DateTime;
use DirectoryIterator;
use Guest;
use Language;
use Link;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;


use \Curl\Curl;
use \Curl\MultiCurl;

/**
 * Class PhenyxToolsCore
 *
 * @since 1.9.1.0
 */
class PhenyxTools {

	protected static $instance;

	protected $_url;

	protected $_crypto_key;

	public $context;

	public $ephenyx_shop_active;

	public $default_theme;

	public $plugins = [];

	public $license;

	public $license_key;

	public function __construct() {

		$this->context = Context::getContext();

		if (!isset($this->context->phenyxConfig)) {
			$this->context->phenyxConfig = Configuration::getInstance();

		}

		if (!isset($this->context->company)) {
			$this->context->company = Company::initialize();
		}

		if (!isset($this->context->theme)) {
			$this->context->theme = new Theme((int) $this->context->company->id_theme);
		}

		if (!isset($this->context->language)) {
			$this->context->language = $this->context->_tools->jsonDecode($this->context->_tools->jsonEncode(Language::buildObject($this->context->phenyxConfig->get('EPH_LANG_DEFAULT'))));
		}

		if (!isset($this->context->_link)) {
			$this->context->_link = Link::getInstance();
		}

		if (!isset($this->context->translations)) {

			$this->context->translations = new Translate($this->context->language->iso_code, $this->context->company);
		}
		
		if (!isset($this->context->_tools)) {
            $this->context->_tools = PhenyxTool::getInstance();
        }

		$this->default_theme = $this->context->theme->directory;

		if (!isset($this->context->language)) {
			$this->context->language = $this->context->_tools->jsonDecode($this->context->_tools->jsonEncode(Language::buildObject($this->context->phenyxConfig->get('EPH_LANG_DEFAULT'))));
		}

		$this->ephenyx_shop_active = $this->context->phenyxConfig->get('_EPHENYX_SHOP_ACTIVE_');
        if ($this->context->company->company_url !== 'ephenyx.io') {
    		$this->license_key = $this->context->phenyxConfig->get('_EPHENYX_LICENSE_KEY_', null, false);
            $this->_url = _EPH_PHENYX_API_;
            $string = $this->license_key . '/' . $this->context->company->company_url;
            $this->_crypto_key = $this->context->_tools->encrypt_decrypt('encrypt', $string, _PHP_ENCRYPTION_KEY_,  $this->license_key);

            $this->license = $this->checkLicense();
            $this->context->license = $this->license;

            $this->plugins = $this->getInstalledPluginsDirOnDisk();
        }

	}

	public function getInstalledPluginsDirOnDisk() {
        
		$cacheId = 'getInstalledPluginsDirOnDisk';

		if ($this->context->cache_enable && is_object($this->context->cache_api)) {
			$value = $this->context->cache_api->getData($cacheId);
			$temp = empty($value) ? null : Tools::jsonDecode($value, true);

			if (!empty($temp)) {
				return $temp;
			}

		}

		$plugins = [];
		$pluginList = [];
		$plugs = scandir(_EPH_PLUGIN_DIR_);

		foreach ($plugs as $name) {

			if (in_array($name, ['.', '..'])) {
				continue;
			}

			if (is_file(_EPH_PLUGIN_DIR_ . $name)) {
				continue;
			} else

			if (is_dir(_EPH_PLUGIN_DIR_ . $name . DIRECTORY_SEPARATOR) && file_exists(_EPH_PLUGIN_DIR_ . $name . '/' . $name . '.php')) {

				if (!Validate::isPluginName($name)) {
					throw new PhenyxException(sprintf('Plugin %s is not a valid plugin name', $name));
				}

				$pluginList[] = $name;
			}
        }

		$plugs = scandir(_EPH_SPECIFIC_PLUGIN_DIR_);

		foreach ($plugs as $name) {

            if (in_array($name, ['.', '..'])) {
				continue;
			}

			if (is_file(_EPH_SPECIFIC_PLUGIN_DIR_ . $name)) {
				continue;
			} else

			if (is_dir(_EPH_SPECIFIC_PLUGIN_DIR_ . $name . DIRECTORY_SEPARATOR) && file_exists(_EPH_SPECIFIC_PLUGIN_DIR_ . $name . DIRECTORY_SEPARATOR . $name . '.php')) {

				if (!Validate::isPluginName($name)) {
					throw new PhenyxException(sprintf('Plugin %s is not a valid plugin name', $name));
				}

				$pluginList[] = $name;
			}

        }

			foreach ($pluginList as $plugin) {

				if (in_array($plugin, ['.', '..'])) {
					continue;
				}

				if (Plugin::isInstalled($plugin)) {
					$plugins[$plugin] = true;
				}

			}

		

		if ($this->context->cache_enable && is_object($this->context->cache_api)) {
			$temp = $plugins === null ? null : Tools::jsonEncode($plugins);
			$this->context->cache_api->putData($cacheId, $temp, 3600);
		}

		return $plugins;

	}

	public static function getInstance() {

		if (!PhenyxTools::$instance) {
			PhenyxTools::$instance = new PhenyxTools(); 
		}

		return PhenyxTools::$instance;
	}
    
    public static function getBasesLink($domain, $ssl = null, $relativeProtocol = false) {
       
        $link = Link::getInstance();      
    
        return $link->getBasesLink($domain, $ssl, $relativeProtocol);
    }
    

	public function generateCurrentJson($use_cache = true): array {

		// Lecture du cache si disponible	
    	if ($use_cache && file_exists(_EPH_CONFIG_DIR_ . 'json/new_json.json')) {
        	$md5List = file_get_contents(_EPH_CONFIG_DIR_ . 'json/new_json.json');
        	unlink(_EPH_CONFIG_DIR_ . 'json/new_json.json');
        	return Tools::jsonDecode($md5List, true) ?? [];
		}

    	if (!$use_cache && file_exists(_EPH_CONFIG_DIR_ . 'json/new_json.json')) {
        	unlink(_EPH_CONFIG_DIR_ . 'json/new_json.json');
    	}

		// CORRIGÉ : initialisé à [] — retour null impossible même si tous les fichiers sont exclus
    	$md5List = [];
    	$excludes = [];

    	$directories = Theme::getInstalledThemeDirectories();

    	$recursive_directory = [
        	'app/xml',
        	'content/backoffice',
        	'content/css',
        	'content/fonts',
        	'content/js',
        	'content/localization',
        	'content/img/pdfWorker',
        	'content/mails',
        	'content/mp3',
        	'content/pdf',
        	'content/themes/phenyx-theme-default',
        	'includes/classes',
        	'includes/controllers',
        	'vendor/ephenyxdigital',
        	'webephenyx',
    	];

    	$iso_langs = [];
    	$languages = Language::getLanguages(false);

    	foreach ($languages as $language) {
        	$recursive_directory[] = 'content/translations/' . $language['iso_code'];
        	$iso_langs[]           = $language['iso_code'];
    	}

    	// CORRIGÉ : $this->plugins est ['pluginName' => true/false]
    	// L'ancien code faisait foreach($this->plugins as $plugin) ce qui donnait
    	// $plugin = true/false au lieu du nom du plugin.
    	foreach ($this->plugins as $plugin => $installed) {

        	if ($installed && is_dir(_EPH_PLUGIN_DIR_ . $plugin)) {
            	$recursive_directory[] = 'includes/plugins/' . $plugin;
        	}
		}

    	$iterator = new AppendIterator();
    	$iterator->append(new DirectoryIterator(_EPH_ROOT_DIR_ . '/content/themes/'));

    	foreach ($recursive_directory as $directory) {
			
        	if (is_dir(_EPH_ROOT_DIR_ . '/' . $directory)) {
            	$iterator->append(new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator(_EPH_ROOT_DIR_ . '/' . $directory . '/')
            	));
			}
    	}

    	$iterator->append(new DirectoryIterator(_EPH_ROOT_DIR_ . '/app/'));
    	$iterator->append(new DirectoryIterator(_EPH_ROOT_DIR_ . '/'));

    	// Construction des exclusions de thèmes
    	foreach ($directories as $directory) {

        	if ($directory === 'phenyx-theme-default') {
				continue;
        	}

        	foreach (['css', 'fonts', 'font', 'img', 'js', 'plugins', 'pdf', 'mail', 'docs'] as $sub) {
            	$excludes[] = '/' . $directory . '/' . $sub . '/';
        	}
    	}

    	$excludedFiles = ['.', '..', '.htaccess', '.env', 'composer.lock', 'settings.inc.php', '.gitattributes', '.php-ini', '.php-version', 'config.json'];
    	$excludedExtensions = ['txt', 'zip', 'dat', 'log'];
    	$excludedPaths = ['/uploads/', '/cache/', '/views/docs/', 'sitemap.xml'];

    	foreach ($iterator as $file) {

        	if (in_array($file->getFilename(), $excludedFiles, true)) {
            	continue;
        	}

        	if (is_dir($file->getPathname())) {
            	continue;
        	}

        	$filePath = str_replace(_EPH_ROOT_DIR_, '', $file->getPathname());
        	$ext      = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
			
			if (str_contains($filePath, 'uploads/revslider')) {
                continue;
            }

        	if (in_array($ext, $excludedExtensions, true)) {
            	continue;
        	}

        	// Vérification des exclusions de chemin
        	$skip = false;

        	foreach ($excludes as $exclude) {

            	if (str_contains($filePath, $exclude)) {
                	$skip = true;
                	break;
            	}
			}

        	foreach ($excludedPaths as $excludedPath) {

				if (str_contains($filePath, $excludedPath)) {
					$skip = true;
                	break;
            	}
        	}

        	if ($skip) {
            	continue;
        	}

        	// Exclusion des CSS personnalisés
        	if (str_contains($filePath, 'custom_') && $ext === 'css') {
            	continue;
        	}
			
        	// CORRIGÉ : filtre des traductions de plugins
        	// Avant : le continue portait sur foreach($this->plugins), pas sur foreach($iterator)
        	// → les fichiers de traduction non pertinents étaient inclus quand même.
        	// Après : flag $skipFile qui porte sur la bonne boucle.
        	if (str_contains($filePath, '/plugins/') && str_contains($filePath, '/translations/')) {
            	$skipFile = false;

            	foreach ($this->plugins as $plugin => $installed) {

                	if (str_contains($filePath, '/plugins/' . $plugin . '/translations/')) {
                    	$isoTest = str_replace('/includes/plugins/' . $plugin . '/translations/', '', $filePath);
						$test2 = str_replace('/'.$file->getFilename(), '', $isoTest);
                    	$isoTest = str_replace('.php', '', $isoTest);
						if (!in_array($test2, $iso_langs)) {
							if (!in_array($isoTest, $iso_langs)) {
								$skipFile = true;
                    		}
						}

                    	break;
                	}
            	}

            	if ($skipFile) {
                	continue;
            	}
        	}

        	$md5List[$filePath] = md5_file($file->getPathname());
    	}

    	return $md5List;
	}


	public function generateOwnCurrentJson(): bool {

		// Supprimer le cache périmé s'il existe
    	if (file_exists(_EPH_CONFIG_DIR_ . 'json/new_json.json')) {
        	unlink(_EPH_CONFIG_DIR_ . 'json/new_json.json');
    	}

    	// Générer la nouvelle liste (sans lire le cache)
    	$md5List = $this->generateCurrentJson(false);

    	if (!empty($md5List)) {
        	file_put_contents(
            	_EPH_CONFIG_DIR_ . 'json/new_json.json',
            	json_encode($md5List, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        	);
        	chmod(_EPH_CONFIG_DIR_ . 'json/new_json.json', 0777);
        	return true;
    	}

    	return false;
	}


	public static function getConfiguration($tags) {

		return Context::getContext()->phenyxConfig->get($tags);
	}

	public static function execHook($hook, $args = [], $return = false) {

		return Hook::getInstance()->exec($hook, $args, null, $return);
	}

	public static function getSmartyLink(string $method, $args) {

    	$link = new Link();

    	if (method_exists($link, $method)) {

        	if (!is_array($args)) {
            	$args = [$args];
        	}	

        	return $link->{$method}(implode(',', $args)); // ← était $link->{method}
    	}

    	return null;
	}

	public static function addJsDef($jsDef) {

		return Context::getContext()->media->addJsDef($jsDef);

	}

	public static function addJsDefL($params, $content, $smarty = null, &$repeat = false) {

		return Context::getContext()->media->addJsDefL($params, $content, $smarty, $repeat);
	}

	// =========================================================================
	// AUTH API PAR SIGNATURE (Ed25519) — côté client
	// =========================================================================

	/**
	 * Génère la paire de clés Ed25519 au premier appel et enrôle la clé publique
	 * auprès d'ephenyx.io (requête authentifiée par le schéma legacy, accepté par
	 * le dispatcher en mode dual). Idempotent : ne refait rien une fois enrôlé.
	 */
	private function ensureApiEnrollment() {

		$cfg = $this->context->phenyxConfig;

		if (!$cfg->get('_EPH_API_PRIVATE_KEY_', null, false)) {
			$pair = sodium_crypto_sign_keypair();
			$cfg->updateValue('_EPH_API_PRIVATE_KEY_', base64_encode(sodium_crypto_sign_secretkey($pair)));
			$cfg->updateValue('_EPH_API_PUBLIC_KEY_', base64_encode(sodium_crypto_sign_publickey($pair)));
			$cfg->updateValue('_EPH_API_KEY_ID_', 1);
		}

		if (!$cfg->get('_EPH_API_KEY_REGISTERED_', null, false)) {
			if ($this->registerApiPublicKey()) {
				$cfg->updateValue('_EPH_API_KEY_REGISTERED_', 1);
			}
		}
	}

	/**
	 * Construit le payload signé d'une requête API.
	 * Les paramètres métier sont transportés dans `body` (string JSON), dont on
	 * signe le sha256 avec ts + nonce pour garantir intégrité et anti-rejeu.
	 */
	private function signedPayload($action, array $params = []) {

		$cfg        = $this->context->phenyxConfig;
		$licenseKey = $cfg->get('_EPHENYX_LICENSE_KEY_', null, false);
		$sk         = base64_decode($cfg->get('_EPH_API_PRIVATE_KEY_', null, false), true);

		$bodyStr = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$ts      = time();
		$nonce   = bin2hex(random_bytes(16));
		$base    = $licenseKey . "\n" . $action . "\n" . $ts . "\n" . $nonce . "\n" . hash('sha256', $bodyStr);
		$sig     = base64_encode(sodium_crypto_sign_detached($base, $sk));

		return [
			'action'      => $action,
			'license_key' => $licenseKey,
			'ts'          => $ts,
			'nonce'       => $nonce,
			'body'        => $bodyStr,
			'sig'         => $sig,
			'alg'         => 'ed25519',
			'kid'         => (int) $cfg->get('_EPH_API_KEY_ID_', null, false) ?: 1,
		];
	}

	/** Appel API générique. Signe la requête si la clé est enrôlée, sinon repli legacy. */
	private function apiCall($action, array $params = []) {

		$this->ensureApiEnrollment();

		$cfg = $this->context->phenyxConfig;

		if ($cfg->get('_EPH_API_KEY_REGISTERED_', null, false)) {
			// Schéma cible : requête signée Ed25519.
			$data_array = $this->signedPayload($action, $params);
		} else {
			// Repli legacy tant que la clé publique n'est pas enrôlée côté serveur
			// (ex. serveur pas encore migré). Garantit la compatibilité ascendante.
			$data_array = array_merge($params, [
				'action'      => $action,
				'license_key' => $cfg->get('_EPHENYX_LICENSE_KEY_', null, false),
				'crypto_key'  => $this->_crypto_key,
			]);
		}

		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->setTimeout(30);
		$curl->post($this->_url, json_encode($data_array));

		return $curl->response;
	}

	/**
	 * Enregistre la clé publique du site sur ephenyx.io. Authentifié par le
	 * schéma legacy (crypto_key) le temps de la transition.
	 */
	public function registerApiPublicKey() {

		$cfg = $this->context->phenyxConfig;

		$data_array = [
			'action'      => 'registerPublicKey',
			'license_key' => $cfg->get('_EPHENYX_LICENSE_KEY_', null, false),
			'crypto_key'  => $this->_crypto_key,
			'public_key'  => $cfg->get('_EPH_API_PUBLIC_KEY_', null, false),
			'key_alg'     => 'ed25519',
			'key_id'      => 1,
		];

		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->setTimeout(30);
		$curl->post($this->_url, json_encode($data_array));

		$resp = $curl->response;

		return is_array($resp) ? !empty($resp['success']) : false;
	}

	public function checkLicense() {

		return $this->apiCall('checkLicence', []);
	}

	public function getPhenyxPlugins() {

		$plugins = Plugin::getInstalledPluginsOnDisk();

		$response = $this->apiCall('getPhenyxPlugins', ['plugins' => $plugins]);
		$response = Tools::jsonDecode(Tools::jsonEncode($response), true);

		if (is_array($response)) {
			file_put_contents(
				_EPH_CONFIG_DIR_ . 'json/plugin_sources.json',
				json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
			);
			chmod(_EPH_CONFIG_DIR_ . 'json/plugin_sources.json', 0777);
			return true;
		}

		return false;

	}

	// =========================================================================
	// ph_upgrader — actions de mise a jour du coeur (meme auth que getPhenyxPlugins)
	// =========================================================================

	public function checkCoreUpdate($channel = 'stable') {

		return $this->apiCall('checkCoreUpdate', ['channel' => $channel, 'version' => _EPH_VERSION_]);
	}

	public function getCorePackage($version) {

		return $this->apiCall('getCorePackage', ['version' => $version]);
	}

	public function reportUpgrade($from, $to, $state, $message = '') {

		return $this->apiCall('reportUpgrade', [
			'version_from' => $from,
			'version_to'   => $to,
			'state'        => $state,
			'message'      => $message,
		]);
	}

	/**
	 * Met a jour UNIQUEMENT la version (_EPH_VERSION_) dans settings.inc.php,
	 * en place. Les adresses de base et les secrets sont desormais charges depuis
	 * .env ($_ENV) : on ne reecrit donc plus tout le fichier (ce qui figerait les
	 * credentials en dur et casserait le schema .env).
	 *
	 * @param string $version
	 *
	 * @return bool
	 */
	public function writeNewSettings($version) {

		$settingsFile = _EPH_CONFIG_DIR_ . 'settings.inc.php';

		if (!is_file($settingsFile) || !is_writable($settingsFile)) {
			PhenyxLogger::addLog('writeNewSettings: settings.inc.php absent ou non inscriptible', 3, null, 'PhenyxTools');
			return false;
		}

		$content = file_get_contents($settingsFile);

		if ($content === false) {
			return false;
		}

		// Sauvegarde de securite.
		@copy($settingsFile, str_replace('.php', '.old.php', $settingsFile));

		$safeVersion = addslashes($version);
		$count       = 0;

		// Remplacement en place de la valeur du define _EPH_VERSION_.
		$new = preg_replace(
			"/define\\(\\s*'_EPH_VERSION_'\\s*,\\s*'[^']*'\\s*\\)\\s*;/",
			"define('_EPH_VERSION_', '" . $safeVersion . "');",
			$content,
			1,
			$count
		);

		if ($new === null) {
			return false;
		}

		// Si le define n'existait pas, on l'ajoute en fin de fichier.
		if ($count === 0) {
			$new = rtrim($content) . PHP_EOL . "define('_EPH_VERSION_', '" . $safeVersion . "');" . PHP_EOL;
		}

		$ok = (bool) @file_put_contents($settingsFile, $new);

		if ($ok && function_exists('opcache_invalidate')) {
			opcache_invalidate($settingsFile, true);
		}

		return $ok;
	}

	public function alterSqlTable(string $table, string $column, string $type, string $after): void {

    	$query = 'SELECT `COLUMN_NAME`
        	FROM `INFORMATION_SCHEMA`.`COLUMNS`
			WHERE `TABLE_SCHEMA` = \'' . pSQL(_DB_NAME_) . '\'
        	AND `TABLE_NAME` = \'' . pSQL(_DB_PREFIX_ . $table) . '\'
			AND `COLUMN_NAME` = \'' . pSQL($column) . '\'';

    	$result = Db::getInstance()->getValue(trim($query));

    	if ($result !== $column) {
        	$sql = 'ALTER TABLE `' . bqSQL(_DB_PREFIX_ . $table) . '`'
				. ' ADD `' . bqSQL($column) . '` ' . $type
				. ' AFTER `' . bqSQL($after) . '`';
        	Db::getInstance()->execute(trim($sql));
    	}
	}

	public function checkString($string) {

		if (!is_numeric($string)) {
			$string = addslashes($string);
		}

		return $string;
	}

	public function cleanBackTabs() {

		$today = date("Y-m-d");
		$date = new DateTime($today);
		$date->modify('-180 days');
		$dateCheck = $date->format('Y-m-d');
		$last_maintenance = $this->context->phenyxConfig->get('BACK_TAB_MAINTENANCE');

		if (!is_null($last_maintenance) && $last_maintenance > $dateCheck) {
			return true;
		}

		$query = 'SELECT id_back_tab, class_name  FROM `' . _DB_PREFIX_ . 'back_tab` ORDER BY id_back_tab ASC';
		$tabClasses = Db::getInstance()->executeS($query);

		foreach ($tabClasses as $tablasse) {

			if (class_exists($tablasse['class_name'] . 'Controller')) {
				continue;
			} else {

				if (str_contains($tablasse['class_name'], 'Parent')) {
					continue;
				} else {
					$bckTab = new BackTab($tablasse['id_back_tab']);
					$bckTab->delete();
					$id_meta = Meta::getIdMetaByPage(strtolower($tablasse['class_name']));

					if ($id_meta > 0) {
						$meta = new Meta($id_meta);
						$meta->delete();
					}

				}

			}

		}

		$idLang = $this->context->language->id;

		$result = true;

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'back_tab` CHANGE `id_back_tab` `id_back_tab` INT(10) UNSIGNED NOT NULL';
		$result &= Db::getInstance()->execute($sql);

		$query = 'SELECT id_back_tab  FROM `' . _DB_PREFIX_ . 'back_tab` ORDER BY id_back_tab ASC';
		$tabClasses = Db::getInstance()->executeS($query);

		$maxIndex = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('MAX(`id_back_tab`) + 1')
				->from('back_tab')
		);

		foreach ($tabClasses as $tab) {

			$sql = 'UPDATE `' . _DB_PREFIX_ . 'back_tab` SET `id_back_tab` = ' . $maxIndex . ' WHERE `id_back_tab` = ' . $tab['id_back_tab'];
			$result &= Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'back_tab_lang` SET id_back_tab = ' . $maxIndex . ' WHERE id_back_tab = ' . $tab['id_back_tab'];
			$result &= Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'employee_access` SET `id_back_tab` = ' . $maxIndex . ' WHERE `id_back_tab` = ' . $tab['id_back_tab'];
			$result &= Db::getInstance()->execute($sql);
			$maxIndex++;

		}

		$query = 'SELECT id_back_tab  FROM `' . _DB_PREFIX_ . 'back_tab` ORDER BY id_back_tab ASC';

		$tabs = Db::getInstance()->executeS($query);

		$i = 1;

		foreach ($tabs as $tab) {

			$parents = Db::getInstance()->executes(
				(new DbQuery())
					->select('`id_back_tab`')
					->from('back_tab')
					->where('`id_parent` = ' . (int) $tab['id_back_tab'])
			);

			foreach ($parents as $parent) {
				$sql = 'UPDATE `' . _DB_PREFIX_ . 'back_tab` SET id_parent = ' . $i . ' WHERE id_back_tab = ' . $parent['id_back_tab'];
				$result &= Db::getInstance()->execute($sql);
			}

			$sql = 'UPDATE `' . _DB_PREFIX_ . 'back_tab` SET id_back_tab = ' . $i . ' WHERE id_back_tab = ' . $tab['id_back_tab'];
			$result &= Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'back_tab_lang` SET id_back_tab = ' . $i . ' WHERE id_back_tab = ' . $tab['id_back_tab'];
			$result &= Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'employee_access` SET id_back_tab = ' . $i . ' WHERE id_back_tab = ' . $tab['id_back_tab'];
			$result &= Db::getInstance()->execute($sql);
			$i++;
		}

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'back_tab` CHANGE `id_back_tab` `id_back_tab` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT';
		$result &= Db::getInstance()->execute($sql);

		if ($result && $this->context->cache_enable && is_object($this->context->cache_api)) {
			$this->context->cache_api->cleanByStartingKey('generateTabs_');
			$this->context->cache_api->cleanByStartingKey('getBckTab_');
		}

		if ($result) {
			$this->context->phenyxConfig->updateValue('BACK_TAB_MAINTENANCE', date("Y-m-d"));
		}

		return $result;

	}

	public function getLegitimeMeta() {

		$controllers = [];
		$ctrls = Meta::getPages();

		foreach ($ctrls as $key => $ctrl) {

			foreach ($ctrl as $k => $value) {

				if (str_contains($k, '/')) {
					$ks = explode('/', $k);
					$controllers[] = $ks[1];
				} else {
					$controllers[] = $k;
				}

			}

		}

		return $controllers;

	}

	public function cleanGuest() {

		$query = 'SELECT id_guest  FROM `' . _DB_PREFIX_ . 'guest` WHERE id_user = 0';

		$guests = Db::getInstance()->executeS($query);

		foreach ($guests as $guest) {
			$guest = new Guest($guest['id_guest']);
			$guest->delete();
		}

		$result = true;

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'guest` CHANGE `id_guest` `id_guest` INT(10) UNSIGNED NOT NULL';
		$result &= Db::getInstance()->execute($sql);

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'guest` DROP PRIMARY KEY';
		$result &= Db::getInstance()->execute($sql);

		$query = 'SELECT id_guest  FROM `' . _DB_PREFIX_ . 'guest` ORDER BY id_guest ASC';

		$guests = Db::getInstance()->executeS($query);
		$maxIndex = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('MAX(`id_guest`) + 1')
				->from('guest')
		);

		foreach ($guests as $guest) {
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'guest` SET `id_guest` = ' . $maxIndex . ' WHERE `id_guest` = ' . $guest['id_guest'];
			$result &= Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'guest_meta` SET `id_guest` = ' . $maxIndex . ' WHERE `id_guest` = ' . $guest['id_guest'];
			$result &= Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'connections` SET `id_guest` = ' . $maxIndex . ' WHERE `id_guest` = ' . $guest['id_guest'];
			$result &= Db::getInstance()->execute($sql);
			Hook::getInstance()->exec('updateGuestIndex', ['index' => $maxIndex, 'id_guest' => $guest['id_guest']]);
			$maxIndex++;
		}

		$query = 'SELECT id_guest  FROM `' . _DB_PREFIX_ . 'guest` ORDER BY id_guest ASC';

		$guests = Db::getInstance()->executeS($query);
		$i = 1;

		foreach ($guests as $guest) {
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'guest` SET `id_guest` = ' . $i . ' WHERE `id_guest` = ' . $guest['id_guest'];
			$result &= Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'guest_meta` SET `id_guest` = ' . $i . ' WHERE `id_guest` = ' . $guest['id_guest'];
			$result &= Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'connections` SET `id_guest` = ' . $i . ' WHERE `id_guest` = ' . $guest['id_guest'];
			$result &= Db::getInstance()->execute($sql);
			Hook::getInstance()->exec('updateGuestIndex', ['index' => $i, 'id_guest' => $guest['id_guest']]);

			$i++;
		}

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'guest` CHANGE `id_guest` `id_guest` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id_guest`)';
		$result &= Db::getInstance()->execute($sql);

		if ($result) {
			$this->context->phenyxConfig->updateValue('GUEST_MAINTENANCE', date("Y-m-d"));
		}

		return $result;

	}

	public function cleanConfiguration() {

		$query = 'SELECT id_configuration  FROM `' . _DB_PREFIX_ . 'configuration_lang` ORDER BY id_configuration ASC';
		$configurations = Db::getInstance()->executeS($query);

		foreach ($configurations as $configuration) {
			$parent = Db::getInstance()->getValue(
				(new DbQuery())
					->select('`id_configuration`')
					->from('configuration')
					->where('`id_configuration` = ' . (int) $configuration['id_configuration'])
			);

			if (!$parent) {
				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'configuration_lang` WHERE id_configuration = ' . $configuration['id_configuration'];
				$result &= Db::getInstance()->execute($sql);
			}

		}

		$result = true;

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'configuration` CHANGE `id_configuration` `id_configuration` INT(10) UNSIGNED NOT NULL';
		$result &= Db::getInstance()->execute($sql);

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'configuration` DROP PRIMARY KEY';
		$result &= Db::getInstance()->execute($sql);

		$query = 'SELECT id_configuration  FROM `' . _DB_PREFIX_ . 'configuration` ORDER BY id_configuration ASC';

		$configurations = Db::getInstance()->executeS($query);
		$maxIndex = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('MAX(`id_configuration`) + 1')
				->from('configuration')
		);

		foreach ($configurations as $configuration) {
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'configuration` SET `id_configuration` = ' . $maxIndex . ' WHERE `id_configuration` = ' . $configuration['id_configuration'];
			$result &= Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'configuration_lang` SET `id_configuration` = ' . $maxIndex . ' WHERE `id_configuration` = ' . $configuration['id_configuration'];
			$result &= Db::getInstance()->execute($sql);

			$maxIndex++;
		}

		$query = 'SELECT id_configuration  FROM `' . _DB_PREFIX_ . 'configuration` ORDER BY id_configuration ASC';

		$configurations = Db::getInstance()->executeS($query);
		$i = 1;

		foreach ($configurations as $configuration) {
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'configuration` SET `id_configuration` = ' . $i . ' WHERE `id_configuration` = ' . $configuration['id_configuration'];
			$result &= Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'configuration_lang` SET `id_configuration` = ' . $i . ' WHERE `id_configuration` = ' . $configuration['id_configuration'];
			$result &= Db::getInstance()->execute($sql);

			$i++;
		}

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'configuration` CHANGE `id_configuration` `id_configuration` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id_configuration`)';
		$result &= Db::getInstance()->execute($sql);

		if ($result) {
			$this->context->phenyxConfig->updateValue('CONFIGURATION_MAINTENANCE', date("Y-m-d"));
		}

		return $result;

	}

	public function cleanMetas() {

		$result = true;

		$legitimeMetas = $this->getLegitimeMeta();
		$idLang = $this->context->language->id;

		$query = 'SELECT id_meta, page  FROM `' . _DB_PREFIX_ . 'meta` ORDER BY id_meta ASC';

		$metas = Db::getInstance()->executeS($query);

		foreach ($metas as $meta) {

			if (in_array($meta['page'], $legitimeMetas)) {
				continue;
			} else {
				$meta = new Meta($meta['id_meta']);
				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'theme_meta` WHERE id_meta = ' . $meta->id;
				$meta->delete();

			}

		}

		$query = 'SELECT id_meta  FROM `' . _DB_PREFIX_ . 'theme_meta` ORDER BY id_meta ASC';
		$themeMetas = Db::getInstance()->executeS($query);

		foreach ($themeMetas as $themeMeta) {
			$parent = Db::getInstance()->getValue(
				(new DbQuery())
					->select('`id_meta`')
					->from('meta')
					->where('`id_meta` = ' . (int) $themeMeta['id_meta'])
			);

			if (!$parent) {
				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'theme_meta` WHERE id_meta = ' . $themeMeta['id_meta'];
				$result &= Db::getInstance()->execute($sql);
			}

		}

		$query = 'SELECT id_meta  FROM `' . _DB_PREFIX_ . 'meta_lang` WHERE id_lang = ' . $idLang . ' ORDER BY id_meta ASC';
		$metaLangs = Db::getInstance()->executeS($query);

		foreach ($metaLangs as $metaLang) {
			$parent = Db::getInstance()->getValue(
				(new DbQuery())
					->select('`id_meta`')
					->from('meta')
					->where('`id_meta` = ' . (int) $metaLang['id_meta'])
			);

			if (!$parent) {
				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'meta_lang` WHERE id_meta = ' . $metaLang['id_meta'];
				$result &= Db::getInstance()->execute($sql);
			}

		}

		$query = 'SELECT id_theme_meta  FROM `' . _DB_PREFIX_ . 'theme_meta` ORDER BY id_theme_meta ASC';

		$theme_metas = Db::getInstance()->executeS($query);
		$maxIndex = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('MAX(`id_theme_meta`) + 1')
				->from('theme_meta')
		);

		foreach ($theme_metas as $theme_meta) {
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'theme_meta` SET `id_theme_meta` = ' . $maxIndex . ' WHERE `id_theme_meta` = ' . $theme_meta['id_theme_meta'];
			$result &= Db::getInstance()->execute($sql);

			$maxIndex++;
		}

		$query = 'SELECT id_theme_meta  FROM `' . _DB_PREFIX_ . 'theme_meta` ORDER BY id_theme_meta ASC';

		$theme_metas = Db::getInstance()->executeS($query);
		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'theme_meta` DROP INDEX `id_theme_2`';
		$result &= Db::getInstance()->execute($sql);

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'theme_meta` DROP INDEX `id_theme`';
		$result &= Db::getInstance()->execute($sql);

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'theme_meta` DROP INDEX `id_meta`';
		$result &= Db::getInstance()->execute($sql);
		$i = 1;

		foreach ($theme_metas as $theme_meta) {
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'theme_meta` SET `id_theme_meta` = ' . $i . ' WHERE `id_theme_meta` = ' . $theme_meta['id_theme_meta'];
			$result &= Db::getInstance()->execute($sql);

			$i++;
		}

		$query = 'SELECT id_meta  FROM `' . _DB_PREFIX_ . 'meta` ORDER BY id_meta ASC';

		$metas = Db::getInstance()->executeS($query);

		$maxIndex = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('MAX(`id_meta`) + 1')
				->from('meta')
		);

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'meta` CHANGE `id_meta` `id_meta` INT(10) UNSIGNED NOT NULL';
		$result &= Db::getInstance()->execute($sql);

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'meta` DROP PRIMARY KEY';
		$result &= Db::getInstance()->execute($sql);

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'meta` DROP INDEX `page`';
		$result &= Db::getInstance()->execute($sql);

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'meta_lang` DROP PRIMARY KEY';
		$result &= Db::getInstance()->execute($sql);

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'meta_lang` DROP INDEX `id_lang`';
		$result &= Db::getInstance()->execute($sql);

		foreach ($metas as $meta) {

			$sql = 'UPDATE `' . _DB_PREFIX_ . 'meta` SET `id_meta` = ' . $maxIndex . ' WHERE `id_meta` = ' . $meta['id_meta'];
			$result &= Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'meta_lang` SET `id_meta` = ' . $maxIndex . ' WHERE `id_meta` = ' . $meta['id_meta'];
			$result &= Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'theme_meta` SET `id_meta` = ' . $maxIndex . ' WHERE `id_meta` = ' . $meta['id_meta'];
			$result &= Db::getInstance()->execute($sql);

			$maxIndex++;

		}

		$query = 'SELECT id_meta  FROM `' . _DB_PREFIX_ . 'meta` ORDER BY id_meta ASC';

		$metas = Db::getInstance()->executeS($query);

		$i = 1;

		foreach ($metas as $meta) {

			$sql = 'UPDATE `' . _DB_PREFIX_ . 'meta` SET id_meta = ' . $i . ' WHERE id_meta = ' . $meta['id_meta'];
			$result &= Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'meta_lang` SET id_meta = ' . $i . ' WHERE id_meta = ' . $meta['id_meta'];
			$result &= Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'theme_meta` SET id_meta = ' . $i . ' WHERE id_meta = ' . $meta['id_meta'];

			$result &= Db::getInstance()->execute($sql);
			$i++;
		}

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'meta` CHANGE `id_meta` `id_meta` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id_meta`)';
		$result &= Db::getInstance()->execute($sql);

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'meta` ADD UNIQUE `page` (`page`) USING BTREE';
		$result &= Db::getInstance()->execute($sql);

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'meta_lang` ADD PRIMARY KEY (`id_meta`, `id_lang`) USING BTREE';
		$result &= Db::getInstance()->execute($sql);

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'meta_lang` ADD INDEX `id_lang` (`id_lang`) USING BTREE';
		$result &= Db::getInstance()->execute($sql);

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'theme_meta` ADD UNIQUE `id_theme_2` (`id_theme`, `id_meta`) USING BTREE';
		$result &= Db::getInstance()->execute($sql);
		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'theme_meta` ADD INDEX `id_theme` (`id_theme`) USING BTREE';
		$result &= Db::getInstance()->execute($sql);
		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'theme_meta` ADD INDEX `id_meta` (`id_meta`) USING BTREE';
		$result &= Db::getInstance()->execute($sql);

		if ($result && $this->context->cache_enable && is_object($this->context->cache_api)) {
			$this->context->cache_api->cleanByStartingKey('metaGetPages_');
		}

		if ($result) {
			$this->context->phenyxConfig->updateValue('META_MAINTENANCE', date("Y-m-d"));
		}

		return $result;

	}

	public function cleanPluginHook() {

		$result = true;
		$query = 'SELECT hp.id_plugin, hp.id_hook, h.name as hookname, p.name
        FROM `' . _DB_PREFIX_ . 'hook_plugin` hp
        LEFT JOIN `' . _DB_PREFIX_ . 'hook` h On h.id_hook = hp.id_hook
        LEFT JOIN `' . _DB_PREFIX_ . 'plugin` p On p.id_plugin = hp.id_plugin
        ORDER BY hp.id_plugin ASC';
		$pluginHooks = Db::getInstance()->executeS($query);

		foreach ($pluginHooks as $pluginhook) {

			if ($pluginhook['name'] == 'revslider') {
				continue;
			}

			$method = false;

			$retroHookName = $this->context->_hook->getRetroHookName($pluginhook['hookname']);

			if (file_exists(_EPH_PLUGIN_DIR_ . $pluginhook['name'] . '/' . $pluginhook['name'] . '.php')) {
				require_once _EPH_PLUGIN_DIR_ . $pluginhook['name'] . '/' . $pluginhook['name'] . '.php';
			} else

			if (file_exists(_EPH_SPECIFIC_PLUGIN_DIR_ . $pluginhook['name'] . '/' . $pluginhook['name'] . '.php')) {
				require_once _EPH_SPECIFIC_PLUGIN_DIR_ . $pluginhook['name'] . '/' . $pluginhook['name'] . '.php';
			}

			if (class_exists($pluginhook['name'], false)) {

				$plugin = Plugin::getInstanceByName($pluginhook['name']);

				if (method_exists($plugin, 'hook' . $pluginhook['hookname']) || method_exists($plugin, 'hook' . $retroHookName)) {
					$method = true;
				}

				if ($method) {
					continue;
				}

				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'hook_plugin` WHERE `id_hook` = ' . $pluginhook['id_hook'] . ' AND `id_plugin` = ' . $pluginhook['id_plugin'];
				$result &= Db::getInstance()->execute($sql);
			}

		}

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'hook_plugin` CHANGE `id_hook_plugin` `id_hook_plugin` INT(10) UNSIGNED NOT NULL';
		$result &= Db::getInstance()->execute($sql);
		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'hook_plugin` DROP PRIMARY KEY';
		$result &= Db::getInstance()->execute($sql);
		$query = 'SELECT `id_hook_plugin`  FROM `' . _DB_PREFIX_ . 'hook_plugin` ORDER BY `id_hook_plugin` ASC';
		$hookPlugins = Db::getInstance()->executeS($query);
		$maxIndex = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('MAX(`id_hook_plugin`) + 1')
				->from('hook_plugin')
		);

		foreach ($hookPlugins as $hook) {

			$sql = 'UPDATE `' . _DB_PREFIX_ . 'hook_plugin` SET `id_hook_plugin` = ' . $maxIndex . ' WHERE `id_hook_plugin` = ' . $hook['id_hook_plugin'];
			$result &= Db::getInstance()->execute($sql);

			$maxIndex++;

		}

		$query = 'SELECT `id_hook_plugin`  FROM `' . _DB_PREFIX_ . 'hook_plugin` ORDER BY `id_hook_plugin` ASC';
		$hookPlugins = Db::getInstance()->executeS($query);

		$i = 1;

		foreach ($hookPlugins as $hook) {

			$sql = 'UPDATE `' . _DB_PREFIX_ . 'hook_plugin` SET `id_hook_plugin` = ' . $i . ' WHERE `id_hook_plugin` = ' . $hook['id_hook_plugin'];
			$result &= Db::getInstance()->execute($sql);

			$i++;

		}

		$sql = 'ALTER TABLE`' . _DB_PREFIX_ . 'hook_plugin` CHANGE `id_hook_plugin` `id_hook_plugin` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id_hook_plugin`)';
		$result &= Db::getInstance()->execute($sql);

		if ($result) {
			$this->context->phenyxConfig->updateValue('PLUGIN_HOOK_MAINTENANCE', date("Y-m-d"));
		}

		return $result;

	}

	public function cleanPlugins() {

		$result = true;

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'plugin` CHANGE `id_plugin` `id_plugin` INT(10) UNSIGNED NOT NULL';
		$result &= Db::getInstance()->execute($sql);
		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'plugin` DROP PRIMARY KEY';
		$result &= Db::getInstance()->execute($sql);
		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'plugin_access` DROP PRIMARY KEY';
		$result &= Db::getInstance()->execute($sql);
		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'plugin_group` DROP PRIMARY KEY';
		$result &= Db::getInstance()->execute($sql);

		$query = 'SELECT id_plugin  FROM `' . _DB_PREFIX_ . 'plugin` ORDER BY id_plugin ASC';
		$plugs = Db::getInstance()->executeS($query);
		$maxIndex = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('MAX(`id_plugin`) + 1')
				->from('plugin')
		);

		foreach ($plugs as $plugin) {
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'plugin` SET id_plugin = ' . $maxIndex . ' WHERE id_plugin = ' . $plugin['id_plugin'];
			$result &= Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'hook_plugin` SET id_plugin = ' . $maxIndex . '  WHERE id_plugin = ' . $plugin['id_plugin'];
			$result &= Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'hook_plugin_exceptions` SET id_plugin = ' . $maxIndex . '  WHERE id_plugin = ' . $plugin['id_plugin'];
			$result &= Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'plugin_access` SET id_plugin = ' . $maxIndex . ' WHERE id_plugin = ' . $plugin['id_plugin'];
			$result &= Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'plugin_group` SET id_plugin = ' . $maxIndex . '  WHERE id_plugin = ' . $plugin['id_plugin'];
			$result &= Db::getInstance()->execute($sql);

			if ($this->ephenyx_shop_active) {
				$sql = 'UPDATE `' . _DB_PREFIX_ . 'plugin_carrier` SET id_plugin = ' . $maxIndex . ' WHERE id_plugin = ' . $plugin['id_plugin'];
				$result &= Db::getInstance()->execute($sql);
				$sql = 'UPDATE `' . _DB_PREFIX_ . 'plugin_country` SET id_plugin = ' . $maxIndex . ' WHERE id_plugin = ' . $plugin['id_plugin'];
				$result &= Db::getInstance()->execute($sql);
				$sql = 'UPDATE `' . _DB_PREFIX_ . 'plugin_currency` SET id_plugin = ' . $maxIndex . '  WHERE id_plugin = ' . $plugin['id_plugin'];
				$result &= Db::getInstance()->execute($sql);
				$sql = 'UPDATE `' . _DB_PREFIX_ . 'payment_mode` SET id_plugin = ' . $maxIndex . '  WHERE id_plugin = ' . $plugin['id_plugin'];
				$result &= Db::getInstance()->execute($sql);
			}

			$maxIndex++;

		}

		$query = 'SELECT id_plugin  FROM `' . _DB_PREFIX_ . 'plugin` ORDER BY position ASC';
		$plugins = Db::getInstance()->executeS($query);
		$i = 1;

		foreach ($plugins as $plugin) {

			$sql = 'UPDATE `' . _DB_PREFIX_ . 'plugin` SET id_plugin = ' . $i . ', position = ' . $i . ' WHERE id_plugin = ' . $plugin['id_plugin'];
			$result &= Db::getInstance()->execute($sql);

			$sql = 'UPDATE `' . _DB_PREFIX_ . 'plugin_access` SET id_plugin = ' . $i . ' WHERE id_plugin = ' . $plugin['id_plugin'];
			$result &= Db::getInstance()->execute($sql);

			$sql = 'UPDATE `' . _DB_PREFIX_ . 'hook_plugin` SET id_plugin = ' . $i . '  WHERE id_plugin = ' . $plugin['id_plugin'];
			$result &= Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'hook_plugin_exceptions` SET id_plugin = ' . $i . '  WHERE id_plugin = ' . $plugin['id_plugin'];
			$result &= Db::getInstance()->execute($sql);

			$result &= Db::getInstance()->execute($sql);
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'plugin_group` SET id_plugin = ' . $i . '  WHERE id_plugin = ' . $plugin['id_plugin'];
			$result &= Db::getInstance()->execute($sql);

			if ($this->ephenyx_shop_active) {
				$sql = 'UPDATE `' . _DB_PREFIX_ . 'plugin_carrier` SET id_plugin = ' . $i . ' WHERE id_plugin = ' . $plugin['id_plugin'];
				$result &= Db::getInstance()->execute($sql);
				$sql = 'UPDATE `' . _DB_PREFIX_ . 'plugin_country` SET id_plugin = ' . $i . ' WHERE id_plugin = ' . $plugin['id_plugin'];
				$result &= Db::getInstance()->execute($sql);
				$sql = 'UPDATE `' . _DB_PREFIX_ . 'plugin_currency` SET id_plugin = ' . $i . '  WHERE id_plugin = ' . $plugin['id_plugin'];
				$result &= Db::getInstance()->execute($sql);
				$sql = 'UPDATE `' . _DB_PREFIX_ . 'payment_mode` SET id_plugin = ' . $i . '  WHERE id_plugin = ' . $plugin['id_plugin'];
				$result &= Db::getInstance()->execute($sql);
			}

			$i++;

		}

		$sql = 'ALTER TABLE`' . _DB_PREFIX_ . 'plugin` CHANGE `id_plugin` `id_plugin` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id_plugin`);';
		$result &= Db::getInstance()->execute($sql);
		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'plugin_access` ADD PRIMARY KEY(`id_profile`, `id_plugin`)';
		$result &= Db::getInstance()->execute($sql);
		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'plugin_group` ADD PRIMARY KEY(`id_plugin`, `id_group`)';
		$result &= Db::getInstance()->execute($sql);

		$result &= $this->resetPlugin($result);

		if ($result) {
			$this->context->phenyxConfig->updateValue('PLUGIN_MAINTENANCE', date("Y-m-d"));
		}

		return $result;

	}

	public function resetPlugin(&$result = true) {

		$query = 'SELECT *  FROM `' . _DB_PREFIX_ . 'plugin` ORDER BY id_plugin ASC';
		$plugins = Db::getInstance()->executeS($query);

		foreach ($plugins as $plugin) {

			if (file_exists(_EPH_PLUGIN_DIR_ . $plugin['name'] . '/' . $plugin['name'] . '.php')) {
				require_once _EPH_PLUGIN_DIR_ . $plugin['name'] . '/' . $plugin['name'] . '.php';
			} else

			if (file_exists(_EPH_SPECIFIC_PLUGIN_DIR_ . $plugin['name'] . '/' . $plugin['name'] . '.php')) {
				require_once _EPH_SPECIFIC_PLUGIN_DIR_ . $plugin['name'] . '/' . $plugin['name'] . '.php';
			}

			if (class_exists($plugin['name'], false)) {

				$tmpPlugin = Adapter_ServiceLocator::get($plugin['name']);

				if (method_exists($tmpPlugin, 'reset')) {

					$plugin = Plugin::getInstanceByName($plugin['name']);

					try {

						$result &= $plugin->reset();

					} catch (PhenyxException $e) {

						PhenyxLogger::addLog("Plugin reset error for :" . $plugin['name'] . " " . $e->getMessage(), 4);

					}

				}

			}

		}

		if ($this->context->cache_enable && is_object($this->context->cache_api)) {
			$this->context->cache_api->cleanCache();
		}

		$this->context->_session->destroy();

		return $result;

	}

	public function cleanHook() {

		$result = true;

		$query = 'SELECT DISTINCT(id_hook)  FROM `' . _DB_PREFIX_ . 'hook_plugin_exceptions`  ORDER BY id_hook ASC';

		$hooks = Db::getInstance()->executeS($query);

		foreach ($hooks as $hook) {
			$parent = Db::getInstance()->getValue(
				(new DbQuery())
					->select('`id_hook`')
					->from('hook')
					->where('`id_hook` = ' . (int) $hook['id_hook'])
			);

			if (!$parent) {
				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'hook_plugin_exceptions` WHERE id_hook = ' . $hook['id_hook'];

				$result &= Db::getInstance()->execute($sql);
			}

		}

		$query = 'SELECT DISTINCT(id_hook)  FROM `' . _DB_PREFIX_ . 'hook_plugin`  ORDER BY id_hook ASC';

		$hooks = Db::getInstance()->executeS($query);

		foreach ($hooks as $hook) {
			$parent = Db::getInstance()->getValue(
				(new DbQuery())
					->select('`id_hook`')
					->from('hook')
					->where('`id_hook` = ' . (int) $hook['id_hook'])
			);

			if (!$parent) {
				$sql = 'DELETE FROM `' . _DB_PREFIX_ . 'hook_plugin` WHERE id_hook = ' . $hook['id_hook'];

				$result &= Db::getInstance()->execute($sql);
			}

		}

		$query = 'SELECT *  FROM `' . _DB_PREFIX_ . 'hook` ORDER BY id_hook ASC';

		$hooks = Db::getInstance()->executeS($query);

		$i = 1;

		foreach ($hooks as $hook) {
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'hook` SET id_hook = ' . $i . ' WHERE id_hook = ' . $hook['id_hook'];

			$result &= Db::getInstance()->execute($sql);

			$sql = 'UPDATE `' . _DB_PREFIX_ . 'hook_plugin_exceptions` SET id_hook = ' . $i . ' WHERE id_hook = ' . $hook['id_hook'];

			$result &= Db::getInstance()->execute($sql);

			$sql = 'UPDATE `' . _DB_PREFIX_ . 'hook_plugin` SET id_hook = ' . $i . ' WHERE id_hook = ' . $hook['id_hook'];
			$result &= Db::getInstance()->execute($sql);
			$i++;

		}

		$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'hook` MODIFY `id_hook` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=' . $i . ';';
		$result &= Db::getInstance()->execute($sql);

		if ($result) {
			$this->context->phenyxConfig->updateValue('HOOK_MAINTENANCE', date("Y-m-d"));
		}

		return $result;

	}

	public function exportLang($iso, $theme, $plugins) {

		if ($iso && $theme) {

			$items = array_flip(Language::getFilesList($iso, $theme, false, false, false, false, false));
			$plugins = array_flip($this->getPluginFilesList($iso, $theme, $plugins));
			$fileName = _EPH_TRANSLATIONS_DIR_ . '/export/' . $iso . '.gzip';
			$gz = new Archive_Tar($fileName, true);
			$gz->createModify($items, null, _SHOP_ROOT_DIR_);
			$gz->addModify($plugins, null, _EPH_ROOT_DIR_ . '/includes');

			$pathFile = _EPH_ROOT_DIR_ . '/packs/' . _EPH_VERSION_ . '/' . $iso . '/' . $iso . '.gzip';
			copy($fileName, $pathFile);

		} else {

			$this->errors[] = $this->la('Please select a language and a theme.');
		}

		if (count($this->errors)) {
			$result = [
				'success' => false,
				'message' => implode(PHP_EOL, $this->errors),
			];
		} else {

			$result = [
				'success' => true,
				'message' => $this->la('Language has been exported successfully'),
			];
		}

		die(Tools::jsonEncode($result));
	}

	public function getPluginFilesList($isoFrom, $themeFrom, $plugins) {

		$filesPlugins = [];
		$number = 0;

		foreach ($plugins as $mod) {

			$modDir = null;

			if (is_dir(_EPH_PLUGIN_DIR_ . $mod)) {
				$modDir = _EPH_PLUGIN_DIR_ . $mod;
			} else if (is_dir(_EPH_SPECIFIC_PLUGIN_DIR_ . $mod)) {
				$modDir = _EPH_SPECIFIC_PLUGIN_DIR_ . $mod;
			}

			if (!$modDir) {
				continue;
			}

			// Legacy flat lang file

			if (file_exists($modDir . '/translations/' . (string) $isoFrom . '.php')) {
				$filesPlugins[$modDir . '/translations/' . (string) $isoFrom . '.php'] = ++$number;
			}

			// Per-domain lang files (admin, class, front, mail, pdf): each is independent and must all be exported

			foreach (['admin', 'class', 'front', 'mail', 'pdf'] as $domain) {

				$domainFile = $modDir . '/translations/' . (string) $isoFrom . '/' . $domain . '.php';

				if (file_exists($domainFile)) {
					$filesPlugins[$domainFile] = ++$number;
				}

			}

			

		}

		return $filesPlugins;
	}

	/**
	 * @deprecated Refactor 2026-05 — l'agrégation pré-page des traductions n'est
	 * plus utile : Translate.php charge maintenant les fichiers de chaque plugin
	 * à la demande avec un cache mémoire process-wide. La méthode est conservée
	 * en no-op pour ne pas casser un éventuel appelant externe (modules tiers).
	 *
	 * L'ancien corps (~300 lignes d'agrégation + écriture de fichiers .php) a
	 * été retiré ici — voir l'historique git pour la version complète.
	 *
	 * À supprimer définitivement quand on aura confirmé qu'aucun module externe
	 * ne l'appelle (recherche full-tree sur "mergeLanguages" + audit de quelques
	 * mois en prod sans incident).
	 */
	public function mergeLanguages() {

		return true;
	}

	protected function _legacy_mergeLanguages_DO_NOT_USE() {

		$iso = $this->context->language->iso_code;
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
    
    public function getIoFiles($content, $destination) {
        
        $path = dirname($destination);
        if(!is_dir(_EPH_ROOT_DIR_.$path)) {
            mkdir(_EPH_ROOT_DIR_.$path);
        }
                
        return file_put_contents(_EPH_ROOT_DIR_.$destination, $content);
    }

}
	
