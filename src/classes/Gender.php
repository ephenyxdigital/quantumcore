<?php

/**
 * @since 1.9.1.0
 */
class Gender extends PhenyxObjectModel {

    public $require_context = false;
    // @codingStandardsIgnoreStart
    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'gender',
        'primary'   => 'id_gender',
        'multilang' => true,
        'fields'    => [
            'type'      => ['type' => self::TYPE_INT, 'required' => true],

            /* Lang fields */
            'generated' => ['type' => self::TYPE_BOOL, 'lang' => true],
            'name'      => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => true, 'size' => 20],
        ],
    ];
    public $id_gender;
    public $generated;
    public $name;
    // @codingStandardsIgnoreEnd
    public $type;

    /**
     * GenderCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idCompany
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct($id = null, $idLang = null) {

        parent::__construct($id, $idLang);

        $this->image_dir = _EPH_GENDERS_DIR_;
    }

    /**
     * @param null $idLang
     *
     * @return PhenyxCollection
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getGenders($idLang = null) {

        if (is_null($idLang)) {
            $idLang = Context::getContext()->language->id;
        }

        $genders = new PhenyxCollection('Gender', $idLang);

        return $genders;
    }

    /**
     * @param bool $useUnknown
     *
     * @return string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getImage($useUnknown = false) {

        if (!isset($this->id) || empty($this->id) || !file_exists(_EPH_GENDERS_DIR_ . $this->id . '.jpg')) {
            return _THEME_GENDERS_DIR_ . 'Unknown.jpg';
        }

        return _THEME_GENDERS_DIR_ . $this->id . '.jpg';
    }

}
