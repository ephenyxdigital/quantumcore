<?php

abstract class CacheApi {

	const APIS_DEFAULT = 'FileBased';

	protected static $instance;
	/**
	 * @var string The maximum SMF version that this will work with.
	 */
	protected $version_compatible = _EPH_VERSION_;

	/**
	 * @var string The minimum SMF version that this will work with.
	 */
	protected $min_eph_version = _EPH_VERSION_;

	abstract protected function _set($key, $value, $ttl = 0);

	abstract protected function _get($key);

	abstract protected function _exists($key);

	abstract protected function _writeKeys();

	abstract protected function _delete($key);

	abstract public function flush();

	protected $keys = [];

	protected static $local = [];

	/**
	 * @var string The prefix for all keys.
	 */
	protected $prefix = '';

	/**
	 * @var int The default TTL.
	 */
	protected $ttl = 1864000;

	public $boardurl;

	public $cachedir;

	public $boarddir;

	public $context;

	/**
	 * Does basic setup of a cache method when we create the object but before we call connect.
	 *
	 * @access public
	 */
	public function __construct() {

		$this->boardurl = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
		$this->cachedir = _EPH_CACHE_DIR_ . 'cacheapi/';
		$this->boarddir = _EPH_ROOT_DIR_;
		$this->setPrefix();
	}

	public static function getInstance() {

		if (!static::$instance) {
			$sql = new DbQuery();
			$sql->select('`value`');
			$sql->from('configuration');
			$sql->where('`name` = \'EPH_PAGE_CACHE_TYPE\'');
			$cachingSystem = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($sql, false);

			if ($cachingSystem) {
				static::$instance = new $cachingSystem();
			} else {
				static::$instance = new FileBased();
			}

		}

		return static::$instance;
	}

	public static function retrieveAll() {
		return CacheApi::$local;
	}

	public static function isEnabled() {

		return (bool) Context::getContext()->phenyxConfig->get('EPH_CACHE_ENABLED');
	}

	public function get($key) {

		if (!isset($this->keys[$key])) {
			return false;
		}

		return $this->_get($key);
	}

	/**
	 * Checks whether we can use the cache method performed by this API.
	 *
	 * @access public
	 * @param bool $test Test if this is supported or enabled.
	 * @return bool Whether or not the cache is supported
	 */
	public function isSupported($test = false) {

		$cache_enable = Context::getContext()->cache_enable;

		if ($test) {
			return true;
		}

		return !empty($cache_enable);
	}

	/**
	 * Sets the cache prefix.
	 *
	 * @access public
	 * @param string $prefix The prefix to use.
	 *     If empty, the prefix will be generated automatically.
	 * @return bool If this was successful or not.
	 */
	public function setPrefix($prefix = '') {

		if (!is_string($prefix)) {
			$prefix = '';
		}

		// Use the supplied prefix, if there is one.

		if (!empty($prefix)) {
			$this->prefix = $prefix;

			return true;
		}

		$mtime = filemtime(_EPH_CONFIG_DIR_ . 'settings.inc.php');
		$this->prefix = md5($this->boardurl . $mtime) . '-';

		return true;
	}

	/**
	 * Gets the prefix as defined from set or the default.
	 *
	 * @access public
	 * @return string the value of $key.
	 */
	public function getPrefix() {

		return $this->prefix;
	}

	/**
	 * Sets a default Time To Live, if this isn't specified we let the class define it.
	 *
	 * @access public
	 * @param int $ttl The default TTL
	 * @return bool If this was successful or not.
	 */
	public function setDefaultTTL($ttl = 120) {

		$this->ttl = $ttl;

		return true;
	}

	/**
	 * Gets the TTL as defined from set or the default.
	 *
	 * @access public
	 * @return int the value of $ttl.
	 */
	public function getDefaultTTL() {

		return $this->ttl;
	}

	/**
	 * Invalidate all cached data.
	 *
	 * @return bool Whether or not we could invalidate the cache.
	 */
	public function invalidateCache() {

		if (is_writable($this->cachedir . '/' . 'index.php')) {
			@touch($this->cachedir . '/' . 'index.php');
		}

		return true;
	}

	/**
	 * Closes connections to the cache method.
	 *
	 * @access public
	 * @return bool Whether the connections were closed.
	 */
	public function quit() {

		return true;
	}

	/**
	 * Specify custom settings that the cache API supports.
	 *
	 * @access public
	 * @param array $config_vars Additional config_vars, see ManageSettings.php for usage.
	 */
	public function cacheSettings(array &$config_vars) {}

	/**
	 * Gets the latest version of SMF this is compatible with.
	 *
	 * @access public
	 * @return string the value of $key.
	 */
	public function getCompatibleVersion() {

		return $this->version_compatible;
	}

	/**
	 * Gets the min version that we support.
	 *
	 * @access public
	 * @return string the value of $key.
	 */
	public function getMinimumVersion() {

		return $this->min_eph_version;
	}

	/**
	 * Gets the Version of the Caching API.
	 *
	 * @access public
	 * @return string the value of $key.
	 */
	public function getVersion() {

		return $this->min_eph_version;
	}

	public static function isStored($key) {

		return isset(CacheApi::$local[$key]);
	}

	public static function store($key, $value) {

		// PHP is not efficient at storing array
		// Better delete the whole cache if there are
		// more than 1000 elements in the array

		if (count(CacheApi::$local) > 1000) {
			CacheApi::$local = [];
		}

		CacheApi::$local[$key] = $value;
	}

	public static function retrieve($key) {

		return isset(CacheApi::$local[$key]) ? CacheApi::$local[$key] : null;
	}

	public static function clean($key) {

		if (strpos($key, '*') !== false) {
			$regexp = str_replace('\\*', '.*', preg_quote($key, '#'));

			foreach (array_keys(CacheApi::$local) as $key) {

				if (preg_match('#^' . $regexp . '$#', $key)) {
					unset(CacheApi::$local[$key]);
				}

			}

		} else {
			unset(CacheApi::$local[$key]);
		}

	}

	/**
	 * Run housekeeping of this cache
	 * exp. clean up old data or do optimization
	 *
	 * @access public
	 * @return void
	 */
	public function housekeeping() {}

	/**
	 * Gets the class identifier of the current caching API implementation.
	 *
	 * @access public
	 * @return string the unique identifier for the current class implementation.
	 */
	public function getImplementationClassKeyName() {

		$class_name = get_class($this);

		if ($position = strrpos($class_name, '\\')) {
			return substr($class_name, $position + 1);
		} else {
			return get_class($this);
		}

	}

}

?>