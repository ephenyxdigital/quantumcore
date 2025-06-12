<?php

/**
 * Class CacheMemcache
 *
 * @since 1.0.0
 */
class CacheMemcache extends CacheApi implements CacheApiInterface {

	const CLASS_KEY = 'cache_memcached';

	/**
	 * @var Memcache The memcache instance.
	 */
	private $memcache = null;

	/**
	 * {@inheritDoc}
	 */
	public function isSupported($test = false) {

		global $cache_memcached;

		$supported = class_exists('Memcache');

		if ($test) {
			return $supported;
		}

		return parent::isSupported() && $supported && !empty($cache_memcached);
	}

	/**
	 * {@inheritDoc}
	 */
	public function connect() {

		global $db_persist, $cache_memcached;

		$this->memcache = new Memcache();

		$servers = explode(',', $cache_memcached);
		$port = 0;

		// Don't try more times than we have servers!
		$connected = false;
		$level = 0;

		// We should keep trying if a server times out, but only for the amount of servers we have.

		while (!$connected && $level < count($servers)) {
			++$level;

			$server = trim($servers[array_rand($servers)]);

			// No server, can't connect to this.

			if (empty($server)) {
				continue;
			}

			// Normal host names do not contain slashes, while e.g. unix sockets do. Assume alternative transport pipe with port 0.

			if (strpos($server, '/') !== false) {
				$host = $server;
			} else {
				$server = explode(':', $server);
				$host = $server[0];
				$port = isset($server[1]) ? $server[1] : 11211;
			}

			// Don't wait too long: yes, we want the server, but we might be able to run the query faster!

			if (empty($db_persist)) {
				$connected = $this->memcache->connect($host, $port);
			} else {
				$connected = $this->memcache->pconnect($host, $port);
			}

		}

		return $connected;
	}

	public static function getMemcachedServers() {

		return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'memcached_servers', true, false);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getData($key, $ttl = null) {

		return $this->_get($key);
	}

	protected function _exists($key) {

		return (bool) $this->_get($key);
	}

	protected function _get($key) {

		$key = $this->prefix . strtr($key, ':/', '-_');

		$value = $this->memcache->get($key);

		// $value should return either data or false (from failure, key not found or empty array).

		if ($value === false) {
			return null;
		}

		return $value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function putData($key, $value, $ttl = null) {

		return $this->_set($key, $value, $ttl);
	}

	protected function _set($key, $value, $ttl = 0) {

		$key = $this->prefix . strtr($key, ':/', '-_');

		return $this->memcache->set($key, $value, 0, $ttl !== null ? $ttl : $this->ttl);
	}

	protected function _delete($key) {

		if (!$this->is_connected) {
			return false;
		}

		return $this->memcache->delete($key);
	}

	protected function _writeKeys() {

		if (!$this->is_connected) {
			return false;
		}

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function quit() {

		return $this->memcache->close();
	}

	/**
	 * {@inheritDoc}
	 */
	public function cleanCache($type = '') {

		$this->invalidateCache();

		return $this->memcache->flush();
	}

	public function flush() {

		return $this->memcache->flush();
	}

	/**
	 * {@inheritDoc}
	 */
	public function cacheSettings(array &$config_vars) {

		global $context, $txt;

		if (!in_array($txt[self::CLASS_KEY . '_settings'], $config_vars)) {
			$config_vars[] = $txt[self::CLASS_KEY . '_settings'];
			$config_vars[] = [
				self::CLASS_KEY,
				$txt[self::CLASS_KEY . '_servers'],
				'file',
				'text',
				0,
				'subtext' => $txt[self::CLASS_KEY . '_servers_subtext']];
		}

		if (!isset($context['settings_post_javascript'])) {
			$context['settings_post_javascript'] = '';
		}

		if (empty($context['settings_not_writable'])) {
			$context['settings_post_javascript'] .= '
			$("#cache_accelerator").change(function (e) {
				var cache_type = e.currentTarget.value;
				$("#' . self::CLASS_KEY . '").prop("disabled", cache_type != "MemcacheImplementation" && cache_type != "MemcachedImplementation");
			});';
		}

	}

	/**
	 * {@inheritDoc}
	 */
	public function getVersion() {

		if (!is_object($this->memcache)) {
			return false;
		}

		// This gets called in Subs-Admin getServerVersions when loading up support information.  If we can't get a connection, return nothing.
		$result = $this->memcache->getVersion();

		if (!empty($result)) {
			return $result;
		}

		return false;
	}

}
