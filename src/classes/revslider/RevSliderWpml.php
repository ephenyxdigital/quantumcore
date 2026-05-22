<?php

namespace EphenyxDigital\QuantumCore;

use Context;

/**
 * Stub class - WordPress WPML plugin integration is irrelevant in Phenyx
 * (multilingual is handled natively by PhenyxShop). Original 4 KB removed.
 */
class RevSliderWpml {

	public function __construct() {
	}

	public function wpml_exists() {
		return false;
	}

	public function add_javascript_language() {
		return '';
	}

	public static function getCurrentLang() {
		$ctx = Context::getContext();
		if (isset($ctx->language) && isset($ctx->language->iso_code)) {
			return $ctx->language->iso_code;
		}
		return '';
	}

}
