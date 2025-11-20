<?php

/**
 * Class CacheMemcached
 *
 * @since 1.0.0
 */
class CacheMemcached extends CacheApi implements CacheApiInterface {

	const CLASS_KEY = 'cache_memcached';

	/** @var Memcached The memcache instance. */
	private $memcached = null;

	/** @var string[] */
	private $servers;
    
    protected $is_connected = false;

	/**
	 * {@inheritDoc}
	 */
	public function __construct() {
        $this->connect();
        if ($this->is_connected) {
            $this->memcached->setOption(Memcached::OPT_PREFIX_KEY, _DB_PREFIX_);
            if ($this->memcached->getOption(Memcached::HAVE_IGBINARY)) {
                $this->memcached->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_IGBINARY);
            }
        }
    }

	/**
	 * {@inheritDoc}
	 */
	public function isSupported($test = false) {

		global $cache_memcached;

		$supported = class_exists('Memcached');

		if ($test) {
			return $supported;
		}

		return parent::isSupported() && $supported && !empty($cache_memcached);
	}

	/**
	 * {@inheritDoc}
	 */
	public function connect()
    {
        if (class_exists('Memcached') && extension_loaded('memcached')) {
            $this->memcached = new Memcached();
        } else {
            return;
        }

        $servers = static::getMemcachedServers();
        if (!$servers) {
            return;
        }
        foreach ($servers as $server) {
            $this->memcached->addServer($server['ip'], $server['port'], (int) $server['weight']);
        }
        
        if (!is_object($this->memcached)) {
			$this->is_connected = false;
		} else {
            
            $this->is_connected = true;
        }
    }
    
    public static function getMemcachedServers() {
        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS('SELECT * FROM '._DB_PREFIX_.'memcached_servers', true, false);
    }

	/**
	 * Add memcached servers.
	 *
	 * Don't add servers if they already exist. Ideal for persistent connections.
	 *
	 * @return bool True if there are servers in the daemon, false if not.
	 */
	public static function addServer($ip, $port, $weight)
    {
        return Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'memcached_servers (ip, port, weight) VALUES(\''.pSQL($ip).'\', '.(int) $port.', '.(int) $weight.')', false);
    }
    
    public static function deleteServer($id_server) {
        return Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'memcached_servers WHERE id_memcached_server='.(int) $id_server);
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

		$value = $this->memcached->get($key);

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

		return $this->memcached->set($key, $value, $ttl !== null ? $ttl : $this->ttl);
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
	public function cleanCache($type = '') {

		$this->invalidateCache();

		// Memcached accepts a delay parameter, always use 0 (instant).
		return $this->memcached->flush(0);
	}

	public function flush() {

		return $this->memcached->flush();
	}

	protected function _delete($key) {

		if (!$this->is_connected) {
			return false;
		}

		return $this->memcached->delete($key);
	}

	/**
	 * {@inheritDoc}
	 */
	public function quit() {

		return $this->memcached->quit();
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

		if (!is_object($this->memcached)) {
			return false;
		}

		// This gets called in Subs-Admin getServerVersions when loading up support information.  If we can't get a connection, return nothing.
		$result = $this->memcached->getVersion();

		if (!empty($result)) {
			return current($result);
		}

		return false;
	}

}
