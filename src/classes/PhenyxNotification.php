<?php

/**
 * @since 1.9.1.0
 */
class PhenyxNotification extends PhenyxObjectModel {

    public $require_context = false;

    public $generated;

    public $notification;

    public $date_notification;
    // @codingStandardsIgnoreStart
    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'phenyx_notification',
        'primary'   => 'id_phenyx_notification',
        'multilang' => true,
        'fields'    => [
            'date_notification' => ['type' => self::TYPE_DATE, 'required' => true],

            /* Lang fields */
            'generated'         => ['type' => self::TYPE_BOOL, 'lang' => true],
            'notification'      => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => true],
        ],
    ];

    public function add($autoDate = false, $nullValues = false) {

        $this->date_notification = date('Y-m-d');

        return parent::add($autoDate, $nullValues);

    }

    public static function addNotification($object) {

        $object = Tools::jsonDecode(Tools::jsonEncode($object), true);

        $notification = new PhenyxNotification();

        foreach ($object as $key => $value) {

            if (is_array($value)) {

                foreach (Language::getIDs(false) as $idLang) {

                    if (property_exists($notification, $key)) {
                        $notification->{$key}

                        [(int) $idLang] = $value[(int) $idLang];
                    }

                }

            } else

            if (property_exists($notification, $key)) {
                $notification->{$key}

                = $value;
            }

        }

        $result = $notification->add();

        return $result;
    }

}
