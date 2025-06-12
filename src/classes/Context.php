<?php

use Detection\DeviceDetect;

#[AllowDynamicProperties]

/**
 * Class ContextCore
 *
 * @since 2.1.0.0
 */
class Context {

	// @codingStandardsIgnoreStart
	/** @var int */
	const DEVICE_COMPUTER = 1;
	/** @var int */
	const DEVICE_TABLET = 2;
	/** @var int */
	const DEVICE_MOBILE = 4;
	/** @var int */
	const MODE_STD = 1;
	/** @var int */
	const MODE_STD_CONTRIB = 2;
	/** @var int */
	const MODE_HOST_CONTRIB = 4;
	/** @var int */
	const MODE_HOST = 8;
	/* @var Context */
	protected static $instance;
	/** @var Company */
	public $company;

	public $phenyxConfig;

	public $license;

	/** @var Customer */
	public $user;

	public $guest;
	/** @var Cookie */
	public $cookie;

	public $_hook;

	public $_session;

	public $hook_args;
	/** @var Link */
	public $_link;
	/** @var Country */
	public $country;
	/** @var Employee */
	public $employee;
	/** @var AdminController|FrontController */
	public $controller;

	public $tabs_controllers;
	/** @var string */
	public $override_controller_name_for_translations;
	/** @var Language */
	public $language;

	public $img_manager;

	public $webp;
	/** @var AdminTab */
	public $tab;
	/** @var Theme */
	public $theme;
	/** @var Front Theme */
	public $front_theme;
	/** @var Smarty */
	public $smarty;
	/** @var Mobile_Detect */
	public $mobile_detect;
	/** @var int */
	public $mode;

	public $front_controller;

	public $cache_enable;

	public $cache_api;

	public $phenyxtool;

	public $_tools;

	public $phenyxgrid;

	public $_composer;

	public $translations;

	public $media;

	public $cart;

	public $currency;

	public $studentEducation;

	public $cart_education;

	public $agent;

	public $tabscript = [];
	/**
	 * Mobile device of the customer
	 *
	 * @var bool|null
	 */
	protected $mobile_device = null;
	/** @var bool|null */
	protected $is_mobile = null;
	/** @var bool|null */
	protected $is_tablet = null;
	// @codingStandardsIgnoreEnd

	/**
	 * @param Context $testInstance Unit testing purpose only
	 *
	 * @since 2.1.0.0
	 */
	public static function setInstanceForTesting($testInstance) {

		static::$instance = $testInstance;
	}

	/**
	 * Unit testing purpose only
	 *
	 * @since 2.1.0.0
	 */
	public static function deleteTestingInstance() {

		static::$instance = null;
	}

	/**
	 * Sets mobile_device context variable
	 *
	 * @return bool
	 *
	 * @since 2.1.0.0
	 * @throws PhenyxException
	 */
	public function getMobileDevice() {

		if ($this->mobile_device === null) {
			$this->mobile_device = false;

			if ($this->checkMobileContext()) {
				$phenyxConfig = Configuration::getInstance();

				if (isset(Context::getContext()->cookie->no_mobile) && Context::getContext()->cookie->no_mobile == false && (int) $phenyxConfig->get('EPH_ALLOW_MOBILE_DEVICE') != 0) {
					$this->mobile_device = true;
				} else {

					switch ((int) $phenyxConfig->get('EPH_ALLOW_MOBILE_DEVICE')) {
					case 1: // Only for mobile device

						if ($this->isMobile() && !$this->isTablet()) {
							$this->mobile_device = true;
						}

						break;
					case 2: // Only for touchpads

						if ($this->isTablet() && !$this->isMobile()) {
							$this->mobile_device = true;
						}

						break;
					case 3: // For touchpad or mobile devices

						if ($this->isMobile() || $this->isTablet()) {
							$this->mobile_device = true;
						}

						break;
					}

				}

			}

		}

		return $this->mobile_device;
	}

