<?php

/**
 * @since 1.9.1.0
 */
class CronJobs extends PhenyxObjectModel {

    public $require_context = false;
    // @codingStandardsIgnoreStart
    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'cronjobs',
        'primary' => 'id_cronjobs',
        'fields'  => [
            'description' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'class_name'  => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'task'        => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'hour'        => ['type' => self::TYPE_INT],
            'day'         => ['type' => self::TYPE_INT],
            'month'       => ['type' => self::TYPE_INT],
            'day_of_week' => ['type' => self::TYPE_INT],
            'custom'      => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'args'        => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'updated_at'  => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false],
            'active'      => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],

            /* Lang fields */

        ],
    ];

    // @codingStandardsIgnoreEnd
    public $description;
    public $class_name;
    public $task;
    public $hour;
    public $day;
    public $month;
    public $day_of_week;
    public $custom;
    public $args;
    public $updated_at;
    public $active;

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
    public function __construct($id = null, $idLang = null, $idShop = null) {

        parent::__construct($id, $idLang, $idShop);

    }

    public static function runTasksCrons() {

        $file = fopen("testrunTasksCrons.txt", "a");
        $date = new DateTime("now", new DateTimeZone('America/New_York'));
        $query = 'SELECT * FROM ' . _DB_PREFIX_ . 'cronjobs WHERE `active` = 1';
        $crons = Db::getInstance()->executeS($query);

        if (is_array($crons) && (count($crons) > 0)) {

            foreach ($crons as &$cron) {
                fwrite($file, "we test " . $cron['id_cronjobs'] . PHP_EOL);

                if (CronJobs::shouldBeExecuted($cron) == true) {
                    fwrite($file, "go cow " . $cron['description'] . PHP_EOL . PHP_EOL);
                    $class_name = $cron['class_name'];

                    if (class_exists($class_name)) {

                        $instance = new $class_name();
                        $task = $cron['task'];

                        if (method_exists($instance, $task)) {
                            $args = !empty($cron['arg']) ? $cron['arg'] : null;
                            try {
                                $result = $instance->{$task}

                                ($args);
                                $query = 'UPDATE ' . _DB_PREFIX_ . 'cronjobs
                                SET `updated_at` = NOW()
                                WHERE `id_cronjobs` = ' . (int) $cron['id_cronjobs'];
                                Db::getInstance()->execute($query);
                            } catch (PhenyxException $e) {
                                fwrite($file, 'Error ' . $e->getMessage() . PHP_EOL);
                            }

                        } else {
                            fwrite($file, "method " . $task . ' has not been found' . PHP_EOL . PHP_EOL);
                        }

                    } else {
                        fwrite($file, "class " . $class_name . ' has not been found' . PHP_EOL . PHP_EOL);
                    }

                }

            }

        }

    }

    public static function shouldBeExecuted($cron) {

        $hour = ($cron['hour'] == -1) ? date('H') : $cron['hour'];
        $day = ($cron['day'] == -1) ? date('d') : $cron['day'];
        $month = ($cron['month'] == -1) ? date('m') : $cron['month'];
        $day_of_week = ($cron['day_of_week'] == -1) ? date('D') : date('D', strtotime('Sunday +' . $cron['day_of_week'] . ' days'));

        $day = date('Y') . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
        $execution = $day_of_week . ' ' . $day . ' ' . str_pad($hour, 2, '0', STR_PAD_LEFT);
        $now = date('D Y-m-d H');

        return !(bool) strcmp($now, $execution);
    }

}
