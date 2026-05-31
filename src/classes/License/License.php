<?php

namespace EphenyxDigital\QuantumCore;

use Address;
use AppendIterator;
use Customer;
use DateTime;
use DirectoryIterator;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use User;
use VersionController;



/**
 * Class License
 *
 * Corrections appliquées :
 *
 * BUGS CRITIQUES
 * - getInstance()          : License:$instance → License::$instance (parse error)
 * - getExpeditionFile()    : $this->cryptthis->cryp_keyo_key → $this->cryp_key
 * - pushMetas()            : clé 'metad' → 'metas'
 * - generateSqlTable()     : fclose($fp) sur $fp non défini → supprimé
 * - registerNewSubscription(): $wuser['user_email'] → $user_email
 * - registerNewSubscription(): $this->la() dans méthode statique → PhenyxLogger direct
 * - registerNewSubscription(): $license->ftp_password → $license->ftp_passwd
 * - registerNewSubscription(): $license->id_user → $license->id_customer
 *
 * SÉCURITÉ
 * - getLicenceBykey()  : guillemets doubles → pSQL() (SQL injection)
 * - generateLicenceKey(): rand() → random_int() (CSPRNG)
 * - getJsonFile()      : die() supprimé du modèle → retour tableau + exception
 * - website validé avant construction de l'URL cURL (anti-SSRF)
 *
 * QUALITÉ
 * - callApi() : méthode privée centrale — élimine ~400 lignes de boilerplate cURL
 * - Tous les fopen/fwrite de debug supprimés (testgetJsonFile.txt, testNotificationError.txt)
 * - str_contains($filePath, 'truc') supprimé (filtre de debug oublié)
 * - content/pdf dupliqué dans $recursive_directory supprimé
 * - Timeouts explicites sur toutes les méthodes callApi()
 * - getJsonFile() ne fait plus die() — retourne un tableau de résultat
 */

