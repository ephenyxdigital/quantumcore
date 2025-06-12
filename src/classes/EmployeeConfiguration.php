<?php

class EmployeeConfiguration extends PhenyxObjectModel {

	public static $instance;

	public static $definition = [
		'table'     => 'employee_configuration',
		'primary'   => 'id_employee_configuration',
		'multilang' => true,
		'fields'    => [
			'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
			'name'        => ['type' => self::TYPE_STRING],
			'date_add'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
			'date_upd'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
			'generated'   => ['type' => self::TYPE_BOOL, 'lang' => true],
			'value'       => ['type' => self::TYPE_JSON, 'lang' => true],
		],
	];

	/** @var string Key */
	public $name;
	public $id_employee;
	/** @var string Value */
	public $generated;
	public $value;
	/** @var string Object creation date */
	public $date_add;
	/** @var string Object last modification date */
	public $date_upd;

	public function __construct($id = null, $idLang = null) {

		parent::__construct($id, $idLang);

		if ($this->id) {

			$this->value = $this->context->_tools->jsonDecode($this->value, true);

		}

	}

	public static function buildObject($id, $id_lang = null, $className = null) {

		$objectData = parent::buildObject($id, $id_lang, $className);
		$objectData['value'] = Tools::jsonDecode($objectData['value'], true);

		return Tools::jsonDecode(PhenyxTool::getInstance()->jsonEncode($objectData));
	}

	public function add($autoDate = true, $nullValues = false) {

		return parent::add($autoDate, $nullValues);

	}

	public function update($nullValues = false) {

		$return = parent::update($nullValues);

		return $return;
	}

	public static function getInstance($id = null, $idLang = null) {

		if (!static::$instance) {
			static::$instance = new EmployeeConfiguration($id, $idLang);
		}

		return static::$instance;
	}

	public function get($key, $idLang = null) {

		if (!isset($this->context->employee->id)) {
			return false;
		}

		$result = $this->context->_session->get('getEmployeeConfig_' . $key . '_' . $this->context->employee->id . '_' . $this->context->employee->id_lang);

		if (!empty($result)) {
			return $result = Validate::isJSON($result) ? Tools::jsonDecode($result, true) : $result;
		}

		$result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('ecl.`value`')
				->from('employee_configuration', 'ec')
				->leftJoin('employee_configuration_lang', 'ecl', 'ecl.`id_employee_configuration` = ec.`id_employee_configuration` AND ecl.`id_lang` = ' . $this->context->employee->id_lang)
				->where('ec.`name` LIKE \'' . $key . '\' AND ec.id_employee = ' . $this->context->employee->id)
		);

		if (!empty($result)) {
			$this->context->_session->set('getEmployeeConfig_' . $key . '_' . $this->context->employee->id . '_' . $this->context->employee->id_lang, $result);
			$result = Validate::isJSON($result) ? Tools::jsonDecode($result, true) : $result;

		}

		return $result;

	}

	public function updateValue($key, $values) {

		$file = fopen("testUpdateElokiyeeValue.txt", "w");

		if (!isset($this->context->employee->id)) {
			return false;
		}

		fwrite($file, $key . PHP_EOL);
		fwrite($file, print_r($values, true) . PHP_EOL);

		$hasKey = $this->hasKey($key);

		if ($hasKey > 0) {
			fwrite($file, 'has key' . PHP_EOL);
			$configuration = new EmployeeConfiguration($hasKey);

			foreach (Language::getLanguages(false) as $lang) {
				$configuration->value[$lang['id_lang']] = is_array($values) ? Tools::jsonEncode($values) : $values;
			}

			$result = $configuration->update();

		} else {
			fwrite($file, 'no key' . PHP_EOL);
			$configuration = new EmployeeConfiguration();
			$configuration->id_employee = $this->context->employee->id;
			$configuration->name = $key;

			foreach (Language::getLanguages(false) as $lang) {
				$configuration->value[$lang['id_lang']] = is_array($values) ? Tools::jsonEncode($values) : $values;
			}

			$result = $configuration->add();
		}

		if ($result) {
			return true;
		}

		$result = [
			'success' => false,
			'message' => sprintf($this->la('We encounter a problem creating %s %s preference'), $employee->firstname, $employee->lastname),
		];
		die(Tools::jsonEncode($result));

	}

	public function hasKey($key) {

		return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
			(new DbQuery())
				->select('`id_employee_configuration`')
				->from('employee_configuration')
				->where('`id_employee` = ' . (int) $this->context->employee->id . ' AND `name` LIKE \'' . $key . '\'')
		);

	}

}
