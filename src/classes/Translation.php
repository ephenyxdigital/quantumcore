<?php
use \Curl\Curl;

/**
 * Class Translation
 *
 * @since 1.9.1.0
 */
class Translation extends PhenyxObjectModel {

    protected static $instance;
    
    public $dbUser;
    
    public $dbPasswd;
    
    public $dbName;
    
    public $dbServer;

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'translation',
        'primary' => 'id_translation',
        'fields'  => [
            'iso_code'    => ['type' => self::TYPE_STRING, 'validate' => 'isLanguageIsoCode', 'required' => true, 'size' => 2],
            'file_name'    => ['type' => self::TYPE_STRING],
            'origin'      => ['type' => self::TYPE_HTML, 'required' => true],
            'translation' => ['type' => self::TYPE_HTML, 'required' => true],
            'date_upd'    => ['type' => self::TYPE_HTML, 'required' => true],
        ],
    ];
    /** @var string Name */
    public $iso_code;
    
    public $file_name;

    public $origin;
    public $translation;
    public $date_upd;
    
    public $translations = [];

    public function __construct($id = null, $isos = null)  {
        
        $this->className = get_class($this);
        $this->context = Context::getContext();
        if (!PhenyxObjectModel::$hook_instance) {
            PhenyxObjectModel::$hook_instance = Hook::getInstance();
            $this->context->_hook = PhenyxObjectModel::$hook_instance;
        }
        if (!isset(PhenyxObjectModel::$loaded_classes[$this->className])) {
            $this->def = PhenyxObjectModel::getDefinition($this->className);            
            PhenyxObjectModel::$loaded_classes[$this->className] = get_object_vars($this);
            
        } else {
            foreach (PhenyxObjectModel::$loaded_classes[$this->className] as $key => $value) {
                $this->{$key}  = $value;
            }

        }
        
        $this->dbUser = 'AdminPhenyxTraduction';
        
        $this->dbPasswd = '?188f2jwC';
        
        $this->dbName = 'phenyx_traduction';
        
        $this->dbServer = 'quizzical-hopper.155-133-130-82.plesk.page';
        
        $this->translations = $this->getGlobalTranslations($isos);   

        if ($id) {
            $this->id = $id;
            $entityMapper = Adapter_ServiceLocator::get("Adapter_EntityMapper");
            $entityMapper->load($this->id, null, $this, $this->def, false,  $this->dbUser, $this->dbPasswd, $this->dbName, $this->dbServer);           
		}

    }
    
    public function getGlobalTranslations($isos = null) {
        
        $translations = $this->context->_session->get('getGlobalTranslations');

        if (!empty($translations) && is_array($translations)) {
            return $translations;
        }
        
        $translations = [];
        if(is_null($isos)) {
            $isos = Language::getLanguages(true);
        } else {
            $isos = Language::getLanguagesByIsos($isos);
        }
        
       
        
        foreach ($isos as $lang) {   
            
            $translations[$lang['iso_code']] = Db::getCrmInstance($this->dbUser, $this->dbPasswd, $this->dbName, $this->dbServer)->executeS(
                (new DbQuery())
                ->select('*')
                ->from('translation')
                ->where('`iso_code` = \'' . trim($lang['iso_code']) . '\'')
            );
            
        }
        $this->context->_session->set('getGlobalTranslations', $translations);
        
        return $translations;
    }
    
    public function updateGlobalTranslations($translations) {
        if(is_array($translations)) {
            foreach($translations as $translation) {
                if(!empty($translation['translation'])) {
                    $id_translation = $this->getExistingObjectTranslation($translation['iso_code'], $translation['origin']);
            
                    if(!is_null($id_translation)) {
                        $translation = new Translation($id_translation);
                        $translation->file_name = $translation['file_name'];
                        $translation->translation = $translation['translation'];
                        $translation->update();
                    } else {
                        $translation = new Translation();
                        $translation->iso_code = $translation['iso_code'];
                        $translation->file_name = $translation['file_name'];
                        $translation->origin = $translation['origin'];
                        $translation->translation = $translation['translation'];
                        $translation->add();
                    }
                }
            
            }
        }
            
        
    }

    public static function getInstance($id = null, $idLang = null) {

        if (!Translation::$instance) {
            Translation::$instance = new Translation($id, $idLang);
        }

        return Translation::$instance;
    }

    public function add($autoDate = false, $nullValues = false) {
        
        $result = parent::add($autoDate, $nullValues);
        
        $this->context->translations = $this->getGlobalTranslations();
        
        return $result;

    }
    
    public function update($nullValues = false) {
        
        $result = parent::update($nullValues);

		$this->context->translations = $this->getGlobalTranslations();
        
        return $result;
	}

    public function getExistingTranslation($iso_code, $origin) {
        
        $result = $this->context->_session->get('getExistingTranslation_'.$origin.'_'.$iso_code);

        if (!empty($result) && is_string($result)) {
            return $result;
        }

       $translation = Db::getCrmInstance($this->dbUser, $this->dbPasswd, $this->dbName, $this->dbServer)->getValue(
            (new DbQuery())
            ->select('`translation`')
            ->from('translation')
            ->where('`iso_code` = \'' . trim($iso_code) . '\'')
            ->where('`origin` = \'' . bqSQL(trim($origin)) . '\'')
        );
        
        $this->context->_session->set('getExistingTranslation_'.$origin.'_'.$iso_code, $translation);
        
        return $translation;
    }
    
    public function getExistingObjectTranslation($iso_code, $origin) {

       $id_translation = Db::getCrmInstance($this->dbUser, $this->dbPasswd, $this->dbName, $this->dbServer)->getValue(
            (new DbQuery())
            ->select('`id_translation`')
            ->from('translation')
            ->where('`iso_code` = \'' . trim($iso_code) . '\'')
            ->where('`origin` = \'' . bqSQL(trim($origin)) . '\'')
        );
        
        if(Validate::isUnsignedId($id_translation)) {
            return $id_translation;
        }
        
        return null;
    }

    public function getExistingTranslationByIso($iso_code) {
        
        $javareturn = $this->context->_session->get('getExistingTranslationByIso_'.$iso_code);

        if (!empty($javareturn) && is_array($javareturn)) {
            return $javareturn;
        }

        $javareturn = [];

        $results = Db::getCrmInstance($this->dbUser, $this->dbPasswd, $this->dbName, $this->dbServer)->executeS(
            (new DbQuery())
            ->select('*')
            ->from('translation')
            ->where('`iso_code` = \'' . trim($iso_code) . '\'')
        );

        foreach ($results as $result) {
            $javareturn[$result['origin']] = $result['translation'];
        }
        
        $this->context->_session->set('getExistingTranslationByIso_'.$iso_code, $javareturn);

        return $javareturn;
    }

    public static function addTranslation($object) {

        $object = Tools::jsonDecode(Tools::jsonEncode($object), true);

        $translation = new Translation();

        foreach ($object as $key => $value) {

            if (property_exists($translation, $key)) {
                $translation->{$key}
                = $value;
            }

        }

        $result = $translation->add();

        return $result;
    }

}
