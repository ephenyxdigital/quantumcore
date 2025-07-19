<?php

/**
 * Class StateCore
 *
 * @since 1.9.1.0
 */
class State extends PhenyxObjectModel {

    public $require_context = false;
    // @codingStandardsIgnoreStart
    /** @var int Country id which state belongs */
    public $id_country;
    /** @var int Zone id which state belongs */
    public $id_zone;
    /** @var string 2 letters iso code */
    public $iso_code;
    /** @var string Name */
    public $name;
    /** @var bool Status for delivery */
    public $active = true;
    // @codingStandardsIgnoreEnd

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'state',
        'primary' => 'id_state',
        'fields'  => [
            'id_country' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_zone'    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'iso_code'   => ['type' => self::TYPE_STRING, 'validate' => 'isStateIsoCode', 'required' => true, 'size' => 7],
            'name'       => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'active'     => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
        ],
    ];

    public static function getStates($idLang = false, $active = false) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_state`, `id_country`, `id_zone`, `iso_code`, `name`, `active`')
                ->from('state', 's')
                ->where($active ? '`active` = 1' : '')
                ->orderBy('`name` ASC')
        );
    }

    public static function getNameById($idState) {

        if (!$idState) {
            return false;
        }

        $cacheId = 'State::getNameById_' . (int) $idState;

        if (!CacheApi::isStored($cacheId)) {
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('`name`')
                    ->from('state')
                    ->where('`id_state` = ' . (int) $idState)
            );
            CacheApi::store($cacheId, $result);

            return $result;
        }

        return CacheApi::retrieve($cacheId);
    }

    public static function getIdByName($state) {

        if (empty($state)) {
            return false;
        }

        $cacheId = 'State::getIdByName_' . pSQL($state);

        if (!CacheApi::isStored($cacheId)) {
            $result = (int) Db::getInstance()->getValue(
                (new DbQuery())
                    ->select('`id_state`')
                    ->from('state')
                    ->where('`name` = \'' . pSQL($state) . '\'')
            );
            CacheApi::store($cacheId, $result);

            return $result;
        }

        return CacheApi::retrieve($cacheId);
    }

    public static function getIdByIso($isoCode, $idCountry = null) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_state`')
                ->from('state')
                ->where('`iso_code` = \'' . pSQL($isoCode) . '\'')
                ->where($idCountry ? '`id_country` = ' . (int) $idCountry : '')
        );
    }

    public static function getStatesByIdCountry($idCountry) {

        if (empty($idCountry)) {
            die(Tools::displayError());
        }

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('state', 's')
                ->where('s.`id_country` = ' . (int) $idCountry)
        );
    }

    public static function hasCounties($idState) {

        return count(County::getCounties((int) $idState));
    }

    public static function getIdZone($idState) {

        if (!Validate::isUnsignedId($idState)) {
            die(Tools::displayError());
        }

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_zone`')
                ->from('state')
                ->where('`id_state` = ' . (int) $idState)
        );
    }

    public function delete() {

        if (!$this->isUsed()) {
            // Database deletion
            $result = Db::getInstance()->delete($this->def['table'], '`' . $this->def['primary'] . '` = ' . (int) $this->id);

            if (!$result) {
                return false;
            }

            // Database deletion for multilingual fields related to the object

            if (!empty($this->def['multilang'])) {
                Db::getInstance()->delete(bqSQL($this->def['table']) . '_lang', '`' . $this->def['primary'] . '` = ' . (int) $this->id);
            }

            return $result;
        } else {
            return false;
        }

    }

    public function isUsed() {

        return ($this->countUsed() > 0);
    }

    public function countUsed() {

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('address')
                ->where('`' . bqSQL(static::$definition['primary']) . '` = ' . (int) $this->id)
        );

        return $result;
    }

    public function affectZoneToSelection($idsStates, $idZone) {

        // cast every array values to int (security)
        $idsStates = array_map('intval', $idsStates);

        return Db::getInstance()->update(
            'state',
            [
                'id_zone' => (int) $idZone,
            ],
            '`id_state` IN (' . implode(',', $idsStates) . ')'
        );
    }

}
