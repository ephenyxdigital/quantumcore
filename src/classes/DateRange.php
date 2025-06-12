<?php

/**
 * Class DateRange
 *
 * @since 1.9.1.0
 */
class DateRange extends PhenyxObjectModel {

    public $require_context = false;
    // @codingStandardsIgnoreStart
    /** @var string $time_start */
    public $time_start;
    /** @var string $time_end */
    public $time_end;
    // @codingStandardsIgnoreEnd
    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'date_range',
        'primary' => 'id_date_range',
        'fields'  => [
            'time_start' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
            'time_end'   => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
        ],
    ];

    public static function getCurrentRange() {

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`id_date_range`, `time_end`')
                ->from('date_range')
                ->where('`time_end` = (SELECT MAX(`time_end`) FROM `' . _DB_PREFIX_ . 'date_range`)')
        );

        if (!$result['id_date_range'] || strtotime($result['time_end']) < strtotime(date('Y-m-d H:i:s'))) {
            // The default range is set to 1 day less 1 second (in seconds)
            $rangeSize = 86399;
            $dateRange = new static();
            $dateRange->time_start = date('Y-m-d', time());
            $dateRange->time_end = date('Y-m-d H:i:s', time() + $rangeSize);
            $dateRange->add();

            return $dateRange->id;
        }

        return $result['id_date_range'];
    }

}
