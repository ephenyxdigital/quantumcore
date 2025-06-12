<?php

/**
 * Class Page
 *
 * @since 1.9.1.0
 */
class Page extends PhenyxObjectModel {

    public $require_context = false;
    // @codingStandardsIgnoreStart

    public $id_page_type;
    public $id_object;

    // @codingStandardsIgnoreEnd
    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'page',
        'primary' => 'id_page',
        'fields'  => [
            'id_page_type' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_object'    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
        ],
    ];

    public static function getCurrentId() {

        $controller = Performer::getInstance()->getController();
        $pageTypeId = Page::getPageTypeByName($controller);

        $specialArray = [
            'pfg' => 'id_pfg',
        ];
        $extraSpecialArrays = Context::getContext()->_hook->exec('actionGetSpecialArrays', [], null, true);

        if (is_array($extraSpecialArrays) && count($extraSpecialArrays)) {

            foreach ($extraSpecialArrays as $plugin => $pages) {

                if (is_array($pages) && count($pages)) {

                    foreach ($pages as $key => $value) {
                        $specialArray[] = $value;
                    }

                }

            }

        }

        $where = '';
        $insertData = [
            'id_page_type' => $pageTypeId,
        ];

        if (array_key_exists($controller, $specialArray)) {
            $objectId = Tools::getValue($specialArray[$controller], null);
            $where = ' AND `id_object` = ' . (int) $objectId;
            $insertData['id_object'] = (int) $objectId;
        }

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`id_page`')
                ->from('page')
                ->where('`id_page_type` = ' . (int) $pageTypeId . $where)
        );

        if (!empty($result) && $result['id_page']) {
            return $result['id_page'];
        }

        Db::getInstance()->insert('page', $insertData, true);

        return Db::getInstance()->Insert_ID();
    }

    public static function getPageTypeByName($name) {

        if (empty($name)) {
            return;
        }

        if ($value = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
            ->select('`id_page_type`')
            ->from('page_type')
            ->where('`name` = \'' . pSQL($name) . '\'')
        )) {
            return $value;
        }

        $type = new PageType();
        $type->name = $name;
        return $type->add();

    }

    public static function setPageViewed($idPage) {

        $idDateRange = DateRange::getCurrentRange();
        $context = Context::getContext();

        $sql = 'UPDATE `' . _DB_PREFIX_ . 'page_viewed`
                SET `counter` = `counter` + 1
                WHERE `id_date_range` = ' . (int) $idDateRange . '
                    AND `id_page` = ' . (int) $idPage;
        Db::getInstance()->execute($sql);

        if (Db::getInstance()->Affected_Rows() == 0) {
            Db::getInstance()->insert(
                'page_viewed',
                [
                    'id_date_range' => (int) $idDateRange,
                    'id_page'       => (int) $idPage,
                    'counter'       => 1,
                ]
            );
        }

    }

}
