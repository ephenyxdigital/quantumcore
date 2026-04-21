<?php

class ComposerCategory extends ComposerMap {
	
	public $require_context = false;
	
	public $position;
	public $active;
    public $generated;
	public $name;
	

	
	public static $definition = [
		'table'          => 'composer_category',
		'primary'        => 'id_composer_category',
		'multilang'      => true,
		'fields'         => [
			'position'       => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
			'active'         => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
			/* Lang fields */
            'generated'      => ['type' => self::TYPE_BOOL, 'lang' => true],
			'name'           => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName', 'size' => 128],
		],
	];
	
	public function __construct($id = null, $idLang = null) {
		
		parent::__construct($id, $idLang);
		
		
		
	}
	
	public function add($autoDate = true, $nullValues = false) {

		
		$this->position = ComposerCategory::getNewLastPosition();

		if (!parent::add($autoDate, $nullValues)) {
			return false;
		}
		return true;

		
	}
	
	public static function getNewLastPosition() {

		return (Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('IFNULL(MAX(`position`), 0) + 1')
				->from('composer_category')
		));
	}
	
	public static function geCategories($idLang = null) {

		if (is_null($idLang)) {
			$idLang = Context::getContext()->language->id;
		}
		$titlesArray = [];
		 $db_results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('c.`id_composer_category`, cl.`name`')
                ->from('composer_category', 'c')
                ->leftJoin('composer_category_lang', 'cl', 'cl.`id_composer_category` = c.`id_composer_category` AND cl.`id_lang` = ' . (int) $idLang)
        );
		
		foreach ($db_results as $category) {
            /** @var Gender $gender */
            $titlesArray[$category['id_composer_category']] = $category['name'];
        }


		return $titlesArray;
	}
	
	public static function getMapsItems($idLang) {

        $db_results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('c.`base`, c.`type`, c.`icon`, c.`is_container`, cml.`name`, ccl.`name` as `category`, cml.`description`')
                ->from('composer_map', 'c')
                ->leftJoin('composer_map_lang', 'cml', 'cml.`id_composer_map` = c.`id_composer_map` AND cml.`id_lang` = ' . (int) $idLang)
                ->leftJoin('composer_category_lang', 'ccl', 'ccl.`id_composer_category` = c.`id_composer_category` AND ccl.`id_lang` = ' . (int) $idLang)
                ->where('c.`content_element` = 1')
        );

        $items = [];

        foreach ($db_results as $key => $value) {
			
            $result = [];
            $result['name'] = $value['name'];
            $result['base'] = $value['base'];
            $result['category'] = $value['category'];
            $result['is_container'] = $value['is_container'];
			$result['type'] = $value['type'];
            $result['icon'] = $value['icon'];
            $result['description'] = $value['description'];
            $items[] = $result;

        }

        return $items;
    }
	
	public static function getMapsCategories($idLang) {

        $db_results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`name`')
                ->from('composer_category_lang')
                ->where('`id_lang` = ' . (int) $idLang)
        );

        $categories = [];

        foreach ($db_results as $key => $value) {
            $categories[] = $value['name'];

        }

        return $categories;
    }
	
	public static function getMapsbaseItems() {

        $db_results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('`base`')
                ->from('composer_map')
        );
        $base = [];

        foreach ($db_results as $key => $value) {
            $base[] = $value['base'];
        }

        return $base;

    }
	
	public static function getDefaultTemplates($idLang) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('ct.`image_path`, ct.`content`, ctl.`name`')
                ->from('composer_template', 'ct')
                ->leftJoin('composer_template_lang', 'ctl', 'ctl.`id_composer_template` = ct.`id_composer_template` AND ctl.`id_lang` = ' . (int) $idLang)
        );

    }



}
