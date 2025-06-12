<?php

class TopMenuColumnWrap extends PhenyxObjectModel {

    public $id_topmenu;
    public $id_menu_depend;
    public $internal_name;
    public $bg_color;
    public $txt_color_column;
    public $txt_color_column_over;
    public $txt_color_element;
    public $txt_color_element_over;
    public $position;
    public $width;
    public $custom_class;
    public $privacy;
    public $chosen_groups;
    public $active = 1;
    public $active_desktop = 1;
    public $active_mobile = 1;
    public $generated;
    public $value_over;
    public $value_under;

    public $columns;

    public static $definition = [
        'table'     => 'topmenu_columns_wrap',
        'primary'   => 'id_topmenu_columns_wrap',
        'multishop' => false,
        'multilang' => true,
        'fields'    => [
            'id_topmenu'             => ['type' => self::TYPE_INT, 'required' => true],
            'id_menu_depend'         => ['type' => self::TYPE_INT],
            'internal_name'          => ['type' => self::TYPE_STRING],
            'bg_color'               => ['type' => self::TYPE_STRING],
            'txt_color_column'       => ['type' => self::TYPE_STRING],
            'txt_color_column_over'  => ['type' => self::TYPE_STRING],
            'txt_color_element'      => ['type' => self::TYPE_STRING],
            'txt_color_element_over' => ['type' => self::TYPE_STRING],
            'position'               => ['type' => self::TYPE_INT],
            'width'                  => ['type' => self::TYPE_INT],
            'custom_class'           => ['type' => self::TYPE_STRING, 'required' => false, 'size' => 255],
            'privacy'                => ['type' => self::TYPE_INT],
            'chosen_groups'          => ['type' => self::TYPE_STRING],
            'active'                 => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'active_desktop'         => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'active_mobile'          => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],

            'generated'              => ['type' => self::TYPE_BOOL, 'lang' => true],
            'value_over'             => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString'],
            'value_under'            => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString'],
        ],
    ];

    public function __construct($id = null, $id_lang = null) {

        if (is_null($id_lang)) {
            $id_lang = Context::getContext()->language->id;
        }

        parent::__construct($id, $id_lang);

        if ($this->id) {
            $this->chosen_groups = Tools::jsonDecode($this->chosen_groups);
            $this->columns = $this->getMenuColums();
        }

    }

    public static function buildObject($id, $id_lang = null, $className = null, $full = true) {

        $objectData = parent::buildObject($id, $id_lang, $className);
        $objectData['chosen_groups'] = Tools::jsonDecode($objectData['chosen_groups']);
        $objectData['columns'] = self::getStaticMenuColums($objectData);

        return Tools::jsonDecode(Tools::jsonEncode($objectData));
    }

    public function getMenuColums() {

        if ($this->context->cache_enable && is_object($this->context->cache_api)) {
            $value = $this->context->cache_api->getData('getMenuColums_' . $this->id, 864000);
            $temp = empty($value) ? null : Tools::jsonDecode($value);

            if (!empty($temp)) {
                return $temp;
            }

        }

        $column = [];

        $columns = new PhenyxCollection('TopMenuColumn', $this->context->language->id);
        $columns->where('id_topmenu_columns_wrap', '=', $this->id);

        if (!$this->request_admin) {
            $columns->where('active', '=', 1);
        }

        $columns->orderBy('position');

        foreach ($columns as $wrap) {
            $column[] = new TopMenuColumn($wrap->id);
        }

        if ($this->context->cache_enable && is_object($this->context->cache_api)) {
            $temp = $column === null ? null : Tools::jsonEncode($column);
            $this->context->cache_api->putData('getMenuColums_' . $this->id, $temp);
        }

        return $column;
    }

    public static function getStaticMenuColums($objectData) {

        $context = Context::getContext();

        if ($context->cache_enable && is_object($context->cache_api)) {
            $value = $context->cache_api->getData('getMenuColums_' . $objectData['id'], 864000);
            $temp = empty($value) ? null : Tools::jsonDecode($value);

            if (!empty($temp)) {
                return $temp;
            }

        }

        $column = [];

        $columns = new PhenyxCollection('TopMenuColumn', $context->language->id);
        $columns->where('id_topmenu_columns_wrap', '=', $objectData['id']);

        if (!PhenyxObjectModel::$admin_request) {
            $columns->where('active', '=', 1);
        }

        $columns->orderBy('position');

        foreach ($columns as $wrap) {
            $column[] = TopMenuColumn::buildObject($wrap->id, $context->language->id);
        }

        if ($context->cache_enable && is_object($context->cache_api)) {
            $temp = $column === null ? null : Tools::jsonEncode($column);
            $context->cache_api->putData('getMenuColums_' . $objectData['id'], $temp);
        }

        return $column;
    }

    public function add($autodate = true, $nullValues = false) {

        $result = parent::add($autodate, $nullValues);

        if ($result) {
            $this->_session->remove('getAdminMenus');
            $this->_session->remove('getFrontMenus');
        }

        return $result;
    }

    public function update($nullValues = false) {

        $result = parent::update($nullValues);

        if ($result) {
            $this->_session->remove('getAdminMenus');
            $this->_session->remove('getFrontMenus');
        }

        return $result;
    }

    public function delete() {

        $columns = new PhenyxCollection('TopMenuColumn');
        $columns->where('id_topmenu_columns_wrap', '=', $this->id);

        foreach ($columns as $column) {
            $column->delete();
        }

        $this->_session->remove('getAdminMenus');
        $this->_session->remove('getFrontMenus');

        return parent::delete();
    }

    public static function getMenuColumnsWrap($id_topmenu, $id_lang, $active = true) {

        return Db::getInstance()->executeS(
            (new DbQuery())
                ->select('atmcw.`id_topmenu_columns_wrap` as id_columns_wrap, atmcw.*, atmcwl.*')
                ->from('topmenu_columns_wrap', 'atmcw')
                ->leftJoin('topmenu_columns_wrap_lang', 'atmcwl', 'atmcw.`id_topmenu_columns_wrap` = atmcwl.`id_topmenu_columns_wrap` AND atmcwl.`id_lang` = ' . (int) $id_lang)
                ->where(($active ? ' atmcw.`active` = 1 AND (atmcw.`active_desktop` = 1 || atmcw.`active_mobile` = 1) AND' : '') . ' atmcw.`id_topmenu` = ' . (int) $id_topmenu)
                ->orderBy('atmcw.`position`')
        );

    }

    public static function getMenusColumnsWrap($menus, $id_lang) {

        $columnWrap = [];

        if (is_array($menus) && count($menus)) {

            foreach ($menus as $menu) {
                $columnWrap[$menu->id] = self::getMenuColumnsWrap($menu->id, $id_lang);
            }

        }

        return $columnWrap;
    }

    public static function getColumnsWrap($id_lang = false, $active = true) {

        $query = new DbQuery();
        $query->select('atmcw.* ' . ($id_lang ? ',' : ''));

        if ($id_lang) {
            $query->leftJoin('topmenu_columns_wrap_lang', 'atmcwl', 'atmcw.`id_topmenu_columns_wrap` = atmcwl.`id_topmenu_columns_wrap` AND atmcwl.`id_lang` = ' . (int) $id_lang);
        }

        if ($active) {
            $query->where('atmcw.`active` = 1 AND (atmcw.`active_desktop` = 1 || atmcw.`active_mobile` = 1)');
        }

        $query->orderBy('atmcw.`position`');

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->ExecuteS($query);
    }

    public static function getColumnWrapIds($ids_menu) {

        $result = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('`id_topmenu_columns_wrap`')
                ->from('topmenu_columns_wrap')
                ->where('`id_topmenu` IN(' . pSQL($ids_menu) . ')')
        );

        $columnsWrap = [];

        foreach ($result as $row) {
            $columnsWrap[] = $row['id_topmenu_columns_wrap'];
        }

        return $columnsWrap;
    }

    public static function getColumnWrapNumber($ids_menu) {

        return Db::getInstance()->getValue(
            (new DbQuery())
                ->select('COUNT(`id_topmenu_columns_wrap`)')
                ->from('topmenu_columns_wrap')
                ->where('`id_topmenu` = ' . (int) $ids_menu)
        );

    }

}
