<?php
use Defuse\Crypto\Crypto;
use \Curl\Curl;

class License extends PhenyxObjectModel {

	protected static $instance;

	public $website;
	public $type;
	public $is_main;
	public $id_customer;
	public $purchase_key;
	public $ftp_user;
	public $ftp_ssl;
	public $ftp_path;
	public $crypto_key;
	public $ftp_passwd;
	public $user_ip;
	public $active;
	public $master_shop = 0;
	public $iso_langs = [];
	public $plugins = [];
	public $rdb;
	public $autoupdate;
	public $has_device;
	public $has_cron;
	public $date_add;
	public $date_upd;
	public $partner;
	public $partner_firstname;
	public $partner_lastname;
	public $partner_company;

	public $cryp_key;

	public $referentEducationTopBars;

	public $referentShopTopBars;

	public static $definition = [
		'table'   => 'license',
		'primary' => 'id_license',
		'fields'  => [
			'website'       => ['type' => self::TYPE_STRING, 'validate' => 'isUrl'],
			'type'          => ['type' => self::TYPE_STRING, 'copy_post' => false],
			'is_main'       => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
			'id_customer'   => ['type' => self::TYPE_INT],
			'purchase_key'  => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'copy_post' => false],
			'ftp_user'      => ['type' => self::TYPE_STRING, 'validate' => 'isPasswd', 'required' => true, 'size' => 60],
			'ftp_ssl'       => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
			'ftp_path'      => ['type' => self::TYPE_STRING, 'copy_post' => false],
			'crypto_key'    => ['type' => self::TYPE_STRING, 'validate' => 'isPasswd'],
			'ftp_passwd'    => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 60],
			'user_ip'       => ['type' => self::TYPE_STRING, 'copy_post' => false],
			'active'        => ['type' => self::TYPE_BOOL],
			'has_device'    => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
			'master_shop'   => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
			'has_cron'      => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
			'iso_langs'     => ['type' => self::TYPE_STRING, 'copy_post' => false],
			'plugins'       => ['type' => self::TYPE_STRING, 'copy_post' => false],
			'autoupdate'    => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
			'rdb'           => ['type' => self::TYPE_INT],
			'date_add'      => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
			'date_upd'      => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
		],
	];

	public function __construct($id = null) {

		parent::__construct($id);

		if ($this->id) {

			$partner = new Customer($this->id_customer);
            if (!empty($this->iso_langs)  && Validate::isJSON($this->iso_langs)) {
				$this->iso_langs = $this->context->_tools->jsonDecode($this->iso_langs, true);
			}
			
            if (!empty($this->plugins) && Validate::isJSON($this->plugins)) {
				$this->plugins = $this->context->_tools->jsonDecode($this->plugins, true);
			}
			
			$this->partner_firstname = $partner->firstname;
			$this->partner_lastname = $partner->lastname;
			$this->partner_company = $partner->company;
			$string = $this->purchase_key . '/' . $this->website;
			$this->cryp_key = $this->context->_tools->encrypt_decrypt('encrypt', $string, $this->crypto_key, $this->purchase_key);
			
		}

	}
	
	public static function buildObject( $id, $idLang = null, $className = null) {
        
        $objectData = parent::buildObject( $id, $idLang, $className);
		if (!empty($objectData['iso_langs'])  && Validate::isJSON($objectData['iso_langs'])) {
			$objectData['iso_langs'] = Tools::jsonDecode($objectData['iso_langs'], true);
		}
			
        if (!empty($objectData['plugins']) && Validate::isJSON($objectData['plugins'])) {
			$objectData['plugins'] = Tools::jsonDecode($objectData['plugins'], true);
		}
		$string = $objectData['purchase_key'].'/' . $objectData['website'];
		$objectData['cryp_key'] = Tools::encrypt_decrypt('encrypt', $string, $objectData['crypto_key'], $objectData['purchase_key']);
       
        return Tools::jsonDecode(Tools::jsonEncode($objectData));
    }

	public static function getInstance($id = null, $idLang = null) {

		if (!License::$instance) {
			License:$instance = new License($id, $idLang);
		}

		return License::$instance;
	}

	public function add($autoDate = true, $nullValues = false) {

		if (is_array($this->iso_langs)) {
			$this->iso_langs = Tools::jsonEncode($this->iso_langs);
		}

		if (is_array($this->plugins)) {
			$this->plugins = Tools::jsonEncode($this->plugins);
		}

		return parent::add($autoDate, true);
	}

	public function update($nullValues = false) {

		if (is_array($this->iso_langs)) {
			$this->iso_langs = Tools::jsonEncode($this->iso_langs);
		}

		if (is_array($this->plugins)) {
			$this->plugins = Tools::jsonEncode($this->plugins);
		}

		return parent::update(true);
	}

	public static function getCustomer() {

		$users = [];
		$customers = Db::getInstance()->executeS(
			(new DbQuery())
				->select('`id_user`,  `firstname`, `lastname`')
				->from('user')
				->where('`active` = 1')
				->orderBy('id_user DESC')
		);

		foreach ($customers as $customer) {
			$users[] = [
				'name' => $customer['firstname'] . ' ' . $customer['lastname'],
				'id'   => $customer['id_user'],
			];
		}

		return $users;
	}

	public static function getMasterShop() {}
	
	public static function getAutoUpdateLiceneCollection() {

		$collection = [];
		$licenses = Db::getInstance()->executeS(
			(new DbQuery())
				->select('id_license')
				->from('license')
				->where('autoupdate = 1')
		);

		foreach ($licenses as $license) {

			$collection[] = new License($license['id_license']);
		}

		return $collection;
	}

	public static function getLiceneCollection() {

		$collection = [];
		$licenses = Db::getInstance()->executeS(
			(new DbQuery())
				->select('id_license')
				->from('license')
				->where('active = 1')
		);

		foreach ($licenses as $license) {

			$collection[] = new License($license['id_license']);
		}

		return $collection;
	}
    
    public static function getObjLicenseCollection() {

		$collection = [];
		$licenses = Db::getInstance()->executeS(
			(new DbQuery())
				->select('id_license')
				->from('license')
				->where('active = 1')
		);

		foreach ($licenses as $license) {

			$collection[] = License::buildObject($license['id_license']);
		}

		return $collection;
	}

	public static function getLicenceBykey($key) {

		$id = Db::getInstance()->getValue(
			(new DbQuery())
				->select('id_license')
				->from('license')
				->where('`purchase_key` = "' . $key . '"')
		);

		if ($id) {
			return new License($id);
		}

		return false;
	}

	public static function getEducationLicenceCollection() {

		$collection = [];
		$licenses = Db::getInstance()->executeS(
			(new DbQuery())
				->select('id_license')
				->from('license')
				->where('`has_device` = 1')
		);

		foreach ($licenses as $license) {

			$collection[] = new License($license['id_license']);
		}

		return $collection;
	}

	public static function generateSecureKey($website, $purchaseKey, $securekey) {

		$data = $website . '|' . $purchaseKey;
		$ciphertext = Crypto::encrypt($data, $securekey, true);
		return $ciphertext;
	}

	public static function checkLicenseValidity($website, $purchaseKey, $userIp, $requestLang, $psVersion, $ephVersion) {

		require_once _EPH_CONFIG_DIR_ . 'bootstrap.php';
		$isCorrectWebsite = true;
		$isCorrectIp = true;
		$interval = 0;
		$sql = new DbQuery();
		$sql->select('*');
		$sql->from(bqSQL(static::$definition['table']));
		$sql->where('`purchase_key` = \'' . pSQL($purchaseKey) . '\'');

		$result = Db::getInstance()->getRow($sql);

		if (!empty($result)) {

			$id_license = (int) $result['id_license'];
			$secret_iv = Tools::hash($website);

			if (!empty($result['website']) && $result['website'] != $website) {
				$isCorrectWebsite = false;
			} else {
				$license = new License($id_license);

				if ($license->active == 0) {
					$interval = License::registerLicense($license, $userIp);
				} else {

					if ($license->user_ip != $userIp) {
						$isCorrectIp = false;
					}

					$expiryDate = new DateTime($license->date_exp);
					$currentTime = new DateTime("now");
					$interval = $currentTime->diff($expiryDate);
					$interval = $interval->format('%R%a');
				}

			}

		}

		return VersionController::checkLicense($website, $purchaseKey, $userIp, $requestLang, $psVersion, $ephVersion, $isCorrectWebsite, $isCorrectIp, $interval);

	}

	public static function registerLicense(License $license, $userIp) {

		$license->active = 1;
		$license->user_ip = $userIp;
		$license->date_exp = date('Y-m-d H:i:s', strtotime('+1 years'));

		if ($license->update()) {
			return 365;
		}

	}

	public static function checkLicense($purchaseKey, $website) {

		$sql = new DbQuery();
		$sql->select('*');
		$sql->from(bqSQL(static::$definition['table']));
		$sql->where('`website` LIKE \'' . pSQL($website) . '\'');
		$result = Db::getInstance()->getRow($sql);

		if (!empty($result)) {

			$id_license = (int) $result['id_license'];
			$license = new License($id_license);

			if ($license->purchase_key == $purchaseKey) {
				return $license;
			}

			return false;

		}

		return false;

	}

	public function getLicense($purchaseKey, $website) {

		$sql = new DbQuery();
		$sql->select('*');
		$sql->from(bqSQL(static::$definition['table']));
		$sql->where('`purchase_key` = \'' . pSQL($purchaseKey) . '\'');
		$result = Db::getInstance()->getRow($sql);

		if (!empty($result)) {

			$id_license = (int) $result['id_license'];
			$license = new License($id_license);

			if ($license->website == $website) {
				return Tools::jsonDecode(Tools::jsonEncode($license), true);
			}

			return false;

		}

	}

	public function encrypt_decrypt($action, $string, $secret_key, $secret_iv) {

		$output = false;
		$encrypt_method = "AES-256-CBC";
		$key = hash('sha256', $secret_key);
		$iv = substr(hash('sha256', $secret_iv), 0, 16);

		if ($action == 'encrypt') {
			$output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
			$output = base64_encode($output);
		} else

		if ($action == 'decrypt') {
			$output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
		}

		return $output;
	}

	public static function registerNewSubscription($data) {

		$session = $data['session'];

		$user_email = $session->adminEmail;

		$idCountry = Country::getByIso($session->companyCountry);
		$customer = User::userExists($user_email);

		if (User::userExists($user_email)) {
			$user = User::getUsersByEmail($wuser['user_email']);
			$user = new Customer($user[0]['id_user']);
			$user->firstname = $session->adminFirstname;
			$user->lastname = $session->adminLastname;
			$user->update();
		} else {
			$user = new User();
			$user->email = $user_email;
			$user->firstname = $session->adminFirstname;
			$user->lastname = $session->adminLastname;
			$password = Tools::generateStrongPassword();
			$user->passwd = Tools::hash($password);
			$user->password = $password;
			$user->company = $session->companyName;
			$user->ip_registration_newsletter = pSQL(Tools::getRemoteAddr());
			$user->newsletter_date_add = pSQL(date('Y-m-d H:i:s'));
			$user->newsletter = 1;
			$user->id_default_group = Context::getContext()->phenyxConfig->get('EPH_DEFAULT_GROUP');
			$user->groupBox = [3, 4];
			$user->id_theme = 1;
			$postCode = $session->companyPostCode;

			$user->customer_code = User::generateUserCode($idCountry, $postCode);
			try {
				$result = $user->add(true, true, false);
			} catch (Exception $ex) {
				$message = sprintf($this->la('Error while adding an user: %s'), $ex->getMessage());
				PhenyxLogger::addLog($message, 3, null, 'License');
			}

			if ($result) {
				$address = new Address();
				$address->id_user = $user->id;
				$address->id_country = $idCountry;
				$address->alias = 'Facturation';
				$address->company = $session->companyName;
				$address->lastname = $user->lastname;
				$address->firstname = $user->firstname;
				$address->address1 = $session->companyAddress;
				$address->postcode = $postCode;
				$address->city = $session->companyCity;
				$address->phone = $session->companyPhone;

				$mobile = str_replace(' ', '', $address->phone);
				$country = new Country($idCountry);

				if ($country->call_prefix > 0) {
					$mobile = '+' . $country->call_prefix . substr($mobile, 1);
					$address->phone = $mobile;
				}

				$result = $address->add();
			}

		}

		$license = new License();
		$license->id_user = $user->id;
		$license->website = $session->website;
		$license->type = 'is_corporate';
		$license->purchase_key = self::generateLicenceKey();
		$license->ftp_user = $session->ftp_user;
		$license->crypto_key = $session->crypto_key;
		$license->ftp_password = $session->ftp_password;
		$license->user_ip = $session->databaseServer;
		$license->iso_langs = [$session->companyCountry];

		$result = $license->add();

		if ($result) {
			return $license->purchase_key;
		}

		return null;

	}

	public static function generateLicenceKey() {

		$tokens = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$segment_chars = 5;
		$num_segments = 4;
		$key_string = '';

		for ($i = 0; $i < $num_segments; $i++) {
			$segment = '';

			for ($j = 0; $j < $segment_chars; $j++) {
				$segment .= $tokens[rand(0, 35)];
			}

			$key_string .= $segment;

			if ($i < ($num_segments - 1)) {
				$key_string .= '-';
			}

		}

		return $key_string;
	}

	public static function synchOfFiles() {

		$recursive_directory = ['app/education_classes', 'app/education_controllers'];

		$iterator = new AppendIterator();

		foreach ($recursive_directory as $key => $directory) {
			$iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_EPH_ROOT_DIR_ . '/' . $directory . '/')));

		}

		foreach ($iterator as $file) {

			if (in_array($file->getFilename(), ['.', '..', 'index.php'])) {
				continue;
			}

			$filePath = $file->getPathname();
			$target = str_replace('app', 'phenyxEducation', $filePath);
			copy($filePath, $target);
		}

	}

	public function generateReferenceFiles() {

       
		if (Validate::isJSON($this->iso_langs)) {
			$this->iso_langs = Tools::jsonDecode($this->iso_langs, true);
		}

		if (Validate::isJSON($this->plugins)) {
			$this->plugins = Tools::jsonDecode($this->plugins, true);
		}
        

		$recursive_directory = [
			'app/xml',
			'content/css',
			'content/mails',
			'content/pdf',
			'content/mp3',
			'content/pdf',
			'content/img/pdfWorker',
			'content/fonts',
			'content/js',
			'content/backoffice',
			'content/themes/phenyx-theme-default',
			'includes/classes',
			'includes/controllers',
            'vendor/ephenyxdigital',
            'webephenyx',
		];

		foreach ($this->iso_langs as $iso_langs) {

			foreach ($iso_langs as $iso_lang => $name) {
				$recursive_directory[] = 'content/translations/' . $iso_lang;
			}

		}

		
		foreach ($this->plugins as $plugin => $installed) {

			if ($installed) {
				$recursive_directory[] = 'includes/plugins/' . $plugin;
			}

		}

		$iterator = new AppendIterator();

		foreach ($recursive_directory as $key => $directory) {

			if (is_dir(_EPH_ROOT_DIR_ . '/' . $directory . '/')) {
				$iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(_EPH_ROOT_DIR_ . '/' . $directory . '/')));
			}

		}

		$iterator->append(new DirectoryIterator(_EPH_ROOT_DIR_ . '/content/themes/'));
		$iterator->append(new DirectoryIterator(_EPH_ROOT_DIR_ . '/app/'));

		$iterator->append(new DirectoryIterator(_EPH_ROOT_DIR_ . '/phenyxDigital/'));
		
        foreach ($iterator as $file) {
			if (in_array($file->getFilename(), ['.', '..', '.htaccess', 'composer.lock', 'settings.inc.php', '.gitattributes', '.user.ini', '.php-ini', '.php-version'])) {
				continue;
			}
			$filePath = $file->getPathname();
			$filePath = str_replace(_EPH_ROOT_DIR_, '', $filePath);
			$ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);

			if (is_dir($file->getPathname())) {
				continue;
			}

			if ($ext == 'txt') {
				continue;
			}

			if ($ext == 'csv') {
				continue;
			}

			if ($ext == 'zip') {
				continue;
			}
            
            if ($ext == 'dat') {
				continue;
			}

			if (str_contains($filePath, 'custom_') && $ext == 'css') {
				continue;
			}

			if (str_contains($filePath, '/plugins/') && str_contains($filePath, '/translations/')) {

				foreach ($this->plugins as $plugin => $installed) {

					if (str_contains($filePath, '/plugins/' . $plugin . '/translations/')) {

						$test = str_replace('/includes/plugins/' . $plugin . '/translations/', '', $filePath);

						$test = str_replace('.php', '', $test);

						if (!array_key_exists($test, $this->iso_langs)) {
							continue;

						}

					}

				}

			}
            
            if (str_contains($filePath, '/webephenyx/cronGetLang.php')) {
				continue;
			}
            
            if (str_contains($filePath, '/webephenyx/cronOF.php')) {
				continue;
			}
            
            if (str_contains($filePath, '/webephenyx/fonts.php')) {
				continue;
			}
            if (str_contains($filePath, '/webephenyx/veille.php')) {
				continue;
			}

			
            if (str_contains($filePath, '/.git/')) {
				continue;
			}
            

			if (str_contains($filePath, '/uploads/')) {
				continue;
			}

			if (str_contains($filePath, '/cache/')) {
				continue;
			}

			if (str_contains($filePath, 'sitemap.xml')) {
				continue;
			}
            if (str_contains($filePath, 'truc')) {
				continue;
			}


			$md5List[$filePath] = md5_file($file->getPathname());
		}

		file_put_contents(
			_EPH_CONFIG_DIR_ . 'json/files.json',
			json_encode($md5List, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
		);

	}

	public static function executeCron() {

		$licences = new PhenyxCollection('License');
		$licences->where('has_cron', '=', 1);

		foreach ($licences as $licence) {
            $licence = new License($licence->id);
			$licence->executeCronAction('executeCron');

		}

	}

	public static function executeGetLangCron() {

		$licences = new PhenyxCollection('License');
		$licences->where('has_cron', '=', 1);

		foreach ($licences as $licence) {
			$licence->getInstalledLangs();
			$lic = new License($licence->id);
			$lic->mergeGlobalLanuages();

		}

	}

	public static function executeFileCron() {
        
		$licences = new PhenyxCollection('License');
		$licences->where('has_cron', '=', 1);

		foreach ($licences as $licence) {
            $licence = new License($licence->id);
			$licence->getOwnJsonFile();

		}

	}
    
    public function showTab($id_back_tab) {
        
        $url = 'https://' . $this->website . '/api';
		$data_array = [
			'action'      => 'showTab',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
			'id_back_tab'   => $id_back_tab,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
        
    }
    
    public function hideTab($id_back_tab) {
        
        $url = 'https://' . $this->website . '/api';
		$data_array = [
			'action'      => 'hideTab',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
			'id_back_tab'   => $id_back_tab,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
        
    }

	public function compareSqlTable($table) {

		$dbParams = $this->getdBParam();

		$context = Context::getContext();

		$dbName = $dbParams['_DB_NAME_'];
		$dbPasswd = $dbParams['_DB_PASSWD_'];
		$dbUser = $dbParams['_DB_USER_'];

		$current = Db::getInstance()->executeS(
			(new DbQuery())
				->select('a.*, l.*')
				->from($table, 'a')
				->leftJoin($table . '_lang', 'l', 'l.`id_' . $table . '` = a.`id_' . $table . '` AND l.`id_lang` = ' . $context->language->id)
		);

		$distant = Db::getCrmInstance($dbUser, $dbPasswd, $dbName)->executeS(
			(new DbQuery())
				->select('a.*, l.*')
				->from($table, 'a')
				->leftJoin($table . '_lang', 'l', 'l.`id_' . $table . '` = a.`id_' . $table . '` AND l.`id_lang` = ' . $context->language->id)
		);
		$currentTable = [];

		foreach ($current as $index => $contentLines) {

			foreach ($contentLines as $key => $line) {

				if ($key == 'id_' . $table) {
					continue;
				}

				$currentTable[$index][] = [$key => $line];
			}

		}

	}

	public static function generateSqlTable($table) {

		$date = time();

		$request = '';

		$idLicenseReferer = Context::getContext()->phenyxConfig->get('EPH_TOPBAR_REFERER');
		$licenceReferer = new License($idLicenseReferer);
		$dbParams = $licenceReferer->getdBParam();
		$dbName = $dbParams['_DB_NAME_'];
		$dbPasswd = $dbParams['_DB_PASSWD_'];
		$dbUser = $dbParams['_DB_USER_'];

		$schema = Db::getCrmInstance($dbUser, $dbPasswd, $dbName)->executeS('SHOW CREATE TABLE `' . _DB_PREFIX_ . $table . '`');

		if (count($schema) != 1 || !isset($schema[0]['Table']) || !isset($schema[0]['Create Table'])) {
			fclose($fp);
			return false;
		}

		$request .= 'DROP TABLE IF EXISTS `' . $schema[0]['Table'] . '`;' . PHP_EOL;

		$request .= $schema[0]['Create Table'] . ";\n\n";

		$data = Db::getCrmInstance($dbUser, $dbPasswd, $dbName)->query('SELECT * FROM `' . $schema[0]['Table'] . '`');
		$sizeof = Db::getCrmInstance($dbUser, $dbPasswd, $dbName)->NumRows();
		$lines = explode("\n", $schema[0]['Create Table']);

		if ($data && $sizeof > 0) {
			// Export the table data

			$request .= 'INSERT INTO `' . $schema[0]['Table'] . "` VALUES\n";
			$i = 1;

			while ($row = Db::getCrmInstance($dbUser, $dbPasswd, $dbName)->nextRow($data)) {
				$s = '(';

				foreach ($row as $field => $value) {
					$tmp = "'" . pSQL($value, true) . "',";

					if ($tmp != "'',") {
						$s .= $tmp;
					} else {

						foreach ($lines as $line) {

							if (strpos($line, '`' . $field . '`') !== false) {

								if (preg_match('/(.*NOT NULL.*)/Ui', $line)) {
									$s .= "'',";
								} else {
									$s .= 'NULL,';
								}

								break;
							}

						}

					}

				}

				$s = rtrim($s, ',');

				if ($i % 200 == 0 && $i < $sizeof) {
					$s .= ");\nINSERT INTO `" . $schema[0]['Table'] . "` VALUES\n";
				} else

				if ($i < $sizeof) {
					$s .= "),\n";
				} else {
					$s .= ");\n";
				}

				$request .= $s;
				++$i;
			}

		}

		return $request;
	}

	public static function getJsonFileFromWebSite($website) {

		unlink(_EPH_CONFIG_DIR_ . 'json/' . $website . '.json');

		$data_array = [];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post('https://' . $website . '/api', json_encode($data_array));
		$md5List = $curl->response;

		if (is_array($md5List)) {
			file_put_contents(
				_EPH_CONFIG_DIR_ . 'json/' . $website . '.json',
				json_encode($md5List, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
			);
			return true;
		}

		return false;

	}

	public function cleanDirectory($dir) {

		$url = 'https://' . $this->website . '/api';
		$data_array = [
			'action'      => 'cleanDirectory',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
			'directory'   => $dir,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;
	}
    
    public function getGenerateTabs() {
        
        $url = 'https://' . $this->website . '/api';
		$data_array = [
			'action'     => 'getGenerateTabs',
            'license_key' => $this->purchase_key,
			'crypto_key' => $this->cryp_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->setTimeout(6000);
		$curl->post($url, json_encode($data_array));
		return $curl->response;

        
    }

	public function getFrontJsonFile() {

		$url = 'https://' . $this->website . '/api';
		$data_array = [
			'action'     => 'getFrontJsonFile',
			'crypto_key' => $this->cryp_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->setTimeout(6000);
		$curl->post($url, json_encode($data_array));
		$md5List = $curl->response;

		if (is_array($md5List)) {
			file_put_contents(
				_EPH_CONFIG_DIR_ . 'json/front-' . $this->id . '.json',
				json_encode($md5List, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
			);
			return true;
		}

		return false;

	}

	public function cleanEmptyDirectory() {

		$url = 'https://' . $this->website . '/api';
		$data_array = [
			'action'      => 'cleanEmptyDirectory',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->setTimeout(6000);
		$curl->post($url, json_encode($data_array));

	}

	public function getJsonFile() {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'getJsonFile',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
		];

		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
		$curl->setTimeout(6000);
		$curl->post($url, json_encode($data_array));

		if ($curl->error) {
			$return = [
				'success' => false,
				'message'   => 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage,
			];
            die(Tools::jsonEncode($return));

		}

		$md5List = $curl->response;

		if (is_array($md5List)) {
			file_put_contents(
				_EPH_CONFIG_DIR_ . 'json/' . $this->id . '.json',
				json_encode($md5List, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
			);
			$return = [
				'success' => true,
			];
		} else {
            $file = fopen("testgetJsonFile.txt","w");
            fwrite($file,$md5List);
            
            $return = [
				'success' => false,
                'message'   => 'Error: no array return',
			];
        }

		die(Tools::jsonEncode($return));

	}

	public function getOwnJsonFile() {

		$url = 'https://' . $this->website . '/api';
		$data_array = [
			'action'      => 'getOwnJsonFile',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
		];

		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->setOpt(CURLOPT_TIMEOUT, 1);
        $curl->setOpt(CURLOPT_NOSIGNAL, 1);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));

		return true;

	}

	public function generateClassIndex() {

		$url = 'https://' . $this->website . '/api';
		$data_array = [
			'action'      => 'generateClassIndex',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setOpt(CURLOPT_TIMEOUT, 1);
        $curl->setOpt(CURLOPT_NOSIGNAL, 1);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
        return true;
		
	}

	public function getInstalledLangs() {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'getInstalledLangs',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
		];

		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
		$curl->setTimeout(6000);
		$curl->post($url, json_encode($data_array));
		$langs = $curl->response;

		if (is_array($langs)) {
			$this->iso_langs = $langs;
			$this->update();
		}

		if (is_string($this->iso_langs)) {
			$this->iso_langs = Tools::jsonDecode($this->iso_langs, true);
		}

	}

	public function mergeGlobalLanuages() {

		$translations = new Translation(null, $this->iso_langs);
		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'       => 'mergeGlobalLanuages',
			'translations' => $translations->translations,
			'license_key'  => $this->purchase_key,
			'crypto_key'   => $this->cryp_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
	}

	public function getPluginOnDisk() {

		$url = 'https://' . $this->website . '/api';
		$data_array = [
			'action'      => 'getPluginOnDisk',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
		];

		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
		$curl->setTimeout(6000);
		$curl->post($url, json_encode($data_array));
		$plugins = $curl->response;

		if (is_array($plugins)) {

			$this->plugins = $plugins;
			$this->update();
		} else {

		}

		if (is_string($this->plugins)) {
			$this->plugins = Tools::jsonDecode($this->plugins, true);
		}

	}

	public function getXmlDataBase() {

		$database = [];

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'getXmlDatabase',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
		$curl->post($url, json_encode($data_array));
		$formattedXML = $curl->response;
		$fp = fopen(_EPH_CONFIG_DIR_ . 'json/' . $this->id . '.xml', 'w+');
		fwrite($fp, $formattedXML);
		fclose($fp);

		$xml = @simplexml_load_file(_EPH_CONFIG_DIR_ . 'json/' . $this->id . '.xml');
		$tables = json_decode(json_encode($xml->database), true);

		foreach ($tables as $key => $column) {
			$columns = [];

			foreach ($column['columns'] as $col => $attribute) {
				$columns[$col]['type'] = $attribute['@attributes']['type'];
				$columns[$col]['Null'] = $attribute['@attributes']['Null'];
			}

			$database[$key] = $columns;
		}

		return $database;
	}

	public function getdBParam() {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'getdBParam',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;

	}

	public function getNeededSupplies() {

		$url = 'https://' . $this->website . '/api';
		$data_array = [
			'action'      => 'getNeededSupplies',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;
	}

	public function getExpeditionFile() {

		$url = 'https://' . $this->website . '/api';
		$data_array = [
			'action'      => 'getExpeditionFile',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryptthis->cryp_keyo_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;
	}

	public function pushSqlRequest($query, $method) {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'pushSqlRequest',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
			'query'       => $query,
			'method'      => $method,
		];

		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;

	}

	public function getVendoStatus() {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'getVendoStatus',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;
	}

	public function getCertificationFile($idMonth) {

		$url = 'https://' . $this->website . '/api';
		$data_array = [
			'action'      => 'getCertificationFile',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
			'idMonth'     => $idMonth,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;
	}

	public function mergeLanuages() {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'mergeLanuages',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
        $curl->setOpt(CURLOPT_TIMEOUT, 1);
        $curl->setOpt(CURLOPT_NOSIGNAL, 1);
		$curl->post($url, json_encode($data_array));
		return true;
	}

	public function getPhenyxInvoices() {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'getPhenyxInvoices',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;
	}

	public function getPhenyxSupplies() {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'getPhenyxSupplies',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;
	}

	public function getPhenyxPrevisionnel($dateFrom, $dateTo) {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'getPhenyxPrevisionnel',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
			'dateFrom'    => $dateFrom,
			'dateTo'      => $dateTo,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;
	}

	public function getDistantTables() {

		$currentTables = Db::getInstance()->executeS('SHOW TABLES');

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'        => 'getDistantTables',
			'license_key'   => $this->purchase_key,
			'crypto_key'    => $this->cryp_key,
			'currentTables' => $currentTables,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;

	}

	public function generateAdminFichierAccess() {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'generateAdminHtaccess',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;

	}
    
    public function cleanBckTab() {
        
        $url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'cleanBckTab',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
        return $curl->response;
    }
    
    public function cleanMeta() {
        
        $url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'cleanMeta',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
        return $curl->response;
    }
    
    public function cleanPlugin() {
        
        $url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'cleanPlugin',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
        return $curl->response;
    }
    
    public function cleanHook() {
        
        $url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'cleanHook',
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
        return $curl->response;
    }

	public function executeCronAction($cronAction) {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => $cronAction,
			'license_key' => $this->purchase_key,
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;
	}

	public function upgradeVersion($version) {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'writeNewSettings',
			'version'     => $version,
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;
	}

	public function createNotification($notification) {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'createNotification',
			'object'      => $notification,
			'license_key' => $this->purchase_key,
			'crypto_key'  => $this->cryp_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));

		if ($curl->error) {
			$file = fopen("testNotificationError.txt", "a");
			fwrite($file, $this->website . PHP_EOL);
			fwrite($file, 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage . PHP_EOL);
		}

		return $curl->response;
	}

	public function getEmployeeInformation($idEmployee) {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'getEmployeeInformation',
			'crypto_key'  => $this->cryp_key,
			'license_key' => $this->purchase_key,
			'idEmployee'  => $idEmployee,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;
	}

	public function getEmployeeEmail($idEmployee) {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'getEmployeeEmail',
			'crypto_key'  => $this->cryp_key,
			'license_key' => $this->purchase_key,
			'idEmployee'  => $idEmployee,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;
	}

	public function pushTracking($session) {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'pushTracking',
			'crypto_key'  => $this->cryp_key,
			'license_key' => $this->purchase_key,
			'session'     => $session,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;
	}
    
    public function pushFile($origine, $destination) {
        
        $size = filesize($origine);
        if($size > 245385110) {
           // return $this->pushZipFile($origine, $destination);
        }
        $content = file_get_contents($origine);

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'       => 'downloadFile',
            'content'      => $content,
            'destination'  => $destination,
			'crypto_key'   => $this->cryp_key,
			'license_key'  => $this->purchase_key,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;
	}
    
    public function pushZipFile($zipPath) {
       
        $url = 'https://' . $this->website . '/api';
        $data_array = [
           'action'       => 'downloadZipFile',
           'zipPath'      => $zipPath,
           'crypto_key'   => $this->cryp_key,
           'license_key'  => $this->purchase_key,
        ];
        $curl = new Curl();
        $curl->setDefaultJsonDecoder($assoc = true);
        $curl->setHeader('Content-Type', 'application/json');
        //$curl->setTimeout(6000);
        $curl->post($url, json_encode($data_array));
        return $curl->response;
        
        
    }

	public function deleteBulkFile($files) {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'deleteBulkFile',
			'crypto_key'  => $this->cryp_key,
			'license_key' => $this->purchase_key,
			'files'       => $files,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;
	}
    
    public function deleteFile($files) {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'deleteFiles',
			'crypto_key'  => $this->cryp_key,
			'license_key' => $this->purchase_key,
			'files'        => $files,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;
	}
    
    public function deleteJsonFile() {

		if (file_exists(_EPH_CONFIG_DIR_ . 'json/' . $this->id . '.json')) {
			return unlink(_EPH_CONFIG_DIR_ . 'json/' . $this->id . '.json');
		}
	}
    
    public function pushMeta($meta) {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'pushMeta',
			'crypto_key'  => $this->cryp_key,
			'license_key' => $this->purchase_key,
			'meta'        => $meta,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;
	}
    
    public function pushMetas($metas) {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'pushMetas',
			'crypto_key'  => $this->cryp_key,
			'license_key' => $this->purchase_key,
			'metad'        => $metas,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;
	}
    
    public function pushBackTab($backTab) {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'pushBackTab',
			'crypto_key'  => $this->cryp_key,
			'license_key' => $this->purchase_key,
			'backTab'        => $backTab,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;
	}
    
    public function pushBackTabs($backTabs) {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'pushBackTabs',
			'crypto_key'  => $this->cryp_key,
			'license_key' => $this->purchase_key,
			'backTabs'        => $backTabs,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;
	}
    
    public function pushObject($class, $objet) {

		$url = 'https://' . $this->website . '/api';

		$data_array = [
			'action'      => 'pushObject',
			'crypto_key'  => $this->cryp_key,
			'license_key' => $this->purchase_key,
			'class'        => $class,
            'objet'        => $objet,
		];
		$curl = new Curl();
		$curl->setDefaultJsonDecoder($assoc = true);
		$curl->setHeader('Content-Type', 'application/json');
		$curl->post($url, json_encode($data_array));
		return $curl->response;
	}

}