use Defuse\Crypto\Crypto;
// Aliasé en PhpCurl : le nom court "Curl" entre en collision avec l'alias
// EphenyxDigital\QuantumCore\Curl généré par aliases.php (fatal au chargement).
use Curl\Curl as PhpCurl;

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
            'website'      => ['type' => self::TYPE_STRING, 'validate' => 'isUrl'],
            'type'         => ['type' => self::TYPE_STRING, 'copy_post' => false],
            'is_main'      => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'id_customer'  => ['type' => self::TYPE_INT],
            'purchase_key' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'copy_post' => false],
            'ftp_user'     => ['type' => self::TYPE_STRING, 'validate' => 'isPasswd', 'required' => true, 'size' => 60],
            'ftp_ssl'      => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'ftp_path'     => ['type' => self::TYPE_STRING, 'copy_post' => false],
            'crypto_key'   => ['type' => self::TYPE_STRING, 'validate' => 'isPasswd'],
            'ftp_passwd'   => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 60],
            'user_ip'      => ['type' => self::TYPE_STRING, 'copy_post' => false],
            'active'       => ['type' => self::TYPE_BOOL],
            'has_device'   => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'master_shop'  => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'has_cron'     => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'iso_langs'    => ['type' => self::TYPE_STRING, 'copy_post' => false],
            'plugins'      => ['type' => self::TYPE_STRING, 'copy_post' => false],
            'autoupdate'   => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'rdb'          => ['type' => self::TYPE_INT],
            'date_add'     => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
            'date_upd'     => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
        ],
    ];

    // =========================================================================
    // CONSTRUCTEUR & INITIALISATION
    // =========================================================================

    public function __construct($id = null) {

        parent::__construct($id);

        if ($this->id) {
            $partner = new Customer($this->id_customer);

            if (!empty($this->iso_langs) && Validate::isJSON($this->iso_langs)) {
                $this->iso_langs = $this->context->_tools->jsonDecode($this->iso_langs, true);
            }

            if (!empty($this->plugins) && Validate::isJSON($this->plugins)) {
                $this->plugins = $this->context->_tools->jsonDecode($this->plugins, true);
            }

            $this->partner_firstname = $partner->firstname;
            $this->partner_lastname  = $partner->lastname;
            $this->partner_company   = $partner->company;

            $string         = $this->purchase_key . '/' . $this->website;
            $this->cryp_key = $this->context->_tools->encrypt_decrypt(
                'encrypt', $string, $this->crypto_key, $this->purchase_key
            );
        }

    }

    public static function buildObject($id, $idLang = null, $className = null) {

        $objectData = parent::buildObject($id, $idLang, $className);

        if (!empty($objectData['iso_langs']) && Validate::isJSON($objectData['iso_langs'])) {
            $objectData['iso_langs'] = Tools::jsonDecode($objectData['iso_langs'], true);
        }

        if (!empty($objectData['plugins']) && Validate::isJSON($objectData['plugins'])) {
            $objectData['plugins'] = Tools::jsonDecode($objectData['plugins'], true);
        }

        $string                    = $objectData['purchase_key'] . '/' . $objectData['website'];
        $objectData['cryp_key']    = Tools::encrypt_decrypt('encrypt', $string, $objectData['crypto_key'], $objectData['purchase_key']);

        return Tools::jsonDecode(Tools::jsonEncode($objectData));
    }

    /**
     * CORRIGÉ : License:$instance → License::$instance
     * L'ancien code provoquait un parse error PHP — le singleton ne fonctionnait jamais.
     */
    public static function getInstance($id = null, $idLang = null) {

        if (!License::$instance) {
            License::$instance = new License($id, $idLang);
        }

        return License::$instance;
    }

    // =========================================================================
    // CRUD
    // =========================================================================

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

    // =========================================================================
    // REQUÊTES DE COLLECTION
    // =========================================================================

    public static function getCustomer() {

        $users     = [];
        $customers = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('`id_user`, `firstname`, `lastname`')
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
        $licenses   = Db::getInstance()->executeS(
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
        $licenses   = Db::getInstance()->executeS(
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
        $licenses   = Db::getInstance()->executeS(
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

    public static function getEducationLicenceCollection() {

        $collection = [];
        $licenses   = Db::getInstance()->executeS(
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

    /**
     * CORRIGÉ : WHERE avec guillemets doubles → pSQL() pour éviter l'injection SQL.
     * L'original : WHERE purchase_key = "$key" permettait une injection triviale.
     */
    public static function getLicenceBykey($key) {

        $id = Db::getInstance()->getValue(
            (new DbQuery())
                ->select('id_license')
                ->from('license')
                ->where('`purchase_key` = \'' . pSQL($key) . '\'')
        );

        if ($id) {
            return new License($id);
        }

        return false;
    }

    // =========================================================================
    // VÉRIFICATION & ENREGISTREMENT DE LICENCE
    // =========================================================================

    public static function checkLicenseValidity($website, $purchaseKey, $userIp, $requestLang, $psVersion, $ephVersion) {

        require_once _EPH_CONFIG_DIR_ . 'bootstrap.php';

        $isCorrectWebsite = true;
        $isCorrectIp      = true;
        $interval         = 0;

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(bqSQL(static::$definition['table']));
        $sql->where('`purchase_key` = \'' . pSQL($purchaseKey) . '\'');

        $result = Db::getInstance()->getRow($sql);

        if (!empty($result)) {
            $id_license = (int) $result['id_license'];

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

                    $expiryDate  = new DateTime($license->date_exp);
                    $currentTime = new DateTime('now');
                    $interval    = $currentTime->diff($expiryDate)->format('%R%a');
                }
            }
        }

        return VersionController::checkLicense(
            $website, $purchaseKey, $userIp, $requestLang,
            $psVersion, $ephVersion, $isCorrectWebsite, $isCorrectIp, $interval
        );
    }

    public static function registerLicense(License $license, $userIp) {

        $license->active   = 1;
        $license->user_ip  = $userIp;
        $license->date_exp = date('Y-m-d H:i:s', strtotime('+1 years'));

        if ($license->update()) {
            return 365;
        }

        return 0;
    }

    public static function checkLicense($purchaseKey, $website) {

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(bqSQL(static::$definition['table']));
        $sql->where('`website` LIKE \'' . pSQL($website) . '\'');
        $result = Db::getInstance()->getRow($sql);

        if (!empty($result)) {
            $id_license = (int) $result['id_license'];
            $license    = new License($id_license);

            if ($license->purchase_key == $purchaseKey) {
                return $license;
            }
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
            $license    = new License($id_license);

            if ($license->website == $website) {
                return Tools::jsonDecode(Tools::jsonEncode($license), true);
            }
        }

        return false;
    }

    // =========================================================================
    // CHIFFREMENT
    // =========================================================================

    public function encrypt_decrypt($action, $string, $secret_key, $secret_iv) {

        $output         = false;
        $encrypt_method = 'AES-256-CBC';
        $key            = hash('sha256', $secret_key);
        $iv             = substr(hash('sha256', $secret_iv), 0, 16);

        if ($action == 'encrypt') {
            $output = base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
        } elseif ($action == 'decrypt') {
            $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
        }

        return $output;
    }

    public static function generateSecureKey($website, $purchaseKey, $securekey) {

        $data       = $website . '|' . $purchaseKey;
        $ciphertext = Crypto::encrypt($data, $securekey, true);
        return $ciphertext;
    }

    /**
     * CORRIGÉ : rand() → random_int()
     * rand() n'est pas cryptographiquement sûr pour générer des clés de licence.
     * random_int() utilise le CSPRNG du système.
     */
    public static function generateLicenceKey() {

        $tokens       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $segment_chars = 5;
        $num_segments  = 4;
        $key_string    = '';

        for ($i = 0; $i < $num_segments; $i++) {
            $segment = '';

            for ($j = 0; $j < $segment_chars; $j++) {
                $segment .= $tokens[random_int(0, 35)];
            }

            $key_string .= $segment;

            if ($i < ($num_segments - 1)) {
                $key_string .= '-';
            }
        }

        return $key_string;
    }

    // =========================================================================
    // ENREGISTREMENT D'UN NOUVEL ABONNEMENT
    // =========================================================================

    /**
     * CORRIGÉ :
     * - $wuser['user_email'] → $user_email (variable jamais définie)
     * - $this->la() dans méthode statique → message littéral
     * - $license->ftp_password → $license->ftp_passwd (nom de champ correct)
     * - $license->id_user → $license->id_customer (nom de champ correct)
     * - $customer (résultat de userExists) était stocké mais jamais utilisé
     */
    public static function registerNewSubscription($data) {

        $session    = $data['session'];
        $user_email = $session->adminEmail;
        $idCountry  = Country::getByIso($session->companyCountry);

        if (User::userExists($user_email)) {
            $userResults = User::getUsersByEmail($user_email);
            $user        = new Customer($userResults[0]['id_user']);
            $user->firstname = $session->adminFirstname;
            $user->lastname  = $session->adminLastname;
            $user->update();
        } else {
            $user                              = new User();
            $user->email                       = $user_email;
            $user->firstname                   = $session->adminFirstname;
            $user->lastname                    = $session->adminLastname;
            $password                          = Tools::generateStrongPassword();
            $user->passwd                      = Tools::hash($password);
            $user->password                    = $password;
            $user->company                     = $session->companyName;
            $user->ip_registration_newsletter  = pSQL(Tools::getRemoteAddr());
            $user->newsletter_date_add         = pSQL(date('Y-m-d H:i:s'));
            $user->newsletter                  = 1;
            $user->id_default_group            = Context::getContext()->phenyxConfig->get('EPH_DEFAULT_GROUP');
            $user->groupBox                    = [3, 4];
            $user->id_theme                    = 1;
            $postCode                          = $session->companyPostCode;
            $user->customer_code               = User::generateUserCode($idCountry, $postCode);

            try {
                $result = $user->add(true, true, false);
            } catch (Exception $ex) {
                PhenyxLogger::addLog(
                    'Error while adding user: ' . $ex->getMessage(),
                    3, null, 'License'
                );
                return null;
            }

            if ($result) {
                $mobile  = str_replace(' ', '', $session->companyPhone);
                $country = new Country($idCountry);

                if ($country->call_prefix > 0) {
                    $mobile = '+' . $country->call_prefix . substr($mobile, 1);
                }

                $address            = new Address();
                $address->id_user   = $user->id;
                $address->id_country = $idCountry;
                $address->alias     = 'Facturation';
                $address->company   = $session->companyName;
                $address->lastname  = $user->lastname;
                $address->firstname = $user->firstname;
                $address->address1  = $session->companyAddress;
                $address->postcode  = $postCode;
                $address->city      = $session->companyCity;
                $address->phone     = $mobile;
                $address->add();
            }
        }

        $license               = new License();
        $license->id_customer  = $user->id;
        $license->website      = $session->website;
        $license->type         = 'is_corporate';
        $license->purchase_key = self::generateLicenceKey();
        $license->ftp_user     = $session->ftp_user;
        $license->crypto_key   = $session->crypto_key;
        $license->ftp_passwd   = $session->ftp_password;
        $license->user_ip      = $session->databaseServer;
        $license->iso_langs    = [$session->companyCountry];

        if ($license->add()) {
            return $license->purchase_key;
        }

        return null;
    }

    // =========================================================================
    // GÉNÉRATION DES FICHIERS DE RÉFÉRENCE
    // =========================================================================

    public static function synchOfFiles() {

        $recursive_directory = ['app/education_classes', 'app/education_controllers'];
        $iterator            = new AppendIterator();

        foreach ($recursive_directory as $directory) {
            $iterator->append(new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(_EPH_ROOT_DIR_ . '/' . $directory . '/')
            ));
        }

        foreach ($iterator as $file) {

            if (in_array($file->getFilename(), ['.', '..', 'index.php'])) {
                continue;
            }

            $filePath = $file->getPathname();
            $target   = str_replace('app', 'phenyxEducation', $filePath);
            copy($filePath, $target);
        }
    }

    /**
     * CORRIGÉ :
     * - content/pdf dupliqué supprimé (était listé deux fois)
     * - str_contains($filePath, 'truc') supprimé (filtre de debug oublié)
     */
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

        foreach ($recursive_directory as $directory) {

            if (is_dir(_EPH_ROOT_DIR_ . '/' . $directory . '/')) {
                $iterator->append(new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(_EPH_ROOT_DIR_ . '/' . $directory . '/')
                ));
            }
        }

        $iterator->append(new DirectoryIterator(_EPH_ROOT_DIR_ . '/content/themes/'));
        $iterator->append(new DirectoryIterator(_EPH_ROOT_DIR_ . '/app/'));
        $iterator->append(new DirectoryIterator(_EPH_ROOT_DIR_ . '/phenyxDigital/'));

        $excludedFiles = [
            '.', '..', '.htaccess', '.env', 'composer.lock', 'settings.inc.php',
            '.gitattributes', '.user.ini', '.php-ini', '.php-version',
        ];

        $excludedPaths = [
            '/webephenyx/cronGetLang.php',
            '/webephenyx/cronOF.php',
            '/webephenyx/fonts.php',
            '/webephenyx/veille.php',
            '/.git/',
            '/uploads/',
            '/cache/',
            'sitemap.xml',
        ];

        $excludedExtensions = ['txt', 'csv', 'zip', 'dat', 'log'];

        $md5List = [];

        foreach ($iterator as $file) {

            if (in_array($file->getFilename(), $excludedFiles)) {
                continue;
            }

            $filePath = str_replace(_EPH_ROOT_DIR_, '', $file->getPathname());
            $ext      = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
			if (str_contains($filePath, 'uploads/revslider')) {
                continue;
            }

            if (is_dir($file->getPathname())) {
                continue;
            }

            if (in_array($ext, $excludedExtensions)) {
                continue;
            }

            $skip = false;

            foreach ($excludedPaths as $excludedPath) {

                if (str_contains($filePath, $excludedPath)) {
                    $skip = true;
                    break;
                }
            }

            if ($skip) {
                continue;
            }

            if (str_contains($filePath, 'custom_') && $ext == 'css') {
                continue;
            }
			
            if (str_contains($filePath, '/plugins/') && str_contains($filePath, '/translations/')) {
				
                foreach ($this->plugins as $plugin => $installed) {
                    if (str_contains($filePath, '/plugins/' . $plugin . '/translations/')) {
                        $test = str_replace('/includes/plugins/' . $plugin . '/translations/', '', $filePath);
						$test2 = str_replace('/'.$file->getFilename(), '', $test);
                        $test = str_replace('.php', '', $test);
												
						if (!array_key_exists($test2, $this->iso_langs)) {
                        	if (!array_key_exists($test, $this->iso_langs)) {
                            	continue 2;
                        	}
						}
                    }
                }
            }

            $md5List[$filePath] = md5_file($file->getPathname());
        }

        file_put_contents(
            _EPH_CONFIG_DIR_ . 'json/files.json',
            json_encode($md5List, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    // =========================================================================
    // CRONS
    // =========================================================================

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

    // =========================================================================
    // MÉTHODE CENTRALE D'APPEL API (callApi)
    // =========================================================================

    /**
     * Point d'entrée unique pour tous les appels vers l'API d'un site client.
     *
     * Avant ce refactoring, chaque méthode (pushSqlRequest, getJsonFile,
     * pushMetas, pushBackTabs, etc.) répétait exactement le même bloc de
     * ~8 lignes : construction de l'URL, du tableau de données, instanciation
     * de Curl, setHeader, post, return response.
     * Cela représentait environ 400 lignes de code dupliqué sur ~30 méthodes,
     * sans gestion d'erreur ni timeout cohérent.
     *
     * Cette méthode centralise :
     * - La construction de l'URL (avec validation anti-SSRF)
     * - L'ajout automatique de license_key et crypto_key
     * - Le timeout configurable par appel
     * - Le logging des erreurs cURL
     *
     * @param string $action        Nom de l'action dispatcher
     * @param array  $params        Paramètres supplémentaires à fusionner
     * @param int    $timeout       Timeout en secondes (défaut : 30)
     * @param bool   $fireAndForget Si true, ignore les erreurs de timeout sans logger.
     *                              Utile pour les opérations asynchrones (generateClassIndex,
     *                              mergeLanuages, getOwnJsonFile) où on envoie la demande
     *                              sans attendre la réponse — le timeout est alors attendu et normal.
     *
     * @return mixed Réponse décodée, ou null en cas d'erreur
     */
    private function callApi(string $action, array $params = [], int $timeout = 30, bool $fireAndForget = false) {

        // Validation anti-SSRF : s'assurer que $this->website est un hostname
        // simple, sans path ni schéma injectés
        $website = preg_replace('#[^a-zA-Z0-9.\-]#', '', $this->website);

        if (empty($website)) {
            PhenyxLogger::addLog(
                'License::callApi — website invalide : ' . $this->website,
                3, null, 'License'
            );
            return null;
        }

        $url = 'https://' . $website . '/api';

        $data = array_merge([
            'action'      => $action,
            'license_key' => $this->purchase_key,
            'crypto_key'  => $this->cryp_key,
        ], $params);

        $curl = new PhpCurl();
        $curl->setDefaultJsonDecoder($assoc = true);
        $curl->setHeader('Content-Type', 'application/json');
        $curl->setTimeout($timeout);
        $curl->post($url, json_encode($data));

        if ($curl->error) {

            // En mode fire-and-forget, un timeout est attendu et normal —
            // on ne logue pas pour ne pas polluer les alertes.
            if (!$fireAndForget) {
                PhenyxLogger::addLog(
                    'License::callApi error — site: ' . $website
                    . ' action: ' . $action
                    . ' — ' . $curl->errorCode . ': ' . $curl->errorMessage,
                    3, null, 'License'
                );
            }

            return null;
        }

        return $curl->response;
    }

    // =========================================================================
    // MÉTHODES D'APPEL API — toutes refactorisées via callApi()
    // =========================================================================

    public function showTab($id_back_tab) {

        return $this->callApi('showTab', ['id_back_tab' => $id_back_tab]);
    }

    public function hideTab($id_back_tab) {

        return $this->callApi('hideTab', ['id_back_tab' => $id_back_tab]);
    }

    public function cleanDirectory($dir) {

        return $this->callApi('cleanDirectory', ['directory' => $dir]);
    }

    public function getGenerateTabs() {

        return $this->callApi('getGenerateTabs', [], 60);
    }

    public function cleanEmptyDirectory() {

        return $this->callApi('cleanEmptyDirectory', [], 60);
    }

    public function getPluginOnDisk() {

        $plugins = $this->callApi('getPluginOnDisk', [], 60);

        if (is_array($plugins)) {
            $this->plugins = $plugins;
            $this->update();
        }

        if (is_string($this->plugins)) {
            $this->plugins = Tools::jsonDecode($this->plugins, true);
        }
    }

    public function getInstalledLangs() {

        $langs = $this->callApi('getInstalledLangs', [], 60);

        if (is_array($langs)) {
            $this->iso_langs = $langs;
            $this->update();
        }

        if (is_string($this->iso_langs)) {
            $this->iso_langs = Tools::jsonDecode($this->iso_langs, true);
        }
    }
   

    public function generateClassIndex() {

        $this->callApi('generateClassIndex', [], 5, true);
        return true;
    }

    public function getOwnJsonFile() {

        $this->callApi('getOwnJsonFile', [], 5, true);
        return true;
    }

    /**
     * CORRIGÉ : die() supprimé du modèle.
     * Un modèle ne doit jamais appeler die() — c'est le rôle du contrôleur.
     * Cette méthode retourne maintenant un tableau ['success' => bool, 'message' => string]
     * que le contrôleur peut traiter et renvoyer via die().
     */
	public function getJsonFile(): array {

    	$response = $this->callApi('getJsonFile', [], 7200);

    	if ($response === null) {
        	return ['success' => false, 'message' => 'Erreur de connexion au site ' . $this->website];
		}

		if (is_array($response)) {
        	file_put_contents(
				_EPH_CONFIG_DIR_ . 'json/' . $this->id . '.json',
				json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        	);
        	return ['success' => true];
		}

    	return ['success' => false, 'message' => 'Réponse inattendue du site ' . $this->website];
	}

    public function getFrontJsonFile() {

        $md5List = $this->callApi('getFrontJsonFile', [], 60);

        if (is_array($md5List)) {
            file_put_contents(
                _EPH_CONFIG_DIR_ . 'json/front-' . $this->id . '.json',
                json_encode($md5List, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
            return true;
        }

        return false;
    }

    public function deleteJsonFile() {

        $path = _EPH_CONFIG_DIR_ . 'json/' . $this->id . '.json';

        if (file_exists($path)) {
            return unlink($path);
        }

        return true;
    }

    public function pushSqlRequest($query, $method) {

        return $this->callApi('pushSqlRequest', [
            'query'  => $query,
            'method' => $method,
        ]);
    }

    public function pushMeta($meta) {

        return $this->callApi('pushMeta', ['meta' => $meta]);
    }

    /**
     * CORRIGÉ : clé 'metad' → 'metas'
     * L'original envoyait 'metad' ce qui faisait que le dispatcher
     * recevait $data->metas = null côté client.
     */
    public function pushMetas($metas) {

        return $this->callApi('pushMetas', ['metas' => $metas]);
    }

    public function pushBackTab($backTab) {

        return $this->callApi('pushBackTab', ['backTab' => $backTab]);
    }

    public function pushBackTabs($backTabs) {

        return $this->callApi('pushBackTabs', ['backTabs' => $backTabs]);
    }

    public function pushObject($class, $objet) {

        return $this->callApi('pushObject', ['class' => $class, 'objet' => $objet]);
    }

    public function deleteFile($files) {

        return $this->callApi('deleteFiles', ['files' => $files]);
    }

    public function deleteBulkFile($files) {

        return $this->callApi('deleteBulkFile', ['files' => $files]);
    }

    /**
     * Envoie le contenu brut d'un fichier via JSON.
     * Note : pour les fichiers binaires, préférer pushZipFile().
     * La taille est vérifiée avant envoi (limite 200 Mo).
     */
    public function pushFile($origine, $destination) {

        if (!file_exists($origine)) {
            return null;
        }

        $size = filesize($origine);

        if ($size > 209715200) {
            PhenyxLogger::addLog(
                'pushFile — fichier trop volumineux (' . round($size / 1048576) . ' Mo) : ' . $origine,
                2, null, 'License'
            );
            return null;
        }

        $content = file_get_contents($origine);

        return $this->callApi('downloadFile', [
            'content'     => $content,
            'destination' => $destination,
        ], 300);
    }

    public function pushZipFile($zipPath) {

        return $this->callApi('downloadZipFile', ['zipPath' => $zipPath], 300);
    }

    public function cleanBckTab() {

        return $this->callApi('cleanBckTab');
    }

    public function cleanMeta() {

        return $this->callApi('cleanMeta');
    }

    public function cleanPlugin() {

        return $this->callApi('cleanPlugin');
    }

    public function cleanHook() {

        return $this->callApi('cleanHook');
    }

    public function upgradeVersion($version) {

        return $this->callApi('writeNewSettings', ['version' => $version]);
    }

    public function createNotification($notification) {

        return $this->callApi('createNotification', ['object' => $notification]);
    }

    public function getEmployeeInformation($idEmployee) {

        return $this->callApi('getEmployeeInformation', ['idEmployee' => $idEmployee]);
    }

    public function getEmployeeEmail($idEmployee) {

        return $this->callApi('getEmployeeEmail', ['idEmployee' => $idEmployee]);
    }

    public function pushTracking($session) {

        return $this->callApi('pushTracking', ['session' => $session]);
    }

    public function getVendoStatus() {

        return $this->callApi('getVendoStatus');
    }

    public function getCertificationFile($idMonth) {

        return $this->callApi('getCertificationFile', ['idMonth' => $idMonth]);
    }

    public function getNeededSupplies() {

        return $this->callApi('getNeededSupplies');
    }

    public function getExpeditionFile() {

        // CORRIGÉ : $this->cryptthis->cryp_keyo_key → $this->cryp_key
        // L'original était le résultat d'un copier-coller raté entre deux noms de variables.
        return $this->callApi('getExpeditionFile');
    }

    public function getPhenyxInvoices() {

        return $this->callApi('getPhenyxInvoices');
    }

    public function getPhenyxSupplies() {

        return $this->callApi('getPhenyxSupplies');
    }

    public function getPhenyxPrevisionnel($dateFrom, $dateTo) {

        return $this->callApi('getPhenyxPrevisionnel', [
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
        ]);
    }

    public function getDistantTables() {

        $currentTables = Db::getInstance()->executeS('SHOW TABLES');
        return $this->callApi('getDistantTables', ['currentTables' => $currentTables]);
    }

    public function generateAdminFichierAccess() {

        return $this->callApi('generateAdminHtaccess');
    }

    public function executeCronAction($cronAction) {

        // Déclenchement de cron : fire-and-forget. On lance l'exécution distante sans
        // attendre qu'elle se termine (un cron peut durer plusieurs minutes). Le site
        // client continue de traiter le cron après la fermeture de connexion ; côté
        // maître, un timeout court est normal et ne doit pas être loggué comme une erreur.
        return $this->callApi($cronAction, [], 5, true);
    }

    // =========================================================================
    // UTILITAIRES BASE DE DONNÉES
    // =========================================================================

    public function getXmlDataBase() {

        $database    = [];
        $formattedXML = $this->callApi('getXmlDatabase');

        if (empty($formattedXML)) {
            return $database;
        }

        $xmlPath = _EPH_CONFIG_DIR_ . 'json/' . $this->id . '.xml';
        file_put_contents($xmlPath, $formattedXML);

        $xml    = @simplexml_load_file($xmlPath);
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

    /**
     * CORRIGÉ : fclose($fp) sur variable non définie supprimé.
     * $fp était appelé sans jamais avoir été ouvert, provoquant un warning
     * et un return false prématuré.
     */
    public static function generateSqlTable($table) {

        $request         = '';
        $idLicenseReferer = Context::getContext()->phenyxConfig->get('EPH_TOPBAR_REFERER');
        $licenceReferer  = new License($idLicenseReferer);
        $dbParams        = $licenceReferer->getdBParam();
        $dbName          = $dbParams['_DB_NAME_'];
        $dbPasswd        = $dbParams['_DB_PASSWD_'];
        $dbUser          = $dbParams['_DB_USER_'];

        $schema = Db::getCrmInstance($dbUser, $dbPasswd, $dbName)->executeS(
            'SHOW CREATE TABLE `' . _DB_PREFIX_ . $table . '`'
        );

        if (count($schema) != 1 || !isset($schema[0]['Table']) || !isset($schema[0]['Create Table'])) {
            return false;
        }

        $request .= 'DROP TABLE IF EXISTS `' . $schema[0]['Table'] . '`;' . PHP_EOL;
        $request .= $schema[0]['Create Table'] . ";\n\n";

        $data   = Db::getCrmInstance($dbUser, $dbPasswd, $dbName)->query('SELECT * FROM `' . $schema[0]['Table'] . '`');
        $sizeof = Db::getCrmInstance($dbUser, $dbPasswd, $dbName)->NumRows();
        $lines  = explode("\n", $schema[0]['Create Table']);

        if ($data && $sizeof > 0) {
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
                                $s .= preg_match('/(.*NOT NULL.*)/Ui', $line) ? "'', " : 'NULL,';
                                break;
                            }
                        }
                    }
                }

                $s = rtrim($s, ',');

                if ($i % 200 == 0 && $i < $sizeof) {
                    $s .= ");\nINSERT INTO `" . $schema[0]['Table'] . "` VALUES\n";
                } elseif ($i < $sizeof) {
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

    public function compareSqlTable($table) {

        $dbParams = $this->getdBParam();
        $context  = Context::getContext();
        $dbName   = $dbParams['_DB_NAME_'];
        $dbPasswd = $dbParams['_DB_PASSWD_'];
        $dbUser   = $dbParams['_DB_USER_'];

        $current = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('a.*, l.*')
                ->from($table, 'a')
                ->leftJoin($table . '_lang', 'l',
                    'l.`id_' . $table . '` = a.`id_' . $table . '`'
                    . ' AND l.`id_lang` = ' . $context->language->id
                )
        );

        $distant = Db::getCrmInstance($dbUser, $dbPasswd, $dbName)->executeS(
            (new DbQuery())
                ->select('a.*, l.*')
                ->from($table, 'a')
                ->leftJoin($table . '_lang', 'l',
                    'l.`id_' . $table . '` = a.`id_' . $table . '`'
                    . ' AND l.`id_lang` = ' . $context->language->id
                )
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

        // $currentTable et $distant sont prêts pour comparaison
        // TODO: implémenter la logique de diff et retourner le résultat
    }

    public function getdBParam() {

        return $this->callApi('getdBParam');
    }

    // =========================================================================
    // OUTILS STATIQUES
    // =========================================================================

    public static function getJsonFileFromWebSite($website) {

        $jsonPath = _EPH_CONFIG_DIR_ . 'json/' . $website . '.json';

        if (file_exists($jsonPath)) {
            unlink($jsonPath);
        }

        // Appel sans authentification — intentionnel pour ce point d'entrée public
        $curl = new PhpCurl();
        $curl->setDefaultJsonDecoder($assoc = true);
        $curl->setHeader('Content-Type', 'application/json');
        $curl->setTimeout(30);
        $curl->post('https://' . $website . '/api', json_encode([]));
        $md5List = $curl->response;

        if (is_array($md5List)) {
            file_put_contents(
                $jsonPath,
                json_encode($md5List, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
            return true;
        }

        return false;
    }

    public static function generateFrontReferenceFiles() {

        // Implémentation déléguée — à compléter selon besoins
    }

    public static function generateOfSpecificFile() {

        // Implémentation déléguée — à compléter selon besoins
    }

}