	/**
	 * Get a singleton instance of Context object
	 *
	 * @return Context
	 *
	 * @since 2.1.0.0
	 */
	public static function getContext() {

		if (!isset(static::$instance)) {
			static::$instance = new Context();
		}

		return static::$instance;
	}

	public static function setContext($public, $value) {

		if (!isset(static::$instance)) {
			static::$instance = new Context();
		}

		if (property_exists(static::$instance, $public)) {
			static::$instance->{$public}

			= $value;
		}

		return static::$instance;
	}

	/**
	 * Checks if visitor's device is a mobile device
	 *
	 * @return bool
	 *
	 * @since 2.1.0.0
	 */
	public function isMobile() {

		if ($this->is_mobile === null) {
			$DeviceDetect = $this->getMobileDetect();
			$this->is_mobile = $DeviceDetect->isMobile();
		}

		return $this->is_mobile;
	}

	/**
	 * Sets Mobile_Detect tool object
	 *
	 * @return Mobile_Detect
	 *
	 * @since 2.1.0.0
	 */
	public function getMobileDetect() {

		if ($this->mobile_detect === null) {
			$this->mobile_detect = new DeviceDetect();
		}

		return $this->mobile_detect;
	}

	/**
	 * Checks if visitor's device is a tablet device
	 *
	 * @return bool
	 *
	 * @since 2.1.0.0
	 */
	public function isTablet() {

		if ($this->is_tablet === null) {
			$mobileDetect = $this->getMobileDetect();
			$this->is_tablet = $mobileDetect->isTablet();
		}

		return $this->is_tablet;
	}

	public function isMobileDevice() {

		if (isset($_SERVER["HTTP_USER_AGENT"])) {
			return !$this->isTabletDevice() && preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
		}

	}

	public function isTabletDevice() {

		if (isset($_SERVER["HTTP_USER_AGENT"])) {
			return preg_match("/(iPad|tablet)/i", $_SERVER["HTTP_USER_AGENT"]);
		}

	}

	public function isAppleDevice() {

		if (isset($_SERVER["HTTP_USER_AGENT"])) {
			return preg_match("/(iPod|iPhone|iPad)/i", $_SERVER["HTTP_USER_AGENT"]);
		}

	}

	/**
	 * Returns mobile device type
	 *
	 * @return int
	 *
	 * @since 2.1.0.0
	 */
	public function getDevice() {

		static $device = null;

		if ($device === null) {

			if ($this->isTablet()) {
				$device = Context::DEVICE_TABLET;
			} else

			if ($this->isMobile()) {
				$device = Context::DEVICE_MOBILE;
			} else {
				$device = Context::DEVICE_COMPUTER;
			}

		}

		return $device;
	}

	/**
	 * Clone current context object
	 *
	 * @return Context
	 *
	 * @since 2.1.0.0
	 */
	public function cloneContext() {

		return clone ($this);
	}

	/**
	 * Checks if mobile context is possible
	 *
	 * @return bool
	 * @throws PhenyxException
	 *
	 * @since 2.1.0.0
	 */
	protected function checkMobileContext() {

		// Check mobile context
		$phenyxConfig = Configuration::getInstance();

		if (Tools::isSubmit('no_mobile_theme')) {
			Context::getContext()->cookie->no_mobile = true;

			if (Context::getContext()->cookie->id_guest) {
				$guest = new Guest(Context::getContext()->cookie->id_guest);
				$guest->mobile_theme = false;
				$guest->update();
			}

		} else

		if (Tools::isSubmit('mobile_theme_ok')) {
			Context::getContext()->cookie->no_mobile = false;

			if (Context::getContext()->cookie->id_guest) {
				$guest = new Guest(Context::getContext()->cookie->id_guest);
				$guest->mobile_theme = true;
				$guest->update();
			}

		}

		return isset($_SERVER['HTTP_USER_AGENT'])
		&& isset(Context::getContext()->cookie)
		&& (bool) $phenyxConfig->get('EPH_ALLOW_MOBILE_DEVICE')
		&& !Context::getContext()->cookie->no_mobile;
	}

}
