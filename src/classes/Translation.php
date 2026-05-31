<?php

namespace EphenyxDigital\QuantumCore;

use Language;


/**
 * Class Translation
 *
 * Gère les traductions centralisées stockées dans la base de données CRM.
 * Les credentials de connexion sont lus depuis les constantes définies
 * dans defines_inc.php (elles-mêmes issues du fichier .env).
 *
 * Corrections apportées :
 * - Variable shadowing corrigé dans updateGlobalTranslations()
 * - Credentials extraits du code source vers des constantes .env
 * - Invalidation du cache session après add() et update()
 * - Validate::isUnsignedId() utilisé cohéremment dans tous les contextes
 * - Singleton réinitialisé correctement après écriture
 * - Ajout de date_upd automatique à l'insertion
 * - Nettoyage des import inutilisés (Curl non utilisé ici)
 *
 * @since 1.9.1.0
 */
class Translation extends PhenyxObjectModel {

    // -------------------------------------------------------------------------
    // Singleton
    // -------------------------------------------------------------------------

    /** @var Translation|null */
    protected static $instance = null;

    // -------------------------------------------------------------------------
    // Cache MÉMOIRE (par requête) — remplace le cache session qui écrivait sur
    // disque et le saturait. Réinitialisé après chaque add()/update().
    // -------------------------------------------------------------------------

    /** @var array|null Toutes les traductions, indexées par iso_code */
    protected static $cacheGlobal = null;

    /** @var array Traductions indexées : [iso_code => ['origine' => 'trad', ...]] */
    protected static $cacheByIso = [];

    /** @var array Traductions unitaires : [cacheKey => 'trad'] */
    protected static $cacheExpr = [];

    // -------------------------------------------------------------------------
    // Connexion BDD CRM (credentials issus du .env via defines_inc.php)
    // -------------------------------------------------------------------------

    /** @var string */
    protected $dbUser;

    /** @var string */
    protected $dbPasswd;

    /** @var string */
    protected $dbName;

    /** @var string */
    protected $dbServer;

