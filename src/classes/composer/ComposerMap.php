<?php

class ComposerMap extends PhenyxObjectModel {

	protected static $sc = [];
	protected static $categories = [];
	protected static $user_sc = false;
	protected static $user_sorted_sc = false;
	protected static $user_categories = false;
	protected static $settings, $user_role;
	protected static $tags_regexp;
	public $seetings_maps = null;
	protected $init_activity = [];

	public $base;
	public $id_composer_category;
	public $class;
	public $content_element;
	public $type;
	public $is_container;
	public $weight;
	public $show_settings_on_create;
	public $wrapper_class;
	public $allowed_container_element;
	public $controls;
	public $custom_markup;
	public $default_content;
	public $js_view;
	public $is_corporate;
	public $active;
    public $generated;
	public $name;
	public $description;
	public $params;

	public $phenyxImgSizesOption;

	public $tab_id_1;
	public $tab_id_2;

	public $icons_arr;

	public $column_width_list;
    
    public $extraMaps = null;

	public static $definition = [
		'table'     => 'composer_map',
		'primary'   => 'id_composer_map',
		'multilang' => true,
		'fields'    => [
			'base'                      => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
			'id_composer_category'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
			'class'                     => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
			'type'                      => ['type' => self::TYPE_STRING],
			'content_element'           => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
			'is_container'              => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
			'icon'                      => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
			'weight'                    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
			'show_settings_on_create'   => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
			'wrapper_class'             => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
			'allowed_container_element' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
			'controls'                  => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
			'custom_markup'             => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'],
			'default_content'           => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'],
			'js_view'                   => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
			'is_corporate'              => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
			'active'                    => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false],
			/* Lang fields */
            'generated'                 => ['type' => self::TYPE_BOOL, 'lang' => true],
			'name'                      => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName', 'required' => true, 'size' => 128],
			'description'               => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'],
		],
	];

	public function __construct($id = null, $idLang = null) {

		parent::__construct($id, $idLang);

		$this->phenyxImgSizesOption = Composer::getPhenyxImgSizesOption();

		$this->tab_id_1 = time() . '-1-' . rand(0, 100);

		$this->tab_id_2 = time() . '-2-' . rand(0, 100);

		$this->icons_arr = [
			$this->l('None')                     => 'none',
			$this->l('Address book icon')        => 'wpb_address_book',
			$this->l('Alarm clock icon')         => 'wpb_alarm_clock',
			$this->l('Anchor icon')              => 'wpb_anchor',
			$this->l('Application Image icon')   => 'wpb_application_image',
			$this->l('Arrow icon')               => 'wpb_arrow',
			$this->l('Asterisk icon')            => 'wpb_asterisk',
			$this->l('Hammer icon')              => 'wpb_hammer',
			$this->l('Balloon icon')             => 'wpb_balloon',
			$this->l('Balloon Buzz icon')        => 'wpb_balloon_buzz',
			$this->l('Balloon Facebook icon')    => 'wpb_balloon_facebook',
			$this->l('Balloon Twitter icon')     => 'wpb_balloon_twitter',
			$this->l('Battery icon')             => 'wpb_battery',
			$this->l('Binocular icon')           => 'wpb_binocular',
			$this->l('Document Excel icon')      => 'wpb_document_excel',
			$this->l('Document Image icon')      => 'wpb_document_image',
			$this->l('Document Music icon')      => 'wpb_document_music',
			$this->l('Document Office icon')     => 'wpb_document_office',
			$this->l('Document PDF icon')        => 'wpb_document_pdf',
			$this->l('Document Powerpoint icon') => 'wpb_document_powerpoint',
			$this->l('Document Word icon')       => 'wpb_document_word',
			$this->l('Bookmark icon')            => 'wpb_bookmark',
			$this->l('Camcorder icon')           => 'wpb_camcorder',
			$this->l('Camera icon')              => 'wpb_camera',
			$this->l('Chart icon')               => 'wpb_chart',
			$this->l('Chart pie icon')           => 'wpb_chart_pie',
			$this->l('Clock icon')               => 'wpb_clock',
			$this->l('Fire icon')                => 'wpb_fire',
			$this->l('Heart icon')               => 'wpb_heart',
			$this->l('Mail icon')                => 'wpb_mail',
			$this->l('Play icon')                => 'wpb_play',
			$this->l('Shield icon')              => 'wpb_shield',
			$this->l('Video icon')               => "wpb_video",
		];

		$this->column_width_list = [
			$this->l('1 column - 1/12')    => '1/12',
			$this->l('2 columns - 1/6')    => '1/6',
			$this->l('3 columns - 1/4')    => '1/4',
			$this->l('4 columns - 1/3')    => '1/3',
			$this->l('5 columns - 5/12')   => '5/12',
			$this->l('6 columns - 1/2')    => '1/2',
			$this->l('7 columns - 7/12')   => '7/12',
			$this->l('8 columns - 2/3')    => '2/3',
			$this->l('9 columns - 3/4')    => '3/4',
			$this->l('10 columns - 5/6')   => '5/6',
			$this->l('11 columns - 11/12') => '11/12',
			$this->l('12 columns - 1/1')   => '1/1',
		];

		if ($this->id) {

			$this->params = $this->getShortCodeParam();
		}
        
        if ($this->context->cache_enable && is_object($this->context->cache_api)) {            
            $value = $this->context->cache_api->getData('getExtraMap_'.$this->context->language->id);
            $temp = empty($value) ? null : Tools::jsonDecode($value, true);

            if (!empty($temp) && is_array($temp) && count($temp)) {
                $this->extraMaps =  $temp;
            }

        } 
        if(is_null($this->extraMaps)) {
            $this->extraMaps = $this->context->_hook->exec('actionGetExtraMaps', ['phenyxImgSizesOption' => $this->phenyxImgSizesOption, 'animationStyles' => $this->animationStyles()], null, true);
            if ($this->context->cache_enable && is_object($this->context->cache_api)) {
                $temp = $this->extraMaps === null ? null : Tools::jsonEncode($this->extraMaps);
                $this->context->cache_api->putData('getExtraMap_'.$this->context->language->id, $temp);
            }
        }
        
        if ($this->context->cache_enable && is_object($this->context->cache_api)) {            
            $value = $this->context->cache_api->getData('getSeetingMap_'.$this->context->language->id);
            $temp = empty($value) ? null : Tools::jsonDecode($value, true);

            if (!empty($temp) && is_array($temp) && count($temp)) {
                $this->seetings_maps =  $temp;
            }

        } 
        if(is_null($this->seetings_maps)) {
            $this->seetings_maps = $this->getMapperFields();
            if ($this->context->cache_enable && is_object($this->context->cache_api)) {
                $temp = $this->seetings_maps === null ? null : Tools::jsonEncode($this->seetings_maps);
                $this->context->cache_api->putData('getSeetingMap_'.$this->context->language->id, $temp);
            }
        }


		

	}

	public function initFront() {

		$this->callActivities();

	}

	public function buildShortCodeTag() {

		foreach ($this->seetings_maps as $tag => $attributes) {

			if (isset(self::$sc[$tag])) {
				continue;
			}

			self::$sc[$tag] = $attributes;
			self::$sc[$tag]['params'] = [];

			if (!empty($attributes['params'])) {

				foreach ($attributes['params'] as $attribute) {

					self::$sc[$tag]['params'][] = $attribute;
				}

			}

			ephenyx_composer()->addShortCode(self::$sc[$tag]);
			//visual_composer()->addShortCode(self::$sc[$tag]);

		}

	}

	public function buildFrontShortCodeTag() {

		foreach ($this->seetings_maps as $tag => $attributes) {

			if (isset(self::$sc[$tag])) {
				continue;
			}

			self::$sc[$tag] = $attributes;
			self::$sc[$tag]['params'] = [];

			if (!empty($attributes['params'])) {

				foreach ($attributes['params'] as $attribute) {

					self::$sc[$tag]['params'][] = $attribute;
				}

			}

			visual_composer()->addShortCode(self::$sc[$tag]);

		}

	}

	public function getShortCodeParam() {

		$collections = [];
		$params = Db::getInstance()->executeS('SELECT `id_composer_map_params` FROM `' . _DB_PREFIX_ . 'composer_map_params` WHERE `id_composer_map` = ' . (int) $this->id);

		foreach ($params as $param) {
			$collections[] = new ParamMap($param['id_composer_map_params']);
		}

		return $collections;
	}

	public function addActivity($object, $method, $params = []) {

		$this->init_activity[] = [$object, $method, $params];
	}

	protected function callActivities() {

		foreach ($this->init_activity as $activity) {
			list($object, $method, $params) = $activity;

			if ($object == 'mapper') {

				switch ($method) {
				case 'map':
					ComposerMap::map($params['tag'], $params['attributes']);
					break;
				case 'drop_param':
					ComposerMap::dropParam($params['name'], $params['attribute_name']);
					break;
				case 'add_param':
					ComposerMap::addParam($params['name'], $params['attribute']);
					break;
				case 'mutate_param':
					ComposerMap::mutateParam($params['name'], $params['attribute']);
					break;
				case 'drop_shortcode':
					ComposerMap::dropShortcode($params['name']);
					break;
				case 'modify':
					ComposerMap::modify($params['name'], $params['setting_name'], $params['value']);
					break;
				}

			}

		}

	}

	protected static function getSettings() {

		return false;

	}

	public static function exists($tag) {

		return (bool) isset(self::$sc[$tag]);
	}

	public static function map($tag, $attributes) {

		if (empty($attributes['name'])) {
			trigger_error(sprintf($this->l("Wrong name for shortcode:%s. Name required"), $tag));
		} else

		if (empty($attributes['base'])) {
			trigger_error(sprintf($this->l("Wrong base for shortcode:%s. Base required"), $tag));
		} else {

			if (isset(self::$sc[$tag])) {
				return;
			}

			self::$sc[$tag] = $attributes;
			self::$sc[$tag]['params'] = [];

			if (!empty($attributes['params'])) {

				foreach ($attributes['params'] as $attribute) {

					if (isset(Composer::$sds_action_hooks['vc_mapper_attribute_' . $attribute['type']])) {
						$attribute = call_user_func(Composer::$sds_action_hooks['vc_mapper_attribute_' . $attribute['type']], $attribute);
					}

					self::$sc[$tag]['params'][] = $attribute;
				}

			}

			ephenyx_composer()->addShortCode(self::$sc[$tag]);

		}

	}

	public static function vc_map($tag, $attributes) {

		$vc_main = vc_manager();

		if (!self::$is_init) {
			vc_mapper()->addActivity(
				'mapper', 'vc_map', [
					'tag'        => $tag,
					'attributes' => $attributes,
				]
			);
			return false;
		}

		if (empty($attributes['name'])) {
			trigger_error(sprintf($vc_main->l("Wrong name for shortcode:%s. Name required"), $tag));
		} else

		if (empty($attributes['base'])) {
			trigger_error(sprintf($vc_main->l("Wrong base for shortcode:%s. Base required"), $tag));
		} else {

			if (isset(self::$sc[$tag])) {
				return;
			}

			self::$sc[$tag] = $attributes;
			self::$sc[$tag]['params'] = [];

			if (!empty($attributes['params'])) {

				foreach ($attributes['params'] as $attribute) {

					if (isset(JsComposer::$sds_action_hooks['vc_mapper_attribute_' . $attribute['type']])) {
						$attribute = call_user_func(JsComposer::$sds_action_hooks['vc_mapper_attribute_' . $attribute['type']], $attribute);
					}

					self::$sc[$tag]['params'][] = $attribute;
				}

			}

			visual_composer()->addShortCode(self::$sc[$tag]);

		}

	}

	protected static function generateUserData($force = false) {

		if (!$force && self::$user_sc !== false && self::$user_categories !== false) {
			return;
		}

		$settings = self::getSettings();
		self::$user_sc = self::$user_categories = self::$user_sorted_sc = [];

		foreach (self::$sc as $name => $values) {

			if (in_array($name, ['vc_column', 'vc_row', 'vc_row_inner', 'vc_column_inner']) || !isset($settings[self::$user_role]['shortcodes'])
				|| (isset($settings[self::$user_role]['shortcodes'][$name]) && (int) $settings[self::$user_role]['shortcodes'][$name] == 1)
			) {

				if (!isset($values['content_element']) || $values['content_element'] === true) {
					$categories = isset($values['category']) ? $values['category'] : '_other_category_';
					$values['_category_ids'] = [];

					if (is_array($categories)) {

						foreach ($categories as $c) {

							if (array_search($c, self::$user_categories) === false) {
								self::$user_categories[] = $c;
							}

							$values['_category_ids'][] = md5($c); // array_search($category, self::$categories);
						}

					} else {

						if (array_search($categories, self::$user_categories) === false) {
							self::$user_categories[] = $categories;
						}

						$values['_category_ids'][] = md5($categories); // array_search($category, self::$categories);
					}

				}

				self::$user_sc[$name] = $values;
				self::$user_sorted_sc[] = $values;

			}

		}

		@usort(self::$user_sorted_sc, ["ComposerMap", "sort"]);
	}

	public static function wpb_generate_custom_shortcodes() {

		self::generateUserData(true);
	}

	public static function getShortCodes() {

		return self::$sc;
	}

	public static function getTemplate($tag) {

		$code = new ComposerShortCodeFishBones(self::$sc[$tag]);

		return $code->template();
	}

	public static function getSortedUserShortCodes() {

		self::generateUserData();
		return self::$user_sorted_sc;
	}

	public static function getUserShortCodes() {

		self::generateUserData();
		return self::$user_sc;
	}

	public static function getShortCode($tag) {

		return self::$sc[$tag];
	}

	public static function getShortCodeTag($tag) {

		$tag = new ComposerShortCode(self::$sc[$tag]);
		return $tag->template();
	}

	public static function getCategories() {

		return self::$categories;
	}

	public static function getUserCategories() {

		self::generateUserData();
		return self::$user_categories;
	}

	public static function dropParam($name, $attribute_name) {

		if (!self::$is_init) {

			ephenyx_mapper()->addActivity(
				'mapper', 'drop_param', [
					'name'           => $name,
					'attribute_name' => $attribute_name,
				]
			);
			return;
		}

		foreach (self::$sc[$name]['params'] as $index => $param) {

			if ($param['param_name'] == $attribute_name) {
				array_splice(self::$sc[$name]['params'], $index, 1);
				return;
			}

		}

	}

	public static function vc_dropParam($name, $attribute_name) {

		if (!self::$is_init) {
			vc_mapper()->addActivity(
				'mapper', 'drop_param', [
					'name'           => $name,
					'attribute_name' => $attribute_name,
				]
			);
			return;
		}

		foreach (self::$sc[$name]['params'] as $index => $param) {

			if ($param['param_name'] == $attribute_name) {
				array_splice(self::$sc[$name]['params'], $index, 1);
				return;
			}

		}

	}

	public static function getParam($tag, $param_name) {

		if (!isset(self::$sc[$tag])) {
			return trigger_error(sprintf($this->l("Wrong name for shortcode:%s. Name required"), $tag));
		}

		foreach (self::$sc[$tag]['params'] as $index => $param) {

			if ($param['param_name'] == $param_name) {
				return self::$sc[$tag]['params'][$index];
			}

		}

		return false;
	}

	public static function addParam($name, $attribute = []) {

		if (!self::$is_init) {
			ephenyx_mapper()->addActivity(
				'mapper', 'add_param', [
					'name'      => $name,
					'attribute' => $attribute,
				]
			);
			return;
		}

		if (!isset(self::$sc[$name])) {
			return trigger_error(sprintf($this->l("Wrong name for shortcode:%s. Name required"), $name));
		} else

		if (!isset($attribute['param_name'])) {
			trigger_error(sprintf($this->l("Wrong attribute for '%s' shortcode. Attribute 'param_name' required"), $name));
		} else {

			$replaced = false;

			foreach (self::$sc[$name]['params'] as $index => $param) {

				if ($param['param_name'] == $attribute['param_name']) {
					$replaced = true;
					self::$sc[$name]['params'][$index] = $attribute;
				}

			}

			if ($replaced === false) {
				self::$sc[$name]['params'][] = $attribute;
			}

			ephenyx_composer()->addShortCode(self::$sc[$name]);
		}

	}

	public static function vc_addParam($name, $attribute = []) {

		$vc_main = vc_manager();

		if (!self::$is_init) {
			vc_mapper()->addActivity(
				'mapper', 'add_param', [
					'name'      => $name,
					'attribute' => $attribute,
				]
			);
			return;
		}

		if (!isset(self::$sc[$name])) {
			return trigger_error(sprintf($vc_main->l("Wrong name for shortcode:%s. Name required"), $name));
		} else

		if (!isset($attribute['param_name'])) {
			trigger_error(sprintf($vc_main->l("Wrong attribute for '%s' shortcode. Attribute 'param_name' required"), $name));
		} else {

			$replaced = false;

			foreach (self::$sc[$name]['params'] as $index => $param) {

				if ($param['param_name'] == $attribute['param_name']) {
					$replaced = true;
					self::$sc[$name]['params'][$index] = $attribute;
				}

			}

			if ($replaced === false) {
				self::$sc[$name]['params'][] = $attribute;
			}

			visual_composer()->addShortCode(self::$sc[$name]);
		}

	}

	public static function mutateParam($name, $attribute = []) {

		if (!self::$is_init) {
			ephenyx_mapper()->addActivity(
				'mapper', 'mutate_param', [
					'name'      => $name,
					'attribute' => $attribute,
				]
			);
			return false;
		}

		if (!isset(self::$sc[$name])) {
			return trigger_error(sprintf($this->l("Wrong name for shortcode:%s. Name required"), $name));
		} else

		if (!isset($attribute['param_name'])) {
			trigger_error(sprintf($this->l("Wrong attribute for '%s' shortcode. Attribute 'param_name' required"), $name));
		} else {

			$replaced = false;

			foreach (self::$sc[$name]['params'] as $index => $param) {

				if ($param['param_name'] == $attribute['param_name']) {
					$replaced = true;
					self::$sc[$name]['params'][$index] = array_merge($param, $attribute);
				}

			}

			if ($replaced === false) {
				self::$sc[$name]['params'][] = $attribute;
			}

			ephenyx_composer()->addShortCode(self::$sc[$name]);
		}

		return true;
	}

	public static function vc_mutateParam($name, $attribute = []) {

		$vc_main = vc_manager();

		if (!self::$is_init) {
			vc_mapper()->addActivity(
				'mapper', 'mutate_param', [
					'name'      => $name,
					'attribute' => $attribute,
				]
			);
			return false;
		}

		if (!isset(self::$sc[$name])) {
			return trigger_error(sprintf($vc_main->l("Wrong name for shortcode:%s. Name required"), $name));
		} else

		if (!isset($attribute['param_name'])) {
			trigger_error(sprintf($vc_main->l("Wrong attribute for '%s' shortcode. Attribute 'param_name' required"), $name));
		} else {

			$replaced = false;

			foreach (self::$sc[$name]['params'] as $index => $param) {

				if ($param['param_name'] == $attribute['param_name']) {
					$replaced = true;
					self::$sc[$name]['params'][$index] = array_merge($param, $attribute);
				}

			}

			if ($replaced === false) {
				self::$sc[$name]['params'][] = $attribute;
			}

			visual_composer()->addShortCode(self::$sc[$name]);
		}

		return true;
	}

	public static function dropShortcode($name) {

		if (!self::$is_init) {

			ephenyx_mapper()->addActivity(
				'mapper', 'drop_shortcode', [
					'name' => $name,
				]
			);
			return false;
		}

		unset(self::$sc[$name]);
		ephenyx_composer()->removeShortCode($name);

	}

	public static function vc_dropShortcode($name) {

		if (!self::$is_init) {
			vc_mapper()->addActivity(
				'mapper', 'drop_shortcode', [
					'name' => $name,
				]
			);
			return false;
		}

		unset(self::$sc[$name]);
		visual_composer()->removeShortCode($name);

	}

	public static function modify($name, $setting_name, $value = '') {

		if (!self::$is_init) {

			ephenyx_mapper()->addActivity(
				'mapper', 'modify', [
					'name'         => $name,
					'setting_name' => $setting_name,
					'value'        => $value,
				]
			);
			return false;
		}

		if (!isset(self::$sc[$name])) {
			return trigger_error(sprintf($this->l("Wrong name for shortcode:%s. Name required"), $name));
		} else

		if ($setting_name === 'base') {
			return trigger_error(sprintf($this->l("Wrong setting_name for shortcode:%s. Base can't be modified."), $name));
		}

		if (is_array($setting_name)) {

			foreach ($setting_name as $key => $value) {
				self::modify($name, $key, $value);
			}

		} else {
			self::$sc[$name][$setting_name] = $value;
			$this->updateShortcodeSetting($name, $setting_name, $value);
		}

		return self::$sc;
	}

	public static function vc_modify($name, $setting_name, $value = '') {

		$vc_main = vc_manager();

		if (!self::$is_init) {
			vc_mapper()->addActivity(
				'mapper', 'modify', [
					'name'         => $name,
					'setting_name' => $setting_name,
					'value'        => $value,
				]
			);
			return false;
		}

		if (!isset(self::$sc[$name])) {
			return trigger_error(sprintf($vc_main->l("Wrong name for shortcode:%s. Name required"), $name));
		} else

		if ($setting_name === 'base') {
			return trigger_error(sprintf($vc_main->l("Wrong setting_name for shortcode:%s. Base can't be modified."), $name));
		}

		if (is_array($setting_name)) {

			foreach ($setting_name as $key => $value) {
				self::modify($name, $key, $value);
			}

		} else {
			self::$sc[$name][$setting_name] = $value;
			visual_composer()->updateShortcodeSetting($name, $setting_name, $value);
		}

		return self::$sc;
	}

	public static function getTagsRegexp() {

		if (empty(self::$tags_regexp)) {
			self::$tags_regexp = implode('|', array_keys(self::$sc));
		}

		return self::$tags_regexp;
	}

	public static function sort($a, $b) {

		$a_weight = isset($a['weight']) ? (int) $a['weight'] : 0;
		$b_weight = isset($b['weight']) ? (int) $b['weight'] : 0;

		if ($a_weight == $b_weight) {
			$cmpa = array_search($a, self::$user_sorted_sc);
			$cmpb = array_search($b, self::$user_sorted_sc);
			return ($cmpa > $cmpb) ? 1 : -1;
		}

		return ($a_weight < $b_weight) ? 1 : -1;
	}

	public function getActiveForm() {
        
        if($this->context->cache_enable && is_object($this->context->cache_api)) {
            $value = $this->context->cache_api->getData('getActiveMapForm', 864000);
            $temp = empty($value) ? null : PhenyxTool::getInstance()->jsonDecode($value, true);
            if(!empty($temp)) {
                return $temp;
            }
        }

		$alias = [];
		$results = Db::getInstance()->executeS(
			(new DbQuery())
				->select('pl.`id_pfg`, pl.`title`')
				->from('pfg_lang', 'pl')
				->leftJoin('pfg', 'p', 'p.`id_pfg` = pl.`id_pfg` AND pl.id_lang = ' . $this->context->language->id)
				->where('p.`active` = 1')
		);

		foreach ($results as $result) {
			$alias[$result['title']] = $result['id_pfg'];
		}
        if($this->context->cache_enable && is_object($this->context->cache_api)) {
            $temp = $alias === null ? null : PhenyxTool::getInstance()->jsonEncode($alias);
            $this->context->cache_api->putData('getActiveMapForm', $temp);
        }	

		return $alias;
	}

	public function getCms() {

		$cms = [];

		$cmsPages = CMS::listCms();

		foreach ($cmsPages as $result) {
			$cms[$result['meta_title']] = $result['id_cms'];
		}

		return $cms;
	}

	public function getRevsliserAlias() {

        if($this->context->cache_enable && is_object($this->context->cache_api)) {
            $value = $this->context->cache_api->getData('getRevsliserMapAlias', 864000);
            $temp = empty($value) ? null : PhenyxTool::getInstance()->jsonDecode($value, true);
            if(!empty($temp)) {
                return $temp;
            }
        }
		$alias = [];
		$results = Db::getInstance()->executeS(
			(new DbQuery())
				->select('`alias`')
				->from('revslider_slider')
		);

		foreach ($results as $result) {
			$alias["[rev_slider alias=" . $result['alias'] . "]"] = $result['alias'];
		}
        
        if($this->context->cache_enable && is_object($this->context->cache_api)) {
            $temp = $alias === null ? null : PhenyxTool::getInstance()->jsonEncode($alias);
            $this->context->cache_api->putData('getRevsliserMapAlias', $temp);
        }

		return $alias;
	}
    
    public function getMediaPDF() {
        
        $pdf = [];
        $pdf[$this->l('Choose an existing Media')] = 0;
        $medias = ComposerMedia::getPdfMedia($this->context->language->id);
        if(is_array($medias) && count($medias)) {
            foreach($medias as $media) {
                $pdf[$media['legend']] = $media['id_vc_media'];
            }
        }
        
        return $pdf;
    }
    
	public function getMapperFields() {

		$vc_main = ephenyx_manager();
		$map = [
			'vc_row'                   => [
				'name'                    => $this->l('Row'),
				'base'                    => 'vc_row',
				'is_container'            => true,
				'icon'                    => 'icon-wpb-row',
				'show_settings_on_create' => false,
				'category'                => $this->l('Content'),
				'description'             => $this->l('Place content elements inside the row'),
				'params'                  => [
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Row stretch'),
						'param_name'  => 'full_width',
						'value'       => [
							$this->l('Default')                               => '',
							$this->l('Stretch row')                           => 'stretch_row',
							$this->l('Stretch row and content')               => 'stretch_row_content',
							$this->l('Stretch row and content (no paddings)') => 'stretch_row_content_no_spaces',
						],
						'description' => $this->l('Select stretching options for row and content (Note: stretched may not work properly if parent container has "overflow: hidden" CSS property).'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Columns gap'),
						'param_name'  => 'gap',
						'value'       => [
							'0px'  => '0',
							'1px'  => '1',
							'2px'  => '2',
							'3px'  => '3',
							'4px'  => '4',
							'5px'  => '5',
							'10px' => '10',
							'15px' => '15',
							'20px' => '20',
							'25px' => '25',
							'30px' => '30',
							'35px' => '35',
						],
						'std'         => '0',
						'description' => $this->l('Select gap between columns in row.'),
					],
					[
						'type'        => 'checkbox',
						'heading'     => $this->l('Full height row?'),
						'param_name'  => 'full_height',
						'description' => $this->l('If checked row will be set to full height.'),
						'value'       => [$this->l('Yes') => 'yes'],
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Columns position'),
						'param_name'  => 'columns_placement',
						'value'       => [
							$this->l('Middle')  => 'middle',
							$this->l('Top')     => 'top',
							$this->l('Bottom')  => 'bottom',
							$this->l('Stretch') => 'stretch',
						],
						'description' => $this->l('Select columns position within row.'),
						'dependency'  => [
							'element'   => 'full_height',
							'not_empty' => true,
						],
					],
					[
						'type'        => 'checkbox',
						'heading'     => $this->l('Equal height'),
						'param_name'  => 'equal_height',
						'description' => $this->l('If checked columns will be set to equal height.'),
						'value'       => [$this->l('Yes') => 'yes'],
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Content position'),
						'param_name'  => 'content_placement',
						'value'       => [
							$this->l('Default') => '',
							$this->l('Top')     => 'top',
							$this->l('Middle')  => 'middle',
							$this->l('Bottom')  => 'bottom',
						],
						'description' => $this->l('Select content position within columns.'),
					],
					[
						'type'        => 'checkbox',
						'heading'     => $this->l('Use video background?'),
						'param_name'  => 'video_bg',
						'description' => $this->l('If checked, video will be used as row background.'),
						'value'       => [$this->l('Yes') => 'yes'],
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('YouTube link'),
						'param_name'  => 'video_bg_url',
						'value'       => '',
						'description' => $this->l('Add YouTube link.'),
						'dependency'  => [
							'element'   => 'video_bg',
							'not_empty' => true,
						],
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Parallax'),
						'param_name'  => 'video_bg_parallax',
						'value'       => [
							$this->l('None')      => '',
							$this->l('Simple')    => 'content-moving',
							$this->l('With fade') => 'content-moving-fade',
						],
						'description' => $this->l('Add parallax type background for row.'),
						'dependency'  => [
							'element'   => 'video_bg',
							'not_empty' => true,
						],
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Parallax'),
						'param_name'  => 'parallax',
						'value'       => [
							$this->l('None')      => '',
							$this->l('Simple')    => 'content-moving',
							$this->l('With fade') => 'content-moving-fade',
						],
						'description' => $this->l('Add parallax type background for row (Note: If no image is specified, parallax will use background image from Design Options).'),
						'dependency'  => [
							'element'  => 'video_bg',
							'is_empty' => true,
						],
					],
					[
						'type'        => 'attach_image',
						'heading'     => $this->l('Image'),
						'param_name'  => 'parallax_image',
						'value'       => '',
						'description' => $this->l('Select image from media library.'),
						'dependency'  => [
							'element'   => 'parallax',
							'not_empty' => true,
						],
					],
					[
						'type'        => 'el_id',
						'heading'     => $this->l('Row ID'),
						'param_name'  => 'el_id',
						'description' => sprintf($this->l('Enter row ID (Note: make sure it is unique and valid according to <a href="%s" target="_blank">w3c specification</a>).'), 'http://www.w3schools.com/tags/att_global_id.asp'),
					],
					[
						'type'             => 'colorpicker',
						'heading'          => $this->l('Font Color'),
						'param_name'       => 'font_color',
						'description'      => $this->l('Select font color'),
						'edit_field_class' => 'vc_col-md-6 vc_column',
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
					[
						'type'       => 'css_editor',
						'heading'    => $this->l('Css'),
						'param_name' => 'css',
						'group'      => $this->l('Design options'),
					],
				],
				'js_view'                 => 'VcRowView',
			],
			'vc_row_inner'             => [
				'name'                    => $this->l('Row'),
				'base'                    => 'vc_row_inner',
				'content_element'         => false,
				'is_container'            => true,
				'icon'                    => 'icon-wpb-row',
				'weight'                  => 1000,
				'show_settings_on_create' => false,
				'description'             => $this->l('Place content elements inside the row'),
				'params'                  => [
					[
						'type'             => 'colorpicker',
						'heading'          => $this->l('Font Color'),
						'param_name'       => 'font_color',
						'description'      => $this->l('Select font color'),
						'edit_field_class' => 'vc_col-md-6 vc_column',
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
					[
						'type'       => 'css_editor',
						'heading'    => $this->l('Css'),
						'param_name' => 'css',
						'group'      => $this->l('Design options'),
					],
				],
				'js_view'                 => 'VcRowView',
			],
			'vc_column'                => [
				'name'            => $this->l('Column'),
				'base'            => 'vc_column',
				'is_container'    => true,
				'content_element' => false,
				'params'          => [
					[
						'type'             => 'colorpicker',
						'heading'          => $this->l('Font Color'),
						'param_name'       => 'font_color',
						'description'      => $this->l('Select font color'),
						'edit_field_class' => 'vc_col-md-6 vc_column',
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
					[
						'type'       => 'css_editor',
						'heading'    => $this->l('Css'),
						'param_name' => 'css',
						'group'      => $this->l('Design options'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Width'),
						'param_name'  => 'width',
						'value'       => $this->column_width_list,
						'group'       => $this->l('Width & Responsiveness'),
						'description' => $this->l('Select column width.'),
						'std'         => '1/1',
					],
					[
						'type'        => 'column_offset',
						'heading'     => $this->l('Responsiveness'),
						'param_name'  => 'offset',
						'group'       => $this->l('Width & Responsiveness'),
						'description' => $this->l('Adjust column for different screen sizes. Control width, offset and visibility settings.'),
					],
				],
				'js_view'         => 'VcColumnView',
			],
			"vc_column_inner"          => [
				"name"                      => $this->l("Column"),
				"base"                      => "vc_column_inner",
				"class"                     => "",
				"icon"                      => "",
				"wrapper_class"             => "",
				"controls"                  => "full",
				"allowed_container_element" => false,
				"content_element"           => false,
				"is_container"              => true,
				"params"                    => [
					[
						'type'             => 'colorpicker',
						'heading'          => $this->l('Font Color'),
						'param_name'       => 'font_color',
						'description'      => $this->l('Select font color'),
						'edit_field_class' => 'vc_col-md-6 vc_column',
					],
					[
						"type"        => "textfield",
						"heading"     => $this->l("Extra class name"),
						"param_name"  => "el_class",
						"value"       => "",
						"description" => $this->l("If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file."),
					],
					[
						"type"       => "css_editor",
						"heading"    => $this->l('Css'),
						"param_name" => "css",
						"group"      => $this->l('Design options'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Width'),
						'param_name'  => 'width',
						'value'       => $this->column_width_list,
						'group'       => $this->l('Width & Responsiveness'),
						'description' => $this->l('Select column width.'),
						'std'         => '1/1',
					],
				],
				"js_view"                   => 'VcColumnView',
			],
			'vc_column_text'           => [
				'name'          => $this->l('Text Block'),
				'base'          => 'vc_column_text',
				'icon'          => 'icon-wpb-layer-shape-text',
				'wrapper_class' => 'clearfix',
				'category'      => $this->l('Content'),
				'description'   => $this->l('A block of text with WYSIWYG editor'),
				'params'        => [
					[
						'type'       => 'textarea_html',
						'holder'     => 'div',
						'heading'    => $this->l('Text'),
						'param_name' => 'content',
						'value'      => $this->l('<p>I am text block. Click edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.</p>'),
					],
					[
						'type'        => 'animation',
						'heading'     => $this->l('CSS Animation'),
						'param_name'  => 'css_animation',
						'admin_label' => true,
						'value'       => $this->animationStyles(),
						'description' => $this->l('Select type of animation if you want this element to be animated when it enters into the browsers viewport. Note: Works only in modern browsers.'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
					[
						'type'       => 'css_editor',
						'heading'    => $this->l('Css'),
						'param_name' => 'css',
						'group'      => $this->l('Design options'),
					],
				],
			],
			'vc_separator'             => [
				'name'                    => $this->l('Separator'),
				'base'                    => 'vc_separator',
				'icon'                    => 'icon-wpb-ui-separator',
				'show_settings_on_create' => true,
				'category'                => $this->l('Content'),
				'description'             => $this->l('Horizontal separator line'),
				'params'                  => [
					[
						'type'               => 'dropdown',
						'heading'            => $this->l('Color'),
						'param_name'         => 'color',
						'value'              => array_merge(getComposerShared('colors'), [$this->l('Custom color') => 'custom']),
						'std'                => 'grey',
						'description'        => $this->l('Separator color.'),
						'param_holder_class' => 'vc_colored-dropdown',
					],
					[
						'type'        => 'colorpicker',
						'heading'     => $this->l('Custom Border Color'),
						'param_name'  => 'accent_color',
						'description' => $this->l('Select border color for your element.'),
						'dependency'  => [
							'element' => 'color',
							'value'   => ['custom'],
						],
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Style'),
						'param_name'  => 'style',
						'value'       => getComposerShared('separator styles'),
						'description' => $this->l('Separator style.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Element width'),
						'param_name'  => 'el_width',
						'value'       => getComposerShared('separator widths'),
						'description' => $this->l('Separator element width in percents.'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
				],
			],
			'vc_text_separator'        => [
				'name'        => $this->l('Separator with Text'),
				'base'        => 'vc_text_separator',
				'icon'        => 'icon-wpb-ui-separator-label',
				'category'    => $this->l('Content'),
				'description' => $this->l('Horizontal separator line with heading'),
				'params'      => [
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Title'),
						'param_name'  => 'title',
						'holder'      => 'div',
						'value'       => $this->l('Title'),
						'description' => $this->l('Separator title.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Title position'),
						'param_name'  => 'title_align',
						'value'       => [
							$this->l('Align center') => 'separator_align_center',
							$this->l('Align left')   => 'separator_align_left',
							$this->l('Align right')  => "separator_align_right",
						],
						'description' => $this->l('Select title location.'),
					],
					[
						'type'               => 'dropdown',
						'heading'            => $this->l('Color'),
						'param_name'         => 'color',
						'value'              => array_merge(getComposerShared('colors'), [$this->l('Custom color') => 'custom']),
						'std'                => 'grey',
						'description'        => $this->l('Separator color.'),
						'param_holder_class' => 'vc_colored-dropdown',
					],
					[
						'type'        => 'colorpicker',
						'heading'     => $this->l('Custom Color'),
						'param_name'  => 'accent_color',
						'description' => $this->l('Custom separator color for your element.'),
						'dependency'  => [
							'element' => 'color',
							'value'   => ['custom'],
						],
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Style'),
						'param_name'  => 'style',
						'value'       => getComposerShared('separator styles'),
						'description' => $this->l('Separator style.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Element width'),
						'param_name'  => 'el_width',
						'value'       => getComposerShared('separator widths'),
						'description' => $this->l('Separator element width in percents.'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
				],
				'js_view'     => 'VcTextSeparatorView'],
			'vc_message'               => [
				'name'          => $this->l('Message Box'),
				'base'          => 'vc_message',
				'icon'          => 'icon-wpb-information-white',
				'wrapper_class' => 'alert',
				'category'      => $this->l('Content'),
				'description'   => $this->l('Notification box'),
				'params'        => [
					[
						'type'               => 'dropdown',
						'heading'            => $this->l('Message box type'),
						'param_name'         => 'color',
						'value'              => [
							$this->l('Informational') => 'alert-info',
							$this->l('Warning')       => 'alert-warning',
							$this->l('Success')       => 'alert-success',
							$this->l('Error')         => "alert-danger",
						],
						'description'        => $this->l('Select message type.'),
						'param_holder_class' => 'vc_message-type',
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Style'),
						'param_name'  => 'style',
						'value'       => getComposerShared('alert styles'),
						'description' => $this->l('Alert style.'),
					],
					[
						'type'       => 'textarea_html',
						'holder'     => 'div',
						'class'      => 'messagebox_text',
						'heading'    => $this->l('Message text'),
						'param_name' => 'content',
						'value'      => $this->l('<p>I am message box. Click edit button to change this text.</p>'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('CSS Animation'),
						'param_name'  => 'css_animation',
						'admin_label' => true,
						'value'       => [
							$this->l('No')                 => '',
							$this->l('Top to bottom')      => 'top-to-bottom',
							$this->l('Bottom to top')      => 'bottom-to-top',
							$this->l('Left to right')      => 'left-to-right',
							$this->l('Right to left')      => 'right-to-left',
							$this->l('Appear from center') => "appear",
						],
						'description' => $this->l('Select type of animation if you want this element to be animated when it enters into the browsers viewport. Note: Works only in modern browsers.'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
				],
				'js_view'       => 'VcMessageView',
			],
			'vc_facebook'              => [
				'name'        => $this->l('Facebook Like'),
				'base'        => 'vc_facebook',
				'icon'        => 'icon-wpb-balloon-facebook-left',
				'category'    => $this->l('Social'),
				'description' => $this->l('Facebook like button'),
				'params'      => [
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Button type'),
						'param_name'  => 'type',
						'admin_label' => true,
						'value'       => [
							$this->l('Standard')     => 'standard',
							$this->l('Button count') => 'button_count',
							$this->l('Box count')    => 'box_count',
						],
						'description' => $this->l('Select button type.'),
					],
				],
			],
			'vc_tweetmeme'             => [
				'name'        => $this->l('Tweetmeme Button'),
				'base'        => 'vc_tweetmeme',
				'icon'        => 'icon-wpb-tweetme',
				'category'    => $this->l('Social'),
				'description' => $this->l('Share on twitter button'),
				'params'      => [
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Button type'),
						'param_name'  => 'type',
						'admin_label' => true,
						'value'       => [
							$this->l('Horizontal') => 'horizontal',
							$this->l('Vertical')   => 'vertical',
							$this->l('None')       => 'none',
						],
						'description' => $this->l('Select button type.'),
					],
				],
			],
			'vc_googleplus'            => [
				'name'        => $this->l('Google+ Button'),
				'base'        => 'vc_googleplus',
				'icon'        => 'icon-wpb-application-plus',
				'category'    => $this->l('Social'),
				'description' => $this->l('Recommend on Google'),
				'params'      => [
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Button size'),
						'param_name'  => 'type',
						'admin_label' => true,
						'value'       => [
							$this->l('Standard') => '',
							$this->l('Small')    => 'small',
							$this->l('Medium')   => 'medium',
							$this->l('Tall')     => 'tall',
						],
						'description' => $this->l('Select button size.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Annotation'),
						'param_name'  => 'annotation',
						'admin_label' => true,
						'value'       => [
							$this->l('Inline') => 'inline',
							$this->l('Bubble') => '',
							$this->l('None')   => 'none',
						],
						'description' => $this->l('Select type of annotation'),
					],
				],
			],
			'vc_pinterest'             => [
				'name'        => $this->l('Pinterest'),
				'base'        => 'vc_pinterest',
				'icon'        => 'icon-wpb-pinterest',
				'category'    => $this->l('Social'),
				'description' => $this->l('Pinterest button'),
				"params"      => [
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Button layout'),
						'param_name'  => 'type',
						'admin_label' => true,
						'value'       => [
							$this->l('Horizontal') => '',
							$this->l('Vertical')   => 'vertical',
							$this->l('No count')   => 'none'],
						'description' => $this->l('Select button layout.'),
					],
				],
			],
			'vc_toggle'                => [
				'name'        => $this->l('FAQ'),
				'base'        => 'vc_toggle',
				'icon'        => 'icon-wpb-toggle-small-expand',
				'category'    => $this->l('Content'),
				'description' => $this->l('Toggle element for Q&A block'),
				'params'      => [
					[
						'type'        => 'textfield',
						'holder'      => 'h4',
						'class'       => 'toggle_title',
						'heading'     => $this->l('Toggle title'),
						'param_name'  => 'title',
						'value'       => $this->l('Toggle title'),
						'description' => $this->l('Toggle block title.'),
					],
					[
						'type'        => 'textarea_html',
						'holder'      => 'div',
						'class'       => 'toggle_content',
						'heading'     => $this->l('Toggle content'),
						'param_name'  => 'content',
						'value'       => $this->l('<p>Toggle content goes here, click edit button to change this text.</p>'),
						'description' => $this->l('Toggle block content.'),
					],
					[
						'type'        => 'animation',
						'heading'     => $this->l('CSS Animation'),
						'param_name'  => 'css_animation',
						'admin_label' => true,
						'value'       => $this->animationStyles(),
						'description' => $this->l('Select type of animation if you want this element to be animated when it enters into the browsers viewport. Note: Works only in modern browsers.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Default state'),
						'param_name'  => 'open',
						'value'       => [
							$this->l('Closed') => 'false',
							$this->l('Open')   => 'true',
						],
						'description' => $this->l('Select "Open" if you want toggle to be open by default.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('CSS Animation'),
						'param_name'  => 'css_animation',
						'admin_label' => true,
						'value'       => [
							$this->l('No')                 => '',
							$this->l('Top to bottom')      => 'top-to-bottom',
							$this->l('Bottom to top')      => 'bottom-to-top',
							$this->l('Left to right')      => 'left-to-right',
							$this->l('Right to left')      => 'right-to-left',
							$this->l('Appear from center') => "appear",
						],
						'description' => $this->l('Select type of animation if you want this element to be animated when it enters into the browsers viewport. Note: Works only in modern browsers.'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
				],
				'js_view'     => 'VcToggleView',
			],
            'vc_parallax_image'           => [
				'name'          => $this->l('Image and parallax text Block'),
				'base'          => 'vc_parallax_image',
				'icon'          => 'icon-wpb-single-image',
				'wrapper_class' => 'clearfix',
				'category'      => $this->l('Content'),
				'description'   => $this->l('A parallax effects block with image and text with WYSIWYG editor'),
				'params'        => [
                    [
						'type'        => 'attach_image',
						'heading'     => $this->l('Image'),
						'param_name'  => 'image',
						'value'       => '',
						'description' => $this->l('Select image from media library.'),
					],
					[
						'type'       => 'textarea_html',
						'holder'     => 'div',
						'heading'    => $this->l('Text'),
						'param_name' => 'content',
						'value'      => $this->l('<p>I am text block. Click edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.</p>'),
					],
					[
						'type'        => 'animation',
						'heading'     => $this->l('CSS Animation'),
						'param_name'  => 'css_animation',
						'admin_label' => true,
						'value'       => $this->animationStyles(),
						'description' => $this->l('Select type of animation if you want this element to be animated when it enters into the browsers viewport. Note: Works only in modern browsers.'),
					],
                    [
						'type'               => 'colorpicker',
						'heading'            => $this->l('Overlay Color'),
						'param_name'         => 'color',
                        'edit_field_class' => 'vc_col-md-6 vc_column',
					],
                    [
						'type'               => 'colorpicker',
						'heading'            => $this->l('Text Color'),
						'param_name'         => 'text_color',
						'description'        => $this->l('Text color.'),
                        'edit_field_class' => 'vc_col-md-6 vc_column',
					],
					
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
					
				],
			],
			'vc_parallax_video'           => [
				'name'          => $this->l('Video and parallax text Block'),
				'base'          => 'vc_parallax_video',
				'icon'          => 'icon-wpb-film-youtube',
				'wrapper_class' => 'clearfix',
				'category'      => $this->l('Content'),
				'description'   => $this->l('A parallax effects block with video and text with WYSIWYG editor'),
				'params'        => [
                    [
						'type'        => 'textfield',
						'heading'     => $this->l('Video'),
						'param_name'  => 'video',
						'value'       => '',
						'description' => $this->l('Video link, youtube, vimeo ex https://vimeo.com/xxxx...'),
					],
					[
						'type'       => 'textarea_html',
						'holder'     => 'div',
						'heading'    => $this->l('Text'),
						'param_name' => 'content',
						'value'      => $this->l('<p>I am text block. Click edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.</p>'),
					],     
					[
						'type'        => 'animation',
						'heading'     => $this->l('CSS Animation'),
						'param_name'  => 'css_animation',
						'admin_label' => true,
						'value'       => $this->animationStyles(),
						'description' => $this->l('Select type of animation if you want this element to be animated when it enters into the browsers viewport. Note: Works only in modern browsers.'),
					],
					[
						'type'               => 'colorpicker',
						'heading'            => $this->l('Text Color'),
						'param_name'         => 'text_color',
						'description'        => $this->l('Text color.'),
                        'edit_field_class' => 'vc_col-md-6 vc_column',
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
					
				],
			],
			'vc_single_image'          => [
				'name'        => $this->l('Single Image'),
				'base'        => 'vc_single_image',
				'icon'        => 'icon-wpb-single-image',
				'category'    => $this->l('Content'),
				'description' => $this->l('Simple image with CSS animation'),
				'params'      => [
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Widget title'),
						'param_name'  => 'title',
						'description' => $this->l('Enter text which will be used as widget title. Leave blank if no title is needed.'),
					],
					[
						'type'        => 'attach_image',
						'heading'     => $this->l('Image'),
						'param_name'  => 'image',
						'value'       => '',
						'description' => $this->l('Select image from media library.'),
					],
					[
						'type'        => 'animation',
						'heading'     => $this->l('CSS Animation'),
						'param_name'  => 'css_animation',
						'admin_label' => true,
						'value'       => $this->animationStyles(),
						'description' => $this->l('Select type of animation if you want this element to be animated when it enters into the browsers viewport. Note: Works only in modern browsers.'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Image size'),
						'param_name'  => 'img_size',
						'description' => $this->l('Enter image size. Example: ' . vc_get_image_sizes_string() . '. Leave empty to use main image.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Image alignment'),
						'param_name'  => 'alignment',
						'value'       => [
							$this->l('Align left')   => '',
							$this->l('Align right')  => 'right',
							$this->l('Align center') => 'center',
						],
						'description' => $this->l('Select image alignment.'),
					],
					[
						'type'       => 'dropdown',
						'heading'    => $this->l('Image style'),
						'param_name' => 'style',
						'value'      => getComposerShared('single image styles'),
					],
					[
						'type'               => 'dropdown',
						'heading'            => $this->l('Border color'),
						'param_name'         => 'border_color',
						'value'              => getComposerShared('colors'),
						'std'                => 'grey',
						'dependency'         => [
							'element' => 'style',
							'value'   => ['vc_box_border', 'vc_box_border_circle', 'vc_box_outline', 'vc_box_outline_circle'],
						],
						'description'        => $this->l('Border color.'),
						'param_holder_class' => 'vc_colored-dropdown',
					],
					[
						'type'        => 'checkbox',
						'heading'     => $this->l('Link to large image?'),
						'param_name'  => 'img_link_large',
						'description' => $this->l('If selected, image will be linked to the larger image.'),
						'value'       => [$this->l('Yes, please') => 'yes'],
					],
					[
						'type'        => 'href',
						'heading'     => $this->l('Image link'),
						'param_name'  => 'link',
						'description' => $this->l('Enter URL if you want this image to have a link.'),
						'dependency'  => [
							'element'  => 'img_link_large',
							'is_empty' => true,
							'callback' => 'wpb_single_image_img_link_dependency_callback',
						],
					],
					[
						'type'       => 'dropdown',
						'heading'    => $this->l('Link Target'),
						'param_name' => 'img_link_target',
						'value'      => [
							$this->l('Same window') => '_self',
							$this->l('New window')  => "_blank",
						],
						'dependency' => [
							'element'   => 'img_link',
							'not_empty' => true,
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
					[
						'type'       => 'css_editor',
						'heading'    => $this->l('Css'),
						'param_name' => 'css',
						'group'      => $this->l('Design options'),
					],
				],
			],
			'vc_gallery'               => [
				'name'        => $this->l('Image Gallery'),
				'base'        => 'vc_gallery',
				'icon'        => 'icon-wpb-images-stack',
				'category'    => $this->l('Content'),
				'description' => $this->l('Responsive image gallery'),
				'params'      => [
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Widget title'),
						'param_name'  => 'title',
						'description' => $this->l('Enter text which will be used as widget title. Leave blank if no title is needed.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Gallery type'),
						'param_name'  => 'type',
						'value'       => [
							$this->l('Flex slider fade')  => 'flexslider_fade',
							$this->l('Flex slider slide') => 'flexslider_slide',
							$this->l('Nivo slider')       => 'nivo',
							$this->l('Image grid')        => 'image_grid',
						],
						'description' => $this->l('Select gallery type.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Auto rotate slides'),
						'param_name'  => 'interval',
						'value'       => [3, 5, 10, 15, $this->l('Disable') => 0],
						'description' => $this->l('Auto rotate slides each X seconds.'),
						'dependency'  => [
							'element' => 'type',
							'value'   => ['flexslider_fade', 'flexslider_slide', 'nivo'],
						],
					],
					[
						'type'        => 'attach_images',
						'heading'     => $this->l('Images'),
						'param_name'  => 'images',
						'value'       => '',
						'description' => $this->l('Select images from media library.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Image size'),
						'param_name'  => 'img_size',
						'value'       => $vc_main->image_sizes_dropdown,
						'description' => $this->l('Enter image size.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('On click'),
						'param_name'  => 'eventclick',
						'value'       => [
							$this->l('Open prettyPhoto') => 'link_image',
							$this->l('Do nothing')       => 'link_no',
							$this->l('Open custom link') => 'custom_link',
						],
						'description' => $this->l('Define action for onclick event if needed.'),
					],
					[
						'type'        => 'exploded_textarea',
						'heading'     => $this->l('Custom links'),
						'param_name'  => 'custom_links',
						'description' => $this->l('Enter links for each slide here. Divide links with linebreaks or comma (Enter) or (,) . '),
						'dependency'  => [
							'element' => 'onclick',
							'value'   => ['custom_link'],
						],
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Custom link target'),
						'param_name'  => 'custom_links_target',
						'description' => $this->l('Select where to open  custom links.'),
						'dependency'  => [
							'element' => 'onclick',
							'value'   => ['custom_link'],
						],
						'value'       => [
							$this->l('Same window') => '_self',
							$this->l('New window')  => "_blank",
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
				],
			],
			'vc_images_carousel'       => [
				'name'        => $this->l('Image Carousel'),
				'base'        => 'vc_images_carousel',
				'icon'        => 'icon-wpb-images-carousel',
				'category'    => $this->l('Content'),
				'description' => $this->l('Animated carousel with images'),
				'params'      => [
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Widget title'),
						'param_name'  => 'title',
						'description' => $this->l('Enter text which will be used as widget title. Leave blank if no title is needed.'),
					],
					[
						'type'        => 'attach_images',
						'heading'     => $this->l('Images'),
						'param_name'  => 'images',
						'value'       => '',
						'description' => $this->l('Select images from media library.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Image size'),
						'param_name'  => 'img_size',
						'value'       => $vc_main->image_sizes_dropdown,
						'description' => $this->l('Enter image size.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('On click'),
						'param_name'  => 'eventclick',
						'value'       => [
							$this->l('Open prettyPhoto') => 'link_image',
							$this->l('Do nothing')       => 'link_no',
							$this->l('Open custom link') => 'custom_link',
						],
						'description' => $this->l('What to do when slide is clicked?'),
					],
					[
						'type'        => 'exploded_textarea',
						'heading'     => $this->l('Custom links'),
						'param_name'  => 'custom_links',
						'description' => $this->l('Enter links for each slide here. Divide links with linebreaks or comma (Enter) or (,) . '),
						'dependency'  => [
							'element' => 'onclick',
							'value'   => ['custom_link'],
						],
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Custom link target'),
						'param_name'  => 'custom_links_target',
						'description' => $this->l('Select where to open  custom links.'),
						'dependency'  => [
							'element' => 'onclick',
							'value'   => ['custom_link'],
						],
						'value'       => [
							$this->l('Same window') => '_self',
							$this->l('New window')  => "_blank",
						],
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Slider mode'),
						'param_name'  => 'mode',
						'value'       => [
							$this->l('Horizontal') => 'horizontal',
							$this->l('Vertical')   => 'vertical',
						],
						'description' => $this->l('Slides will be positioned horizontally (for horizontal swipes) or vertically (for vertical swipes)'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Slider speed'),
						'param_name'  => 'speed',
						'value'       => '5000',
						'description' => $this->l('Duration of animation between slides (in ms)'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Slides per view'),
						'param_name'  => 'slides_per_view',
						'value'       => '1',
						'description' => $this->l('Set numbers of slides you want to display at the same time on slider\'s container for carousel mode. Supports also "auto" value, in this case it will fit slides depending on container\'s width. "auto" mode isn\'t compatible with loop mode.'),
					],
					[
						'type'        => 'checkbox',
						'heading'     => $this->l('Slider autoplay'),
						'param_name'  => 'autoplay',
						'description' => $this->l('Enables autoplay mode.'),
						'value'       => [$this->l('Yes, please') => 'yes'],
					],
					[
						'type'        => 'checkbox',
						'heading'     => $this->l('Hide pagination control'),
						'param_name'  => 'hide_pagination_control',
						'description' => $this->l('If YES pagination control will be removed.'),
						'value'       => [$this->l('Yes, please') => 'yes'],
					],
					[
						'type'        => 'checkbox',
						'heading'     => $this->l('Hide prev/next buttons'),
						'param_name'  => 'hide_prev_next_buttons',
						'description' => $this->l('If "YES" prev/next control will be removed.'),
						'value'       => [$this->l('Yes, please') => 'yes'],
					],
					[
						'type'        => 'checkbox',
						'heading'     => $this->l('Partial view'),
						'param_name'  => 'partial_view',
						'description' => $this->l('If "YES" part of the next slide will be visible on the right side.'),
						'value'       => [$this->l('Yes, please') => 'yes'],
					],
					[
						'type'        => 'checkbox',
						'heading'     => $this->l('Slider loop'),
						'param_name'  => 'wrap',
						'description' => $this->l('Enables loop mode.'),
						'value'       => [$this->l('Yes, please') => 'yes'],
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
				],
			],
			'vc_tabs'                  => [
				"name"                    => $this->l('Tabs'),
				'base'                    => 'vc_tabs',
				'show_settings_on_create' => false,
				'is_container'            => true,
				'icon'                    => 'icon-wpb-ui-tab-content',
				'category'                => $this->l('Content'),
				'description'             => $this->l('Tabbed content'),
				'params'                  => [
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Widget title'),
						'param_name'  => 'title',
						'description' => $this->l('Enter text which will be used as widget title. Leave blank if no title is needed.'),
					],
                    [
						'type'        => 'animation',
						'heading'     => $this->l('CSS Animation'),
						'param_name'  => 'css_animation',
						'admin_label' => true,
						'value'       => $this->animationStyles(),
						'description' => $this->l('Select type of animation if you want this element to be animated when it enters into the browsers viewport. Note: Works only in modern browsers.'),
					],
					
                    [
						'type'        => 'dropdown',
						'heading'     => $this->l('Tabs display mode'),
						'param_name'  => 'tabs_mode',
						'value'       => [$this->l('Classic (with tabs)') => 0, $this->l('Multi page (with navigate button)') => 1],
						'std'         => 0,
						'description' => $this->l('Auto rotate tabs each X seconds.'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
				],
				'custom_markup'           => '<div class="wpb_tabs_holder wpb_holder vc_container_for_children"><ul class="tabs_controls"></ul>%content%</div>',
				'default_content'         => '[vc_tab title="' . $this->l('Tab 1') . '" tab_id="' . $this->tab_id_1 . '"][/vc_tab][vc_tab title="' . $this->l('Tab 2') . '" tab_id="' . $this->tab_id_2 . '"][/vc_tab]',
               
				'js_view'                 => 'VcTabsView',
			],
			'vc_tour'                  => [
				'name'                    => $this->l('Tour'),
				'base'                    => 'vc_tour',
				'show_settings_on_create' => false,
				'is_container'            => true,
				'container_not_allowed'   => true,
				'icon'                    => 'icon-wpb-ui-tab-content-vertical',
				'category'                => $this->l('Content'),
				'wrapper_class'           => 'vc_clearfix',
				'description'             => $this->l('Vertical tabbed content'),
				'params'                  => [
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Widget title'),
						'param_name'  => 'title',
						'description' => $this->l('Enter text which will be used as widget title. Leave blank if no title is needed.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Auto rotate slides'),
						'param_name'  => 'interval',
						'value'       => [$this->l('Disable') => 0, 3, 5, 10, 15],
						'std'         => 0,
						'description' => $this->l('Auto rotate slides each X seconds.'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
				],
				'custom_markup'           => '<div class="wpb_tabs_holder wpb_holder vc_clearfix vc_container_for_children"><ul class="tabs_controls"></ul>%content%</div>',
				'default_content'         => '[vc_tab title="' . $this->l('Tab 1') . '" tab_id="' . $this->tab_id_1 . '"][/vc_tab][vc_tab title="' . $this->l('Tab 2') . '" tab_id="' . $this->tab_id_2 . '"][/vc_tab]',
				'js_view'                 => 'VcTabsView',
			],
			'vc_tab'                   => [
				'name'                      => $this->l('Tab'),
				'base'                      => 'vc_tab',
				'allowed_container_element' => 'vc_row',
				'is_container'              => true,
				'content_element'           => false,
				'params'                    => [
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Title'),
						'param_name'  => 'title',
						'description' => $this->l('Tab title.'),
					],
					[
						'type'       => 'tab_id',
						'heading'    => $this->l('Tab ID'),
						'param_name' => "tab_id",
					],
				],
				'js_view'                   => 'VcTabView',
			],
			'vc_accordion'             => [
				'name'                    => $this->l('Accordion'),
				'base'                    => 'vc_accordion',
				'show_settings_on_create' => false,
				'is_container'            => true,
				'icon'                    => 'icon-wpb-ui-accordion',
				'category'                => $this->l('Content'),
				'description'             => $this->l('Collapsible content panels'),
				'params'                  => [
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Widget title'),
						'param_name'  => 'title',
						'description' => $this->l('Enter text which will be used as widget title. Leave blank if no title is needed.'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Active section'),
						'param_name'  => 'active_tab',
						'description' => $this->l('Enter section number to be active on load or enter false to collapse all sections.'),
					],
					[
						'type'        => 'checkbox',
						'heading'     => $this->l('Allow collapsible all'),
						'param_name'  => 'collapsible',
						'description' => $this->l('Select checkbox to allow all sections to be collapsible.'),
						'value'       => [$this->l('Allow') => 'yes'],
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
				],
				'custom_markup'           => '
<div class="wpb_accordion_holder wpb_holder clearfix vc_container_for_children">
%content%
</div>
<div class="tab_controls ui-accordion-header">
    <a class="add_tab" title="' . $this->l('Add section') . '"><span class="vc_icon ui-icon-triangle-1-e"></span> <span class="tab-label">' . $this->l('Add section') . '</span></a>
</div>
',
				'default_content'         => '
    [vc_accordion_tab title="' . $this->l('Section 1') . '"][/vc_accordion_tab]
    [vc_accordion_tab title="' . $this->l('Section 2') . '"][/vc_accordion_tab]
',
				'js_view'                 => 'VcAccordionView',
			],
			'vc_accordion_tab'         => [
				'name'                      => $this->l('Section'),
				'base'                      => 'vc_accordion_tab',
				'allowed_container_element' => 'vc_row',
				'is_container'              => true,
				'content_element'           => false,
				'params'                    => [
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Title'),
						'param_name'  => 'title',
						'description' => $this->l('Accordion section title.'),
					],
				],
				'js_view'                   => 'VcAccordionTabView',
			],
			'vc_button'                => [
				'name'        => $this->l('Button'),
				'base'        => 'vc_button',
				'icon'        => 'icon-wpb-ui-button',
				'category'    => $this->l('Content'),
				'description' => $this->l('Eye catching button'),
				'params'      => [
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Text on the button'),
						'holder'      => 'button',
						'class'       => 'wpb_button',
						'param_name'  => 'title',
						'value'       => $this->l('Text on the button'),
						'description' => $this->l('Text on the button.'),
					],
					[
						'type'        => 'href',
						'heading'     => $this->l('URL (Link)'),
						'param_name'  => 'href',
						'description' => $this->l('Button link.'),
					],
					[
						'type'       => 'dropdown',
						'heading'    => $this->l('Target'),
						'param_name' => 'target',
						'value'      => [
							$this->l('Same window') => '_self',
							$this->l('New window')  => "_blank",
						],
						'dependency' => ['element' => 'href', 'not_empty' => true, 'callback' => 'vc_button_param_target_callback'],
					],
					[
						'type'               => 'colorpicker',
						'heading'            => $this->l('Color'),
						'param_name'         => 'color',
						'description'        => $this->l('Button color.'),
						'param_holder_class' => 'vc_colored-dropdown',
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Icon'),
						'param_name'  => 'icon',
						'value'       => $this->icons_arr,
						'description' => $this->l('Button icon.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Size'),
						'param_name'  => 'size',
						'value'       => [
							$this->l('Regular size') => 'wpb_regularsize',
							$this->l('Large')        => 'btn-large',
							$this->l('Small')        => 'btn-small',
							$this->l('Mini')         => "btn-mini",
						],
						'description' => $this->l('Button size.'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
				],
				'js_view'     => 'VcButtonView',
			],
			'vc_button2'               => [
				'name'        => $this->l('Button') . " 2",
				'base'        => 'vc_button2',
				'icon'        => 'icon-wpb-ui-button',
				'category'    => [
					$this->l('Content')],
				'description' => $this->l('Eye catching button'),
				'params'      => [
					[
						'type'        => 'vc_link',
						'heading'     => $this->l('URL (Link)'),
						'param_name'  => 'link',
						'description' => $this->l('Button link.'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Text on the button'),
						'holder'      => 'button',
						'class'       => 'vc_btn',
						'param_name'  => 'title',
						'value'       => $this->l('Text on the button'),
						'description' => $this->l('Text on the button.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Style'),
						'param_name'  => 'style',
						'value'       => getComposerShared('button styles'),
						'description' => $this->l('Button style.'),
					],
					[
						'type'               => 'colorpicker',
						'heading'            => $this->l('Color'),
						'param_name'         => 'color',
						'description'        => $this->l('Button color.'),
						'param_holder_class' => 'vc_colored-dropdown',
					],

					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Size'),
						'param_name'  => 'size',
						'value'       => getComposerShared('sizes'),
						'std'         => 'md',
						'description' => $this->l('Button size.'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
				],
				'js_view'     => 'VcButton2View',
			],
			'vc_cta_button'            => [
				'name'        => $this->l('Call to Action Button'),
				'base'        => 'vc_cta_button',
				'icon'        => 'icon-wpb-call-to-action',
				'category'    => $this->l('Content'),
				'description' => $this->l('Catch visitors attention with CTA block'),
				'params'      => [
					[
						'type'        => 'textarea',
						'admin_label' => true,
						'heading'     => $this->l('Text'),
						'param_name'  => 'call_text',
						'value'       => $this->l('Click edit button to change this text.'),
						'description' => $this->l('Enter your content.'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Text on the button'),
						'param_name'  => 'title',
						'value'       => $this->l('Text on the button'),
						'description' => $this->l('Text on the button.'),
					],
					[
						'type'        => 'href',
						'heading'     => $this->l('URL (Link)'),
						'param_name'  => 'href',
						'description' => $this->l('Button link.'),
					],
					[
						'type'       => 'dropdown',
						'heading'    => $this->l('Target'),
						'param_name' => 'target',
						'value'      => [
							$this->l('Same window') => '_self',
							$this->l('New window')  => "_blank",
						],
						'dependency' => ['element' => 'href', 'not_empty' => true, 'callback' => 'vc_cta_button_param_target_callback'],
					],
					[
						'type'               => 'colorpicker',
						'heading'            => $this->l('Color'),
						'param_name'         => 'color',
						'description'        => $this->l('Button color.'),
						'param_holder_class' => 'vc_colored-dropdown',
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Icon'),
						'param_name'  => 'icon',
						'value'       => $this->icons_arr,
						'description' => $this->l('Button icon.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Size'),
						'param_name'  => 'size',
						'value'       => [
							$this->l('Regular size') => 'wpb_regularsize',
							$this->l('Large')        => 'btn-large',
							$this->l('Small')        => 'btn-small',
							$this->l('Mini')         => "btn-mini",
						],
						'description' => $this->l('Button size.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Button position'),
						'param_name'  => 'position',
						'value'       => [
							$this->l('Align right')  => 'cta_align_right',
							$this->l('Align left')   => 'cta_align_left',
							$this->l('Align bottom') => 'cta_align_bottom',
						],
						'description' => $this->l('Select button alignment.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('CSS Animation'),
						'param_name'  => 'css_animation',
						'admin_label' => true,
						'value'       => [
							$this->l('No')                 => '',
							$this->l('Top to bottom')      => 'top-to-bottom',
							$this->l('Bottom to top')      => 'bottom-to-top',
							$this->l('Left to right')      => 'left-to-right',
							$this->l('Right to left')      => 'right-to-left',
							$this->l('Appear from center') => "appear",
						],
						'description' => $this->l('Select type of animation if you want this element to be animated when it enters into the browsers viewport. Note: Works only in modern browsers.'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
				],
				'js_view'     => 'VcCallToActionView',
			],
			'vc_cta_button2'           => [
				'name'        => $this->l('Call to Action Button') . ' 2',
				'base'        => 'vc_cta_button2',
				'icon'        => 'icon-wpb-call-to-action',
				'category'    => [$this->l('Content')],
				'description' => $this->l('Catch visitors attention with CTA block'),
				'params'      => [
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Heading first line'),
						'admin_label' => true,
						'param_name'  => 'h2',
						'value'       => $this->l('Hey! I am first heading line feel free to change me'),
						'description' => $this->l('Text for the first heading line.'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Heading second line'),
						'param_name'  => 'h4',
						'value'       => '',
						'description' => $this->l('Optional text for the second heading line.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('CTA style'),
						'param_name'  => 'style',
						'value'       => getComposerShared('cta styles'),
						'description' => $this->l('Call to action style.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Element width'),
						'param_name'  => 'el_width',
						'value'       => getComposerShared('cta widths'),
						'description' => $this->l('Call to action element width in percents.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Text align'),
						'param_name'  => 'txt_align',
						'value'       => getComposerShared('text align'),
						'description' => $this->l('Text align in call to action block.'),
					],
					[
						'type'        => 'colorpicker',
						'heading'     => $this->l('Custom Background Color'),
						'param_name'  => 'accent_color',
						'description' => $this->l('Select background color for your element.'),
					],
					[
						'type'       => 'textarea_html',
						'heading'    => $this->l('Promotional text'),
						'param_name' => 'content',
						'value'      => $this->l('I am promo text. Click edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.'),
					],
					[
						'type'        => 'vc_link',
						'heading'     => $this->l('URL (Link)'),
						'param_name'  => 'link',
						'description' => $this->l('Button link.'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Text on the button'),
						'param_name'  => 'title',
						'value'       => $this->l('Text on the button'),
						'description' => $this->l('Text on the button.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Button style'),
						'param_name'  => 'btn_style',
						'value'       => getComposerShared('button styles'),
						'description' => $this->l('Button style.'),
					],
					[
						'type'               => 'dropdown',
						'heading'            => $this->l('Color'),
						'param_name'         => 'color',
						'value'              => getComposerShared('colors'),
						'description'        => $this->l('Button color.'),
						'param_holder_class' => 'vc_colored-dropdown',
					],

					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Size'),
						'param_name'  => 'size',
						'value'       => getComposerShared('sizes'),
						'std'         => 'md',
						'description' => $this->l('Button size.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Button position'),
						'param_name'  => 'position',
						'value'       => [
							$this->l('Align right')  => 'right',
							$this->l('Align left')   => 'left',
							$this->l('Align bottom') => 'bottom',
						],
						'description' => $this->l('Select button alignment.'),
					],
					[
						'type'        => 'animation',
						'heading'     => $this->l('CSS Animation'),
						'param_name'  => 'css_animation',
						'admin_label' => true,
						'value'       => $this->animationStyles(),

						'description' => $this->l('Select type of animation if you want this element to be animated when it enters into the browsers viewport. Note: Works only in modern browsers.'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
				],
			],
			'vc_video'                 => [
				'name'        => $this->l('Video Player'),
				'base'        => 'vc_video',
				'icon'        => 'icon-wpb-film-youtube',
				'category'    => $this->l('Content'),
				'description' => $this->l('Embed YouTube/Vimeo player'),
				'params'      => [
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Widget title'),
						'param_name'  => 'title',
						'description' => $this->l('Enter text which will be used as widget title. Leave blank if no title is needed.'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Video link'),
						'param_name'  => 'link',
						'admin_label' => true,
						'description' => $this->l('Link to the video. '),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
					[
						'type'       => 'css_editor',
						'heading'    => $this->l('Css'),
						'param_name' => 'css',
						'group'      => $this->l('Design options'),
					],
				],
			],
			'vc_gmaps'                 => [
				'name'        => $this->l('Google Maps'),
				'base'        => 'vc_gmaps',
				'icon'        => 'icon-wpb-map-pin',
				'category'    => $this->l('Content'),
				'description' => $this->l('Map block'),
				'params'      => [
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Widget title'),
						'param_name'  => 'title',
						'description' => $this->l('Enter text which will be used as widget title. Leave blank if no title is needed.'),
					],
					[
						'type'        => 'textarea_safe',
						'heading'     => $this->l('Map embed iframe'),
						'param_name'  => 'link',
						'description' => sprintf($this->l('Visit %s to create your map. 1) Find location 2) Click "Share" and make sure map is public on the web 3) Click folder icon to reveal "Embed on my site" link 4) Copy iframe code and paste it here.'), '<a href="https://mapsengine.google.com/" target="_blank">Google maps</a>'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Map height'),
						'param_name'  => 'size',
						'admin_label' => true,
						'description' => $this->l('Enter map height in pixels. Example: 200 or leave it empty to make map responsive.'),
					],

					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
				],
			],
			'vc_raw_html'              => [
				'name'          => $this->l('Raw HTML'),
				'base'          => 'vc_raw_html',
				'icon'          => 'icon-wpb-raw-html',
				'category'      => $this->l('Structure'),
				'wrapper_class' => 'clearfix',
				'description'   => $this->l('Output raw html code on your page'),
				'params'        => [
					[
						'type'        => 'textarea_raw_html',
						'holder'      => 'div',
						'heading'     => $this->l('Raw HTML'),
						'param_name'  => 'content',
						'value'       => base64_encode('<p>I am raw html block.<br/>Click edit button to change this html</p>'),
						'description' => $this->l('Enter your HTML content.'),
					],
				],
			],
			'vc_raw_js'                => [
				'name'          => $this->l('Raw JS'),
				'base'          => 'vc_raw_js',
				'icon'          => 'icon-wpb-raw-javascript',
				'category'      => $this->l('Structure'),
				'wrapper_class' => 'clearfix',
				'description'   => $this->l('Output raw javascript code on your page'),
				'params'        => [
					[
						'type'        => 'textarea_raw_html',
						'holder'      => 'div',
						'heading'     => $this->l('Raw js'),
						'param_name'  => 'content',
						'value'       => $this->l(base64_encode('<script type="text/javascript"> alert("Enter your js here!" ); </script>')),
						'description' => $this->l('Enter your JS code.'),
					],
				],
			],
            'vc_raw_code'                => [
				'name'          => $this->l('Raw Code'),
				'base'          => 'vc_raw_code',
				'icon'          => 'icon-wpb-raw-code',
				'category'      => $this->l('Structure'),
				'wrapper_class' => 'clearfix',
				'description'   => $this->l('Output raw code on your page'),
				'params'        => [
					[
						'type'        => 'textarea_raw_code',
						'holder'      => 'div',
						'heading'     => $this->l('Raw Php'),
						'param_name'  => 'content',
						'value'       => base64_encode($this->l('Enter your code here')),
						'description' => $this->l('Enter your Code.'),
					],
                    [
						'type'        => 'el_id',
						'heading'     => $this->l('Row ID'),
						'param_name'  => 'el_id',
						'description' => sprintf($this->l('Enter row ID (Note: make sure it is unique and valid according to <a href="%s" target="_blank">w3c specification</a>).'), 'http://www.w3schools.com/tags/att_global_id.asp'),
					],
                    [
						'type'        => 'animation',
						'heading'     => $this->l('CSS Animation'),
						'param_name'  => 'css_animation',
						'admin_label' => true,
						'value'       => $this->animationStyles(),
						'description' => $this->l('Select type of animation if you want this element to be animated when it enters into the browsers viewport. Note: Works only in modern browsers.'),
					],
				],
			],
			'vc_flickr'                => [
				'base'        => 'vc_flickr',
				'name'        => $this->l('Flickr Widget'),
				'icon'        => 'icon-wpb-flickr',
				'category'    => $this->l('Content'),
				'description' => $this->l('Image feed from your flickr account'),
				"params"      => [
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Widget title'),
						'param_name'  => 'title',
						'description' => $this->l('Enter text which will be used as widget title. Leave blank if no title is needed.'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Flickr ID'),
						'param_name'  => 'flickr_id',
						'admin_label' => true,
						'description' => sprintf($this->l('To find your flickID visit %s.'), '<a href="http://idgettr.com/" target="_blank">idGettr</a>'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Number of photos'),
						'param_name'  => 'count',
						'value'       => [9, 8, 7, 6, 5, 4, 3, 2, 1],
						'description' => $this->l('Number of photos.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Type'),
						'param_name'  => 'type',
						'value'       => [
							$this->l('User')  => 'user',
							$this->l('Group') => 'group',
						],
						'description' => $this->l('Photo stream type.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Display'),
						'param_name'  => 'display',
						'value'       => [
							$this->l('Latest') => 'latest',
							$this->l('Random') => 'random',
						],
						'description' => $this->l('Photo order.'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
				],
			],
			'vc_progress_bar'          => [
				'name'        => $this->l('Progress Bar'),
				'base'        => 'vc_progress_bar',
				'icon'        => 'icon-wpb-graph',
				'category'    => $this->l('Content'),
				'description' => $this->l('Animated progress bar'),
				'params'      => [
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Widget title'),
						'param_name'  => 'title',
						'description' => $this->l('Enter text which will be used as widget title. Leave blank if no title is needed.'),
					],
					[
						'type'        => 'exploded_textarea',
						'heading'     => $this->l('Graphic values'),
						'param_name'  => 'values',
						'description' => $this->l('Input graph values, titles and color here. Divide values with linebreaks (Enter). Example: 90|Development|#e75956'),
						'value'       => "90|Development,80|Design,70|Marketing",
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Units'),
						'param_name'  => 'units',
						'description' => $this->l('Enter measurement units (if needed) Eg. %, px, points, etc. Graph value and unit will be appended to the graph title.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Bar color'),
						'param_name'  => 'bgcolor',
						'value'       => [
							$this->l('Grey')         => 'bar_grey',
							$this->l('Blue')         => 'bar_blue',
							$this->l('Turquoise')    => 'bar_turquoise',
							$this->l('Green')        => 'bar_green',
							$this->l('Orange')       => 'bar_orange',
							$this->l('Red')          => 'bar_red',
							$this->l('Black')        => 'bar_black',
							$this->l('Custom Color') => 'custom',
						],
						'description' => $this->l('Select bar background color.'),
						'admin_label' => true,
					],
					[
						'type'        => 'colorpicker',
						'heading'     => $this->l('Bar custom color'),
						'param_name'  => 'custombgcolor',
						'description' => $this->l('Select custom background color for bars.'),
						'dependency'  => ['element' => 'bgcolor', 'value' => ['custom']],
					],
					[
						'type'       => 'checkbox',
						'heading'    => $this->l('Options'),
						'param_name' => 'options',
						'value'      => [
							$this->l('Add Stripes?')                                      => 'striped',
							$this->l('Add animation? Will be visible with striped bars.') => 'animated',
						],
					],

					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
				],
			],
			'vc_pfg'                   => [
				'name'        => $this->l('A power form'),
				'base'        => 'vc_pfg',
				'icon'        => 'vc_ephenyxshop_icon',
				'category'    => $this->l('Content'),
				'description' => $this->l('Open a form'),
				'params'      => [
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Text on the button'),
						'param_name'  => 'title',
						'description' => $this->l('Enter text on the button.'),
					],
					[
						"type"       => "dropdown",
						"heading"    => $this->l("Form Id"),
						"param_name" => "id",
						"value"      => $this->getActiveForm(),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Button position'),
						'param_name'  => 'position',
						'value'       => [
							$this->l('Align right')  => 'align_right',
							$this->l('Align left')   => 'align_left',
							$this->l('Align center') => 'align_center',
						],
						'description' => $this->l('Select button alignment.'),
					],
					[
						'type'        => 'animation',
						'heading'     => $this->l('CSS Animation'),
						'param_name'  => 'css_animation',
						'admin_label' => true,
						'value'       => $this->animationStyles(),
						'description' => $this->l('Select type of animation if you want this element to be animated when it enters into the browsers viewport. Note: Works only in modern browsers.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Style'),
						'param_name'  => 'style',
						'value'       => getComposerShared('button styles'),
						'description' => $this->l('Button style.'),
					],
					[
						'type'        => 'dropdown',
						'heading'     => $this->l('Size'),
						'param_name'  => 'size',
						'value'       => [
							$this->l('Regular size') => 'wpb_regularsize',
							$this->l('Large')        => 'btn-large',
							$this->l('Small')        => 'btn-small',
							$this->l('Mini')         => "btn-mini",
						],
						'std'         => 'md',
						'description' => $this->l('Button size.'),
					],
					[
						'type'               => 'colorpicker',
						'heading'            => $this->l('Color'),
						'param_name'         => 'color',
						'description'        => $this->l('Button color.'),
					],
                    [
						'type'               => 'colorpicker',
						'heading'            => $this->l('Text Color'),
						'param_name'         => 'text_color',
						'description'        => $this->l('Text color.'),
                        'edit_field_class' => 'vc_col-md-6 vc_column',
					],
				],

			],
			'vc_pie'                   => [
				'name'        => $this->l('Pie chart'),
				'base'        => 'vc_pie',
				'class'       => '',
				'icon'        => 'icon-wpb-vc_pie',
				'category'    => $this->l('Content'),
				'description' => $this->l('Animated pie chart'),
				'params'      => [
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Widget title'),
						'param_name'  => 'title',
						'description' => $this->l('Enter text which will be used as widget title. Leave blank if no title is needed.'),
						'admin_label' => true,
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Pie value'),
						'param_name'  => 'value',
						'description' => $this->l('Input graph value here. Choose range between 0 and 100.'),
						'value'       => '50',
						'admin_label' => true,
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Pie label value'),
						'param_name'  => 'label_value',
						'description' => $this->l('Input integer value for label. If empty "Pie value" will be used.'),
						'value'       => '',
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Units'),
						'param_name'  => 'units',
						'description' => $this->l('Enter measurement units (if needed) Eg. %, px, points, etc. Graph value and unit will be appended to the graph title.'),
					],
					[
						'type'               => 'dropdown',
						'heading'            => $this->l('Bar color'),
						'param_name'         => 'color',
						'value'              => [
							$this->l('Grey')      => 'wpb_button',
							$this->l('Blue')      => 'btn-primary',
							$this->l('Turquoise') => 'btn-info',
							$this->l('Green')     => 'btn-success',
							$this->l('Orange')    => 'btn-warning',
							$this->l('Red')       => 'btn-danger',
							$this->l('Black')     => "btn-inverse",
						],
						'description'        => $this->l('Select pie chart color.'),
						'admin_label'        => true,
						'param_holder_class' => 'vc_colored-dropdown',
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],

				],
			],
			'vc_empty_space'           => [
				'name'                    => $this->l('Empty Space'),
				'base'                    => 'vc_empty_space',
				'icon'                    => 'icon-wpb-ui-empty_space',
				'show_settings_on_create' => true,
				'category'                => $this->l('Content'),
				'description'             => $this->l('Add spacer with custom height'),
				'params'                  => [
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Height'),
						'param_name'  => 'height',
						'value'       => '32px',
						'admin_label' => true,
						'description' => $this->l('Enter empty space height.'),
					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
				],
			],
			'vc_custom_heading'        => [
				'name'                    => $this->l('Custom Heading'),
				'base'                    => 'vc_custom_heading',
				'icon'                    => 'icon-wpb-ui-custom_heading',
				'show_settings_on_create' => true,
				'category'                => $this->l('Content'),
				'description'             => $this->l('Add custom heading text with google fonts'),
				'params'                  => [
					[
						'type'        => 'textarea',
						'heading'     => $this->l('Text'),
						'param_name'  => 'text',
						'admin_label' => true,
						'value'       => $this->l('This is custom heading element with Google Fonts'),
						'description' => $this->l('Enter your content. If you are using non-latin characters be sure to activate them under Settings/Visual Composer/General Settings.'),
					],
					[
						'type'       => 'font_container',
						'param_name' => 'font_container',
						'value'      => '',
						'settings'   => [
							'fields' => [
								'tag'                     => 'h2',
								'text_align',
								'font_size',
								'line_height',
								'color',
								'tag_description'         => $this->l('Select element tag.'),
								'text_align_description'  => $this->l('Select text alignment.'),
								'font_size_description'   => $this->l('Enter font size.'),
								'line_height_description' => $this->l('Enter line height.'),
								'color_description'       => $this->l('Select color for your element.'),
							],
						],
					],
					[
						'type'       => 'google_fonts',
						'param_name' => 'google_fonts',
						'value'      => '',
						'settings'   => [
							'fields' => [
								'font_family'             => 'Abril Fatface:regular',
								'font_style'              => '400 regular:400:normal',
								'font_family_description' => $this->l('Select font family.'),
								'font_style_description'  => $this->l('Select font styling.'),
							],
						],

					],
					[
						'type'        => 'textfield',
						'heading'     => $this->l('Extra class name'),
						'param_name'  => 'el_class',
						'description' => $this->l('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.'),
					],
					[
						'type'       => 'css_editor',
						'heading'    => $this->l('Css'),
						'param_name' => 'css',
						'group'      => $this->l('Design options'),
					],
				],
			],			
		];

		if (Plugin::isInstalled('revslider')) {
			$revslider = Plugin::getInstanceByName('revslider');
			$alias = $this->getRevsliserAlias();
            $values[$this->l("Display on mobile")] = 1;

			if (is_array($alias) && count($alias)) {
				$icon_url = '/includes/plugins/revslider/composer.png';

				$map['rev_slider'] = [
					'name'     => 'Revolution Slider',
					'base'     => 'rev_slider',
					'icon'     => $icon_url,
					'category' => 'Content',
					'params'   => [
						[
							"type"       => "dropdown",
							"heading"    => $this->l("Executed Shortcode"),
							"param_name" => "alias",
							"value"      => $alias,
						], [
							"type"       => "vc_hidden_field",
							"param_name" => "alias",
							"def_value"  => "revslider",
							"value"      => $alias,
						],
                        [
						'type'       => 'checkbox',
						'heading'    => $this->l('Do not display on mobile:'),
						'param_name' => 'display_mobile',
                        'default_check' => 'checked="checked"',
						'value'      => [
							$this->l('Do not display on mobile') => 'not_display_mobile',
						  ],
					   ],
                         [
						'type'       => 'checkbox',
						'heading'    => $this->l('Do not display on tablette:'),
						'param_name' => 'display_tablet',
                        'default_check' => 'checked="checked"',
						'value'      => [							
							$this->l('Do not display on tablette') => 'not_display_tablet',
						  ],
					   ],
                       
					],
				];

			}

		}

		$map['vc_cms'] = [
			'name'     => $this->l('Link to Cms Page'),
			'base'     => 'vc_cms',
			'icon'     => '/includes/plugins/ph_manager/views/img/cms.png',
			'category' => 'Content',
			'params'   => [
				[
					'type'        => 'textfield',
					'heading'     => $this->l('Text on the button'),
					'holder'      => 'button',
					'class'       => 'wpb_button',
					'param_name'  => 'title',
					'value'       => $this->l('Text on the button'),
					'description' => $this->l('Text on the button.'),
				],
				[
					"type"       => "dropdown",
					"heading"    => $this->l("Form Id"),
					"param_name" => "id",
					"value"      => $this->getCms(),
				],
				[
					'type'        => 'dropdown',
					'heading'     => $this->l('Button position'),
					'param_name'  => 'position',
					'value'       => [
						$this->l('Align right')  => 'align_right',
						$this->l('Align left')   => 'align_left',
						$this->l('Align center') => 'align_center',
					],
					'description' => $this->l('Select button alignment.'),
				],
				[
					'type'        => 'animation',
					'heading'     => $this->l('CSS Animation'),
					'param_name'  => 'css_animation',
					'admin_label' => true,
					'value'       => $this->animationStyles(),
					'description' => $this->l('Select type of animation if you want this element to be animated when it enters into the browsers viewport. Note: Works only in modern browsers.'),
				],

				[
					'type'        => 'dropdown',
					'heading'     => $this->l('Style'),
					'param_name'  => 'style',
					'value'       => getComposerShared('button styles'),
					'description' => $this->l('Button style.'),
				],
				[
					'type'        => 'dropdown',
					'heading'     => $this->l('Size'),
					'param_name'  => 'size',
					'value'       => [
						$this->l('Regular size') => 'wpb_regularsize',
						$this->l('Large')        => 'btn-large',
						$this->l('Small')        => 'btn-small',
						$this->l('Mini')         => "btn-mini",
					],
					'std'         => 'md',
					'description' => $this->l('Button size.'),
				],
				[
					'type'               => 'dropdown',
					'heading'            => $this->l('Color'),
					'param_name'         => 'color',
					'value'              => [
						$this->l('Grey')      => 'wpb_button',
						$this->l('Blue')      => 'btn-primary',
						$this->l('Turquoise') => 'btn-info',
						$this->l('Green')     => 'btn-success',
						$this->l('Orange')    => 'btn-warning',
						$this->l('Red')       => 'btn-danger',
						$this->l('Black')     => "btn-inverse",
					],
					'description'        => $this->l('Button color.'),
					'param_holder_class' => 'vc_colored-dropdown',
				],
			],
		];
        
      
        $map['vc_pdf'] = [
			'name'     => $this->l('Add a media type pdf'),
			'base'     => 'vc_pdf',
			'icon'     => _EPH_IMG_.'/pdfWorker/pdf.png',
			'category' => 'Content',
			'params'   => [
                [
					"type"       => "media_dropdown",
					"heading"    => $this->l("PDF Id"),
					"param_name" => "id",
                    "relative_param" => "attach_media",
					"value"      => $this->getMediaPDF(),
				],
				[
					'type'        => 'attach_media',
					'heading'     => $this->l('Attach your file'),
					'param_name'  => 'attach_pdf',
					'value'       => '',
					'description' => $this->l('Select your pdf file from you computer'),
				],
				[
					'type'       => 'dropdown',
					'heading'    => $this->l('Allow user to download the document'),
					'param_name' => 'btnDownloadPdf',
					'value'      => [
						$this->l('Yes') => true,
						$this->l('No')  => false,
					],
				],
				[
					'type'       => 'dropdown',
					'heading'    => $this->l('Allow user to print the document'),
					'param_name' => 'btnPrint',
					'value'      => [
						$this->l('Yes') => true,
						$this->l('No')  => false,
					],
				],
				[
					'type'       => 'dropdown',
					'heading'    => $this->l('Allow user to share the document'),
					'param_name' => 'btnShare',
					'value'      => [
						$this->l('Yes') => true,
						$this->l('No')  => false,
					],
				]

			],
		];
        
       
        if (is_array($this->extraMaps) && count($this->extraMaps)) {

            foreach ($this->extraMaps as $plugin => $vars) {

                if (is_array($vars) && count($vars)) {

                    foreach ($vars as $key => $value) {
                        $map[$key] 
                        = $value;
                    }

                }

            }

        }



		return $map;

	}

	public function animationStyles() {

		return [
			[
				'values' => [
					$this->l('None') => 'none',
				],
			],
			[
				'label'  => $this->l('Attention Seekers'),
				'values' => [
					// text to display => value
					$this->l('bounce')     => [
						'value' => 'bounce',
						'type'  => 'other',
					],
					$this->l('flash')      => [
						'value' => 'flash',
						'type'  => 'other',
					],
					$this->l('pulse')      => [
						'value' => 'pulse',
						'type'  => 'other',
					],
					$this->l('rubberBand') => [
						'value' => 'rubberBand',
						'type'  => 'other',
					],
					$this->l('shake')      => [
						'value' => 'shake',
						'type'  => 'other',
					],
					$this->l('swing')      => [
						'value' => 'swing',
						'type'  => 'other',
					],
					$this->l('tada')       => [
						'value' => 'tada',
						'type'  => 'other',
					],
					$this->l('wobble')     => [
						'value' => 'wobble',
						'type'  => 'other',
					],
				],
			],
			[
				'label'  => $this->l('Bouncing Entrances'),
				'values' => [
					// text to display => value
					$this->l('bounceIn')      => [
						'value' => 'bounceIn',
						'type'  => 'in',
					],
					$this->l('bounceInDown')  => [
						'value' => 'bounceInDown',
						'type'  => 'in',
					],
					$this->l('bounceInLeft')  => [
						'value' => 'bounceInLeft',
						'type'  => 'in',
					],
					$this->l('bounceInRight') => [
						'value' => 'bounceInRight',
						'type'  => 'in',
					],
					$this->l('bounceInUp')    => [
						'value' => 'bounceInUp',
						'type'  => 'in',
					],
				],
			],
			[
				'label'  => $this->l('Bouncing Exits'),
				'values' => [
					// text to display => value
					$this->l('bounceOut')      => [
						'value' => 'bounceOut',
						'type'  => 'out',
					],
					$this->l('bounceOutDown')  => [
						'value' => 'bounceOutDown',
						'type'  => 'out',
					],
					$this->l('bounceOutLeft')  => [
						'value' => 'bounceOutLeft',
						'type'  => 'out',
					],
					$this->l('bounceOutRight') => [
						'value' => 'bounceOutRight',
						'type'  => 'out',
					],
					$this->l('bounceOutUp')    => [
						'value' => 'bounceOutUp',
						'type'  => 'out',
					],
				],
			],
			[
				'label'  => $this->l('Fading Entrances'),
				'values' => [
					// text to display => value
					$this->l('fadeIn')         => [
						'value' => 'fadeIn',
						'type'  => 'in',
					],
					$this->l('fadeInDown')     => [
						'value' => 'fadeInDown',
						'type'  => 'in',
					],
					$this->l('fadeInDownBig')  => [
						'value' => 'fadeInDownBig',
						'type'  => 'in',
					],
					$this->l('fadeInLeft')     => [
						'value' => 'fadeInLeft',
						'type'  => 'in',
					],
					$this->l('fadeInLeftBig')  => [
						'value' => 'fadeInLeftBig',
						'type'  => 'in',
					],
					$this->l('fadeInRight')    => [
						'value' => 'fadeInRight',
						'type'  => 'in',
					],
					$this->l('fadeInRightBig') => [
						'value' => 'fadeInRightBig',
						'type'  => 'in',
					],
					$this->l('fadeInUp')       => [
						'value' => 'fadeInUp',
						'type'  => 'in',
					],
					$this->l('fadeInUpBig')    => [
						'value' => 'fadeInUpBig',
						'type'  => 'in',
					],
				],
			],
			[
				'label'  => $this->l('Fading Exits'),
				'values' => [
					$this->l('fadeOut')         => [
						'value' => 'fadeOut',
						'type'  => 'out',
					],
					$this->l('fadeOutDown')     => [
						'value' => 'fadeOutDown',
						'type'  => 'out',
					],
					$this->l('fadeOutDownBig')  => [
						'value' => 'fadeOutDownBig',
						'type'  => 'out',
					],
					$this->l('fadeOutLeft')     => [
						'value' => 'fadeOutLeft',

						'type'  => 'out',
					],
					$this->l('fadeOutLeftBig')  => [
						'value' => 'fadeOutLeftBig',
						'type'  => 'out',
					],
					$this->l('fadeOutRight')    => [
						'value' => 'fadeOutRight',
						'type'  => 'out',
					],
					$this->l('fadeOutRightBig') => [
						'value' => 'fadeOutRightBig',
						'type'  => 'out',
					],
					$this->l('fadeOutUp')       => [
						'value' => 'fadeOutUp',
						'type'  => 'out',
					],
					$this->l('fadeOutUpBig')    => [
						'value' => 'fadeOutUpBig',
						'type'  => 'out',
					],
				],
			],
			[
				'label'  => $this->l('Flippers'),
				'values' => [
					$this->l('flip')     => [
						'value' => 'flip',
						'type'  => 'other',
					],
					$this->l('flipInX')  => [
						'value' => 'flipInX',
						'type'  => 'in',
					],
					$this->l('flipInY')  => [
						'value' => 'flipInY',
						'type'  => 'in',
					],
					$this->l('flipOutX') => [
						'value' => 'flipOutX',
						'type'  => 'out',
					],
					$this->l('flipOutY') => [
						'value' => 'flipOutY',
						'type'  => 'out',
					],
				],
			],
			[
				'label'  => $this->l('Lightspeed'),
				'values' => [
					$this->l('lightSpeedIn')  => [
						'value' => 'lightSpeedIn',
						'type'  => 'in',
					],
					$this->l('lightSpeedOut') => [
						'value' => 'lightSpeedOut',
						'type'  => 'out',
					],
				],
			],
			[
				'label'  => $this->l('Rotating Entrances'),
				'values' => [
					$this->l('rotateIn')          => [
						'value' => 'rotateIn',
						'type'  => 'in',
					],
					$this->l('rotateInDownLeft')  => [
						'value' => 'rotateInDownLeft',
						'type'  => 'in',
					],
					$this->l('rotateInDownRight') => [
						'value' => 'rotateInDownRight',
						'type'  => 'in',
					],
					$this->l('rotateInUpLeft')    => [
						'value' => 'rotateInUpLeft',
						'type'  => 'in',
					],
					$this->l('rotateInUpRight')   => [
						'value' => 'rotateInUpRight',
						'type'  => 'in',
					],
				],
			],
			[
				'label'  => $this->l('Rotating Exits'),
				'values' => [
					$this->l('rotateOut')          => [
						'value' => 'rotateOut',
						'type'  => 'out',
					],
					$this->l('rotateOutDownLeft')  => [
						'value' => 'rotateOutDownLeft',
						'type'  => 'out',
					],
					$this->l('rotateOutDownRight') => [
						'value' => 'rotateOutDownRight',
						'type'  => 'out',
					],
					$this->l('rotateOutUpLeft')    => [
						'value' => 'rotateOutUpLeft',
						'type'  => 'out',
					],
					$this->l('rotateOutUpRight')   => [
						'value' => 'rotateOutUpRight',
						'type'  => 'out',
					],
				],
			],
			[
				'label'  => $this->l('Specials'),
				'values' => [
					$this->l('hinge')   => [
						'value' => 'hinge',
						'type'  => 'out',
					],
					$this->l('rollIn')  => [
						'value' => 'rollIn',
						'type'  => 'in',
					],
					$this->l('rollOut') => [
						'value' => 'rollOut',
						'type'  => 'out',
					],
				],
			],
			[
				'label'  => $this->l('Zoom Entrances'),
				'values' => [
					$this->l('zoomIn')      => [
						'value' => 'zoomIn',
						'type'  => 'in',
					],
					$this->l('zoomInDown')  => [
						'value' => 'zoomInDown',
						'type'  => 'in',
					],
					$this->l('zoomInLeft')  => [
						'value' => 'zoomInLeft',
						'type'  => 'in',
					],
					$this->l('zoomInRight') => [
						'value' => 'zoomInRight',
						'type'  => 'in',
					],
					$this->l('zoomInUp')    => [
						'value' => 'zoomInUp',
						'type'  => 'in',
					],
				],
			],
			[
				'label'  => $this->l('Zoom Exits'),
				'values' => [
					$this->l('zoomOut')      => [
						'value' => 'zoomOut',
						'type'  => 'out',
					],
					$this->l('zoomOutDown')  => [
						'value' => 'zoomOutDown',
						'type'  => 'out',
					],
					$this->l('zoomOutLeft')  => [
						'value' => 'zoomOutLeft',
						'type'  => 'out',
					],
					$this->l('zoomOutRight') => [
						'value' => 'zoomOutRight',
						'type'  => 'out',
					],
					$this->l('zoomOutUp')    => [
						'value' => 'zoomOutUp',
						'type'  => 'out',
					],
				],
			],
			[
				'label'  => $this->l('Slide Entrances'),
				'values' => [
					$this->l('slideInDown')  => [
						'value' => 'slideInDown',
						'type'  => 'in',
					],
					$this->l('slideInLeft')  => [
						'value' => 'slideInLeft',
						'type'  => 'in',
					],
					$this->l('slideInRight') => [
						'value' => 'slideInRight',
						'type'  => 'in',
					],
					$this->l('slideInUp')    => [
						'value' => 'slideInUp',
						'type'  => 'in',
					],
				],
			],
			[
				'label'  => $this->l('Slide Exits'),
				'values' => [
					$this->l('slideOutDown')  => [
						'value' => 'slideOutDown',
						'type'  => 'out',
					],
					$this->l('slideOutLeft')  => [
						'value' => 'slideOutLeft',
						'type'  => 'out',
					],
					$this->l('slideOutRight') => [
						'value' => 'slideOutRight',
						'type'  => 'out',
					],
					$this->l('slideOutUp')    => [
						'value' => 'slideOutUp',
						'type'  => 'out',
					],
				],
			],
		];

	}

}
