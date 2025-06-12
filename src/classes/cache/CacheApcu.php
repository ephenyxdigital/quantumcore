<?php

/**
 * Our Cache API class
 *
 * @package CacheAPI
 */
class CacheApcu extends CacheApi implements CacheApiInterface {

	/**
	 * {@inheritDoc}
	 */
	public function isSupported($test = false) {

		$supported = function_exists('apcu_fetch') && function_exists('apcu_store');

		if ($test) {
			return $supported;
		}

		return parent::isSupported() && $supported;
	}

	/**
	 * {@inheritDoc}
	 */
	public function connect() {

		return true;
	}

	protected function _set($key, $value, $ttl = 0) {

		return $this->putData($key, $value, $ttl);
	}

	protected function _get($key) {

		return $this->getData($key);
	}

	protected function _exists($key) {

		return (bool) $this->_get($key);
	}

	protected function _writeKeys() {

		$this->_set($this->prefix, $this->keys);

		return true;
	}

	public function getApcuValues() {

		ini_set('memory_limit', '-1');
		$result = [];

		foreach (new APCUIterator('/^\.*/') as $counter) {
			$result[str_replace($this->prefix, '', $counter['key'])] = Validate::isJSON($counter['value']) ? Tools::jsonDecode($counter['value'], true) : $counter['value'];
		}

		ksort($result);
		return $result;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getData($key, $ttl = null) {

		$key = $this->prefix . strtr($key, ':/', '-_');

		$value = apcu_fetch($key);

		return !empty($value) ? $value : null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function putData($key, $value, $ttl = null) {

		$key = $this->prefix . strtr($key, ':/', '-_');
		return apcu_store($key, $value, $ttl !== null ? $ttl : $this->ttl);

	}

	public function cleanByStartingKey($key) {

		ini_set('memory_limit', '-1');
		$result = $this->_delete($key . '*');

		return $result;
	}

	public function removeData($key) {

		if ($this->_exists($key)) {
			return $this->_delete($this->prefix . $key);
		}

	}

	protected function _delete($key) {

		return apcu_delete($key);
	}

	public function flush() {

		return (bool) $this->cleanCache();
	}

	/**
	 * {@inheritDoc}
	 */
	public function cleanCache($type = '') {

		return apcu_clear_cache();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getVersion() {

		return phpversion('apcu');
	}

}

?>