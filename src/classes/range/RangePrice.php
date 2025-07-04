<?php

/**
 * Class RangePrice
 *
 * @since 1.9.1.0
 */
class RangePrice extends PhenyxObjectModel {

    public $require_context = false;

    // @codingStandardsIgnoreStart
    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'range_price',
        'primary' => 'id_range_price',
        'fields'  => [
            'id_carrier' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true],
            'delimiter1' => ['type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat', 'required' => true],
            'delimiter2' => ['type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat', 'required' => true],
        ],
    ];
    /** @var int $id_carrier */
    public $id_carrier;
    /** @var float $delimiter1 */
    public $delimiter1;
    /** @var float $delimiter2 */
    public $delimiter2;
    // @codingStandardsIgnoreEnd

    /**
     * Get all available price ranges
     *
     * @param int $idCarrier
     *
     * @return array Ranges
     *
     * @throws PhenyxDatabaseExceptionException
     * @throws PhenyxException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getRanges($idCarrier) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('*')
                ->from('range_price')
                ->where('`id_carrier` = ' . (int) $idCarrier)
                ->orderBy('`delimiter1` ASC')
        );
    }

    /**
     * @param int      $idCarrier
     * @param float    $delimiter1
     * @param float    $delimiter2
     * @param int|null $idReference
     *
     * @return false|null|string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxException
     */
    public static function rangeExist($idCarrier, $delimiter1, $delimiter2, $idReference = null) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('range_price', 'rp')
                ->join((is_null($idCarrier) && $idReference) ? 'INNER JOIN `' . _DB_PREFIX_ . 'carrier` c on (rp.`id_carrier` = c.`id_carrier`)' : '')
                ->where($idCarrier ? ' `id_carrier` = ' . (int) $idCarrier : '')
                ->where(is_null($idCarrier) && $idReference ? ' c.`id_reference` = ' . (int) $idReference : '')
                ->where('`delimiter1` = ' . (float) $delimiter1)
                ->where('`delimiter2` = ' . (float) $delimiter2)
        );
    }

    /**
     * @param int      $idCarrier
     * @param int      $delimiter1
     * @param int      $delimiter2
     * @param int|null $idRang
     *
     * @return false|null|string
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     * @throws PhenyxException
     */
    public static function isOverlapping($idCarrier, $delimiter1, $delimiter2, $idRang = null) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('range_price')
                ->where('`id_carrier` = ' . (int) $idCarrier)
                ->where('((`delimiter1` >= ' . (float) $delimiter1 . ' AND `delimiter1` < ' . (float) $delimiter2 . ') OR (`delimiter2` > ' . (float) $delimiter1 . ' AND `delimiter2` < ' . (float) $delimiter2 . ') OR (' . (float) $delimiter1 . ' > `delimiter1` AND ' . (float) $delimiter1 . ' < `delimiter2`) OR (' . (float) $delimiter2 . ' < `delimiter1` AND ' . (float) $delimiter2 . ' > `delimiter2`)')
                ->where(!is_null($idRang) ? '`id_range_price` != ' . (int) $idRang : '')
        );
    }

    /**
     * Override add to create delivery value for all zones
     *
     * @see     classes/ObjectModelCore::add()
     *
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool Insertion result
     *
     * @throws PhenyxDatabaseExceptionException
     * @throws PhenyxException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function add($autoDate = true, $nullValues = false) {

        if (!parent::add($autoDate, $nullValues) || !Validate::isLoadedObject($this)) {
            return false;
        }

        if (defined('EPH_INSTALLATION_IN_PROGRESS')) {
            return true;
        }

        $carrier = new Carrier((int) $this->id_carrier);
        $priceList = [];

        foreach ($carrier->getZones() as $zone) {
            $priceList[] = [
                'id_range_price'  => (int) $this->id,
                'id_range_weight' => null,
                'id_carrier'      => (int) $this->id_carrier,
                'id_zone'         => (int) $zone['id_zone'],
                'price'           => 0,
            ];
        }

        $carrier->addDeliveryPrice($priceList);

        return true;
    }

}
