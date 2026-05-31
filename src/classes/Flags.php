<?php

namespace EphenyxDigital\QuantumCore;
/**
 * @since 1.9.1.0
 */
class Flags extends PhenyxObjectModel {

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

        parent::__construct($id, $idLang);
		
    }
	
    public static function getFlagByIso($isoCode) {

        if (!Validate::isLanguageIsoCode($isoCode)) {
            die(Tools::displayError('Fatal error: ISO code is not correct') . ' ' . Tools::safeOutput($isoCode));
        }

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`flag_hash`')
                ->from('flags')
                ->where('`iso_code` = \'' . pSQL(strtolower($isoCode)) . '\'')
        );
    }

   

}
