<?php

/**
 * Class HookPlugin
 *
 * @since 1.9.1.0
 */
class HookPlugin extends PhenyxObjectModel {

    public $require_context = false;

    public $id_plugin;

    public $id_hook;

    public $position;

    /**
     * @see PhenyxObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'hook_plugin',
        'primary' => 'id_hook_plugin',
        'fields'  => [
            'id_plugin' => ['type' => self::TYPE_STRING, 'validate' => 'isHookName', 'required' => true, 'size' => 64],
            'id_hook'   => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'position'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
        ],
    ];

    public function __construct($id = null, $idLang = null) {

        parent::__construct($id, $idLang);

    }

    public function add($autoDate = true, $nullValues = false, $position = null) {

        if (is_null($position)) {
            $this->position = $this->getNewLastPosition();
        } else {
            $this->adjustPosition($position);
            $this->position = $position;
        }

        return parent::add($autoDate, $nullValues);
    }

    public function update($nullValues = true, $init = true) {

        $oldMenu = new HookPlugin($this->id);

        if ($this->position != $oldMenu->position) {
            $this->adjustPosition($this->position);
        }

        if (parent::update($nullValues)) {
            return true;

        }

    }

    public function adjustPosition($position) {

        $menus = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('t.`id_hook_plugin`, t.`position`, t.`id_plugin`')
                ->from('hook_plugin', 't')
                ->where('t.`id_plugin` = ' . (int) $this->id_plugin)
                ->where('t.`position` >= ' . (int) $position)
                ->orderBy('t.`position` ASC')
        );
        $i = $position + 1;

        foreach ($menus as $menu) {
            $sql = 'UPDATE `' . _DB_PREFIX_ . 'hook_plugin` SET `position` = ' . (int) $i . ' WHERE `id_hook_plugin` = ' . (int) $menu['id_hook_plugin'];
            $result = Db::getInstance()->execute($sql);
            $i++;

        }

    }

    public function delete() {

        if (parent::delete()) {

            return $this->cleanPositions();
        }

        return false;
    }

    public function getNewLastPosition() {

        return (Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('IFNULL(MAX(`position`), 0) + 1')
                ->from('hook_plugin')
                ->where('`id_hook` = ' . (int) $this->id_hook)
        ));
    }

    public function cleanPositions() {

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`id_hook_plugin`')
                ->from('hook_plugin')
                ->where('`id_hook` = ' . (int) $this->id_hook)
                ->orderBy('`position`')
        );

        for ($i = 0, $total = count($result); $i < $total; ++$i) {
            Db::getInstance()->update(
                'hook_plugin',
                [
                    'position' => (int) $i,
                ],
                '`id_hook` = ' . (int) $this->id_hook . ' AND `id_hook_plugin` = ' . (int) $result[$i]['id_hook_plugin']
            );
        }

        return true;
    }

}
