<?php

namespace EphenyxDigital\QuantumCore;
/**
 * @since 1.9.1.0
 */
class Flags extends PhenyxObjectModel {
	
	protected static $instance;
	/** @var string */
    protected $dbUser;

    /** @var string */
    protected $dbPasswd;

    /** @var string */
    protected $dbName;

    /** @var string */
    protected $dbServer;

    // @codingStandardsIgnoreStart
    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'flags',
        'primary'   => 'id_flags',
        'fields'    => [
			'name'             => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'iso_code'         => ['type' => self::TYPE_STRING, 'validate' => 'isLanguageIsoCode', 'required' => true, 'size' => 2],
            'flag_hash'        => ['type' => self::TYPE_STRING],
            
        ],
    ];
    /** @var string Name */
    public $name;
    /** @var string 2-letter iso code */
    public $iso_code;
    
    public $flag_hash;

    /**
     * GenderCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct($id = null, $idLang = null) {

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
	
	public static function getInstance($id = null, $idLang = null) {

        if (!isset(static::$instance)) {
            static::$instance = new Flags($id, $idLang);
        }

        return static::$instance;
    }
	
    public function getFlagByIso($isoCode) {

        if (!Validate::isLanguageIsoCode($isoCode)) {
            die(Tools::displayError('Fatal error: ISO code is not correct') . ' ' . Tools::safeOutput($isoCode));
        }

        return Db::getCrmInstance(
                $this->dbUser, $this->dbPasswd, $this->dbName, $this->dbServer
            )->getValue(
            (new DbQuery())
                ->select('`flag_hash`')
                ->from('flags')
                ->where('`iso_code` = \'' . pSQL(strtolower($isoCode)) . '\'')
        );
    }

   

}
