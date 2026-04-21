<?php

class ComposerImageType extends PhenyxObjectModel {

    public $require_context = false;
	public $id;
	public $name;
	
	public $width;
	public $height;
	public $active;

	public static $definition = [
		'table' => 'vc_image_type', 
		'primary' => 'id_vc_image_type', 
		'fields' => [
			'name' => ['type' => self::TYPE_STRING, 'validate' => 'isImageTypeName', 'required' => true, 'size' => 64], 			
			'width' => ['type' => self::TYPE_INT, 'validate' => 'isImageSize', 'required' => true], 
			'height' => ['type' => self::TYPE_INT, 'validate' => 'isImageSize', 'required' => true], 
			'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool']
		]
	];

	protected static $images_types_cache = [];
	protected static $images_types_name_cache = [];
	protected $webserviceParameters = [];

	public static function getImageTypeByName($name) {
		
		$result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
                (new DbQuery())
                    ->select('*')
                    ->from('vc_image_type')
                    ->where('`name` LIKE  \'' . $name.'\'')
            );


		if (!empty($result)) {
			$image['width'] = $result['width'];
			$image['height'] = $result['height'];

			return $image;
		}

		return false;
	}

	public static function getImageTypeById($type) {

		if (!empty($type)) {

			$where = 'WHERE 1';

			if (!empty($type)) {
				$where .= ' AND `id_vc_image_type`=' . bqSQL($type);
			}

			$query = 'SELECT * FROM `' . _DB_PREFIX_ . 'vc_image_type` ' . $where . ' ORDER BY `name` ASC';
			return Db::getInstance()->executeS($query);
		}

		return false;
	}

	public static function getImagesTypes($type = null) {

		if (!empty($type) && !isset(self::$images_types_cache[$type])) {
			$where = 'WHERE 1';

			if (!empty($type)) {
				$where .= ' AND `' . bqSQL($type) . '` = 1 ';
			}

			$query = 'SELECT * FROM `' . _DB_PREFIX_ . 'vc_image_type` ' . $where . ' ORDER BY `name` ASC';
			self::$images_types_cache[$type] = Db::getInstance()->executeS($query);
		}

		return self::$images_types_cache[$type];
	}

	public static function typeAlreadyExists($typeName) {

		if (!Validate::isImageTypeName($typeName)) {
			die(Tools::displayError());
		}

		Db::getInstance()->executeS('
			SELECT `id_vc_image_type`
			FROM `' . _DB_PREFIX_ . 'vc_image_type`
			WHERE `name` = \'' . pSQL($typeName) . '\'');

		return Db::getInstance()->NumRows();
	}

	public static function getByNameNType($name, $type = null, $order = null) {

		if (!isset(self::$images_types_name_cache[$name . '_' . $type . '_' . $order])) {
			self::$images_types_name_cache[$name . '_' . $type . '_' . $order] = Db::getInstance()->getRow('
				SELECT `id_vc_image_type`, `name`, `width`, `height`, `active`
				FROM `' . _DB_PREFIX_ . 'vc_image_type`
				WHERE
				`name` LIKE \'' . pSQL($name) . '\'' . (!is_null($type) ? ' AND `' . pSQL($type) . '` = 1' : '') . (!is_null($order) ? ' ORDER BY `' . bqSQL($order) . '` ASC' : ''));
		}

		return self::$images_types_name_cache[$name . '_' . $type . '_' . $order];
	}

	public static function getFormatedName($name) {

		$theme_name = Context::getContext()->theme->name;
		$name_without_theme_name = str_replace([
			'_' . $theme_name,
			$theme_name . '_',
		], '', $name);

		if (strstr($name, $theme_name) && self::getByNameNType($name)) {
			return $name;
		} else

		if (self::getByNameNType($name_without_theme_name . '_' . $theme_name)) {
			return $name_without_theme_name . '_' . $theme_name;
		} else

		if (self::getByNameNType($theme_name . '_' . $name_without_theme_name)) {
			return $theme_name . '_' . $name_without_theme_name;
		} else {
			return $name_without_theme_name . '_default';
		}

	}

}