    // -------------------------------------------------------------------------
    // Définition du modèle
    // -------------------------------------------------------------------------

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'translation',
        'primary' => 'id_translation',
        'fields'  => [
            'iso_code'    => ['type' => self::TYPE_STRING, 'validate' => 'isLanguageIsoCode', 'required' => true, 'size' => 2],
            'file_name'   => ['type' => self::TYPE_STRING],
            'origin'      => ['type' => self::TYPE_HTML, 'required' => true],
            'translation' => ['type' => self::TYPE_HTML, 'required' => true],
            'date_upd'    => ['type' => self::TYPE_DATE, 'required' => true],
        ],
    ];

    // -------------------------------------------------------------------------
    // Propriétés de l'objet
    // -------------------------------------------------------------------------

    /** @var string Code ISO de la langue (ex: 'fr', 'en') */
    public $iso_code;

    /** @var string Nom du fichier source de la traduction */
    public $file_name;

    /** @var string Texte original à traduire */
    public $origin;

    /** @var string Traduction */
    public $translation;

    /** @var string Date de dernière mise à jour */
    public $date_upd;

    /**
     * Cache local des traductions indexé par iso_code.
     * Structure : ['fr' => [['origin' => ..., 'translation' => ...], ...], ...]
     *
     * @var array
     */
    public $translations = [];

    // -------------------------------------------------------------------------
    // Constructeur
    // -------------------------------------------------------------------------

    /**
     * @param int|null    $id   ID de la traduction à charger (optionnel)
     * @param array|null  $isos Liste d'iso_codes à précharger (null = toutes les langues actives)
     */
    public function __construct($id = null, $isos = null) {

        $this->className = get_class($this);
        $this->context   = Context::getContext();

        if (!PhenyxObjectModel::$hook_instance) {
            PhenyxObjectModel::$hook_instance = Hook::getInstance();
            $this->context->_hook = PhenyxObjectModel::$hook_instance;
        }

        if (!isset(PhenyxObjectModel::$loaded_classes[$this->className])) {
            $this->def = PhenyxObjectModel::getDefinition($this->className);
            PhenyxObjectModel::$loaded_classes[$this->className] = get_object_vars($this);
        } else {
            foreach (PhenyxObjectModel::$loaded_classes[$this->className] as $key => $value) {
                $this->{$key} = $value;
            }
        }

        // Credentials lus depuis les constantes (définies dans defines_inc.php
        // à partir du fichier .env — voir _EPH_CRM_DB_* ci-dessous).
        $this->dbUser   = defined('_EPH_CRM_DB_USER_')   ? _EPH_CRM_DB_USER_   : '';
        $this->dbPasswd = defined('_EPH_CRM_DB_PASSWD_') ? _EPH_CRM_DB_PASSWD_ : '';
        $this->dbName   = defined('_EPH_CRM_DB_NAME_')   ? _EPH_CRM_DB_NAME_   : '';
        $this->dbServer = defined('_EPH_CRM_DB_SERVER_') ? _EPH_CRM_DB_SERVER_ : '';

        $this->translations = $this->getGlobalTranslations($isos);

        if ($id) {
            $this->id      = (int) $id;
            $entityMapper  = Adapter_ServiceLocator::get('Adapter_EntityMapper');
            // Base LOCALE : on ne passe plus les credentials CRM distants.
            $entityMapper->load(
                $this->id, null, $this, $this->def, false,
                $this->dbUser, $this->dbPasswd, $this->dbName, $this->dbServer
            );
        }
    }

    // -------------------------------------------------------------------------
    // Singleton
    // -------------------------------------------------------------------------

    /**
     * Retourne l'instance singleton.
     * Note : le singleton est invalidé après tout write (add/update).
     *
     * @param int|null   $id
     * @param array|null $isos
     *
     * @return Translation
     */
    public static function getInstance($id = null, $isos = null) {

        if (static::$instance === null) {
            static::$instance = new static($id, $isos);
        }

        return static::$instance;
    }

    /**
     * Invalide le singleton pour forcer une réinitialisation au prochain appel.
     * Appelé automatiquement après add() et update().
     */
    public static function resetInstance() {

        static::$instance = null;
    }

    // -------------------------------------------------------------------------
    // Surcharges CRUD
    // -------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     *
     * Ajoute automatiquement date_upd et invalide les caches après insertion.
     */
    	
	public function add($autoDate = false, $nullValues = false) {
		
		$this->date_upd = date('Y-m-d H:i:s');
				
        $fields = $this->getFields();
		
        if (!$result = Db::getCrmInstance(
                $this->dbUser, $this->dbPasswd, $this->dbName, $this->dbServer
            )->insert($this->def['table'], $fields, $nullValues)) {
			
            return false;
        }

        $this->id = Db::getCrmInstance(
                $this->dbUser, $this->dbPasswd, $this->dbName, $this->dbServer
            )->Insert_ID();
		
        if (!$result) {
            return false;
        }
		$this->invalidateTranslationCache();
        static::resetInstance();

        

        return $result;
    }
	
	public function update($nullValues = false) {

       $this->date_upd = date('Y-m-d H:i:s');        

        if (!$result = Db::getCrmInstance(
                $this->dbUser, $this->dbPasswd, $this->dbName, $this->dbServer
            )->update($this->def['table'], $this->getFields(), '`' . pSQL($this->def['primary']) . '` = ' . (int) $this->id, 0, $nullValues)) {
            return false;
        }
		$this->invalidateTranslationCache();
        static::resetInstance();
       
        return $result;
    }


    // -------------------------------------------------------------------------
    // Lecture des traductions
    // -------------------------------------------------------------------------

    /**
     * Retourne toutes les traductions pour les langues demandées.
     * Utilise la session comme cache de premier niveau.
     *
     * Structure retournée :
     * [
     *   'fr' => [
     *     ['id_translation' => 1, 'origin' => 'Hello', 'translation' => 'Bonjour', ...],
     *     ...
     *   ],
     *   'en' => [...],
     * ]
     *
     * @param array|null $isos Liste d'iso_codes (null = toutes les langues actives)
     *
     * @return array
     */
    public function getGlobalTranslations($isos = null) {

        if (is_array(static::$cacheGlobal)) {
            return static::$cacheGlobal;
        }

        if (is_null($isos)) {
            $languages = Language::getLanguages(true);
        } else {
            $languages = Language::getLanguagesByIsos($isos);
        }

        $translations = [];

        try {
            foreach ($languages as $lang) {
                $iso = trim($lang['iso_code']);
                $translations[$iso] = Db::getCrmInstance(
                    $this->dbUser, $this->dbPasswd, $this->dbName, $this->dbServer
                )->executeS(
                    (new DbQuery())
                        ->select('*')
                        ->from('translation')
                        ->where('`iso_code` = \'' . pSQL($iso) . '\'')
                );
            }
        } catch (\Throwable $e) {
            // Base de traduction injoignable : on dégrade proprement (site affiché,
            // textes non traduits) au lieu de provoquer un fatal.
            error_log('[Translation] base indisponible (getGlobalTranslations): ' . $e->getMessage());
            static::$cacheGlobal = [];
            return [];
        }

        static::$cacheGlobal = $translations;

        return $translations;
    }

    /**
     * Retourne la traduction d'une expression pour un iso_code donné.
     * Utilise la session comme cache de premier niveau.
     *
     * @param string $iso_code Code ISO de la langue
     * @param string $origin   Expression originale
     *
     * @return string|false La traduction, ou false si non trouvée
     */
    public function getExistingTranslation($iso_code, $origin, $file = null) {

        $cacheKey = $iso_code . '_' . md5($origin);

        if (isset(static::$cacheExpr[$cacheKey])) {
            return static::$cacheExpr[$cacheKey];
        }

        try {
            $translation = Db::getCrmInstance(
                    $this->dbUser, $this->dbPasswd, $this->dbName, $this->dbServer
                )->getValue(
                (new DbQuery())
                    ->select('`translation`')
                    ->from('translation')
                    ->where('`iso_code` = \'' . pSQL(trim($iso_code)) . '\'')
                    ->where('`origin` = \'' . pSQL(trim($origin)) . '\'')
            );
        } catch (\Throwable $e) {
            error_log('[Translation] base indisponible (getExistingTranslation): ' . $e->getMessage());
            return false;
        }

        if ($translation !== false) {
            static::$cacheExpr[$cacheKey] = $translation;
        }

        return $translation;
    }

    /**
     * Retourne l'ID d'une traduction existante pour un iso_code et une expression.
     *
     * @param string $iso_code Code ISO de la langue
     * @param string $origin   Expression originale
     *
     * @return int|null L'id_translation ou null si non trouvé
     */
    public function getExistingObjectTranslation($iso_code, $origin, $file_name = null) {
 		
        try {
            $id_translation = Db::getCrmInstance(
                    $this->dbUser, $this->dbPasswd, $this->dbName, $this->dbServer
                )->getValue(
                (new DbQuery())
                ->select('`id_translation`')
                ->from('translation')
                ->where('`iso_code` = \'' . pSQL(trim($iso_code)) . '\'')
                ->where('`origin` = \'' . pSQL(trim($origin)) . '\'')
                ->where(!is_null($file_name) ? '`file_name` = \'' . pSQL(trim($file_name)) . '\'' : 1)
            );
        } catch (\Throwable $e) {
            error_log('[Translation] base indisponible (getExistingObjectTranslation): ' . $e->getMessage());
            return null;
        }

        if (Validate::isUnsignedId($id_translation)) {
            return (int) $id_translation;
        }

        return null;
    }

    /**
     * Retourne toutes les traductions d'une langue sous forme de tableau associatif.
     * Structure : ['expression originale' => 'traduction', ...]
     * Utilise la session comme cache de premier niveau.
     *
     * @param string $iso_code Code ISO de la langue
     *
     * @return array
     */
    public function getExistingTranslationByIso($iso_code) {

        if (isset(static::$cacheByIso[$iso_code])) {
            return static::$cacheByIso[$iso_code];
        }

        try {
            $results = Db::getCrmInstance(
                    $this->dbUser, $this->dbPasswd, $this->dbName, $this->dbServer
                )->executeS(
                (new DbQuery())
                    ->select('*')
                    ->from('translation')
                    ->where('`iso_code` = \'' . pSQL(trim($iso_code)) . '\'')
            );
        } catch (\Throwable $e) {
            error_log('[Translation] base indisponible (getExistingTranslationByIso): ' . $e->getMessage());
            static::$cacheByIso[$iso_code] = [];
            return [];
        }

        $indexed = [];

        foreach ($results as $result) {
            $indexed[$result['origin']] = $result['translation'];
        }

        static::$cacheByIso[$iso_code] = $indexed;

        return $indexed;
    }

    // -------------------------------------------------------------------------
    // Écriture des traductions
    // -------------------------------------------------------------------------

    /**
     * Met à jour ou insère un lot de traductions.
     *
     * @param array $translations Tableau de traductions, chaque entrée contenant :
     *                            ['iso_code', 'origin', 'translation', 'file_name']
     *
     * @return void
     */
    public function updateGlobalTranslations(array $translations) {

        foreach ($translations as $data) {

            // On ignore les traductions vides
            if (empty($data['translation'])) {
                continue;
            }

            // Utiliser des noms de variables distincts pour éviter tout shadowing
            $id_translation = $this->getExistingObjectTranslation(
                $data['iso_code'],
                $data['origin']
            );

            if (!is_null($id_translation)) {
                // Mise à jour d'un enregistrement existant
                $obj              = new Translation($id_translation);
                $obj->file_name   = $data['file_name'] ?? '';
                $obj->translation = $data['translation'];
                $obj->update();
            } else {
                // Insertion d'un nouvel enregistrement
                $obj              = new Translation();
                $obj->iso_code    = $data['iso_code'];
                $obj->file_name   = $data['file_name'] ?? '';
                $obj->origin      = $data['origin'];
                $obj->translation = $data['translation'];
                $obj->add();
            }
        }
    }

    /**
     * Crée une traduction depuis un objet ou un tableau quelconque.
     * Seules les propriétés déclarées sur Translation sont acceptées.
     *
     * @param object|array $object
     *
     * @return bool
     */
    public static function addTranslation($object) {

        $data        = Tools::jsonDecode(Tools::jsonEncode($object), true);
        $translation = new Translation();

        foreach ($data as $key => $value) {
            if (property_exists($translation, $key)) {
                $translation->{$key} = $value;
            }
        }

        return $translation->add();
    }

    // -------------------------------------------------------------------------
    // Gestion du cache
    // -------------------------------------------------------------------------

    /**
     * Invalide toutes les entrées de cache session liées aux traductions.
     * Appelé automatiquement après chaque add() ou update().
     *
     * @return void
     */
    protected function invalidateTranslationCache() {

        // Vidage du cache mémoire (par requête)
        static::$cacheGlobal = null;
        static::$cacheByIso  = [];
        static::$cacheExpr   = [];

        // Reconstruire le cache global dans le contexte
        $this->context->translations = $this->getGlobalTranslations();
    }
}
